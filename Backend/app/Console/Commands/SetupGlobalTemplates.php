<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Profession;
use App\Models\HowIntroduced;
use App\Models\CustomerGroup;

class SetupGlobalTemplates extends Command
{
    protected $signature = 'setup:global-templates';
    protected $description = 'Create global templates (salon_id = NULL) for Professions, HowIntroduced, and CustomerGroups';

    public function handle()
    {
        $this->info('Setting up global templates...');

        // 1. Professions
        $defaultProfessions = [
            'خانه‌دار', 'معلم / مدرس', 'کارمند بانک / اداری / شرکتی', 'دانشجو / محصل',
            'پزشک / دندانپزشک', 'عکاس / فیلمبردار', 'پرستار', 'وکیل', 'مشاور',
            'شغل آزاد', 'مهندس', 'خیاط', 'منشی', 'حسابدار', 'داروخانه',
            'فروشنده', 'فیلمبردار', 'آرایشگر', 'استاد دانشگاه', 'مدیر شرکت خصوصی',
            'بلاگر', 'مدل', 'فریلنسر', 'طراح', 'برنامه‌نویس', 'مجری / بازیگر', 'بیکار'
        ];

        foreach ($defaultProfessions as $name) {
            Profession::firstOrCreate(
                ['salon_id' => null, 'name' => $name],
                ['salon_id' => null, 'name' => $name]
            );
        }
        $this->info('Global Professions created.');

        // 2. HowIntroduced
        $defaultHowIntroduced = [
            'اینستاگرام', 'گوگل / اینترنت', 'وب سایت', 'بروشور/ تراکت',
            'معرفی دوستان', 'معرفی مشتریان', 'معرفی پرسنل', 'پیامک',
            'تماس', 'پیام واتساپ', 'بیلبورد', 'همایش / نمایشگاه',
            'تبلیغات', 'تابلوی سالن', 'اتفاقی', 'سایر'
        ];

        foreach ($defaultHowIntroduced as $name) {
            HowIntroduced::firstOrCreate(
                ['salon_id' => null, 'name' => $name],
                ['salon_id' => null, 'name' => $name]
            );
        }
        $this->info('Global HowIntroduced options created.');

        // 3. CustomerGroups
        $defaultCustomerGroups = [
            'مشتریان جدید', 'مشتریان وفادار', 'مشتریان VIP'
        ];

        foreach ($defaultCustomerGroups as $name) {
            CustomerGroup::firstOrCreate(
                ['salon_id' => null, 'name' => $name],
                ['salon_id' => null, 'name' => $name]
            );
        }
        $this->info('Global CustomerGroups created.');

        $this->info('All global templates setup successfully.');
    }
}
