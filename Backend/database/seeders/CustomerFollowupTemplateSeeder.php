<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmsTemplateCategory;
use App\Models\SalonSmsTemplate;

class CustomerFollowupTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create category for Customer Follow-up
        $category = SmsTemplateCategory::firstOrCreate([
            'name' => 'پیگیری مشتری',
            'salon_id' => null,
        ]);

        // Create default templates for customer follow-up
        $templates = [
            [
                'title' => 'پیگیری دلگیری - 15 روز',
                'template' => 'سلام {{customer_name}} عزیز، مدتیه که شما را در {{salon_name}} نداشتیم. دلتنگ دیدار شما هستیم. منتظر حضور شما هستیم! 🌸',
            ],
            [
                'title' => 'پیگیری با تخفیف - 30 روز',
                'template' => '{{customer_name}} عزیز، مدت زیادی است که شما را ندیده‌ایم! برای بازگشت شما، تخفیف ویژه ۲۰٪ برای تمام خدمات {{salon_name}} در نظر گرفتیم. منتظر شما هستیم! 💐',
            ],
            [
                'title' => 'پیگیری صمیمی',
                'template' => 'سلام {{customer_name}}، چند وقته که شما را در {{salon_name}} ندیده‌ایم. دلمان برای شما تنگ شده! امیدواریم که حالتون خوب باشه. به امید دیدار شما 🌹',
            ],
            [
                'title' => 'پیگیری با یادآوری خدمات',
                'template' => '{{customer_name}} عزیز، زمان مناسبی برای تمدید خدمات {{salon_name}} فرا رسیده است. منتظر شما هستیم تا بهترین خدمات را برای شما فراهم کنیم.',
            ],
            [
                'title' => 'پیگیری مراقبتی',
                'template' => 'سلام {{customer_name}}، امیدواریم از خدمات قبلی {{salon_name}} راضی بوده‌اید. برای حفظ زیبایی و سلامت خود، زمان مراجعه مجدد فرا رسیده است. منتظر شما هستیم! ✨',
            ],
        ];

        foreach ($templates as $template) {
            SalonSmsTemplate::firstOrCreate([
                'category_id' => $category->id,
                'salon_id' => null,
                'title' => $template['title'],
            ], [
                'template' => $template['template'],
                'event_type' => 'customer_followup',
                'is_active' => true,
                'template_type' => 'system',
            ]);
        }

        $this->command->info('✅ Customer follow-up templates created successfully!');
        $this->command->info('   - Category: پیگیری مشتری');
        $this->command->info('   - Templates: ' . count($templates) . ' default templates');
    }
}
