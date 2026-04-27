<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerFollowUpHistoryTestSeeder extends Seeder
{
    public function run(): void
    {
        $salonId = 1043;

        // مشتری‌های واقعی سالن
        $customerIds = DB::table('appointments')
            ->where('salon_id', $salonId)
            ->distinct()
            ->pluck('customer_id')
            ->toArray();

        if (empty($customerIds)) {
            $this->command->warn("هیچ مشتری برای سالن {$salonId} پیدا نشد.");
            return;
        }

        // گروه‌های مشتری واقعی
        $groupIds = DB::table('customer_groups')
            ->where('salon_id', $salonId)
            ->pluck('id')
            ->toArray();

        // سرویس‌های واقعی
        $serviceIds = DB::table('services')
            ->where('salon_id', $salonId)
            ->pluck('id')
            ->toArray();

        // تمپلیت سالن
        $template = DB::table('salon_sms_templates')->where('salon_id', $salonId)->first();
        if (!$template) {
            $template = DB::table('salon_sms_templates')->first();
        }
        $templateId = $template ? $template->id : null;

        // پیام‌های نمونه
        $messages = [
            'سلام {نام_مشتری} عزیز، مدتیه از سالن فلاتر خبری ندارید! منتظرتون هستیم 💇‍♀️',
            'سلام {نام_مشتری} گرامی، وقتشه یه سر به سالن فلاتر بزنید! تخفیف ویژه مشتریان قدیمی 🎉',
            '{نام_مشتری} عزیز، از آخرین مراجعه شما مدت زیادی گذشته. با کمال میل منتظرتون هستیم.',
            'سلام، برای رزرو نوبت با سالن فلاتر تماس بگیرید. خدمات جدید اضافه شده! ✨',
            '{نام_مشتری} عزیز، فصل جدید رسید و خدمات ویژه فصلی ما آماده‌ست. منتظرتونیم!',
            'سلام {نام_مشتری} جان، تخفیف ۲۰٪ ویژه مشتریان وفادار فقط تا پایان هفته 🔥',
            '{نام_مشتری} گرامی، خدمات مراقبت مو و پوست ما بروز شده. مشاوره رایگان!',
            'سلام، دلتنگتون شدیم! یه وقت نوبت بذارید بیاید سالن فلاتر 💐',
        ];

        $records = [];
        $now = Carbon::now();

        // ۲۵ رکورد پیگیری در ۶۰ روز اخیر (ترکیب دستی و خودکار)
        for ($i = 0; $i < 25; $i++) {
            $customerId = $customerIds[array_rand($customerIds)];
            $daysAgo = rand(0, 60);
            $hoursAgo = rand(8, 20); // ساعت کاری
            $sentAt = $now->copy()->subDays($daysAgo)->setHour($hoursAgo)->setMinute(rand(0, 59));
            $type = $i < 15 ? 'manual' : 'automatic';

            // گروه‌ها و سرویس‌های تصادفی
            $numGroups = rand(1, min(3, count($groupIds)));
            $randGroupKeys = (array) array_rand($groupIds, $numGroups);
            $selectedGroups = array_values(array_intersect_key($groupIds, array_flip($randGroupKeys)));

            $numServices = rand(1, min(2, count($serviceIds)));
            $randServiceKeys = (array) array_rand($serviceIds, $numServices);
            $selectedServices = array_values(array_intersect_key($serviceIds, array_flip($randServiceKeys)));

            $totalCustomers = rand(1, 8);
            $message = $messages[array_rand($messages)];

            $records[] = [
                'salon_id' => $salonId,
                'customer_id' => $customerId,
                'template_id' => $templateId,
                'message' => $message,
                'sent_at' => $sentAt,
                'type' => $type,
                'customer_group_ids' => json_encode($selectedGroups),
                'service_ids' => json_encode($selectedServices),
                'total_customers' => $totalCustomers,
                'sms_count' => rand(1, 2),
                'created_at' => $sentAt,
                'updated_at' => $sentAt,
            ];
        }

        // مرتب‌سازی بر اساس تاریخ
        usort($records, fn($a, $b) => $b['sent_at'] <=> $a['sent_at']);

        // غیرفعال کردن FK checks به دلیل MyISAM بودن salon_sms_templates
        $connection = DB::connection();
        $connection->statement('SET FOREIGN_KEY_CHECKS=0');
        $connection->table('customer_followup_histories')->insert($records);
        $connection->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info("✅ " . count($records) . " رکورد پیگیری مشتری برای سالن {$salonId} ایجاد شد.");
    }
}
