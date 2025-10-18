<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

echo "Testing SmsTransaction model..." . PHP_EOL;

try {
    $count = \App\Models\SmsTransaction::count();
    echo "SMS Transactions count: " . $count . PHP_EOL;
    
    // Test if we can query the model with the required relationships
    $transactions = \App\Models\SmsTransaction::with(['user', 'salon', 'approver'])
        ->where('sms_type', 'manual_sms')
        ->take(1)
        ->get();
    
    echo "Query successful. Found " . $transactions->count() . " transactions." . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}