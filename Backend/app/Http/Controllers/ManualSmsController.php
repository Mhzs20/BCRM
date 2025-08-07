<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SmsTransaction;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator; // Import this
use Illuminate\Pagination\Paginator; // Import this
use App\Rules\IranianPhoneNumber; // Import the custom rule

class ManualSmsController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Request to send a new manual SMS.
     * The SMS will be stored with 'pending' approval status.
     *
     * @param Request $request
     * @param \App\Models\Salon $salon The salon instance from route model binding.
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendManualSms(Request $request, \App\Models\Salon $salon)
    {
        $request->validate([
            'recipients_type' => 'required|in:all_customers,selected_customers,phone_contacts',
            'customer_ids' => 'array|required_if:recipients_type,selected_customers',
            'customer_ids.*' => 'exists:customers,id',
            'phone_numbers' => 'array|required_if:recipients_type,phone_contacts',
            'phone_numbers.*' => ['string', new IranianPhoneNumber()], // Use the custom rule
            'message_content' => 'required|string|max:500', // Max length for SMS
        ]);

        $user = Auth::user();

        if (!$user->is_superadmin && $user->salon_id !== $salon->id) {
            return response()->json(['message' => 'شما مجاز به ارسال پیامک برای این سالن نیستید.'], 403);
        }

        $recipients = [];
        if ($request->recipients_type === 'all_customers') {
            $recipients = Customer::where('salon_id', $salon->id)->pluck('phone_number')->toArray();
        } elseif ($request->recipients_type === 'selected_customers') {
            $recipients = Customer::whereIn('id', $request->customer_ids)
                                  ->where('salon_id', $salon->id)
                                  ->pluck('phone_number')
                                  ->toArray();
        } elseif ($request->recipients_type === 'phone_contacts') {
            $recipients = $request->phone_numbers;
        }

        if (empty($recipients)) {
            throw ValidationException::withMessages(['recipients' => 'هیچ گیرنده معتبری یافت نشد یا انتخاب نشده است.']);
        }

        $smsContent = $request->message_content;
        $smsPartsPerMessage = $this->smsService->calculateSmsCount($smsContent);
        $totalSmsCountForAllRecipients = $smsPartsPerMessage * count($recipients);

        if ($salon->user->smsBalance->balance < $totalSmsCountForAllRecipients) {
            return response()->json([
                'message' => 'اعتبار پیامک برای این درخواست کافی نیست.',
                'error' => 'اعتبار پیامک سالن کافی نیست. اعتبار فعلی: ' . $salon->user->smsBalance->balance . '، مورد نیاز: ' . $totalSmsCountForAllRecipients,
            ], 400);
        }

        DB::beginTransaction();
        try {
            $batchId = Str::uuid();
            foreach ($recipients as $recipient) {
                SmsTransaction::create([
                    'batch_id' => $batchId,
                    'user_id' => $user->id,
                    'salon_id' => $salon->id,
                    'receptor' => $recipient,
                    'recipients_type' => $request->recipients_type,
                    'recipients_count' => count($recipients),
                    'sms_type' => 'manual_sms',
                    'content' => $smsContent,
                    'sms_parts' => $smsPartsPerMessage,
                    'status' => 'pending',
                    'approval_status' => 'pending',
                ]);
            }
            DB::commit();
            return response()->json(['message' => 'درخواست پیامک دستی برای تایید ارسال شد.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ثبت درخواست پیامک با شکست مواجه شد.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * List pending manual SMS messages for super admin review.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listPendingManualSms(Request $request)
    {
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 403);
        }

        $pendingSmsTransactions = SmsTransaction::where('approval_status', 'pending')
            ->where('sms_type', 'manual_sms')
            ->whereNotNull('batch_id')
            ->with('user', 'salon')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by batch_id to show unique manual SMS requests
        $groupedBatches = $pendingSmsTransactions->groupBy('batch_id')->map(function ($batch) {
            $firstTransaction = $batch->first();
            return (object) [
                'batch_id' => $firstTransaction->batch_id,
                'salon_id' => $firstTransaction->salon_id,
                'user_id' => $firstTransaction->user_id,
                'content' => $firstTransaction->content,
                'recipients_type' => $firstTransaction->recipients_type,
                'recipients_count' => $firstTransaction->recipients_count,
                'sms_parts' => $firstTransaction->sms_parts,
                'created_at' => $firstTransaction->created_at,
                'user' => $firstTransaction->user,
                'salon' => $firstTransaction->salon,
                'total_sms_parts_in_batch' => $batch->sum('sms_parts'),
                'total_recipients_in_batch' => $batch->count(),
            ];
        });

        // Manually paginate the collection
        $perPage = $request->get('per_page', 15);
        $currentPage = Paginator::resolveCurrentPage();
        // Ensure $currentPage is at least 1
        $currentPage = max(1, $currentPage);
        $offset = ($currentPage * $perPage) - $perPage;
        $items = $groupedBatches->slice($offset, $perPage)->all();
        $pendingSmsBatches = new LengthAwarePaginator(
            $items,
            $groupedBatches->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($pendingSmsBatches);
    }

    /**
     * Approve a manual SMS message and send it.
     *
     * @param Request $request
     * @param int $smsTransactionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveManualSms(Request $request, string $batchId)
    {
        if (!Auth::user()->is_superadmin) {
            return redirect()->back()->with('error', 'اقدام غیرمجاز.');
        }

        $transactions = SmsTransaction::where('batch_id', $batchId)
                                      ->where('approval_status', 'pending')
                                      ->get();

        if ($transactions->isEmpty()) {
            return redirect()->back()->with('error', 'تراکنش پیامک یافت نشد یا در انتظار تایید نیست.');
        }

        $approver = Auth::user();
        $salon = $transactions->first()->salon;

        if (!$salon) {
            return redirect()->back()->with('error', 'سالن مرتبط یافت نشد.');
        }

        $totalSmsCount = $transactions->sum('sms_parts');

        if ($salon->user->smsBalance->balance < $totalSmsCount) {
            SmsTransaction::where('batch_id', $batchId)->update([
                'approval_status' => 'rejected',
                'rejection_reason' => 'اعتبار پیامک سالن کافی نیست.',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);
            return redirect()->back()->with('error', 'پیامک به دلیل عدم موجودی کافی رد شد.');
        }

        DB::beginTransaction();
        try {
            $recipients = $transactions->pluck('receptor')->toArray();
            $content = $transactions->first()->content;
            $recipientChunks = array_chunk($recipients, 50);

            foreach ($recipientChunks as $chunk) {
                // First, mark all transactions in this chunk as failed.
                // This handles cases where the API call fails or some recipients are not in the response.
                SmsTransaction::where('batch_id', $batchId)
                    ->whereIn('receptor', $chunk)
                    ->update([
                        'status' => 'failed',
                        'external_response' => 'ارسال اولیه ناموفق بود یا پاسخی از API دریافت نشد.',
                        'approval_status' => 'approved', // It was approved for sending, but sending failed
                        'approved_by' => $approver->id,
                        'approved_at' => now(),
                        'sent_at' => now(),
                    ]);

                $receptorsString = implode(',', $chunk);
                $smsEntries = $this->smsService->sendSms($receptorsString, $content);

                // If the API call was successful, update the status for each recipient in the response.
                if ($smsEntries && !empty($smsEntries)) {
                    foreach ($smsEntries as $entry) {
                        SmsTransaction::where('batch_id', $batchId)
                            ->where('receptor', $entry['receptor'])
                            ->update([
                                'status' => $this->smsService->mapKavenegarStatusToInternal($entry['status'] ?? null),
                                'external_response' => json_encode($entry),
                                // Other fields are already set
                            ]);
                    }
                }
            }

            $salon->user->smsBalance->decrement('balance', $totalSmsCount);
            DB::commit();
            return redirect()->route('admin.manual_sms.reports')->with('success', 'عملیات ارسال پیامک‌ها انجام شد. وضعیت نهایی در این صفحه قابل مشاهده است.');
        } catch (\Exception $e) {
            DB::rollBack();
            SmsTransaction::where('batch_id', $batchId)->update([
                'status' => 'error',
                'external_response' => $e->getMessage(),
                'approval_status' => 'rejected',
                'rejection_reason' => 'خطای سیستمی هنگام ارسال پیامک.',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);
            return redirect()->back()->with('error', 'خطای سیستمی هنگام ارسال پیامک: ' . $e->getMessage());
        }
    }

    /**
     * Reject a manual SMS message.
     *
     * @param Request $request
     * @param int $smsTransactionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectManualSms(Request $request, string $batchId)
    {
        if (!Auth::user()->is_superadmin) {
            return redirect()->back()->with('error', 'اقدام غیرمجاز.');
        }

        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $transactions = SmsTransaction::where('batch_id', $batchId)
                                      ->where('approval_status', 'pending')
                                      ->get();

        if ($transactions->isEmpty()) {
            return redirect()->back()->with('error', 'تراکنش پیامک یافت نشد یا در انتظار تایید نیست.');
        }

        $approver = Auth::user();

        SmsTransaction::where('batch_id', $batchId)->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'درخواست پیامک با موفقیت رد شد.');
    }

    /**
     * Show the manual SMS approval page for super admins.
     *
     * @return \Illuminate\View\View
     */
    public function showApprovalPage(Request $request)
    {
        if (!Auth::user()->is_superadmin) {
            abort(403, 'اقدام غیرمجاز.');
        }

        $pendingSmsTransactions = SmsTransaction::where('approval_status', 'pending')
            ->where('sms_type', 'manual_sms')
            ->whereNotNull('batch_id')
            ->with('user', 'salon')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by batch_id to show unique manual SMS requests
        $groupedBatches = $pendingSmsTransactions->groupBy('batch_id')->map(function ($batch) {
            $firstTransaction = $batch->first();
            return (object) [ // Cast to object for view access
                'batch_id' => $firstTransaction->batch_id,
                'salon_id' => $firstTransaction->salon_id,
                'user_id' => $firstTransaction->user_id,
                'content' => $firstTransaction->content,
                'recipients_type' => $firstTransaction->recipients_type,
                'recipients_count' => $firstTransaction->recipients_count,
                'sms_parts' => $firstTransaction->sms_parts,
                'created_at' => $firstTransaction->created_at,
                'user' => $firstTransaction->user,
                'salon' => $firstTransaction->salon,
                'total_sms_parts_in_batch' => $batch->sum('sms_parts'),
                'total_recipients_in_batch' => $batch->count(),
            ];
        });

        // Manually paginate the collection
        $perPage = $request->get('per_page', 15);
        $currentPage = Paginator::resolveCurrentPage();
        // Ensure $currentPage is at least 1
        $currentPage = max(1, $currentPage);
        $offset = ($currentPage * $perPage) - $perPage;
        $items = $groupedBatches->slice($offset, $perPage)->all();
        $pendingSmsBatches = new LengthAwarePaginator(
            $items,
            $groupedBatches->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.sms-templates.approval', compact('pendingSmsBatches'));
    }

    /**
     * Show the manual SMS reports page for super admins.
     *
     * @return \Illuminate\View\View
     */
    public function showReportsPage(Request $request)
    {
        if (!Auth::user()->is_superadmin) {
            abort(403, 'اقدام غیرمجاز.');
        }

        $smsTransactions = SmsTransaction::where('sms_type', 'manual_sms')
            ->whereNotNull('batch_id')
            ->with('user', 'salon')
            ->orderBy('created_at', 'desc')
            ->get();

        $groupedBatches = $smsTransactions->groupBy('batch_id')->map(function ($batch) {
            $firstTransaction = $batch->first();
            $successfulCount = $batch->where('status', 'sent')->count();
            $failedCount = $batch->where('status', 'failed')->count();
            $pendingCount = $batch->where('status', 'pending')->count();

            return (object) [
                'batch_id' => $firstTransaction->batch_id,
                'salon_name' => $firstTransaction->salon->name ?? 'N/A',
                'user_name' => $firstTransaction->user->name ?? 'N/A',
                'content' => $firstTransaction->content,
                'recipients_count' => $batch->count(),
                'approval_status' => $firstTransaction->approval_status,
                'created_at' => $firstTransaction->created_at,
                'successful_sends' => $successfulCount,
                'failed_sends' => $failedCount,
                'pending_sends' => $pendingCount,
            ];
        });

        $perPage = $request->get('per_page', 15);
        $currentPage = Paginator::resolveCurrentPage('page', 1);
        $offset = ($currentPage * $perPage) - $perPage;
        $items = $groupedBatches->slice($offset, $perPage)->values()->all();

        $smsBatches = new LengthAwarePaginator(
            $items,
            $groupedBatches->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.sms-reports.index', compact('smsBatches'));
    }
}
