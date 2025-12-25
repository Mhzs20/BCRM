<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Bus;
use App\Models\Salon;
use App\Models\SalonSmsTemplate;
use App\Models\Customer;
use App\Rules\IranianPhoneNumber;
use App\Services\SmsService;
use App\Jobs\SendSingleSmsJob;

class ExclusiveLinkController extends Controller
{
    public function send(Request $request, Salon $salon, SmsService $smsService)
    {
        // Authorize
        Gate::authorize('manageResources', $salon);

        $payload = $request->all();

        $validator = Validator::make($payload, [
            'recipients_type' => ['required_without:preparation_id', 'string', 'in:all_customers,selected_customers,phone_contacts'],
            'customer_ids' => ['array', 'required_if:recipients_type,selected_customers'],
            'customer_ids.*' => ['integer', 'exists:customers,id'],
            'phone_numbers' => ['array', 'required_if:recipients_type,phone_contacts'],
            'phone_numbers.*' => ['string', new IranianPhoneNumber()],
            'template_id' => ['required_without:preparation_id', 'integer', 'exists:salon_sms_templates,id'],
            'preparation_id' => ['sometimes', 'integer', 'exists:exclusive_link_preparations,id'],
            'send_to_owner' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // If a preparation record is provided, use its stored recipients/template
        if (!empty($payload['preparation_id'])) {
            $prep = \App\Models\ExclusiveLinkPreparation::find($payload['preparation_id']);
            if (!$prep || $prep->salon_id !== $salon->id) {
                return response()->json(['message' => 'Preparation not found or does not belong to this salon'], 422);
            }
            if ($prep->expires_at && $prep->expires_at->isPast()) {
                return response()->json(['message' => 'Preparation has expired'], 422);
            }

            $template = $prep->template;
            if (!$template || !$template->is_active || $template->salon_id !== null) {
                return response()->json(['message' => 'Template in preparation is invalid or inactive'], 422);
            }

            $recipients = $prep->recipients ?? [];
        } else {
            $template = SalonSmsTemplate::find($payload['template_id']);

            if (!$template || !$template->is_active || $template->salon_id !== null) {
                return response()->json(['message' => 'Template is invalid, inactive or not a system template'], 422);
            }

            [$recipients, $errors] = $this->buildRecipients($payload, $salon);

            if (empty($recipients)) {
                return response()->json(['message' => 'No valid recipients found'], 422);
            }
        }

        // Pre-check total estimated parts and balance
        $totalParts = 0;
        foreach ($recipients as $r) {
            $dataForTemplate = [
                'customer_name' => $r['customer_id'] ? Customer::find($r['customer_id'])->name : '',
                'salon_name' => $salon->name,
                'details_url' => url('/booking/' . $salon->id),
            ];
            $message = $smsService->compileTemplateText($template->template, $dataForTemplate);
            $parts = $smsService->calculateSmsParts($message);
            $totalParts += $parts;
        }

        $currentBalance = $salon->smsBalance?->balance ?? 0;
        if ($currentBalance < $totalParts) {
            return response()->json(['message' => 'Insufficient SMS balance', 'required_parts' => $totalParts, 'current_balance' => $currentBalance], 422);
        }

        $sent = 0;
        $failed = 0;
        $sendErrors = [];

        $ownerPhone = $salon->user->mobile ?? $salon->mobile ?? $salon->phone ?? null;
        foreach ($recipients as $r) {
            try {
                $dataForTemplate = [
                    'customer_name' => $r['customer_id'] ? Customer::find($r['customer_id'])->name : '',
                    'salon_name' => $salon->name,
                    'details_url' => url('/booking/' . $salon->id),
                ];

                $result = $smsService->sendTemplateNow($salon, $template, $r['phone'], $dataForTemplate, $r['customer_id']);

                if (is_array($result) && ($result['status'] ?? '') === 'success') {
                    $sent++;
                    // If requested, also send the same message to the salon owner
                    if (!empty($payload['send_to_owner']) && $ownerPhone) {
                        try {
                            $smsService->sendTemplateNow($salon, $template, $ownerPhone, $dataForTemplate, null);
                        } catch (\Exception $e) {
                            Log::error('Exclusive link owner-forward exception', ['e' => $e]);
                        }
                    }
                } else {
                    $failed++;
                    $sendErrors[] = ['phone' => $r['phone'], 'error' => $result['message'] ?? 'failed'];
                }
            } catch (\Exception $e) {
                $failed++;
                $sendErrors[] = ['phone' => $r['phone'], 'error' => $e->getMessage()];
                Log::error('Exclusive link send exception', ['e' => $e]);
            }
        }

        $sent_to_owner = false;
        // اگر از preparation_id استفاده شده، مقدار send_to_owner را از آن بخوان یا از ورودی
        if (!empty($payload['preparation_id'])) {
            $prep = \App\Models\ExclusiveLinkPreparation::find($payload['preparation_id']);
            if ($prep && isset($prep->sample) && is_array($prep->sample)) {
                // اگر در نمونه sample فیلد sent_to_owner وجود دارد، استفاده کن
                if (isset($prep->sample[0]['sent_to_owner'])) {
                    $sent_to_owner = (bool)$prep->sample[0]['sent_to_owner'];
                } else {
                    $sent_to_owner = !empty($payload['send_to_owner']);
                }
            } else {
                $sent_to_owner = !empty($payload['send_to_owner']);
            }
        } else {
            $sent_to_owner = !empty($payload['send_to_owner']);
        }

        if ($sent_to_owner) {
            $ownerPhone = $salon->user->mobile ?? $salon->mobile ?? $salon->phone ?? null;
            if ($ownerPhone) {
                $summaryMessage = "گزارش ارسال لینک‌های اختصاصی: موفق: {$sent}, ناموفق: {$failed}.";
                SendSingleSmsJob::dispatch($ownerPhone, $summaryMessage)->onQueue('sms');
            }
        }

        // refresh salon balance
        $salon->refresh();
        $remaining_balance = $salon->smsBalance?->balance ?? 0;

        return response()->json([
            'message' => 'Exclusive links processed',
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $sendErrors,
            'sent_to_owner' => $sent_to_owner,
            'remaining_balance' => (int)$remaining_balance,
        ]);
    }

    /**
     * Prepare endpoint - compute recipients, sample messages, estimated parts/costs
     */
    public function prepare(Request $request, Salon $salon, SmsService $smsService)
    {
        Gate::authorize('manageResources', $salon);

        $payload = $request->all();

        $validator = Validator::make($payload, [
            'recipients_type' => ['required', 'string', 'in:all_customers,selected_customers,phone_contacts'],
            'customer_ids' => ['array', 'required_if:recipients_type,selected_customers'],
            'customer_ids.*' => ['integer', 'exists:customers,id'],
            'phone_numbers' => ['array', 'required_if:recipients_type,phone_contacts'],
            'phone_numbers.*' => ['string', new IranianPhoneNumber()],
            'template_id' => ['required', 'integer', 'exists:salon_sms_templates,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $template = SalonSmsTemplate::find($payload['template_id']);

        if (!$template || !$template->is_active || $template->salon_id !== null) {
            return response()->json(['message' => 'Template is invalid, inactive or not a system template'], 422);
        }

        [$recipients, $errors] = $this->buildRecipients($payload, $salon);

        if (empty($recipients)) {
            return response()->json(['message' => 'No valid recipients found'], 422);
        }

        $sample = [];
        $totalParts = 0;
        $limit = min(5, count($recipients));
        for ($i = 0; $i < $limit; $i++) {
            $r = $recipients[$i];
            $dataForTemplate = [
                'customer_name' => $r['customer_id'] ? Customer::find($r['customer_id'])->name : '',
                'salon_name' => $salon->name,
                'details_url' => url('/booking/' . $salon->id),
            ];
            $message = $smsService->compileTemplateText($template->template, $dataForTemplate);
            $parts = $smsService->calculateSmsParts($message);
            $sample[] = ['phone' => $r['phone'], 'message_preview' => $message, 'parts' => $parts];
            $totalParts += $parts;
        }

        // For remaining recipients compute parts sum
        if (count($recipients) > $limit) {
            for ($j = $limit; $j < count($recipients); $j++) {
                $r = $recipients[$j];
                $dataForTemplate = [
                    'customer_name' => $r['customer_id'] ? Customer::find($r['customer_id'])->name : '',
                    'salon_name' => $salon->name,
                    'details_url' => url('/booking/' . $salon->id),
                ];
                $message = $smsService->compileTemplateText($template->template, $dataForTemplate);
                $parts = $smsService->calculateSmsParts($message);
                $totalParts += $parts;
            }
        }

        $costPerPart = $smsService->getSmsCostPerPart();
        $totalPrice = $totalParts * $costPerPart;

        // Persist preparation so it can be used later in send
        $preparation = \App\Models\ExclusiveLinkPreparation::create([
            'salon_id' => $salon->id,
            'template_id' => $template->id,
            'recipients_type' => $payload['recipients_type'],
            'recipients' => $recipients,
            'recipients_count' => count($recipients),
            'estimated_parts' => $totalParts,
            'estimated_cost' => $totalParts, // ذخیره به صورت تعداد پارت
            'sample' => $sample,
            'expires_at' => now()->addMinutes(1),
        ]);

        return response()->json([
            'message' => 'Prepared exclusive link batch',
            'preparation_id' => $preparation->id,
            'recipients_count' => count($recipients),
            'recipients_sample' => $sample,
            'total_estimated_parts' => $totalParts,
            'total_estimated_cost' => $totalParts, // تعداد پارت
            'total_estimated_price' => $totalPrice, // مبلغ کل (اختیاری)
            'required_parts' => $totalParts,
            'current_balance' => $salon->smsBalance?->balance ?? 0,
        ]);
    }

    /**
     * Build recipients list from payload
     */
    private function buildRecipients(array $payload, Salon $salon): array
    {
        $recipients = [];
        $errors = [];

        if ($payload['recipients_type'] === 'all_customers') {
            $customers = Customer::where('salon_id', $salon->id)->get();
            foreach ($customers as $c) {
                if ($c->phone_number) $recipients[] = ['phone' => $c->phone_number, 'customer_id' => $c->id];
            }
        } elseif ($payload['recipients_type'] === 'selected_customers') {
            $customers = Customer::where('salon_id', $salon->id)->whereIn('id', $payload['customer_ids'])->get();
            foreach ($customers as $c) {
                if ($c->phone_number) $recipients[] = ['phone' => $c->phone_number, 'customer_id' => $c->id];
            }
        } else { // phone_contacts
            foreach ($payload['phone_numbers'] as $number) {
                $clean = preg_replace('/[^0-9]/', '', $number);
                if ($clean) $recipients[] = ['phone' => $clean, 'customer_id' => null];
            }
        }

        return [$recipients, $errors];
    }
}
