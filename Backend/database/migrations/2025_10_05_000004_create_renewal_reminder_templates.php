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
        // ایجاد دسته‌بندی یادآوری ترمیم
        $category = SmsTemplateCategory::firstOrCreate([
            'salon_id' => null, // دسته سراسری
            'name' => 'یادآوری ترمیم'
        ]);

        // ایجاد چند قالب پیش‌فرض
        $templates = [
            [
                'title' => 'قالب رسمی یادآوری ترمیم',
                'template' => 'مشتری گرامی {{customer_name}}، وقت ترمیم {{service_name}} شما در {{salon_name}} رسیده است. برای رزرو نوبت جدید با ما تماس بگیرید. 🌸'
            ],
            [
                'title' => 'قالب دوستانه یادآوری ترمیم',
                'template' => 'سلام {{customer_name}} عزیز! 😊 تقریباً زمان ترمیم {{service_name}} شما رسیده. کی میای که دوباره قشنگت کنیم؟ 💅 {{salon_name}}'
            ],
            [
                'title' => 'قالب تبلیغاتی یادآوری ترمیم',
                'template' => '⭐ {{customer_name}} عزیز، وقت ترمیم {{service_name}} شما رسیده! همین حالا نوبت بگیرید و از تخفیف ویژه بهره‌مند شوید. {{salon_name}} 📞'
            ],
            [
                'title' => 'قالب ساده یادآوری ترمیم',
                'template' => '{{customer_name}} جان، ترمیم {{service_name}} شما موعدشه! {{salon_name}}'
            ]
        ];

        foreach ($templates as $templateData) {
            SalonSmsTemplate::firstOrCreate([
                'salon_id' => null, // قالب سراسری
                'category_id' => $category->id,
                'title' => $templateData['title'],
                'template_type' => 'custom'
            ], [
                'template' => $templateData['template'],
                'is_active' => true
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف قالب‌های یادآوری ترمیم
        $category = SmsTemplateCategory::where('salon_id', null)
            ->where('name', 'یادآوری ترمیم')
            ->first();
            
        if ($category) {
            SalonSmsTemplate::where('category_id', $category->id)->delete();
            $category->delete();
        }
    }
};