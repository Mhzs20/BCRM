<?php
/**
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$jsonFile = __DIR__ . '/storage/exports/salon_1043.json';
if (!file_exists($jsonFile)) {
    echo "ERROR: salon_1043.json not found!\n";
    exit(1);
}

$data = json_decode(file_get_contents($jsonFile), true);
if (!$data) {
    echo "ERROR: Failed to parse JSON!\n";
    exit(1);
}

/**

 */
$insertOrder = [
    // 1. Reference / lookup tables first (no FK deps within salon)
    '64_province'                          => ['table' => 'provinces'],
    '65_city'                              => ['table' => 'cities'],
    '62_business_category'                 => ['table' => 'business_categories'],
    '63_business_subcategories'            => ['table' => 'business_subcategories'],
    '74_referral_settings'                 => ['table' => 'referral_settings'],
    '75_card_settings'                     => ['table' => 'card_settings'],
    '68_sms_packages'                      => ['table' => 'sms_packages'],

    // 2. User (salon owner)
    '61_owner_user'                        => ['table' => 'users'],

    // 3. Salon itself
    '01_salons'                            => ['table' => 'salons', 'exclude' => ['owner_name','owner_mobile','owner_email','last_login_at']],

    // 4. Salon sub-category pivot
    '46_salon_business_subcategory'        => ['table' => 'salon_business_subcategory'],

    // 5. Staff
    '02_salon_staff'                       => ['table' => 'salon_staff'],
    '03_staff_schedules'                   => ['table' => 'staff_schedules'],
    '04_staff_breaks'                      => ['table' => 'staff_breaks'],

    // 6. Services
    '05_services'                          => ['table' => 'services'],
    '56_service_staff'                     => ['table' => 'service_staff'],
    '06_staff_service_commissions'         => ['table' => 'staff_service_commissions'],

    // 7. Customers & groups
    '08_customers'                         => ['table' => 'customers'],
    '09_customer_groups'                   => ['table' => 'customer_groups'],
    '10_customer_customer_group'           => ['table' => 'customer_customer_group'],
    '43_how_introduceds'                   => ['table' => 'how_introduceds'],
    '44_professions'                       => ['table' => 'professions'],
    '45_age_ranges'                        => ['table' => 'age_ranges'],

    // 8. Appointments
    '11_appointments'                      => ['table' => 'appointments', 'exclude' => ['customer_name','customer_phone','staff_name']],
    '12_appointment_service'               => ['table' => 'appointment_service'],
    '13_appointment_attachments'           => ['table' => 'appointment_attachments'],
    '49_pending_appointments'              => ['table' => 'pending_appointments'],
    '50_pending_appointment_updates'       => ['table' => 'pending_appointment_updates'],

    // 9. Customer feedback
    '25_customer_feedback'                 => ['table' => 'customer_feedback', 'exclude' => ['customer_name']],

    // 10. Financial
    '14_payments_received'                 => ['table' => 'payments_received'],
    '15_expenses'                          => ['table' => 'expenses'],
    '16_cashboxes'                         => ['table' => 'cashboxes'],
    '17_cashbox_transactions'              => ['table' => 'cashbox_transactions'],
    '18_transaction_categories'            => ['table' => 'transaction_categories'],
    '19_transaction_subcategories'         => ['table' => 'transaction_subcategories'],
    '07_staff_commission_transactions'     => ['table' => 'staff_commission_transactions'],

    // 11. SMS
    '20_salon_sms_balances'                => ['table' => 'salon_sms_balances'],
    '21_sms_transactions'                  => ['table' => 'sms_transactions'],
    '22_salon_sms_templates'               => ['table' => 'salon_sms_templates'],
    '47_sms_template_categories'           => ['table' => 'sms_template_categories'],
    '48_templates'                         => ['table' => 'templates'],
    '23_sms_campaigns'                     => ['table' => 'sms_campaigns'],
    '24_sms_campaign_messages'             => ['table' => 'sms_campaign_messages'],

    // 12. Surveys & followups
    '26_satisfaction_survey_settings'      => ['table' => 'satisfaction_survey_settings'],
    '58_satisfaction_survey_group_settings' => ['table' => 'satisfaction_survey_group_settings'],
    '27_satisfaction_survey_logs'           => ['table' => 'satisfaction_survey_logs'],
    '28_customer_followup_settings'        => ['table' => 'customer_followup_settings'],
    '29_followup_group_settings'           => ['table' => 'customer_followup_group_settings'],
    '30_followup_service_settings'         => ['table' => 'customer_followup_service_settings'],
    '31_customer_followup_histories'       => ['table' => 'customer_followup_histories'],
    '53_manual_followup_preparations'      => ['table' => 'manual_followup_preparations'],

    // 13. Reminders
    '32_renewal_reminder_settings'         => ['table' => 'renewal_reminder_settings'],
    '33_renewal_reminder_logs'             => ['table' => 'renewal_reminder_logs'],
    '34_service_renewal_settings'          => ['table' => 'service_renewal_settings'],
    '35_birthday_reminders'                => ['table' => 'birthday_reminders'],
    '36_birthday_reminder_groups'          => ['table' => 'birthday_reminder_customer_group'],

    // 14. Settings & misc
    '37_settings'                          => ['table' => 'settings'],
    '52_exclusive_link_preparations'       => ['table' => 'exclusive_link_preparations'],
    '51_shared_reports'                    => ['table' => 'shared_reports'],

    // 15. Orders & transactions
    '38_orders'                            => ['table' => 'orders'],
    '59_transactions'                      => ['table' => 'transactions'],

    // 16. Packages
    '66_packages'                          => ['table' => 'packages'],
    '67_package_options'                   => ['table' => 'options', 'exclude' => ['package_id']],
    '54_user_packages'                     => ['table' => 'user_packages'],

    // 17. Admins & permissions
    '41_salon_admins'                      => ['table' => 'salon_admins'],
    '57_salon_admin_permissions'           => ['table' => 'salon_admin_permissions'],
    '76_admin_otp_verifications'           => ['table' => 'admin_otp_verifications'],

    // 18. Discount codes
    '69_discount_codes_used'               => ['table' => 'discount_codes'],
    '42_discount_code_usages'              => ['table' => 'discount_code_salon_usages', 'exclude' => ['code','percentage']],

    // 19. User-level data
    '71_user_sms_balance'                  => ['table' => 'user_sms_balances'],
    '72_wallet_transactions'               => ['table' => 'wallet_transactions'],
    '73_user_referrals'                    => ['table' => 'user_referrals'],

    // 20. Notifications
    '55_notification_salon'                => ['table' => 'notification_salon', 'exclude' => ['title','message']],

    // 21. Logs
    '39_activity_logs'                     => ['table' => 'activity_logs'],
    '40_salon_notes'                       => ['table' => 'salon_notes'],

    // 22. Devices
    '60_connected_devices'                 => ['table' => 'connected_devices'],

    // 23. Permissions (reference)
    '70_permissions'                       => ['table' => 'permissions'],
];

$dbName = DB::connection()->getDatabaseName();

echo "========================================\n";
echo "  Seeding salon 1043 into: {$dbName}\n";
echo "========================================\n\n";

// Disable FK checks
DB::statement('SET FOREIGN_KEY_CHECKS=0');

$totalRows = 0;
$errors = [];

foreach ($insertOrder as $jsonKey => $config) {
    $table = $config['table'];
    $exclude = $config['exclude'] ?? [];

    // Check if JSON key exists
    if (!isset($data[$jsonKey]) || empty($data[$jsonKey])) {
        echo "  SKIP  {$jsonKey} (empty/missing)\n";
        continue;
    }

    // Check if data has _error
    if (isset($data[$jsonKey]['_error'])) {
        echo "  SKIP  {$jsonKey} (had export error)\n";
        continue;
    }

    // Check if table exists
    if (!Schema::hasTable($table)) {
        echo "  SKIP  {$jsonKey} → {$table} (table doesn't exist)\n";
        continue;
    }

    // Get actual table columns
    $tableColumns = Schema::getColumnListing($table);

    $rows = $data[$jsonKey];
    $inserted = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        // Remove excluded columns (from JOINs)
        foreach ($exclude as $col) {
            unset($row[$col]);
        }

        // Only keep columns that exist in the table
        $filtered = array_intersect_key($row, array_flip($tableColumns));

        if (empty($filtered)) {
            $skipped++;
            continue;
        }

        try {
            // Use insertOrIgnore to skip duplicates
            DB::table($table)->insertOrIgnore($filtered);
            $inserted++;
        } catch (\Exception $e) {
            // Try update on duplicate
            try {
                if (isset($filtered['id'])) {
                    $id = $filtered['id'];
                    unset($filtered['id']);
                    DB::table($table)->updateOrInsert(['id' => $id], $filtered);
                    $inserted++;
                } else {
                    $skipped++;
                    if (count($errors) < 20) {
                        $errors[] = "{$table}: " . substr($e->getMessage(), 0, 120);
                    }
                }
            } catch (\Exception $e2) {
                $skipped++;
                if (count($errors) < 20) {
                    $errors[] = "{$table}: " . substr($e2->getMessage(), 0, 120);
                }
            }
        }
    }

    $totalRows += $inserted;
    echo "  OK    {$jsonKey} → {$table} ({$inserted} inserted" . ($skipped > 0 ? ", {$skipped} skipped" : "") . ")\n";
}

// Re-enable FK checks
DB::statement('SET FOREIGN_KEY_CHECKS=1');

echo "\n========================================\n";
echo "  Seeding complete!\n";
echo "  Total rows inserted: {$totalRows}\n";
echo "========================================\n";

if (!empty($errors)) {
    echo "\nErrors (first " . count($errors) . "):\n";
    foreach ($errors as $err) {
        echo "  - {$err}\n";
    }
}
echo "\n";
