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
            'message_content' => 'required_without:template_id|string|max:500', // Max length for SMS
            'template_id' => 'required_without:message_content|exists:salon_sms_templates,id',
        ]);

        $user = Auth::user();

        if (!$user->is_superadmin && !$user->salons->contains($salon)) {
            return response()->json(['message' => 'شما مجاز به ارسال پیامک برای این سالن نیستید.'], 403);
        }

        // Ensure salon's SMS balance is loaded for the check, or create if it doesn't exist
        $salonSmsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);

        $recipients = [];
        if ($request->recipients_type === 'all_customers') {
            $recipients = Customer::where('salon_id', $salon->id)->pluck('phone_number')->toArray();
        } elseif ($request->recipients_type === 'selected_customers') {
            $recipients = Customer::whereIn('id', $request->customer_ids)
                                  ->where('salon_id', $salon->id)
                                  ->pluck('phone_number')
                                  ->toArray();
        } elseif ($request->recipients_type === 'phone_contacts') {
            // Clean phone numbers by removing spaces, dashes, and parentheses
            $recipients = array_map(function($phone) {
                return preg_replace('/[\s\-\(\)]/', '', $phone);
            }, $request->phone_numbers);
        }

        if (empty($recipients)) {
            throw ValidationException::withMessages(['recipients' => 'هیچ گیرنده معتبری یافت نشد یا انتخاب نشده است.']);
        }

        $smsContent = $request->message_content;
        $isCustomMessage = !$request->has('template_id');
        $smsPartsPerMessage = $this->smsService->calculateSmsCount($smsContent);
        $totalSmsCountForAllRecipients = $smsPartsPerMessage * count($recipients);

        if ($salonSmsBalance->balance < $totalSmsCountForAllRecipients) {
            return response()->json([
                'message' => 'اعتبار پیامک برای این درخواست کافی نیست.',
                'error' => 'اعتبار پیامک سالن کافی نیست. اعتبار فعلی: ' . $salonSmsBalance->balance . '، مورد نیاز: ' . $totalSmsCountForAllRecipients,
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Deduct balance immediately upon submission
            $salonSmsBalance->decrement('balance', $totalSmsCountForAllRecipients);

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
                    'content' => $smsContent, // Original content from the user
                    'original_content' => $smsContent, // Store as original
                    'edited_content' => null, // Initially null
                    'sms_parts' => $smsPartsPerMessage,
                    'balance_deducted_at_submission' => $smsPartsPerMessage, // Store deducted amount
                    'status' => 'pending',
                    'approval_status' => $isCustomMessage ? 'pending' : 'approved',
                ]);
            }
            DB::commit();

            if ($isCustomMessage) {
                return response()->json(['message' => ' درخواست پیامک دستی برای تایید ارسال شد. اعتبار از حساب شما کسر گردید در صورت رد شدن پیام شما توسط کارشناسان ما اعتبار کسر شده به حساب شما عودت داده میشود. '], 200);
            } else {
                $this->approveAndSendBatch($batchId, $user);
                return response()->json(['message' => 'پیامک با موفقیت ارسال شد.'], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            // If an error occurs after balance deduction but before transaction creation, refund the balance
            if (isset($totalSmsCountForAllRecipients)) {
                $salonSmsBalance->increment('balance', $totalSmsCountForAllRecipients);
            }
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
            return (object) [ // Cast to object for view access
                'batch_id' => $firstTransaction->batch_id,
                'salon_id' => $firstTransaction->salon_id,
                'user_id' => $firstTransaction->user_id,
                'content' => $firstTransaction->content, // Original content from the user
                'original_content' => $firstTransaction->original_content,
                'edited_content' => $firstTransaction->edited_content,
                'display_content' => $firstTransaction->edited_content ?? $firstTransaction->original_content ?? $firstTransaction->content, // Content to display in the editable field
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

        $request->validate([
            'edited_message_content' => 'nullable|string|max:500', // Allow edited content
        ]);

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

        $totalSmsCountAtSubmission = $transactions->sum('balance_deducted_at_submission');

        // No need to check balance here, it was already deducted at submission.
        // However, we need to re-calculate totalSmsCount based on potentially edited content
        // and ensure the salon still has enough balance if the message length changed.

        DB::beginTransaction();
        try {
            $recipients = $transactions->pluck('receptor')->toArray();
            $firstTransaction = $transactions->first();

            // Determine the content to send
            $contentToSend = $request->edited_message_content ?? $firstTransaction->edited_content ?? $firstTransaction->original_content ?? $firstTransaction->content;

            // Update original_content if it's null (for older entries) and set edited_content
            foreach ($transactions as $transaction) {
                if (is_null($transaction->original_content)) {
                    $transaction->original_content = $transaction->content;
                }
                $transaction->edited_content = $request->edited_message_content ?? $transaction->edited_content; // Update if provided in request, else keep existing edited_content
                $transaction->save();
            }

            $smsPartsPerMessage = $this->smsService->calculateSmsCount($contentToSend);

            // Recalculate total SMS count based on potentially new content
            $totalSmsCountAfterEdit = $smsPartsPerMessage * $transactions->count();

            // If the message length changed, we need to adjust the balance.
            // If totalSmsCountAfterEdit > totalSmsCountAtSubmission, deduct the difference.
            // If totalSmsCountAfterEdit < totalSmsCountAtSubmission, refund the difference.
            $balanceDifference = $totalSmsCountAfterEdit - $totalSmsCountAtSubmission;

            if ($balanceDifference > 0) {
                // Need to deduct more balance
                // Ensure salonSmsBalance is loaded for the check, or create if it doesn't exist
                $salonSmsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);
                if ($salonSmsBalance->balance < $balanceDifference) {
                    // Refund the initially deducted balance before rejecting
                    $salonSmsBalance->increment('balance', $totalSmsCountAtSubmission);
                    SmsTransaction::where('batch_id', $batchId)->update([
                        'approval_status' => 'rejected',
                        'rejection_reason' => 'اعتبار پیامک سالن کافی نیست (پس از ویرایش و نیاز به کسر بیشتر).',
                        'approved_by' => $approver->id,
                        'approved_at' => now(),
                    ]);
                    DB::rollBack();
                    return redirect()->back()->with('error', 'پیامک به دلیل عدم موجودی کافی (پس از ویرایش و نیاز به کسر بیشتر) رد شد.');
                }
                $salonSmsBalance->decrement('balance', $balanceDifference);
            } elseif ($balanceDifference < 0) {
                // Need to refund some balance
                // Ensure salonSmsBalance is loaded for the check, or create if it doesn't exist
                $salonSmsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);
                $salonSmsBalance->increment('balance', abs($balanceDifference));
            }

            // Update sms_parts and balance_deducted_at_submission for all transactions in the batch
            SmsTransaction::where('batch_id', $batchId)->update([
                'sms_parts' => $smsPartsPerMessage,
                'balance_deducted_at_submission' => $smsPartsPerMessage, // Update to reflect new sms_parts
            ]);


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
                $smsEntries = $this->smsService->sendSms($receptorsString, $contentToSend);

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

            // No need to decrement balance here, it's handled by the difference calculation above.
            DB::commit();
            return redirect()->route('admin.manual_sms.reports')->with('success', 'عملیات ارسال پیامک‌ها انجام شد. وضعیت نهایی در این صفحه قابل مشاهده است.');
        } catch (\Exception $e) {
            DB::rollBack();
            // If an error occurs during sending, refund the balance that was adjusted/deducted during approval
            if (isset($balanceDifference) && $balanceDifference > 0) {
                // Ensure salonSmsBalance is loaded for the check, or create if it doesn't exist
                $salonSmsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);
                $salonSmsBalance->increment('balance', $balanceDifference);
            }
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

        // Ensure original_content is set if it's null (for older entries)
        foreach ($transactions as $transaction) {
            if (is_null($transaction->original_content)) {
                $transaction->original_content = $transaction->content;
                $transaction->save();
            }
        }

        $totalDeductedBalance = $transactions->sum('balance_deducted_at_submission');

        SmsTransaction::where('batch_id', $batchId)->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        // Refund the deducted balance
        $salon = $transactions->first()->salon;
        // Ensure salonSmsBalance is loaded for the check, or create if it doesn't exist
        $salonSmsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);
        if ($salonSmsBalance) {
            $salonSmsBalance->increment('balance', $totalDeductedBalance);
        }

        return redirect()->back()->with('success', 'درخواست پیامک با موفقیت رد شد. اعتبار کسر شده به حساب کاربر بازگردانده شد.');
    }

    /**
     * Update the content of a manual SMS batch before approval.
     *
     * @param Request $request
     * @param string $batchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateManualSmsContent(Request $request, string $batchId)
    {
        if (!Auth::user()->is_superadmin) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 403);
        }

        $request->validate([
            'edited_message_content' => 'required|string|max:500',
        ]);

        $transactions = SmsTransaction::where('batch_id', $batchId)
                                      ->where('approval_status', 'pending')
                                      ->get();

        if ($transactions->isEmpty()) {
            return response()->json(['message' => 'تراکنش پیامک یافت نشد یا در انتظار تایید نیست.'], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($transactions as $transaction) {
                // If original_content is null, set it to the current content before editing
                if (is_null($transaction->original_content)) {
                    $transaction->original_content = $transaction->content;
                }
                $transaction->edited_content = $request->edited_message_content;
                $transaction->save();
            }
            DB::commit();
            return response()->json(['message' => 'محتوای پیامک با موفقیت ویرایش شد.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطا در ویرایش محتوای پیامک: ' . $e->getMessage()], 500);
        }
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
                'content' => $firstTransaction->content, // Original content from the user
                'original_content' => $firstTransaction->original_content,
                'edited_content' => $firstTransaction->edited_content,
                'display_content' => $firstTransaction->edited_content ?? $firstTransaction->original_content ?? $firstTransaction->content, // Content to display in the editable field
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
        
        
        try {
            \Log::info('Starting showReportsPage method');
            
            // Get unique batch_ids first with pagination in mind - use VERY aggressive limits
            // to avoid memory exhaustion with 21k+ records
            $batchIds = SmsTransaction::where('sms_type', 'manual_sms')
                ->whereNotNull('batch_id')
                ->select('batch_id', \DB::raw('MAX(created_at) as latest_created_at'))
                ->groupBy('batch_id')
                ->orderBy('latest_created_at', 'desc')
                ->limit(50) // Limit to last 50 batches for performance - can increase later
                ->pluck('batch_id');
                
            \Log::info('Found batch IDs', ['count' => $batchIds->count()]);
            
            // Now get one transaction per batch with relationships
            $smsTransactions = SmsTransaction::whereIn('batch_id', $batchIds)
                ->with(['user', 'salon', 'approver'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('batch_id')
                ->map(function ($group) {
                    return $group->first(); // Get first transaction from each batch
                });
                
            \Log::info('Successfully fetched SMS transactions', ['count' => $smsTransactions->count()]);
        } catch (\Exception $e) {
            \Log::error('Error in showReportsPage', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        $groupedBatches = $smsTransactions->map(function ($firstTransaction) {
            // Get statistics for this specific batch
            $batchStats = SmsTransaction::where('batch_id', $firstTransaction->batch_id)
                ->selectRaw('
                    COUNT(*) as total_count,
                    SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as successful_count,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count
                ')
                ->first();

            return (object) [
                'batch_id' => $firstTransaction->batch_id,
                'salon_name' => $firstTransaction->salon->name ?? 'N/A',
                'user_name' => $firstTransaction->user->name ?? 'N/A',
                'content' => $firstTransaction->content, // Original content from the user
                'original_content' => $firstTransaction->original_content,
                'edited_content' => $firstTransaction->edited_content,
                'recipients_count' => $batchStats->total_count ?? 0,
                'approval_status' => $firstTransaction->approval_status,
                'approved_by' => $firstTransaction->approver, // This can be null
                'approved_at' => $firstTransaction->approved_at,
                'created_at' => $firstTransaction->created_at,
                'successful_sends' => $batchStats->successful_count ?? 0,
                'failed_sends' => $batchStats->failed_count ?? 0,
                'pending_sends' => $batchStats->pending_count ?? 0,
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

    private function approveAndSendBatch(string $batchId, User $approver)
    {
        $transactions = SmsTransaction::where('batch_id', $batchId)
                                      ->where('approval_status', 'pending')
                                      ->get();

        if ($transactions->isEmpty()) {
            return; // Or throw an exception
        }

        $salon = $transactions->first()->salon;
        if (!$salon) {
            return; // Or throw an exception
        }

        $totalSmsCountAtSubmission = $transactions->sum('balance_deducted_at_submission');

        // No need to check balance here, it was already deducted at submission.
        // However, we need to ensure the salon still has enough balance if the message length changed
        // (though for approved templates, content is usually fixed, but good to be safe).
        // For simplicity, we assume content doesn't change for approved templates here.

        DB::beginTransaction();
        try {
            $recipients = $transactions->pluck('receptor')->toArray();
            $content = $transactions->first()->content; // Use original content for approved templates
            $recipientChunks = array_chunk($recipients, 50);

            foreach ($recipientChunks as $chunk) {
                SmsTransaction::where('batch_id', $batchId)
                    ->whereIn('receptor', $chunk)
                    ->update([
                        'status' => 'failed',
                        'external_response' => 'ارسال اولیه ناموفق بود یا پاسخی از API دریافت نشد.',
                        'approval_status' => 'approved',
                        'approved_by' => $approver->id,
                        'approved_at' => now(),
                        'sent_at' => now(),
                    ]);

                $receptorsString = implode(',', $chunk);
                $smsEntries = $this->smsService->sendSms($receptorsString, $content);

                if ($smsEntries && !empty($smsEntries)) {
                    foreach ($smsEntries as $entry) {
                        SmsTransaction::where('batch_id', $batchId)
                            ->where('receptor', $entry['receptor'])
                            ->update([
                                'status' => $this->smsService->mapKavenegarStatusToInternal($entry['status'] ?? null),
                                'external_response' => json_encode($entry),
                            ]);
                    }
                }
            }

            // Balance was already decremented at submission, no need to do it again here.
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // If an error occurs during sending, refund the balance that was deducted at submission
            // Ensure salonSmsBalance is loaded for the check, or create if it doesn't exist
            $salonSmsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);
            if ($salonSmsBalance) {
                $salonSmsBalance->increment('balance', $totalSmsCountAtSubmission);
            }
            SmsTransaction::where('batch_id', $batchId)->update([
                'status' => 'error',
                'external_response' => $e->getMessage(),
                'approval_status' => 'rejected',
                'rejection_reason' => 'خطای سیستمی هنگام ارسال پیامک.',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);
        }
    }
}
