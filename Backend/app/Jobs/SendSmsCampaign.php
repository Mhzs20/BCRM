<?php

namespace App\Jobs;

use App\Models\SmsCampaign;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaign;

    /**
     * Create a new job instance.
     *
     * @param SmsCampaign $campaign
     */
    public function __construct(SmsCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     *
     * @param SmsService $smsService
     * @return void
     */
    public function handle(SmsService $smsService): void
    {
        if ($this->campaign->status !== 'pending') {
            Log::warning("Skipping SMS Campaign #{$this->campaign->id} because its status is not 'pending'.");
            return;
        }

        $this->campaign->update(['status' => 'sending']);

        try {
            $this->campaign->messages()
                ->where('status', 'pending')
                ->chunkById(100, function ($messages) use ($smsService) {
                    foreach ($messages as $message) {
                        $response = $smsService->sendSms($message->phone_number, $message->message, null, $message->id);
                        
                        if ($response && !empty($response[0]['messageid'])) {
                            $message->update([
                                'status' => 'sent',
                                'message_id' => $response[0]['messageid'],
                                'sent_at' => now(),
                            ]);
                        } else {
                            $message->update(['status' => 'failed']);
                        }
                    }
                });

            $this->campaign->update(['status' => 'completed']);
            Log::info("SMS Campaign #{$this->campaign->id} completed successfully.");

        } catch (\Exception $e) {
            $this->campaign->update(['status' => 'failed']);
            Log::error("SMS Campaign #{$this->campaign->id} failed: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
