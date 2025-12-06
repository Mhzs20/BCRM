<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSingleSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    private string $receptor;
    private string $message;
    private ?string $sender;
    private ?int $localId;

    public function __construct(string $receptor, string $message, ?string $sender = null, ?int $localId = null)
    {
        $this->receptor = $receptor;
        $this->message = $message;
        $this->sender = $sender;
        $this->localId = $localId;
    }

    public function handle(SmsService $smsService): void
    {
        try {
            $smsService->sendSms($this->receptor, $this->message, $this->sender, $this->localId);
        } catch (\Throwable $e) {
            Log::error('SendSingleSmsJob failed', [
                'receptor' => $this->receptor,
                'message' => $e->getMessage(),
            ]);
            $this->fail($e);
        }
    }
}
