<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Salon;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ImportAppointmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // خواندن فایل JSON
        $jsonPath = base_path('a.json');
        
        if (!file_exists($jsonPath)) {
            $this->command->error("فایل a.json یافت نشد!");
            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        if (!$data) {
            $this->command->error("خطا در خواندن فایل JSON!");
            return;
        }

        // پیدا کردن بخش data از JSON
        $appointmentsData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
                $appointmentsData = $item['data'];
                break;
            }
        }

        if (!$appointmentsData) {
            $this->command->error("داده‌های نوبت یافت نشد!");
            return;
        }

        $this->command->info("شروع import نوبت‌ها...");
        
        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
        $errors = [];

        DB::beginTransaction();
        
        try {
            foreach ($appointmentsData as $appointmentData) {
                try {
                    // پیدا کردن سالن با نام
                    $salon = Salon::where('name', $appointmentData['salon_name'])->first();
                    
                    if (!$salon) {
                        $errors[] = "سالن '{$appointmentData['salon_name']}' یافت نشد - نوبت شماره {$appointmentData['appointment_id']}";
                        $errorCount++;
                        continue;
                    }

                    // پیدا کردن یا ایجاد مشتری
                    $customer = Customer::where('salon_id', $salon->id)
                        ->where('phone_number', $this->normalizePhone($appointmentData['customer_phone']))
                        ->first();

                    if (!$customer) {
                        // آماده‌سازی داده‌های مشتری
                        $customerData = [
                            'salon_id' => $salon->id,
                            'name' => $appointmentData['customer_name'],
                            'phone_number' => $this->normalizePhone($appointmentData['customer_phone']),
                        ];

                        // اضافه کردن فیلدهای اختیاری مشتری اگر موجود باشند
                        if (!empty($appointmentData['customer_birth_date'])) {
                            $customerData['birth_date'] = $appointmentData['customer_birth_date'];
                        }
                        if (!empty($appointmentData['customer_gender'])) {
                            $customerData['gender'] = $appointmentData['customer_gender'];
                        }
                        if (!empty($appointmentData['customer_address'])) {
                            $customerData['address'] = $appointmentData['customer_address'];
                        }

                        // ایجاد مشتری جدید
                        $customer = Customer::create($customerData);
                    } else {
                        // آپدیت اطلاعات مشتری موجود اگر داده‌های جدید موجود باشند
                        $updateData = [];
                        
                        if (!empty($appointmentData['customer_birth_date']) && empty($customer->birth_date)) {
                            $updateData['birth_date'] = $appointmentData['customer_birth_date'];
                        }
                        if (!empty($appointmentData['customer_gender']) && empty($customer->gender)) {
                            $updateData['gender'] = $appointmentData['customer_gender'];
                        }
                        if (!empty($appointmentData['customer_address']) && empty($customer->address)) {
                            $updateData['address'] = $appointmentData['customer_address'];
                        }

                        if (!empty($updateData)) {
                            $customer->update($updateData);
                        }
                    }

                    // بررسی وجود نوبت با همین شماره
                    $existingAppointment = Appointment::where('salon_id', $salon->id)
                        ->where('appointment_date', $appointmentData['appointment_date'])
                        ->where('start_time', $appointmentData['start_time'])
                        ->where('customer_id', $customer->id)
                        ->first();

                    if ($existingAppointment) {
                        $skippedCount++;
                        continue;
                    }

                    // پیدا کردن اولین staff سالن (یا null)
                    $staffId = $salon->staff()->first()?->id;

                    // آماده‌سازی داده‌های نوبت
                    $appointmentCreateData = [
                        'salon_id' => $salon->id,
                        'customer_id' => $customer->id,
                        'staff_id' => $staffId,
                        'appointment_date' => $appointmentData['appointment_date'],
                        'start_time' => $appointmentData['start_time'],
                        'end_time' => $appointmentData['end_time'],
                        'status' => $appointmentData['status'],
                        'hash' => \Str::random(32),
                        'created_at' => $appointmentData['created_at'],
                        'updated_at' => $appointmentData['updated_at'],
                    ];

                    // اضافه کردن فیلدهای اختیاری نوبت
                    if (isset($appointmentData['notes'])) {
                        $appointmentCreateData['notes'] = $appointmentData['notes'];
                    }
                    if (isset($appointmentData['total_price'])) {
                        $appointmentCreateData['total_price'] = $appointmentData['total_price'];
                    }
                    if (isset($appointmentData['total_duration'])) {
                        $appointmentCreateData['total_duration'] = $appointmentData['total_duration'];
                    }
                    if (isset($appointmentData['deposit_required'])) {
                        $appointmentCreateData['deposit_required'] = (bool) $appointmentData['deposit_required'];
                    }
                    if (isset($appointmentData['deposit_paid'])) {
                        $appointmentCreateData['deposit_paid'] = (bool) $appointmentData['deposit_paid'];
                    }

                    // ایجاد نوبت
                    Appointment::create($appointmentCreateData);

                    $successCount++;
                    
                    if ($successCount % 10 == 0) {
                        $this->command->info("تعداد {$successCount} نوبت با موفقیت ایجاد شد...");
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "خطا در نوبت شماره {$appointmentData['appointment_id']}: " . $e->getMessage();
                    Log::error("خطا در import نوبت: " . $e->getMessage(), [
                        'appointment_id' => $appointmentData['appointment_id'] ?? 'unknown',
                        'exception' => $e
                    ]);
                }
            }

            DB::commit();
            
            $this->command->info("=================================");
            $this->command->info("Import نوبت‌ها با موفقیت انجام شد!");
            $this->command->info("تعداد نوبت‌های موفق: {$successCount}");
            $this->command->info("تعداد نوبت‌های تکراری (رد شده): {$skippedCount}");
            
            if ($errorCount > 0) {
                $this->command->warn("تعداد خطاها: {$errorCount}");
                $this->command->warn("جزئیات خطاها:");
                foreach (array_slice($errors, 0, 20) as $error) {
                    $this->command->warn("  - " . $error);
                }
                if (count($errors) > 20) {
                    $this->command->warn("  ... و " . (count($errors) - 20) . " خطای دیگر");
                }
            }
            
            $this->command->info("=================================");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("خطای کلی در import: " . $e->getMessage());
            Log::error("خطای کلی در import نوبت‌ها: " . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * نرمال‌سازی شماره تلفن
     */
    private function normalizePhone($phone)
    {
        // حذف کاراکترهای غیر عددی
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // حذف 98+ از ابتدا در صورت وجود
        if (substr($phone, 0, 2) === '98') {
            $phone = '0' . substr($phone, 2);
        } elseif (substr($phone, 0, 3) === '989') {
            $phone = '0' . substr($phone, 3);
        }
        
        return $phone;
    }
}
