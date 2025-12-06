<?php

namespace App\Jobs;

use App\Models\SmsTransaction;
use App\Models\Salon;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendManualSmsBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    private string $batchId;
    private string $content;
    private int $approverId;
    private int $salonId;
    private int $totalSmsCount;
    private int $balanceDifference;
    private int $chunkSize;

    public function __construct(
        string $batchId,
        string $content,
        int $approverId,
        int $salonId,
        int $totalSmsCount,
        int $balanceDifference = 0,
        int $chunkSize = 50
    ) {
        $this->batchId = $batchId;
        $this->content = $content;
        $this->approverId = $approverId;
        $this->salonId = $salonId;
        $this->totalSmsCount = $totalSmsCount;
        $this->balanceDifference = $balanceDifference;
        $this->chunkSize = $chunkSize;
    }

    public function handle(SmsService $smsService): void
    {
        $baseQuery = SmsTransaction::where('batch_id', $this->batchId)
            ->where('approval_status', 'approved');

        $total = (clone $baseQuery)->count();
        if ($total === 0) {
            Log::warning('SendManualSmsBatch: no approved transactions found', [
                'batch_id' => $this->batchId,
            ]);
            return;
        }

        Log::info('SendManualSmsBatch started', [
            'batch_id' => $this->batchId,
            'count' => $total,
        ]);

        $baseQuery->orderBy('id')->chunkById($this->chunkSize, function ($transactions) use ($smsService) {
            $receptors = $transactions->pluck('receptor')->toArray();

            // Mark as processing before external call
            SmsTransaction::whereIn('id', $transactions->pluck('id')->toArray())
                ->update([
                    'status' => 'processing',
                    'external_response' => 'در صف ارسال...',
                    'approved_by' => $this->approverId,
                    'approved_at' => now(),
                    'sent_at' => now(),
                ]);

            $receptorsString = implode(',', $receptors);
            $smsEntries = $smsService->sendSms($receptorsString, $this->content);

            if (!$smsEntries || empty($smsEntries)) {
                SmsTransaction::whereIn('id', $transactions->pluck('id')->toArray())
                    ->update([
                        'status' => 'failed',
                        'external_response' => 'ارسال اولیه ناموفق بود یا پاسخی از API دریافت نشد.',
                    ]);
                return;
            }

            $responsesByReceptor = [];
            foreach ($smsEntries as $entry) {
                if (!empty($entry['receptor'])) {
                    $responsesByReceptor[$entry['receptor']] = $entry;
                }
            }

            foreach ($transactions as $transaction) {
                $entry = $responsesByReceptor[$transaction->receptor] ?? null;
                if ($entry) {
                    $status = $smsService->mapKavenegarStatusToInternal($entry['status'] ?? null);
                    $transaction->status = $status;
                    $transaction->external_response = json_encode($entry);
                    $transaction->sent_at = $status === 'sent' ? now() : $transaction->sent_at;
                } else {
                    $transaction->status = 'failed';
                    $transaction->external_response = 'پاسخی برای این گیرنده بازنگشت.';
                }
                $transaction->save();
            }
        });

        Log::info('SendManualSmsBatch finished', [
            'batch_id' => $this->batchId,
        ]);
    }

    public function failed(): void
    {
        $this->refundBalanceAndMarkError('job_failed');
    }

    private function refundBalanceAndMarkError(string $reason): void
    {
        try {
            $salon = Salon::find($this->salonId);
            if ($salon) {
                $salonSmsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);
                $salonSmsBalance->increment('balance', $this->totalSmsCount);
            }

            SmsTransaction::where('batch_id', $this->batchId)->update([
                'status' => 'error',
                'external_response' => 'خطا در صف ارسال: ' . $reason,
                'approval_status' => 'rejected',
                'rejection_reason' => 'خطای سیستم هنگام ارسال پیامک.',
                'approved_by' => $this->approverId,
                'approved_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SendManualSmsBatch failed-handler error', [
                'batch_id' => $this->batchId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
