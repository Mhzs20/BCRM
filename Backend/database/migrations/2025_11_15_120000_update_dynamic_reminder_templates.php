<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('salon_sms_templates')
            ->where('salon_id', null)
            ->where('title', 'یادآوری نوبت - 24 ساعته')
            ->update([
                'template' => 'سلام {{customer_name}}! 🕐 {{time_until_appointment_text}} نوبت {{service_names}} داری. {{appointment_date}} ساعت {{start_time}} در {{salon_name}}. اگه نمیتونی بیای حتما خبرمون کن 📱',
            ]);

        DB::table('salon_sms_templates')
            ->where('salon_id', null)
            ->where('template', '{{customer_name}} عزیز، 24 ساعت تا نوبت {{service_names}} شما باقی مانده. {{appointment_date}} - {{start_time}} در {{salون_name}}. لطفا در صورت لغو اطلاع دهید.')
            ->update([
                'template' => '{{customer_name}} عزیز، {{time_until_appointment_text_formal}} تا نوبت {{service_names}} شما باقی مانده. {{appointment_date}} - {{start_time}} در {{salon_name}}. لطفا در صورت لغو اطلاع دهید.',
            ]);
    }

    public function down(): void
    {
        DB::table('salon_sms_templates')
            ->where('salon_id', null)
            ->where('title', 'یادآوری نوبت - 24 ساعته')
            ->update([
                'template' => 'سلام {{customer_name}}! 🕐 24 ساعت دیگه نوبت {{service_names}} داری. {{appointment_date}} ساعت {{start_time}} در {{salon_name}}. اگه نمیتونی بیای حتما خبرمون کن 📱',
            ]);

        DB::table('salon_sms_templates')
            ->where('salon_id', null)
            ->where('template', '{{customer_name}} عزیز، {{time_until_appointment_text_formal}} تا نوبت {{service_names}} شما باقی مانده. {{appointment_date}} - {{start_time}} در {{salon_name}}. لطفا در صورت لغو اطلاع دهید.')
            ->update([
                'template' => '{{customer_name}} عزیز، 24 ساعت تا نوبت {{service_names}} شما باقی مانده. {{appointment_date}} - {{start_time}} در {{salon_name}}. لطفا در صورت لغو اطلاع دهید.',
            ]);
    }
};
