<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Customer;
use App\Models\Salon;

class ImportCustomersToSalonsSeeder extends Seeder
{
    public function run()
    {
        $jsonPath = base_path('customers.json');
        $reportPath = base_path('import_report.txt');
        // پاک کردن گزارش قبلی
        file_put_contents($reportPath, "");

        if (!file_exists($jsonPath)) {
            echo "customers.json not found.";
            return;
        }

        // پردازش chunk به chunk
        $handle = fopen($jsonPath, 'r');
        if (!$handle) {
            echo "Cannot open customers.json";
            return;
        }
        $buffer = '';
        $chunkSize = 1000; // تعداد مشتری در هر chunk
        while (($line = fgets($handle)) !== false) {
            $buffer .= $line;
            // اگر به انتهای آرایه رسیدیم یا حجم بافر زیاد شد
            if (substr(trim($line), -1) === ']' || substr_count($buffer, '},') >= $chunkSize) {
                // حذف براکت ابتدا و انتها و تبدیل به آرایه
                $buffer = trim($buffer);
                if (substr($buffer, 0, 1) === '[') $buffer = substr($buffer, 1);
                if (substr($buffer, -1) === ']') $buffer = substr($buffer, 0, -1);
                $buffer = '[' . rtrim($buffer, ',') . ']';
                $customers = json_decode($buffer, true);
                if (is_array($customers)) {
                    foreach ($customers as $customer) {
                        $name = $customer['customer_name'] ?? null;
                        $phone = isset($customer['customer_phone']) ? preg_replace('/[^0-9]/', '', $customer['customer_phone']) : null;
                        $userPhone = $customer['user_phone'] ?? null;
                        if (!$name || !$phone || !$userPhone) {
                            file_put_contents($reportPath, "Rejected: Missing data for customer: " . json_encode($customer) . "\n", FILE_APPEND);
                            continue;
                        }
                        $user = \App\Models\User::where('mobile', $userPhone)->first();
                        if (!$user) {
                            file_put_contents($reportPath, "Rejected: User not found for user_phone: $userPhone\n", FILE_APPEND);
                            continue;
                        }
                        $salon = null;
                        if ($user->active_salon_id) {
                            $salon = Salon::find($user->active_salon_id);
                        }
                        if (!$salon) {
                            $salon = Salon::where('user_id', $user->id)->first();
                        }
                        if (!$salon) {
                            file_put_contents($reportPath, "Rejected: No salon found for user_id: {$user->id}\n", FILE_APPEND);
                            continue;
                        }
                        $exists = Customer::withTrashed()
                            ->where('salon_id', $salon->id)
                            ->whereRaw('TRIM(phone_number) = ?', [$phone])
                            ->first();
                        if ($exists) {
                            file_put_contents($reportPath, "Duplicate: Duplicate customer ($phone) in salon {$salon->id}\n", FILE_APPEND);
                            continue;
                        }
                        try {
                            Customer::create([
                                'name' => $name,
                                'phone_number' => $phone,
                                'salon_id' => $salon->id,
                            ]);
                            file_put_contents($reportPath, "Added: Added customer ($phone) to salon {$salon->id}\n", FILE_APPEND);
                        } catch (\Exception $e) {
                            file_put_contents($reportPath, "Error: Could not add customer ($phone) to salon {$salon->id}: " . $e->getMessage() . "\n", FILE_APPEND);
                        }
                    }
                }
                $buffer = '';
            }
        }
        fclose($handle);
        echo "Report saved to import_report.txt\n";
    }
}
