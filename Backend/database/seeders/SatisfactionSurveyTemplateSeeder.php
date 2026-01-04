<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmsTemplateCategory;
use App\Models\SalonSmsTemplate;

class SatisfactionSurveyTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create category
        $category = SmsTemplateCategory::firstOrCreate([
            'name' => 'رضایت‌سنجی',
            'salon_id' => null,
        ]);

        // Create default templates
        $templates = [
            [
                'title' => 'رضایت‌سنجی پس از خدمات',
                'template' => '{نام_مشتری} عزیز، از بازدید شما در {نام_سالن} سپاسگزاریم. لطفا نظر خود را با کلیک بر روی لینک زیر با ما به اشتراک بگذارید: {لینک_رضایت_سنجی}',
            ],
            [
                'title' => 'رضایت‌سنجی ساده',
                'template' => 'سلام {نام_مشتری}، از خدمات {نام_سالن} راضی بودید؟ نظر شما: {لینک_رضایت_سنجی}',
            ],
            [
                'title' => 'رضایت‌سنجی کامل با جزئیات',
                'template' => '{نام_مشتری} عزیز، از انتخاب {نام_سالن} برای دریافت خدمات {نام_خدمت} در تاریخ {تاریخ_نوبت} سپاسگزاریم. لطفا نظر خود را ثبت کنید: {لینک_رضایت_سنجی}',
            ],
        ];

        foreach ($templates as $template) {
            SalonSmsTemplate::firstOrCreate([
                'category_id' => $category->id,
                'salon_id' => null,
                'title' => $template['title'],
            ], [
                'template' => $template['template'],
                'event_type' => 'satisfaction_survey',
                'is_active' => true,
            ]);
        }

        $this->command->info('Satisfaction survey templates created successfully!');
    }
}
