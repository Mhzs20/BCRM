<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\RenewalReminderSetting;
use App\Models\Salon;
use App\Models\Service;
use App\Models\SalonSmsTemplate;
use App\Models\SmsTemplateCategory;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RenewalReminderTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 شروع ایجاد داده‌های تست برای یادآوری ترمیم...');

        // دریافت یا ایجاد دسته‌بندی یادآوری ترمیم
        $category = SmsTemplateCategory::firstOrCreate([
            'salon_id' => null,
            'name' => 'یادآوری ترمیم'
        ]);

        // دریافت اولین سالن
        $salon = Salon::first();
        if (!$salon) {
            $this->command->error('❌ هیچ سالنی در دیتابیس وجود ندارد. ابتدا یک سالن ایجاد کنید.');
            return;
        }

        $this->command->info("🏪 استفاده از سالن: {$salon->name}");

        // فعال کردن تنظیمات یادآوری ترمیم برای سالن
        $template = SalonSmsTemplate::where('category_id', $category->id)
            ->where('salon_id', null)
            ->first();

        if (!$template) {
            $this->command->error('❌ هیچ قالب یادآوری ترمیمی وجود ندارد.');
            return;
        }

        $setting = RenewalReminderSetting::updateOrCreate([
            'salon_id' => $salon->id
        ], [
            'is_active' => true,
            'active_template_id' => $template->id,
            'reminder_days_before' => 7,
            'reminder_time' => '10:00'
        ]);

        $this->command->info("✅ تنظیمات یادآوری ترمیم برای سالن فعال شد.");

        // ایجاد چند مشتری نمونه
        $customers = [
            [
                'name' => 'مریم رضایی',
                'phone_number' => '09123456789'
            ],
            [
                'name' => 'فاطمه احمدی',
                'phone_number' => '09123456788'
            ]
        ];

        foreach ($customers as $customerData) {
            $customer = Customer::firstOrCreate([
                'phone_number' => $customerData['phone_number'],
                'salon_id' => $salon->id
            ], [
                'name' => $customerData['name'],
                'gender' => 'female'
            ]);

            // دریافت یا ایجاد کارمند
            $staff = Staff::where('salon_id', $salon->id)->first();
            if (!$staff) {
                $staff = Staff::create([
                    'salon_id' => $salon->id,
                    'full_name' => 'کارمند نمونه',
                    'phone_number' => '09123456780',
                    'is_active' => true
                ]);
            }

            // دریافت یا ایجاد سرویس
            $service = Service::where('salon_id', $salon->id)->first();
            if (!$service) {
                $service = Service::create([
                    'salon_id' => $salon->id,
                    'name' => 'کاشت ناخن',
                    'price' => 500000,
                    'is_active' => true
                ]);
            }

            // ایجاد نوبت تکمیل شده با تاریخ ترمیم امروز + 7 روز
            $appointment = Appointment::create([
                'salon_id' => $salon->id,
                'customer_id' => $customer->id,
                'staff_id' => $staff->id,
                'appointment_date' => Carbon::today()->subDays(30), // نوبت یک ماه پیش
                'repair_date' => Carbon::today()->addDays(7), // ترمیم 7 روز آینده
                'start_time' => '10:00',
                'end_time' => '12:00',
                'status' => 'completed',
                'total_price' => 500000,
                'notes' => 'نوبت تست برای یادآوری ترمیم'
            ]);

            // اتصال سرویس به نوبت
            $appointment->services()->attach($service->id);

            $this->command->info("✅ نوبت تست برای مشتری {$customer->name} ایجاد شد (ID: {$appointment->id})");
        }

        $this->command->info('🎉 داده‌های تست با موفقیت ایجاد شدند!');
        $this->command->info('💡 برای تست command زیر را اجرا کنید:');
        $this->command->line('   php artisan reminders:send-renewal --dry-run');
    }
}