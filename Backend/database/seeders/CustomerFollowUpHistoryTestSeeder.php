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
        $customerIds = [125429, 112621, 126418];

        // پیدا کردن یک template برای این سالن
        $template = DB::table('salon_sms_templates')->where('salon_id', $salonId)->first();
        if (!$template) {
            $template = DB::table('salon_sms_templates')->first();
        }
        $templateId = $template ? $template->id : null;

        $records = [];
        $now = Carbon::now();

        // ایجاد 10 رکورد تست - پیگیری دستی در روزهای مختلف
        foreach ($customerIds as $index => $customerId) {
            for ($i = 0; $i < 5; $i++) {
                $records[] = [
                    'salon_id' => $salonId,
                    'customer_id' => $customerId,
                    'template_id' => $templateId,
                    'message' => "پیام تست پیگیری شماره " . ($i + 1) . " برای مشتری " . $customerId,
                    'sent_at' => $now->copy()->subDays($i)->subHours($index),
                    'type' => 'manual',
                    'customer_group_ids' => json_encode([1]),
                    'service_ids' => json_encode([1]),
                    'total_customers' => 2,
                    'sms_count' => 1,
                    'created_at' => $now->copy()->subDays($i)->subHours($index),
                    'updated_at' => $now->copy()->subDays($i)->subHours($index),
                ];
            }
        }

        // غیرفعال کردن FK checks به دلیل MyISAM بودن salon_sms_templates
        $connection = DB::connection();
        $connection->statement('SET FOREIGN_KEY_CHECKS=0');
        $connection->table('customer_followup_histories')->insert($records);
        $connection->statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info("✅ " . count($records) . " رکورد تست در customer_followup_histories ایجاد شد.");
    }
}
