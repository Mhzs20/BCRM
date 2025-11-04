<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmsTemplateCategory;
use App\Models\SalonSmsTemplate;

class BirthdayReminderTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $category = SmsTemplateCategory::firstOrCreate([
            'salon_id' => null,
            'name' => 'تبریک تولد'
        ]);

        $templates = [
            [
                'title' => 'قالب رسمی تبریک تولد',
                'template' => 'مشتری گرامی {{customer_name}}، تولدتان مبارک! از طرف {{salon_name}} بهترین آرزوها را داریم. 🎉'
            ],
            [
                'title' => 'قالب دوستانه تبریک تولد',
                'template' => 'سلام {{customer_name}} عزیز! 🎂 تولدت مبارک! امیدواریم روز فوق‌العاده‌ای داشته باشی. {{salon_name}}'
            ],
            [
                'title' => 'قالب تبلیغاتی تبریک تولد',
                'template' => '⭐ {{customer_name}} عزیز، تولدت مبارک! با رزرو نوبت تولد از تخفیف ویژه بهره‌مند شوید. {{salon_name}} 📞'
            ],
            [
                'title' => 'قالب ساده تبریک تولد',
                'template' => '{{customer_name}} جان، تولدت مبارک! {{salon_name}}'
            ]
        ];

        foreach ($templates as $tpl) {
            SalonSmsTemplate::firstOrCreate([
                'salon_id' => null,
                'category_id' => $category->id,
                'title' => $tpl['title'],
                'template_type' => 'custom'
            ], [
                'template' => $tpl['template'],
                'is_active' => true
            ]);
        }
    }
}
