<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalonSmsTemplate;
use App\Models\SmsTemplateCategory;

class AdminBirthdayTemplatesSeeder extends Seeder
{
    public function run()
    {
        // Find or create the 'تبریک تولد' category for admin (global)
        $category = SmsTemplateCategory::firstOrCreate([
            'name' => 'تبریک تولد',
            'salon_id' => null
        ]);

        $templates = [
            [
                'title' => 'قالب رسمی تبریک تولد',
                'template' => 'مشتری گرامی {{customer_name}}، تولدتان مبارک! آرزوی سلامتی و شادی برای شما در {{salon_name}}.',
                'variables' => ['customer_name', 'salon_name']
            ],
            [
                'title' => 'قالب دوستانه تبریک تولد',
                'template' => 'سلام {{customer_name}} عزیز! تولدت مبارک! امیدواریم روز فوق‌العاده‌ای در {{salon_name}} داشته باشی.',
                'variables' => ['customer_name', 'salon_name']
            ],
            [
                'title' => 'قالب تبلیغاتی تبریک تولد',
                'template' => '{{customer_name}} عزیز، تولدت مبارک! با رزرو نوبت امروز از تخفیف ویژه تولد بهره‌مند شوید. {{salon_name}} 📞',
                'variables' => ['customer_name', 'salon_name']
            ],
            [
                'title' => 'قالب ساده تبریک تولد',
                'template' => '{{customer_name}} جان، تولدت مبارک! {{salon_name}}',
                'variables' => ['customer_name', 'salon_name']
            ]
        ];

        foreach ($templates as $tpl) {
            $template = SalonSmsTemplate::create([
                'category_id' => $category->id,
                'salon_id' => null,
                'title' => $tpl['title'],
                'template' => $tpl['template'],
                'variables' => json_encode($tpl['variables']),
                'is_active' => true
            ]);
            
            // محاسبه و به‌روزرسانی estimated_parts و estimated_cost
            $template->updateEstimatedValues();
        }
    }
}
