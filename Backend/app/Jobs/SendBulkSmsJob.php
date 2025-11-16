<?php

namespace App\Jobs;

use App\Models\Salon;
use App\Models\SmsTransaction;
use App\Services\BulkSmsFilterService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendBulkSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts before the job is marked as failed.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600;

    /**
     * The message content that should be sent to each salon owner.
     */
    private string $message;

    /**
     * The filters applied when the job was dispatched.
     */
    private array $filters;

    /**
     * Explicit salon identifiers selected by the admin. Null when send-to-all is used.
     */
    private ?array $salonIds;

    /**
     * Indicates whether the job targets all filtered salons.
     */
    private bool $sendToAll;

    /**
     * Admin identifier who queued the job (if available).
     */
    private ?int $dispatchedById;

    /**
     * Display name for logging purposes.
     */
    private ?string $dispatchedByName;

    /**
     * Chunk size for processing the recipients.
     */
    private int $chunkSize;

    /**
     * Identifier used to group transactions and deduplicate retries.
     */
    private string $batchId;

    public function __construct(
        string $message,
        array $filters,
        ?array $salonIds,
        bool $sendToAll = false,
        ?int $dispatchedById = null,
        ?string $dispatchedByName = null,
        ?string $batchId = null,
        int $chunkSize = 100
    ) {
        $this->message = $message;
        $this->filters = $filters;
        $this->salonIds = $salonIds ? array_values(array_unique(array_map('intval', $salonIds))) : null;
        $this->sendToAll = $sendToAll;
        $this->dispatchedById = $dispatchedById;
        $this->dispatchedByName = $dispatchedByName;
        $this->chunkSize = $chunkSize;
        $this->batchId = $batchId ?? (string) Str::uuid();
    }

    public function handle(SmsService $smsService): void
    {
        Log::info('Bulk SMS job started', [
            'batch_id' => $this->batchId,
            'send_to_all' => $this->sendToAll,
            'salon_ids_count' => $this->salonIds ? count($this->salonIds) : null,
        ]);

        $query = Salon::query()
            ->with(['owner'])
            ->select('salons.*');

        BulkSmsFilterService::apply($query, $this->filters);

        if (!empty($this->salonIds)) {
            $query->whereIn('salons.id', $this->salonIds);
        }

        $query->orderBy('salons.id')->chunkById($this->chunkSize, function ($salons) use ($smsService) {
            $salonIds = $salons->pluck('id')->all();

            $alreadyProcessed = SmsTransaction::where('batch_id', $this->batchId)
                ->whereIn('salon_id', $salonIds)
                ->pluck('salon_id')
                ->all();

            foreach ($salons as $salon) {
                if (in_array($salon->id, $alreadyProcessed, true)) {
                    continue;
                }

                if (!$salon->owner || empty($salon->owner->mobile)) {
                    Log::warning('Skipping bulk SMS send: salon owner mobile missing', [
                        'salon_id' => $salon->id,
                        'send_to_all' => $this->sendToAll,
                    ]);
                    continue;
                }

                $status = 'failed';

                try {
                    $response = $smsService->sendSms($salon->owner->mobile, $this->message);
                    $status = $response ? 'delivered' : 'failed';

                    if (!$response) {
                        Log::warning('Bulk SMS send returned falsy response', [
                            'salon_id' => $salon->id,
                            'mobile' => $salon->owner->mobile,
                        ]);
                    }
                } catch (\Throwable $exception) {
                    Log::error('Bulk SMS send failed', [
                        'salon_id' => $salon->id,
                        'mobile' => $salon->owner->mobile,
                        'message' => $exception->getMessage(),
                    ]);
                }

                SmsTransaction::create([
                    'user_id' => $this->dispatchedById,
                    'salon_id' => $salon->id,
                    'sms_type' => 'bulk',
                    'amount' => 1,
                    'description' => 'پیامک گروهی توسط ادمین (صف)',
                    'receptor' => $salon->owner->mobile,
                    'content' => $this->message,
                    'status' => $status,
                    'sent_at' => $status === 'delivered' ? now() : null,
                    'batch_id' => $this->batchId,
                ]);

                $alreadyProcessed[] = $salon->id;
            }
        });

        Log::info('Bulk SMS job finished', [
            'batch_id' => $this->batchId,
        ]);
    }
}
