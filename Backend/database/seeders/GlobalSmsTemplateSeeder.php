<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SalonSmsTemplate;

class GlobalSmsTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $globalTemplates = [
            // Global Templates - اینها برای همه سالن‌ها نمایش داده می‌شن
            [
                'salon_id' => null,
                'event_type' => 'appointment_confirmation',
                'template' => '{{customer_name}} عزیز، نوبت شما برای {{service_names}} در تاریخ {{appointment_date}} ساعت {{start_time}} در {{salon_name}} تایید گردید.',
                'is_active' => true,
            ],
            
            [
                'salon_id' => null,
                'event_type' => 'appointment_reminder',
                'template' => '{{customer_name}} عزیز، یادآوری می‌کنیم که نوبت شما برای {{service_names}} فردا {{appointment_date}} ساعت {{start_time}} در {{salon_name}} خواهد بود.',
                'is_active' => true,
            ],
            
            [
                'salon_id' => null,
                'event_type' => null, // Custom template
                'template' => '{{customer_name}} عزیز، نوبت شما برای {{service_names}} روز {{appointment_date}} ساعت {{start_time}} در {{salon_name}} رزرو شد. کد پیگیری: {{appointment_hash}}',
                'is_active' => true,
            ],
            
            [
                'salon_id' => null,
                'event_type' => null, // Custom template
                'template' => 'سلام {{customer_name}} عزیز! 🌟 نوبت شما برای {{service_names}} در {{salon_name}} تایید شد. 📅 {{appointment_date}} ⏰ {{start_time}} منتظرتونیم! 💫',
                'is_active' => true,
            ],
            
            [
                'salon_id' => null,
                'event_type' => null, // Custom template  
                'template' => '✨ {{customer_name}} عزیز، یادآوری نوبت! فردا {{appointment_date}} ساعت {{start_time}} برای {{service_names}} در {{salon_name}} منتظرتون هستیم 🌸',
                'is_active' => true,
            ],
            
            [
                'salon_id' => null,
                'event_type' => null, // Custom template
                'template' => '{{customer_name}} عزیز، {{time_until_appointment_text_formal}} تا نوبت {{service_names}} شما باقی مانده. {{appointment_date}} - {{start_time}} در {{salon_name}}. لطفا در صورت لغو اطلاع دهید.',
                'is_active' => true,
            ],
            
            [
                'salon_id' => null,
                'event_type' => 'birthday_greeting',
                'template' => 'تولدتان مبارک {{customer_name}} عزیز! 🎂🎉 تیم {{salon_name}} بهترین آرزوها را برایتان دارد. امیدواریم سالی پر از زیبایی و شادی داشته باشید! 🌟',
                'is_active' => true,
            ],
            
            [
                'salon_id' => null,
                'event_type' => 'appointment_modification',
                'template' => '{{customer_name}} عزیز، نوبت شما در {{salon_name}} به تاریخ جدید {{appointment_date}} ساعت {{start_time}} تغییر یافت. منتظرتان هستیم.',
                'is_active' => true,
            ],
            
            [
                'salon_id' => null,
                'event_type' => 'appointment_cancellation',
                'template' => '{{customer_name}} عزیز، متاسفانه نوبت شما در {{salon_name}} برای تاریخ {{appointment_date}} ساعت {{start_time}} لغو گردید. جهت رزرو مجدد تماس بگیرید.',
                'is_active' => true,
            ],            // Exclusive link template for system
            [
                'salon_id' => null,
                'event_type' => 'exclusive_link',
                'template' => 'سلام {{customer_name}}، برای رزرو آنلاین در سالن {{salon_name}} روی لینک زیر کلیک کنید: {{details_url}}',
                'is_active' => true,
                'template_type' => 'system_event',
            ],
            // Global custom templates for exclusive links (available in admin panel)
            [
                'salon_id' => null,
                'event_type' => 'exclusive_link',
                'template_type' => 'custom',
                'title' => 'قالب لینک رزرو آنلاین رسمی',
                'template' => 'لینک رزرو آنلاین برای {{salon_name}}: {{details_url}}',
                'is_active' => true,
            ],
            [
                'salon_id' => null,
                'event_type' => 'exclusive_link',
                'template_type' => 'custom',
                'title' => 'قالب دوستانه لینک اختصاصی',
                'template' => 'سلام {{customer_name}} عزیز! لینک رزرو شما: {{details_url}} — منتظر دیدارتون هستیم. {{salon_name}}',
                'is_active' => true,
            ],        ];

        foreach ($globalTemplates as $template) {
            // بررسی duplicate بر اساس template content
            $exists = SalonSmsTemplate::where('salon_id', null)
                ->where('template', $template['template'])
                ->first();
                
            if (!$exists) {
                $newTemplate = SalonSmsTemplate::create($template);
                // محاسبه و به‌روزرسانی estimated_parts و estimated_cost
                $newTemplate->updateEstimatedValues();
                $eventType = $template['event_type'] ?? 'custom';
                $this->command->info("✅ Global template created: {$eventType}");
            } else {
                $eventType = $template['event_type'] ?? 'custom';
                $this->command->warn("⚠️  Global template already exists: {$eventType}");
            }
        }
        
        $this->command->info("🎉 Global SMS Templates seeding completed!");
        $this->command->info("📊 Created " . count($globalTemplates) . " global templates that will be available for all salons");
    }
}