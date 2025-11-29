<?php

use Illuminate\Support\Facades\Artisan;
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = $argv[1] ?? 1051;
$mobile = $argv[2] ?? null;

/** @var \App\Models\Salon $salon */
$salon = \App\Models\Salon::with(['smsBalance', 'smsTransactions' => function($q){ $q->latest()->limit(30);}])->find($id);
if (!$salon) {
    echo "Salon with ID {$id} not found." . PHP_EOL;
    exit(0);
}

if ($mobile && $salon->mobile !== $mobile) {
    echo "Salon found but mobile does not match. Salon mobile: {$salon->mobile}. Requested mobile: {$mobile}" . PHP_EOL;
}

// Prepare summary
$now = \Carbon\Carbon::now();
$startOfMonth = (new \Hekmatinasser\Verta\Verta)->now() ? \Hekmatinasser\Verta\Verta::now()->startMonth()->toCarbon() : $now->copy()->startOfMonth();
$endOfMonth = \Hekmatinasser\Verta\Verta::now()->endMonth()->toCarbon();

$purchases = \App\Models\SmsTransaction::where('salon_id', $salon->id)->where('type', 'purchase')->whereBetween('created_at', [$startOfMonth, $endOfMonth])->get();
$consumedMessages = \App\Models\SmsTransaction::where('salon_id', $salon->id)->where('amount', '>', 0)->where(function($q){ $q->whereIn('type', ['send','deduction','manual_send'])->orWhereIn('sms_type', ['send','deduction','manual_send','manual_sms','manual_reminder','appointment_cancellation','appointment_confirmation','satisfaction_survey','appointment_modification','bulk']); })->whereBetween('sent_at', [$startOfMonth, $endOfMonth])->get();

$totalPurchased = $purchases->sum('sms_count');
$totalConsumed = $consumedMessages->sum('sms_count');

$balance = $salon->smsBalance?->balance ?? 0;

$result = [
    'id' => $salon->id,
    'name' => $salon->name,
    'mobile' => $salon->mobile,
    'sms_balance' => $balance,
    'total_purchased_this_month' => $totalPurchased,
    'total_consumed_this_month' => $totalConsumed,
    'percent_consumed' => ($totalConsumed + $balance) > 0 ? round(($totalConsumed / ($totalConsumed + $balance))*100) : 0,
    'recent_transactions' => $salon->smsTransactions->map(function($t) {
        return [
            'id' => $t->id,
            'type' => $t->type,
            'sms_type' => $t->sms_type,
            'status' => $t->status,
            'sms_count' => $t->sms_count,
            'amount' => $t->amount,
            'sent_at' => $t->sent_at ? $t->sent_at->format('Y-m-d H:i:s') : null,
            'created_at' => $t->created_at ? $t->created_at->format('Y-m-d H:i:s') : null,
        ];
    })->toArray(),
];

echo json_encode($result, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . PHP_EOL;
