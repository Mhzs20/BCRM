<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SmsTemplateCategory;
use App\Models\SalonSmsTemplate;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ایجاد دسته‌بندی‌های مربوط به نوبت‌گیری
        $appointmentBookingCategory = SmsTemplateCategory::firstOrCreate([
            'salon_id' => null, // دسته سراسری
            'name' => 'ثبت نوبت'
        ]);

        $appointmentReminderCategory = SmsTemplateCategory::firstOrCreate([
            'salon_id' => null, // دسته سراسری
            'name' => 'یادآوری نوبت'
        ]);

        // ایجاد تمپلیت‌های ثبت نوبت
        $bookingTemplates = [
            [
                'title' => 'تایید ثبت نوبت - رسمی',
                'template' => '{{customer_name}} عزیز، نوبت شما برای {{service_names}} در تاریخ {{appointment_date}} ساعت {{start_time}} در {{salon_name}} ثبت شد. کد نوبت: {{appointment_hash}}'
            ],
            [
                'title' => 'تایید ثبت نوبت - دوستانه',
                'template' => 'سلام {{customer_name}} جان! 🌸 نوبتت برای {{service_names}} روز {{appointment_date}} ساعت {{start_time}} رزرو شد. منتظرتیم! {{salon_name}} 💕'
            ],
            [
                'title' => 'تایید ثبت نوبت - تجاری',
                'template' => '⭐ {{customer_name}} عزیز، نوبت شما با موفقیت ثبت شد! 📅 تاریخ: {{appointment_date}} ⏰ ساعت: {{start_time}} 💎 سرویس: {{service_names}} {{salon_name}} 📞 {{salon_phone}}'
            ]
        ];

        foreach ($bookingTemplates as $templateData) {
            SalonSmsTemplate::firstOrCreate([
                'salon_id' => null, // قالب سراسری
                'category_id' => $appointmentBookingCategory->id,
                'title' => $templateData['title'],
                'template_type' => 'custom'
            ], [
                'template' => $templateData['template'],
                'is_active' => true
            ]);
        }

        // ایجاد تمپلیت‌های یادآوری نوبت
        $reminderTemplates = [
            [
                'title' => 'یادآوری نوبت - رسمی',
                'template' => '{{customer_name}} عزیز، یادآور می‌شود که نوبت شما برای {{service_names}} فردا {{appointment_date}} ساعت {{start_time}} در {{salon_name}} می‌باشد.'
            ],
            [
                'title' => 'یادآوری نوبت - دوستانه',
                'template' => 'سلام {{customer_name}} جان! 😊 یادت نره که فردا {{appointment_date}} ساعت {{start_time}} نوبت {{service_names}} داری. منتظرتیم! {{salon_name}} 🌺'
            ],
            [
                'title' => 'یادآوری نوبت - تجاری',
                'template' => '🔔 یادآوری نوبت {{customer_name}} عزیز! 📅 فردا {{appointment_date}} ⏰ {{start_time}} 💄 {{service_names}} در {{salon_name}} منتظر شما هستیم 📞 {{salon_phone}}'
            ],
            [
                'title' => 'یادآوری نوبت - 24 ساعته',
                'template' => 'سلام {{customer_name}}! 🕐 {{time_until_appointment_text}} نوبت {{service_names}} داری. {{appointment_date}} ساعت {{start_time}} در {{salon_name}}. اگه نمیتونی بیای حتما خبرمون کن 📱'
            ]
        ];

        foreach ($reminderTemplates as $templateData) {
            SalonSmsTemplate::firstOrCreate([
                'salon_id' => null, // قالب سراسری
                'category_id' => $appointmentReminderCategory->id,
                'title' => $templateData['title'],
                'template_type' => 'custom'
            ], [
                'template' => $templateData['template'],
                'is_active' => true
            ]);
        }

        // بروزرسانی یا ایجاد تمپلیت‌های سیستم
        SalonSmsTemplate::updateOrCreate([
            'salon_id' => null,
            'event_type' => 'appointment_confirmation',
            'template_type' => 'system_event'
        ], [
            'template' => '{{customer_name}} عزیز، نوبت شما برای {{service_names}} در تاریخ {{appointment_date}} ساعت {{start_time}} در {{salon_name}} ثبت شد.',
            'is_active' => true
        ]);

        SalonSmsTemplate::updateOrCreate([
            'salon_id' => null,
            'event_type' => 'appointment_reminder',
            'template_type' => 'system_event'
        ], [
            'template' => '{{customer_name}} عزیز، یادآور می‌شود که نوبت شما برای {{service_names}} فردا {{appointment_date}} ساعت {{start_time}} در {{salon_name}} می‌باشد.',
            'is_active' => true
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف قالب‌های ثبت نوبت
        $bookingCategory = SmsTemplateCategory::where('salon_id', null)
            ->where('name', 'ثبت نوبت')
            ->first();
            
        if ($bookingCategory) {
            SalonSmsTemplate::where('category_id', $bookingCategory->id)->delete();
            $bookingCategory->delete();
        }

        // حذف قالب‌های یادآوری نوبت
        $reminderCategory = SmsTemplateCategory::where('salon_id', null)
            ->where('name', 'یادآوری نوبت')
            ->first();
            
        if ($reminderCategory) {
            SalonSmsTemplate::where('category_id', $reminderCategory->id)->delete();
            $reminderCategory->delete();
        }
    }
};
