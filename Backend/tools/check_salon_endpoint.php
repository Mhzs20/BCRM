<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SmsTransactionController;
use Hekmatinasser\Verta\Verta;

$id = $argv[1] ?? 1051;
$mobile = $argv[2] ?? null;

$salon = App\Models\Salon::find($id);
if (!$salon) {
    echo "Salon with ID {$id} not found." . PHP_EOL;
    exit(0);
}

$user = App\Models\User::find($salon->user_id);
if (!$user) {
    echo "Owner user not found for salon {$id}" . PHP_EOL;
    exit(0);
}

// Set the user on the Auth facade so controller sees an authenticated user
Auth::setUser($user);

// Use Jalali date range for current month
$vNow = Verta::now();
$from = $vNow->startMonth()->format('Y/m/d');
$to = $vNow->endMonth()->format('Y/m/d');

// Build request
$request = Request::create('/api/salons/' . $id . '/sms-account/transactions', 'GET', ['from_date' => $from, 'to_date' => $to, 'per_page' => 10]);

$controller = new SmsTransactionController();
$response = $controller->index($request, $salon);

echo $response->getContent() . PHP_EOL;
