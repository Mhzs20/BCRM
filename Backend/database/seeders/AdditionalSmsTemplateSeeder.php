<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SalonSmsTemplate;

class AdditionalSmsTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * این seeder template های جدید اضافه می‌کنه که با موجودی‌ها تداخل نداره
     */
    public function run(): void
    {
        $templates = [
            // Template برای Appointment Follow-up - جدید (با موجودی‌ها تداخل نداره)
            [
                'salon_id' => null,
                'event_type' => 'appointment_followup',
                'template' => '{{customer_name}} عزیز، امیدواریم از سرویس {{service_names}} در {{salon_name}} راضی بوده‌اید. نظر شما برای ما مهم است.',
                'is_active' => true,
            ],
            
            // Template برای Service Completion - جدید
            [
                'salon_id' => null,
                'event_type' => 'service_completion',
                'template' => '{{customer_name}} عزیز، خدمات {{service_names}} شما در {{salon_name}} به پایان رسید. از انتخاب ما متشکریم و منتظر دیدار مجدد شما هستیم.',
                'is_active' => true,
            ],
            
            // Template برای Special Offers - جدید
            [
                'salon_id' => null,
                'event_type' => 'special_offer',
                'template' => '{{customer_name}} عزیز، پیشنهاد ویژه {{salon_name}} برای شما! تخفیف ویژه روی سرویس‌های منتخب. برای اطلاعات بیشتر تماس بگیرید.',
                'is_active' => true,
            ],
            
            // Template برای Payment Reminder - جدید
            [
                'salon_id' => null,
                'event_type' => 'payment_reminder',
                'template' => '{{customer_name}} عزیز، یادآوری پرداخت بابت سرویس {{service_names}} در {{salon_name}}. لطفا در اولین فرصت نسبت به تسویه حساب اقدام فرمایید.',
                'is_active' => true,
            ],
            
            // Template برای Welcome Message - جدید
            [
                'salon_id' => null,
                'event_type' => 'welcome_message',
                'template' => 'به {{salon_name}} خوش آمدید {{customer_name}} عزیز! ما افتخار خدمت‌رسانی به شما را داریم. برای رزرو نوبت با ما تماس بگیرید.',
                'is_active' => true,
            ],
            
            // Template برای Happy Birthday - جدید
            [
                'salon_id' => null,
                'event_type' => 'birthday_greeting',
                'template' => 'تولدتان مبارک {{customer_name}} عزیز! 🎉 در این روز خاص، {{salon_name}} تخفیف ویژه‌ای برای شما در نظر گرفته است. 🎂🎁',
                'is_active' => true,
            ],
        ];

        // Get all active salons
        $salons = \App\Models\Salon::where('is_active', true)->get();
        
        if ($salons->isEmpty()) {
            $this->command->warn("⚠️  No active salons found. Additional templates will not be created.");
            return;
        }

        $newTemplatesCount = 0;
        $existingTemplatesCount = 0;

        foreach ($salons as $salon) {
            foreach ($templates as $template) {
                // Set salon_id for this template
                $template['salon_id'] = $salon->id;
                
                // Check if template already exists for this salon and event_type
                $exists = SalonSmsTemplate::where('salon_id', $salon->id)
                    ->where('event_type', $template['event_type'])
                    ->first();
                    
                if (!$exists) {
                    $newTemplate = SalonSmsTemplate::create($template);
                    // محاسبه و به‌روزرسانی estimated_parts و estimated_cost
                    $newTemplate->updateEstimatedValues();
                    $newTemplatesCount++;
                    $this->command->info("✅ New template created for salon {$salon->name}: {$template['event_type']}");
                } else {
                    $existingTemplatesCount++;
                    $this->command->warn("⚠️  Template already exists for salon {$salon->name}: {$template['event_type']}");
                }
            }
        }
        
        $totalTemplates = count($templates);
        $totalSalons = $salons->count();
        
        $this->command->info("🎉 Additional SMS Templates seeding completed!");
        $this->command->info("📊 Summary:");
        $this->command->info("   - Salons processed: {$totalSalons}");
        $this->command->info("   - Template types: {$totalTemplates}");
        $this->command->info("   - New templates created: {$newTemplatesCount}");
        $this->command->info("   - Already existing: {$existingTemplatesCount}");
    }
}