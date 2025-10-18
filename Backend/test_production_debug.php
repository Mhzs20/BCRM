<?php
// Add this as a test file on your production server
// test_debug.php

echo "PHP Memory Limit: " . ini_get('memory_limit') . "\n";
echo "PHP Version: " . phpversion() . "\n";

try {
    require 'vendor/autoload.php';
    require 'bootstrap/app.php';
    
    echo "Laravel app loaded successfully\n";
    
    // Test database connection
    $pdo = DB::connection()->getPdo();
    echo "Database connected successfully\n";
    
    // Test SmsTransaction model
    $count = \App\Models\SmsTransaction::count();
    echo "SmsTransaction count: " . $count . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}