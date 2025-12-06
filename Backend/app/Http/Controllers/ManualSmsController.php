<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SmsTransaction;
use App\Models\User;
use App\Services\SmsService;
use App\Jobs\SendManualSmsBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;  
use Illuminate\Pagination\Paginator; 
use App\Rules\IranianPhoneNumber;  

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
        $smsPartsPerMessage = $this->smsService->calculateSmsParts($smsContent);
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
                    'type' => 'manual_send',
                    'sms_type' => 'manual_sms',
                    'content' => $smsContent,  
                    'original_content' => $smsContent,  
                    'edited_content' => null,  
                    'sms_parts' => $smsPartsPerMessage,
                    'balance_deducted_at_submission' => $smsPartsPerMessage,  
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
     * List pending manual batches and pending campaigns for a specific salon (salon-level view).
     * This returns grouped manual SMS batches and pending campaigns for the salon.
     *
     * @param Request $request
     * @param \App\Models\Salon $salon
     * @return \Illuminate\Http\JsonResponse
     */
    public function listSalonPendingApprovals(Request $request, \App\Models\Salon $salon)
    {
        $user = Auth::user();

        // Ensure user has access to this salon
        if (!$user->is_superadmin && !$user->salons->contains($salon)) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 403);
        }

        // Manual SMS batches (group by batch_id)
        $pendingSmsTransactions = SmsTransaction::where('approval_status', 'pending')
            ->where('sms_type', 'manual_sms')
            ->where('salon_id', $salon->id)
            ->whereNotNull('batch_id')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $groupedBatches = $pendingSmsTransactions->groupBy('batch_id')->map(function ($batch) {
            $firstTransaction = $batch->first();
            return [
                'batch_id' => $firstTransaction->batch_id,
                'salon_id' => $firstTransaction->salon_id,
                'user_id' => $firstTransaction->user_id,
                'content' => $firstTransaction->content,
                'display_content' => $firstTransaction->edited_content ?? $firstTransaction->original_content ?? $firstTransaction->content,
                'recipients_type' => $firstTransaction->recipients_type,
                'recipients_count' => $firstTransaction->recipients_count,
                'sms_parts' => $firstTransaction->sms_parts,
                'total_sms_parts_in_batch' => $batch->sum('sms_parts'),
                'total_recipients_in_batch' => $batch->count(),
                'total_deducted_balance' => $batch->sum('balance_deducted_at_submission'),
                'created_at' => $firstTransaction->created_at,
                'approval_status' => $firstTransaction->approval_status,
                'user' => $firstTransaction->user ? ['id' => $firstTransaction->user->id, 'name' => $firstTransaction->user->name] : null,
            ];
        })->values();

        // Campaigns pending approval for this salon
        $pendingCampaigns = \App\Models\SmsCampaign::where('salon_id', $salon->id)
            ->where('approval_status', 'pending')
            ->where('uses_template', false)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($campaign) {
                return [
                    'campaign_id' => $campaign->id,
                    'message' => $campaign->message,
                    'customer_count' => $campaign->customer_count,
                    'total_cost' => $campaign->total_cost,
                    'approval_status' => $campaign->approval_status,
                    'created_at' => $campaign->created_at,
                ];
            });

        return response()->json([
            'manual_batches' => $groupedBatches,
            'campaigns' => $pendingCampaigns,
        ]);
    }

    /**
     * Cancel a pending manual SMS batch for the salon (refund the deducted balance).
     *
     * @param Request $request
     * @param \App\Models\Salon $salon
     * @param string $batchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSalonManualSmsRequest(Request $request, \App\Models\Salon $salon, string $batchId)
    {
        $user = Auth::user();

        // Ensure user has access to this salon
        if (!$user->is_superadmin && !$user->salons->contains($salon)) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 403);
        }

        $transactions = SmsTransaction::where('batch_id', $batchId)
                                      ->where('salon_id', $salon->id)
                                      ->where('approval_status', 'pending')
                                      ->get();

        if ($transactions->isEmpty()) {
            return response()->json(['message' => 'تراکنشی برای لغو یافت نشد یا در حالت انتظار نیست.'], 404);
        }

        // Only the user who created the batch or a salon owner / superadmin can cancel
        $batchOwnerId = $transactions->first()->user_id;
        $salonOwnerId = $salon->user_id;
        if (!$user->is_superadmin && $user->id !== $batchOwnerId && $user->id !== $salonOwnerId) {
            return response()->json(['message' => 'فقط فرستنده یا مالک سالن می‌تواند درخواست را لغو کند.'], 403);
        }

        $totalDeductedBalance = $transactions->sum('balance_deducted_at_submission');

        DB::beginTransaction();
        try {
            // Mark transactions as cancelled
            SmsTransaction::where('batch_id', $batchId)
                ->where('salon_id', $salon->id)
                ->where('approval_status', 'pending')
                ->update([
                    'approval_status' => 'cancelled',
                    'rejection_reason' => 'cancelled_by_user',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'status' => 'cancelled',
                ]);

            // Refund deducted balance
            $salonSmsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);
            if ($totalDeductedBalance > 0) {
                $salonSmsBalance->increment('balance', $totalDeductedBalance);
            }

            DB::commit();
            return response()->json(['message' => 'درخواست پیامک با موفقیت لغو شد و اعتبار بازگردانده شد.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطا در لغو درخواست: ' . $e->getMessage()], 500);
        }
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

        // Phase 1: lock rows briefly and adjust balance/content without holding locks during API calls
        $contentToSend = null;
        $smsPartsPerMessage = 0;
        $balanceDifference = 0;
        $recipients = [];
        $totalSmsCountAfterEdit = $totalSmsCountAtSubmission;

        try {
            DB::transaction(function () use (
                $batchId,
                $request,
                $salon,
                $totalSmsCountAtSubmission,
                $approver,
                &$contentToSend,
                &$smsPartsPerMessage,
                &$balanceDifference,
                &$recipients,
                &$totalSmsCountAfterEdit
            ) {
                $lockedTransactions = SmsTransaction::where('batch_id', $batchId)
                    ->where('approval_status', 'pending')
                    ->lockForUpdate()
                    ->get();

                if ($lockedTransactions->isEmpty()) {
                    throw new \RuntimeException('تراکنش پیامک یافت نشد یا در انتظار تایید نیست.');
                }

                $recipients = $lockedTransactions->pluck('receptor')->toArray();
                $firstTransaction = $lockedTransactions->first();

                $contentToSend = $request->edited_message_content
                    ?? $firstTransaction->edited_content
                    ?? $firstTransaction->original_content
                    ?? $firstTransaction->content;

                // Ensure historical rows keep their original content
                foreach ($lockedTransactions as $transaction) {
                    if (is_null($transaction->original_content)) {
                        $transaction->original_content = $transaction->content;
                    }
                    $transaction->edited_content = $request->edited_message_content ?? $transaction->edited_content;
                    $transaction->save();
                }

                $smsPartsPerMessage = $this->smsService->calculateSmsParts($contentToSend);

                $totalSmsCountAfterEdit = $smsPartsPerMessage * $lockedTransactions->count();
                $balanceDifference = $totalSmsCountAfterEdit - $totalSmsCountAtSubmission;

                $salonSmsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);

                if ($balanceDifference > 0) {
                    if ($salonSmsBalance->balance < $balanceDifference) {
                        // Refund the initially deducted balance before rejecting
                        $salonSmsBalance->increment('balance', $totalSmsCountAtSubmission);
                        SmsTransaction::where('batch_id', $batchId)->update([
                            'approval_status' => 'rejected',
                            'rejection_reason' => 'اعتبار پیامک سالن کافی نیست (پس از ویرایش و نیاز به کسر بیشتر).',
                            'approved_by' => $approver->id,
                            'approved_at' => now(),
                        ]);
                        throw new \RuntimeException('اعتبار پیامک سالن کافی نیست (پس از ویرایش).');
                    }
                    $salonSmsBalance->decrement('balance', $balanceDifference);
                } elseif ($balanceDifference < 0) {
                    $salonSmsBalance->increment('balance', abs($balanceDifference));
                }

                SmsTransaction::where('batch_id', $batchId)->update([
                    'sms_parts' => $smsPartsPerMessage,
                    'balance_deducted_at_submission' => $smsPartsPerMessage,
                    'approval_status' => 'approved',
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                    'status' => 'pending',
                    'external_response' => null,
                    'sent_at' => null,
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'خطا در تایید پیامک: ' . $e->getMessage());
        }

        SendManualSmsBatch::dispatch(
            $batchId,
            $contentToSend,
            $approver->id,
            $salon->id,
            $totalSmsCountAfterEdit,
            $balanceDifference
        )->onQueue('sms');

        return redirect()->route('admin.manual_sms.approval')->with('success', 'پیامک برای ارسال در صف قرار گرفت.');
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
                    SUM(CASE WHEN status = "sent" OR (status = "pending" AND sent_at IS NOT NULL) THEN 1 ELSE 0 END) as successful_count,
                    SUM(CASE WHEN status = "failed" OR status = "not_sent" THEN 1 ELSE 0 END) as failed_count,
                    SUM(CASE WHEN (status = "pending" AND sent_at IS NULL) OR status = "processing" THEN 1 ELSE 0 END) as pending_count
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

        $content = $transactions->first()->content; // Use original content for approved templates

        try {
            DB::transaction(function () use ($batchId, $approver) {
                SmsTransaction::where('batch_id', $batchId)
                    ->where('approval_status', 'pending')
                    ->lockForUpdate()
                    ->update([
                        'approval_status' => 'approved',
                        'approved_by' => $approver->id,
                        'approved_at' => now(),
                        'status' => 'pending',
                        'external_response' => null,
                        'sent_at' => null,
                    ]);
            });
        } catch (\Throwable $e) {
            return;
        }

        SendManualSmsBatch::dispatch(
            $batchId,
            $content,
            $approver->id,
            $salon->id,
            $totalSmsCountAtSubmission,
            0
        )->onQueue('sms');
    }
}
