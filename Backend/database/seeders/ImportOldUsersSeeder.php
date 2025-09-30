<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Salon;
use App\Models\SalonSmsBalance;

class ImportOldUsersSeeder extends Seeder
{
    public function run(): void
    {
    $json = file_get_contents(base_path('users.json'));
    $data = json_decode($json, true);
    if (!isset($data[2]['data'])) return;
    $users = $data[2]['data'];
        $newUsers = 0;
        $newSalons = 0;
        $smsBalancesCreated = 0;
        $smsBalancesUpdated = 0;
        $invalidMobiles = 0;
        $newUsersList = [];
        $smsBalancesLog = [];

        foreach ($users as $oldUser) {
            $mobile = $oldUser['phone_number'] ?? null;
            if (!$mobile || !preg_match('/^09\d{9}$/', $mobile)) {
                $invalidMobiles++;
                continue;
            }
            $mysms = is_numeric($oldUser['mysms'] ?? null) ? intval($oldUser['mysms']) : 0;
            $name = $oldUser['name'] ?? null;

            $user = User::where('mobile', $mobile)->first();
            if (!$user) {
                $user = User::create([
                    'name' => $name ?? 'کاربر ' . $mobile,
                    'mobile' => $mobile,
                    'password' => Hash::make($mobile),
                ]);
                $newUsers++;
                $newUsersList[] = $mobile . ($name ? " ($name)" : "");
            }

            $salon = Salon::where('user_id', $user->id)
                ->where('name', $name ?? 'سالن کاربر ' . $mobile)
                ->first();
            if (!$salon) {
                $salon = new Salon([
                    'name' => $name ?? 'سالن کاربر ' . $mobile,
                    'user_id' => $user->id,
                ]);
                $salon->save();
                $newSalons++;
            }

            if ($mysms !== 0) {
                $smsBalance = SalonSmsBalance::where('salon_id', $salon->id)->first();
                if ($smsBalance) {
                    $smsBalance->balance += $mysms;
                    $smsBalance->save();
                    $smsBalancesUpdated++;
                    $smsBalancesLog[] = "اعتبار $mysms به سالن کاربر $mobile اضافه شد";
                } else {
                    SalonSmsBalance::create([
                        'salon_id' => $salon->id,
                        'balance' => $mysms,
                    ]);
                    $smsBalancesCreated++;
                    $smsBalancesLog[] = "اعتبار $mysms برای سالن کاربر $mobile ثبت شد";
                }
            }
        }

        $report = "\n--- گزارش ایمپورت کاربران و سالن‌ها ---\n";
        $report .= "تعداد کاربران جدید: $newUsers\n";
        if ($newUsersList) {
            $report .= "لیست کاربران جدید:\n" . implode("\n", $newUsersList) . "\n";
        }
        $report .= "تعداد سالن‌های جدید: $newSalons\n";
        $report .= "اعتبار پیامک ثبت‌شده جدید: $smsBalancesCreated\n";
        $report .= "اعتبار پیامک آپدیت‌شده: $smsBalancesUpdated\n";
        if ($smsBalancesLog) {
            $report .= "گزارش اعتبار پیامک:\n" . implode("\n", $smsBalancesLog) . "\n";
        }
        $report .= "شماره‌های موبایل نامعتبر رد شده: $invalidMobiles\n";

        file_put_contents(base_path('import_report.txt'), $report);
}
}