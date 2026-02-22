<?php
/**
 * Seeder for financial/commission/cashbox/survey/discount test data
 * for Salon 1043
 * 
 * Run: php seed_financial_test.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$salonId = 1043;
$ownerUserId = 1459;
$now = Carbon::now();

// Reference data from existing records
$staffIds = [1211, 1212, 1213, 1214, 1215];
$serviceIds = [5275, 5276, 5277, 5278, 5279, 5280, 5281, 5282, 5283, 5284, 5285, 5286, 5287, 5288, 5289, 5290, 5291, 5292, 5293, 5294, 5295];
$servicePrices = [
    5275 => 500000, 5276 => 1500000, 5277 => 2000000, 5278 => 1000000,
    5279 => 3000000, 5280 => 2500000, 5281 => 5000000, 5282 => 1500000,
    5283 => 400000, 5284 => 500000, 5285 => 600000, 5286 => 800000,
    5287 => 200000, 5288 => 800000, 5289 => 3500000, 5290 => 1200000,
    5291 => 1500000, 5292 => 800000, 5293 => 600000, 5294 => 1000000,
    5295 => 1111111,
];
$customerGroupIds = [3164, 3165, 3166, 4001];  // meaningful groups

// Get completed appointments with their services
$completedAppts = DB::select("
    SELECT a.id as appointment_id, a.customer_id, a.appointment_date, a.total_price,
           a.start_time
    FROM appointments a 
    WHERE a.salon_id = ? AND a.status = 'completed' AND a.total_price > 0
    ORDER BY a.appointment_date DESC
    LIMIT 100
", [$salonId]);

$apptServices = DB::select("
    SELECT aps.appointment_id, aps.service_id, aps.price_at_booking
    FROM appointment_service aps
    INNER JOIN appointments a ON aps.appointment_id = a.id
    WHERE a.salon_id = ? AND a.status = 'completed'
", [$salonId]);

// Group services by appointment
$apptSvcMap = [];
foreach ($apptServices as $as) {
    $apptSvcMap[$as->appointment_id][] = $as;
}

$totalInserts = 0;

DB::statement('SET FOREIGN_KEY_CHECKS=0');

echo "=== Seeding Financial Test Data for Salon {$salonId} ===\n\n";

// Check if financial test data already exists - skip if so
$existingCashboxes = DB::table('cashboxes')->where('salon_id', $salonId)->count();
$existingCommissions = DB::table('staff_service_commissions')->where('salon_id', $salonId)->count();
$existingCbTrans = DB::table('cashbox_transactions')->where('salon_id', $salonId)->count();

if ($existingCashboxes > 0 || $existingCommissions > 0 || $existingCbTrans > 0) {
    echo "Financial test data already exists for salon {$salonId}. Skipping...\n";
    echo "  cashboxes: {$existingCashboxes}\n";
    echo "  staff_service_commissions: {$existingCommissions}\n";
    echo "  cashbox_transactions: {$existingCbTrans}\n";
    echo "\nTo re-seed, first delete existing data manually.\n";
    exit(0);
}

// ─────────────────────────────────────────────────────────────
// 1. Transaction Categories & Subcategories
// ─────────────────────────────────────────────────────────────
echo "1. Transaction Categories...\n";
$categories = [
    ['salon_id' => $salonId, 'name' => 'دریافت خدمات', 'type' => 'income', 'description' => 'درآمد ارائه خدمات آرایشگاهی', 'is_system' => 1, 'is_active' => 1, 'sort_order' => 1],
    ['salon_id' => $salonId, 'name' => 'فروش محصولات', 'type' => 'income', 'description' => 'فروش لوازم آرایشی و بهداشتی', 'is_system' => 0, 'is_active' => 1, 'sort_order' => 2],
    ['salon_id' => $salonId, 'name' => 'اجاره', 'type' => 'expense', 'description' => 'هزینه اجاره مکان', 'is_system' => 0, 'is_active' => 1, 'sort_order' => 3],
    ['salon_id' => $salonId, 'name' => 'حقوق و دستمزد', 'type' => 'expense', 'description' => 'پرداخت حقوق پرسنل', 'is_system' => 0, 'is_active' => 1, 'sort_order' => 4],
    ['salon_id' => $salonId, 'name' => 'خرید مواد مصرفی', 'type' => 'expense', 'description' => 'خرید مواد و ابزار آرایشی', 'is_system' => 0, 'is_active' => 1, 'sort_order' => 5],
    ['salon_id' => $salonId, 'name' => 'قبض و هزینه‌های جاری', 'type' => 'expense', 'description' => 'آب، برق، گاز، تلفن', 'is_system' => 0, 'is_active' => 1, 'sort_order' => 6],
    ['salon_id' => $salonId, 'name' => 'تبلیغات', 'type' => 'expense', 'description' => 'هزینه تبلیغات و بازاریابی', 'is_system' => 0, 'is_active' => 1, 'sort_order' => 7],
    ['salon_id' => $salonId, 'name' => 'متفرقه', 'type' => 'both', 'description' => 'سایر موارد', 'is_system' => 0, 'is_active' => 1, 'sort_order' => 8],
];

foreach ($categories as &$cat) {
    $cat['created_at'] = $now;
    $cat['updated_at'] = $now;
}
unset($cat);

DB::table('transaction_categories')->insertOrIgnore($categories);
$insertedCats = DB::select("SELECT id, name, type FROM transaction_categories WHERE salon_id=?", [$salonId]);
$catMap = [];
foreach ($insertedCats as $c) $catMap[$c->name] = $c->id;
$totalInserts += count($insertedCats);
echo "  -> " . count($insertedCats) . " categories\n";

// Subcategories
$subcategories = [
    ['category_id' => $catMap['دریافت خدمات'], 'salon_id' => $salonId, 'name' => 'کوتاهی و مدل مو', 'service_id' => 5275],
    ['category_id' => $catMap['دریافت خدمات'], 'salon_id' => $salonId, 'name' => 'رنگ و هایلایت', 'service_id' => 5276],
    ['category_id' => $catMap['دریافت خدمات'], 'salon_id' => $salonId, 'name' => 'خدمات ناخن', 'service_id' => 5283],
    ['category_id' => $catMap['دریافت خدمات'], 'salon_id' => $salonId, 'name' => 'میکاپ', 'service_id' => 5281],
    ['category_id' => $catMap['فروش محصولات'], 'salon_id' => $salonId, 'name' => 'محصولات مراقبت مو'],
    ['category_id' => $catMap['فروش محصولات'], 'salon_id' => $salonId, 'name' => 'لوازم آرایشی'],
    ['category_id' => $catMap['خرید مواد مصرفی'], 'salon_id' => $salonId, 'name' => 'رنگ و اکسیدان'],
    ['category_id' => $catMap['خرید مواد مصرفی'], 'salon_id' => $salonId, 'name' => 'مواد کراتین و صافی'],
    ['category_id' => $catMap['خرید مواد مصرفی'], 'salon_id' => $salonId, 'name' => 'مواد ناخن'],
    ['category_id' => $catMap['قبض و هزینه‌های جاری'], 'salon_id' => $salonId, 'name' => 'برق'],
    ['category_id' => $catMap['قبض و هزینه‌های جاری'], 'salon_id' => $salonId, 'name' => 'آب و گاز'],
    ['category_id' => $catMap['قبض و هزینه‌های جاری'], 'salon_id' => $salonId, 'name' => 'اینترنت و تلفن'],
];
foreach ($subcategories as &$sub) {
    if (!isset($sub['service_id'])) $sub['service_id'] = null;
    $sub['is_active'] = 1;
    $sub['sort_order'] = 0;
    $sub['created_at'] = $now;
    $sub['updated_at'] = $now;
}
unset($sub);
DB::table('transaction_subcategories')->insertOrIgnore($subcategories);
$subCnt = DB::table('transaction_subcategories')->where('salon_id', $salonId)->count();
$totalInserts += $subCnt;
echo "  -> {$subCnt} subcategories\n";

// ─────────────────────────────────────────────────────────────
// 2. Staff Service Commissions (commission rates)
// ─────────────────────────────────────────────────────────────
echo "\n2. Staff Service Commissions...\n";
$commissions = [];
$commissionRates = [
    1212 => 30, // مریم حسینی - 30%
    1213 => 25, // فاطمه کریمی - 25%
    1214 => 35, // سارا رضایی - 35%
    1215 => 20, // امیرمولایی - 20%
];

foreach ($commissionRates as $staffId => $rate) {
    // Assign commissions for services each staff commonly does
    $staffServices = array_slice($serviceIds, 0, 15); // first 15 services
    foreach ($staffServices as $svcId) {
        $commissions[] = [
            'staff_id' => $staffId,
            'service_id' => $svcId,
            'salon_id' => $salonId,
            'commission_type' => 'percentage',
            'commission_value' => $rate,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
// One staff with fixed commission
foreach ([5281, 5282, 5289] as $svcId) { // makeup & microblading
    $commissions[] = [
        'staff_id' => 1211,
        'service_id' => $svcId,
        'salon_id' => $salonId,
        'commission_type' => 'fixed',
        'commission_value' => 500000, // 500k fixed per service
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ];
}

DB::table('staff_service_commissions')->insertOrIgnore($commissions);
$commCnt = DB::table('staff_service_commissions')->where('salon_id', $salonId)->count();
$totalInserts += $commCnt;
echo "  -> {$commCnt} commission rules\n";

// ─────────────────────────────────────────────────────────────
// 3. Staff Commission Transactions (actual earnings from appointments)
// ─────────────────────────────────────────────────────────────
echo "\n3. Staff Commission Transactions...\n";
$commTrans = [];
$months = [];

// For each completed appointment, generate commission transactions
$processedCount = 0;
foreach ($completedAppts as $appt) {
    if ($processedCount >= 60) break; // Generate ~60 commission records
    
    $services = $apptSvcMap[$appt->appointment_id] ?? [];
    foreach ($services as $svc) {
        // Pick a staff member for this service
        $staffId = $staffIds[array_rand(array_slice($staffIds, 1))]; // skip first staff (1211)
        $rate = $commissionRates[$staffId] ?? 25;
        $price = (float)$svc->price_at_booking;
        $discount = rand(0, 3) === 0 ? round($price * 0.1) : 0; // 25% chance of discount
        $base = $price - $discount;
        $commAmount = round($base * $rate / 100);
        
        $apptDate = Carbon::parse($appt->appointment_date);
        $isPaid = $apptDate->lt(Carbon::now()->subMonth());
        
        $commTrans[] = [
            'salon_id' => $salonId,
            'staff_id' => $staffId,
            'appointment_id' => $appt->appointment_id,
            'service_id' => $svc->service_id,
            'transaction_type' => 'commission',
            'service_price' => $price,
            'discount_amount' => $discount,
            'base_amount' => $base,
            'commission_rate' => $rate,
            'commission_type' => 'percentage',
            'amount' => $commAmount,
            'payment_status' => $isPaid ? 'paid' : 'pending',
            'paid_at' => $isPaid ? $apptDate->copy()->addDays(rand(5, 30))->toDateTimeString() : null,
            'for_month' => $apptDate->month,
            'for_year' => $apptDate->year,
            'description' => null,
            'created_by' => $ownerUserId,
            'created_at' => $apptDate->toDateTimeString(),
            'updated_at' => $apptDate->toDateTimeString(),
        ];
        
        $monthKey = $apptDate->format('Y-m');
        if (!isset($months[$monthKey])) $months[$monthKey] = [];
        $months[$monthKey][] = ['staff_id' => $staffId, 'amount' => $commAmount];
        
        $processedCount++;
        if ($processedCount >= 60) break;
    }
}

// Add some adjustment and payment transactions
foreach ($months as $monthKey => $entries) {
    $staffTotals = [];
    foreach ($entries as $e) {
        $staffTotals[$e['staff_id']] = ($staffTotals[$e['staff_id']] ?? 0) + $e['amount'];
    }
    $monthDate = Carbon::parse($monthKey . '-28');
    
    foreach ($staffTotals as $staffId => $total) {
        if ($monthDate->lt(Carbon::now()->subMonth())) {
            // Payment transaction for old months
            $commTrans[] = [
                'salon_id' => $salonId,
                'staff_id' => $staffId,
                'appointment_id' => null,
                'service_id' => null,
                'transaction_type' => 'payment',
                'service_price' => 0,
                'discount_amount' => 0,
                'base_amount' => 0,
                'commission_rate' => 0,
                'commission_type' => 'percentage',
                'amount' => -$total,
                'payment_status' => 'paid',
                'paid_at' => $monthDate->copy()->addDays(2)->toDateTimeString(),
                'for_month' => $monthDate->month,
                'for_year' => $monthDate->year,
                'description' => "پرداخت پورسانت " . $monthDate->format('Y/m'),
                'created_by' => $ownerUserId,
                'created_at' => $monthDate->toDateTimeString(),
                'updated_at' => $monthDate->toDateTimeString(),
            ];
        }
    }
}

foreach (array_chunk($commTrans, 50) as $chunk) {
    DB::table('staff_commission_transactions')->insertOrIgnore($chunk);
}
$commTransCnt = DB::table('staff_commission_transactions')->where('salon_id', $salonId)->count();
$totalInserts += $commTransCnt;
echo "  -> {$commTransCnt} commission transactions\n";

// ─────────────────────────────────────────────────────────────
// 4. Cashboxes
// ─────────────────────────────────────────────────────────────
echo "\n4. Cashboxes...\n";
$cashboxes = [
    [
        'salon_id' => $salonId,
        'name' => 'صندوق نقدی',
        'type' => 'cash',
        'initial_balance' => 5000000,
        'current_balance' => 28450000,
        'description' => 'صندوق نقدی اصلی سالن',
        'is_active' => 1,
        'sort_order' => 1,
        'created_at' => Carbon::parse('2025-01-01'),
        'updated_at' => $now,
    ],
    [
        'salon_id' => $salonId,
        'name' => 'حساب بانکی ملی',
        'type' => 'bank',
        'initial_balance' => 0,
        'current_balance' => 45780000,
        'description' => 'حساب بانک ملی - کارتخوان',
        'is_active' => 1,
        'sort_order' => 2,
        'created_at' => Carbon::parse('2025-01-01'),
        'updated_at' => $now,
    ],
    [
        'salon_id' => $salonId,
        'name' => 'حساب پس‌انداز',
        'type' => 'bank',
        'initial_balance' => 10000000,
        'current_balance' => 65000000,
        'description' => 'حساب پس‌انداز سالن',
        'is_active' => 1,
        'sort_order' => 3,
        'created_at' => Carbon::parse('2025-01-01'),
        'updated_at' => $now,
    ],
];

DB::table('cashboxes')->insertOrIgnore($cashboxes);
$cbIds = DB::table('cashboxes')->where('salon_id', $salonId)->pluck('id')->toArray();
$totalInserts += count($cbIds);
echo "  -> " . count($cbIds) . " cashboxes (IDs: " . implode(', ', $cbIds) . ")\n";

// ─────────────────────────────────────────────────────────────
// 5. Cashbox Transactions
// ─────────────────────────────────────────────────────────────
echo "\n5. Cashbox Transactions...\n";

// Get subcategory IDs for reference
$subCats = DB::select("SELECT id, name, category_id FROM transaction_subcategories WHERE salon_id=?", [$salonId]);
$subCatMap = [];
foreach ($subCats as $sc) $subCatMap[$sc->name] = $sc;

// Get category info
$catInfo = [];
foreach ($insertedCats as $c) $catInfo[$c->id] = $c;

$cashboxTrans = [];
$cashCbId = $cbIds[0] ?? null;
$bankCbId = $cbIds[1] ?? null;
$savingsCbId = $cbIds[2] ?? null;

// Generate 3 months of transactions
$startDate = Carbon::now()->subMonths(3)->startOfMonth();

for ($day = 0; $day < 90; $day++) {
    $date = $startDate->copy()->addDays($day);
    if ($date->gt(Carbon::now())) break;
    
    // Skip some days randomly (weekends, etc)
    if (rand(0, 10) < 3) continue;
    
    // 2-5 income transactions per day (from appointments)
    $incomeCount = rand(2, 5);
    for ($i = 0; $i < $incomeCount; $i++) {
        $svcId = $serviceIds[array_rand($serviceIds)];
        $amount = $servicePrices[$svcId] * (rand(80, 100) / 100); // sometimes with discount
        $method = rand(0, 2) === 0 ? $bankCbId : $cashCbId; // 33% card, 67% cash
        
        $cashboxTrans[] = [
            'salon_id' => $salonId,
            'type' => 'income',
            'cashbox_id' => $method,
            'from_cashbox_id' => null,
            'to_cashbox_id' => null,
            'amount' => round($amount),
            'description' => 'دریافت بابت خدمات',
            'category_id' => $catMap['دریافت خدمات'],
            'subcategory_id' => null,
            'category' => 'دریافت خدمات',
            'subcategory' => null,
            'payment_id' => null,
            'expense_id' => null,
            'commission_transaction_id' => null,
            'transaction_date' => $date->toDateString(),
            'transaction_time' => sprintf('%02d:%02d', rand(9, 19), rand(0, 59)),
            'created_by' => $ownerUserId,
            'created_at' => $date->toDateTimeString(),
            'updated_at' => $date->toDateTimeString(),
        ];
    }
    
    // 0-2 expense transactions per day
    $expenseTypes = [
        ['cat' => 'خرید مواد مصرفی', 'desc' => 'خرید رنگ و مواد', 'min' => 500000, 'max' => 5000000],
        ['cat' => 'قبض و هزینه‌های جاری', 'desc' => 'پرداخت قبض', 'min' => 200000, 'max' => 2000000],
        ['cat' => 'تبلیغات', 'desc' => 'هزینه تبلیغات اینستاگرام', 'min' => 300000, 'max' => 3000000],
        ['cat' => 'متفرقه', 'desc' => 'هزینه‌های متفرقه', 'min' => 100000, 'max' => 1000000],
    ];
    
    if (rand(0, 2) === 0) {
        $exp = $expenseTypes[array_rand($expenseTypes)];
        $cashboxTrans[] = [
            'salon_id' => $salonId,
            'type' => 'expense',
            'cashbox_id' => rand(0, 1) ? $cashCbId : $bankCbId,
            'from_cashbox_id' => null,
            'to_cashbox_id' => null,
            'amount' => rand($exp['min'], $exp['max']),
            'description' => $exp['desc'],
            'category_id' => $catMap[$exp['cat']] ?? null,
            'subcategory_id' => null,
            'category' => $exp['cat'],
            'subcategory' => null,
            'payment_id' => null,
            'expense_id' => null,
            'commission_transaction_id' => null,
            'transaction_date' => $date->toDateString(),
            'transaction_time' => sprintf('%02d:%02d', rand(10, 18), rand(0, 59)),
            'created_by' => $ownerUserId,
            'created_at' => $date->toDateTimeString(),
            'updated_at' => $date->toDateTimeString(),
        ];
    }
    
    // Salary payments (1st of month)
    if ($date->day === 1) {
        foreach ([1212, 1213, 1214] as $staffId) {
            $cashboxTrans[] = [
                'salon_id' => $salonId,
                'type' => 'expense',
                'cashbox_id' => $bankCbId,
                'from_cashbox_id' => null,
                'to_cashbox_id' => null,
                'amount' => rand(8000000, 15000000),
                'description' => 'پرداخت حقوق پرسنل',
                'category_id' => $catMap['حقوق و دستمزد'],
                'subcategory_id' => null,
                'category' => 'حقوق و دستمزد',
                'subcategory' => null,
                'payment_id' => null,
                'expense_id' => null,
                'commission_transaction_id' => null,
                'transaction_date' => $date->toDateString(),
                'transaction_time' => '10:00',
                'created_by' => $ownerUserId,
                'created_at' => $date->toDateTimeString(),
                'updated_at' => $date->toDateTimeString(),
            ];
        }
    }
    
    // Rent payment (5th of month)
    if ($date->day === 5) {
        $cashboxTrans[] = [
            'salon_id' => $salonId,
            'type' => 'expense',
            'cashbox_id' => $bankCbId,
            'from_cashbox_id' => null,
            'to_cashbox_id' => null,
            'amount' => 25000000,
            'description' => 'پرداخت اجاره ماهانه',
            'category_id' => $catMap['اجاره'],
            'subcategory_id' => null,
            'category' => 'اجاره',
            'subcategory' => null,
            'payment_id' => null,
            'expense_id' => null,
            'commission_transaction_id' => null,
            'transaction_date' => $date->toDateString(),
            'transaction_time' => '09:00',
            'created_by' => $ownerUserId,
            'created_at' => $date->toDateTimeString(),
            'updated_at' => $date->toDateTimeString(),
        ];
    }
    
    // Transfer between cashboxes (every 15 days)
    if ($date->day === 15) {
        $transferAmount = rand(5000000, 20000000);
        $cashboxTrans[] = [
            'salon_id' => $salonId,
            'type' => 'transfer',
            'cashbox_id' => null,
            'from_cashbox_id' => $cashCbId,
            'to_cashbox_id' => $savingsCbId,
            'amount' => $transferAmount,
            'description' => 'انتقال به حساب پس‌انداز',
            'category_id' => null,
            'subcategory_id' => null,
            'category' => null,
            'subcategory' => null,
            'payment_id' => null,
            'expense_id' => null,
            'commission_transaction_id' => null,
            'transaction_date' => $date->toDateString(),
            'transaction_time' => '11:00',
            'created_by' => $ownerUserId,
            'created_at' => $date->toDateTimeString(),
            'updated_at' => $date->toDateTimeString(),
        ];
    }
}

foreach (array_chunk($cashboxTrans, 100) as $chunk) {
    DB::table('cashbox_transactions')->insert($chunk);
}
$cbTransCnt = DB::table('cashbox_transactions')->where('salon_id', $salonId)->count();
$totalInserts += $cbTransCnt;
echo "  -> {$cbTransCnt} cashbox transactions\n";

// ─────────────────────────────────────────────────────────────
// 6. Satisfaction Survey Settings
// ─────────────────────────────────────────────────────────────
echo "\n6. Satisfaction Survey Settings...\n";

// First create a template if needed
// Create a satisfaction survey template
$templateId = DB::table('templates')
    ->where('type', 'satisfaction_survey')
    ->where('is_global', 1)
    ->value('id');

if (!$templateId) {
    $templateId = DB::table('templates')->insertGetId([
        'name' => 'قالب نظرسنجی پیش‌فرض',
        'content' => '{customer_name} عزیز، از خدمات سالن ما راضی بودید؟ لطفاً با ارسال عدد ۱ تا ۵ نظر خود را اعلام کنید.',
        'type' => 'satisfaction_survey',
        'salon_id' => null,
        'is_global' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    echo "  -> Created survey template id={$templateId}\n";
} else {
    echo "  -> Using existing survey template id={$templateId}\n";
}

// Try inserting survey settings  
try {
    DB::table('satisfaction_survey_settings')->insertOrIgnore([
        'salon_id' => $salonId,
        'template_id' => $templateId,
        'is_global_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $ssId = DB::table('satisfaction_survey_settings')->where('salon_id', $salonId)->value('id');
    $totalInserts++;
    echo "  -> Survey setting id={$ssId}\n";

    // Group settings for survey
    $surveyGroupSettings = [];
    foreach ($customerGroupIds as $groupId) {
        $surveyGroupSettings[] = [
            'satisfaction_survey_setting_id' => $ssId,
            'customer_group_id' => $groupId,
            'is_active' => 1,
            'send_hours_after' => rand(1, 6) * 2, // 2, 4, 6, 8, 10, or 12 hours
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    DB::table('satisfaction_survey_group_settings')->insertOrIgnore($surveyGroupSettings);
    $sgsCnt = DB::table('satisfaction_survey_group_settings')
        ->where('satisfaction_survey_setting_id', $ssId)->count();
    $totalInserts += $sgsCnt;
    echo "  -> {$sgsCnt} group settings\n";
} catch (\Exception $e) {
    echo "  -> Warning: " . $e->getMessage() . "\n";
}

// ─────────────────────────────────────────────────────────────
// 7. Satisfaction Survey Logs
// ─────────────────────────────────────────────────────────────
echo "\n7. Satisfaction Survey Logs...\n";
$surveyLogs = [];

// Generate logs for recent completed appointments
$recentCompleted = array_slice($completedAppts, 0, 40);
foreach ($recentCompleted as $appt) {
    $scheduledAt = Carbon::parse($appt->appointment_date . ' ' . ($appt->start_time ?? '10:00'))
        ->addHours(rand(2, 8));
    $sent = rand(0, 10) > 1; // 90% success rate
    $failed = !$sent && rand(0, 1);
    
    $surveyLogs[] = [
        'appointment_id' => $appt->appointment_id,
        'customer_id' => $appt->customer_id,
        'salon_id' => $salonId,
        'scheduled_at' => $scheduledAt->toDateTimeString(),
        'sent_at' => $sent ? $scheduledAt->copy()->addMinutes(rand(1, 30))->toDateTimeString() : null,
        'status' => $sent ? 'sent' : ($failed ? 'failed' : 'pending'),
        'error_message' => $failed ? 'ارسال پیامک ناموفق بود' : null,
        'message_id' => $sent ? 'MSG-' . rand(100000, 999999) : null,
        'created_at' => $scheduledAt->toDateTimeString(),
        'updated_at' => $scheduledAt->toDateTimeString(),
    ];
}

DB::table('satisfaction_survey_logs')->insertOrIgnore($surveyLogs);
$slCnt = DB::table('satisfaction_survey_logs')->where('salon_id', $salonId)->count();
$totalInserts += $slCnt;
echo "  -> {$slCnt} survey logs\n";

// ─────────────────────────────────────────────────────────────
// 8. Discount Codes
// ─────────────────────────────────────────────────────────────
echo "\n8. Discount Codes...\n";

$discountCodes = [
    [
        'code' => 'WELCOME10',
        'type' => 'percentage',
        'value' => 10,
        'percentage' => 10,
        'expires_at' => Carbon::now()->addMonths(3)->toDateTimeString(),
        'is_active' => 1,
        'target_users' => null,
        'user_filter_type' => 'all',
        'description' => 'کد تخفیف خوش‌آمدگویی ۱۰ درصدی',
        'min_order_amount' => 500000,
        'max_discount_amount' => 2000000,
        'starts_at' => Carbon::now()->subMonth()->toDateTimeString(),
        'usage_limit' => 100,
        'used_count' => 12,
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'code' => 'VIP20',
        'type' => 'percentage',
        'value' => 20,
        'percentage' => 20,
        'expires_at' => Carbon::now()->addMonths(1)->toDateTimeString(),
        'is_active' => 1,
        'target_users' => json_encode(['group_ids' => [3166]]), // VIP group
        'user_filter_type' => 'filtered',
        'description' => 'تخفیف ۲۰ درصد ویژه مشتریان VIP',
        'min_order_amount' => 1000000,
        'max_discount_amount' => 5000000,
        'starts_at' => Carbon::now()->subWeeks(2)->toDateTimeString(),
        'usage_limit' => 50,
        'used_count' => 5,
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'code' => 'SUMMER500',
        'type' => 'fixed',
        'value' => 500000,
        'percentage' => null,
        'expires_at' => Carbon::now()->subMonth()->toDateTimeString(), // expired
        'is_active' => 0,
        'target_users' => null,
        'user_filter_type' => 'all',
        'description' => 'تخفیف ۵۰۰ هزار تومانی تابستانه',
        'min_order_amount' => 2000000,
        'max_discount_amount' => 500000,
        'starts_at' => Carbon::now()->subMonths(4)->toDateTimeString(),
        'usage_limit' => 200,
        'used_count' => 87,
        'created_at' => Carbon::now()->subMonths(4),
        'updated_at' => Carbon::now()->subMonth(),
    ],
    [
        'code' => 'BRIDE15',
        'type' => 'percentage',
        'value' => 15,
        'percentage' => 15,
        'expires_at' => Carbon::now()->addMonths(6)->toDateTimeString(),
        'is_active' => 1,
        'target_users' => null,
        'user_filter_type' => 'all',
        'description' => 'تخفیف ویژه پکیج عروس',
        'min_order_amount' => 5000000,
        'max_discount_amount' => 3000000,
        'starts_at' => Carbon::now()->subMonth()->toDateTimeString(),
        'usage_limit' => 30,
        'used_count' => 3,
        'created_at' => $now,
        'updated_at' => $now,
    ],
    [
        'code' => 'NEWYEAR25',
        'type' => 'percentage',
        'value' => 25,
        'percentage' => 25,
        'expires_at' => Carbon::parse('2026-04-01')->toDateTimeString(),
        'is_active' => 1,
        'target_users' => null,
        'user_filter_type' => 'all',
        'description' => 'تخفیف نوروزی ۲۵ درصد',
        'min_order_amount' => 300000,
        'max_discount_amount' => 4000000,
        'starts_at' => Carbon::parse('2026-03-01')->toDateTimeString(),
        'usage_limit' => 500,
        'used_count' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ],
];

DB::table('discount_codes')->insertOrIgnore($discountCodes);
$dcIds = DB::table('discount_codes')->whereIn('code', ['WELCOME10', 'VIP20', 'SUMMER500', 'BRIDE15', 'NEWYEAR25'])->pluck('id', 'code')->toArray();
$totalInserts += count($dcIds);
echo "  -> " . count($dcIds) . " discount codes\n";

// Discount code salon usages
$dcUsages = [];
$sampleOrders = DB::select("SELECT id FROM orders WHERE salon_id=? ORDER BY id DESC LIMIT 20", [$salonId]);
$usageCount = 0;

foreach ($dcIds as $code => $dcId) {
    $usedCount = match($code) {
        'WELCOME10' => 12,
        'VIP20' => 5,
        'SUMMER500' => 15, // subset of 87
        'BRIDE15' => 3,
        default => 0,
    };
    
    for ($i = 0; $i < min($usedCount, count($sampleOrders)); $i++) {
        $dcUsages[] = [
            'discount_code_id' => $dcId,
            'salon_id' => $salonId,
            'order_id' => $sampleOrders[$usageCount % count($sampleOrders)]->id ?? null,
            'used_at' => Carbon::now()->subDays(rand(1, 60))->toDateTimeString(),
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $usageCount++;
    }
}

if (!empty($dcUsages)) {
    DB::table('discount_code_salon_usages')->insertOrIgnore($dcUsages);
    $dcuCnt = DB::table('discount_code_salon_usages')->where('salon_id', $salonId)->count();
    $totalInserts += $dcuCnt;
    echo "  -> {$dcuCnt} discount code usages\n";
}

// ─────────────────────────────────────────────────────────────
// 9. Wallet Transactions
// ─────────────────────────────────────────────────────────────
echo "\n9. Wallet Transactions...\n";
$walletTrans = [
    [
        'user_id' => $ownerUserId,
        'type' => 'deposit',
        'amount' => 50000000,
        'balance_before' => 0,
        'balance_after' => 50000000,
        'status' => 'completed',
        'description' => 'شارژ کیف پول اولیه',
        'transaction_id' => 'TXN-' . rand(100000, 999999),
        'referral_id' => null,
        'order_id' => null,
        'admin_id' => null,
        'metadata' => json_encode(['source' => 'manual', 'payment_method' => 'bank_transfer']),
        'created_at' => Carbon::now()->subMonths(3),
        'updated_at' => Carbon::now()->subMonths(3),
    ],
    [
        'user_id' => $ownerUserId,
        'type' => 'purchase',
        'amount' => -2990000,
        'balance_before' => 50000000,
        'balance_after' => 47010000,
        'status' => 'completed',
        'description' => 'خرید پکیج پیامکی ۱۰۰۰ تایی',
        'transaction_id' => 'TXN-' . rand(100000, 999999),
        'referral_id' => null,
        'order_id' => $sampleOrders[0]->id ?? null,
        'admin_id' => null,
        'metadata' => json_encode(['package' => 'sms_1000', 'sms_count' => 1000]),
        'created_at' => Carbon::now()->subMonths(2)->subDays(15),
        'updated_at' => Carbon::now()->subMonths(2)->subDays(15),
    ],
    [
        'user_id' => $ownerUserId,
        'type' => 'purchase',
        'amount' => -4990000,
        'balance_before' => 47010000,
        'balance_after' => 42020000,
        'status' => 'completed',
        'description' => 'تمدید اشتراک ۳ ماهه',
        'transaction_id' => 'TXN-' . rand(100000, 999999),
        'referral_id' => null,
        'order_id' => $sampleOrders[1]->id ?? null,
        'admin_id' => null,
        'metadata' => json_encode(['package' => 'subscription_3m']),
        'created_at' => Carbon::now()->subMonths(2),
        'updated_at' => Carbon::now()->subMonths(2),
    ],
    [
        'user_id' => $ownerUserId,
        'type' => 'deposit',
        'amount' => 30000000,
        'balance_before' => 42020000,
        'balance_after' => 72020000,
        'status' => 'completed',
        'description' => 'شارژ کیف پول از درگاه بانکی',
        'transaction_id' => 'TXN-' . rand(100000, 999999),
        'referral_id' => null,
        'order_id' => null,
        'admin_id' => null,
        'metadata' => json_encode(['source' => 'online_payment', 'gateway' => 'zarinpal']),
        'created_at' => Carbon::now()->subMonth(),
        'updated_at' => Carbon::now()->subMonth(),
    ],
    [
        'user_id' => $ownerUserId,
        'type' => 'purchase',
        'amount' => -1490000,
        'balance_before' => 72020000,
        'balance_after' => 70530000,
        'status' => 'completed',
        'description' => 'خرید پکیج پیامکی ۵۰۰ تایی',
        'transaction_id' => 'TXN-' . rand(100000, 999999),
        'referral_id' => null,
        'order_id' => $sampleOrders[2]->id ?? null,
        'admin_id' => null,
        'metadata' => json_encode(['package' => 'sms_500', 'sms_count' => 500]),
        'created_at' => Carbon::now()->subWeeks(2),
        'updated_at' => Carbon::now()->subWeeks(2),
    ],
    [
        'user_id' => $ownerUserId,
        'type' => 'refund',
        'amount' => 1490000,
        'balance_before' => 70530000,
        'balance_after' => 72020000,
        'status' => 'completed',
        'description' => 'بازگشت وجه سفارش لغو شده',
        'transaction_id' => 'TXN-' . rand(100000, 999999),
        'referral_id' => null,
        'order_id' => $sampleOrders[2]->id ?? null,
        'admin_id' => null,
        'metadata' => json_encode(['reason' => 'order_cancelled']),
        'created_at' => Carbon::now()->subWeeks(1),
        'updated_at' => Carbon::now()->subWeeks(1),
    ],
    [
        'user_id' => $ownerUserId,
        'type' => 'purchase',
        'amount' => -9900000,
        'balance_before' => 72020000,
        'balance_after' => 62120000,
        'status' => 'completed',
        'description' => 'خرید پکیج طلایی ۶ ماهه',
        'transaction_id' => 'TXN-' . rand(100000, 999999),
        'referral_id' => null,
        'order_id' => $sampleOrders[3]->id ?? null,
        'admin_id' => null,
        'metadata' => json_encode(['package' => 'gold_6m']),
        'created_at' => Carbon::now()->subDays(3),
        'updated_at' => Carbon::now()->subDays(3),
    ],
    [
        'user_id' => $ownerUserId,
        'type' => 'bonus',
        'amount' => 5000000,
        'balance_before' => 62120000,
        'balance_after' => 67120000,
        'status' => 'completed',
        'description' => 'جایزه معرفی دوست',
        'transaction_id' => 'TXN-' . rand(100000, 999999),
        'referral_id' => null,
        'order_id' => null,
        'admin_id' => null,
        'metadata' => json_encode(['source' => 'referral_bonus', 'referred_user' => 'salon_xyz']),
        'created_at' => Carbon::now()->subDays(1),
        'updated_at' => Carbon::now()->subDays(1),
    ],
];

DB::table('wallet_transactions')->insertOrIgnore($walletTrans);
$wtCnt = DB::table('wallet_transactions')->where('user_id', $ownerUserId)->count();
$totalInserts += $wtCnt;
echo "  -> {$wtCnt} wallet transactions\n";

DB::statement('SET FOREIGN_KEY_CHECKS=1');

// ─────────────────────────────────────────────────────────────
// Summary
// ─────────────────────────────────────────────────────────────
echo "\n" . str_repeat("=", 50) . "\n";
echo "TOTAL: {$totalInserts} rows inserted\n";
echo str_repeat("=", 50) . "\n";

echo "\nVerification:\n";
$verify = [
    'transaction_categories' => "SELECT COUNT(*) as c FROM transaction_categories WHERE salon_id={$salonId}",
    'transaction_subcategories' => "SELECT COUNT(*) as c FROM transaction_subcategories WHERE salon_id={$salonId}",
    'staff_service_commissions' => "SELECT COUNT(*) as c FROM staff_service_commissions WHERE salon_id={$salonId}",
    'staff_commission_transactions' => "SELECT COUNT(*) as c FROM staff_commission_transactions WHERE salon_id={$salonId}",
    'cashboxes' => "SELECT COUNT(*) as c FROM cashboxes WHERE salon_id={$salonId}",
    'cashbox_transactions' => "SELECT COUNT(*) as c FROM cashbox_transactions WHERE salon_id={$salonId}",
    'satisfaction_survey_settings' => "SELECT COUNT(*) as c FROM satisfaction_survey_settings WHERE salon_id={$salonId}",
    'satisfaction_survey_group_settings' => "SELECT COUNT(*) as c FROM satisfaction_survey_group_settings",
    'satisfaction_survey_logs' => "SELECT COUNT(*) as c FROM satisfaction_survey_logs WHERE salon_id={$salonId}",
    'discount_codes' => "SELECT COUNT(*) as c FROM discount_codes WHERE code IN ('WELCOME10','VIP20','SUMMER500','BRIDE15','NEWYEAR25')",
    'discount_code_salon_usages' => "SELECT COUNT(*) as c FROM discount_code_salon_usages WHERE salon_id={$salonId}",
    'wallet_transactions' => "SELECT COUNT(*) as c FROM wallet_transactions WHERE user_id={$ownerUserId}",
];

foreach ($verify as $table => $sql) {
    $cnt = DB::select($sql)[0]->c;
    echo "  " . str_pad($table, 40) . $cnt . "\n";
}
