<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Salon;
use App\Models\User;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\SharedReport;
use App\Models\CustomerFeedback;
use Carbon\Carbon;

class Salon1043ReportDataSeeder extends Seeder
{
    private $salonId = 1043;
    private $salon;
    private $staff = [];
    private $customers = [];
    private $services = [];
    private $completedAppointments = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if salon exists, if not create it
        $this->salon = Salon::find($this->salonId);
        
        if (!$this->salon) {
            $this->createSalon();
        }

        // Clean up existing data for this salon before seeding
        $this->cleanupExistingData();

        $this->createStaff();
        $this->createServices();
        $this->createCustomers();
        $this->createAppointmentsWithPayments();
        $this->createCustomerFeedbacks();
        $this->createExpenses();
        $this->createSharedReports();

        $this->command->info('✅ سیدر سالن 1043 با موفقیت اجرا شد!');
        $this->command->info("📊 تعداد پرسنل: " . count($this->staff));
        $this->command->info("👥 تعداد مشتریان: " . count($this->customers));
        $this->command->info("💇 تعداد سرویس‌ها: " . count($this->services));
    }

    private function cleanupExistingData(): void
    {
        $this->command->info("🗑️  پاک کردن داده‌های قبلی سالن 1043...");
        
        // Temporarily disable foreign key checks for cleanup
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Delete in order to respect dependencies
        // Use forceDelete for soft-deletable models
        CustomerFeedback::whereHas('appointment', function($q) {
            $q->where('salon_id', $this->salonId);
        })->forceDelete();
        
        Payment::where('salon_id', $this->salonId)->forceDelete();
        
        // Delete appointment_service pivot records
        DB::table('appointment_service')
            ->whereIn('appointment_id', function($query) {
                $query->select('id')
                    ->from('appointments')
                    ->where('salon_id', $this->salonId);
            })->delete();
        
        Appointment::where('salon_id', $this->salonId)->forceDelete();
        Expense::where('salon_id', $this->salonId)->forceDelete();
        SharedReport::where('salon_id', $this->salonId)->forceDelete();
        Customer::where('salon_id', $this->salonId)->forceDelete();
        Service::where('salon_id', $this->salonId)->forceDelete();
        Staff::where('salon_id', $this->salonId)->forceDelete();
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info("✅ داده‌های قبلی پاک شد");
    }

    private function createSalon(): void
    {
        // Find or create owner user
        $owner = User::firstOrCreate(
            ['email' => 'salon1043@test.com'],
            [
                'name' => 'مدیر سالن 1043',
                'password' => bcrypt('password'),
                'phone_number' => '09121234043',
                'role' => 'salon_owner',
            ]
        );

        $this->salon = Salon::create([
            'id' => $this->salonId,
            'owner_id' => $owner->id,
            'name' => 'سالن زیبایی شماره 1043',
            'business_category_id' => 1,
            'address' => 'تهران، خیابان ولیعصر، پلاک 1043',
            'phone_number' => '02144441043',
            'postal_code' => '1234567890',
            'province_id' => 1,
            'city_id' => 1,
            'is_active' => true,
            'latitude' => 35.7219,
            'longitude' => 51.4044,
            'working_hours' => json_encode([
                'saturday' => ['09:00-21:00'],
                'sunday' => ['09:00-21:00'],
                'monday' => ['09:00-21:00'],
                'tuesday' => ['09:00-21:00'],
                'wednesday' => ['09:00-21:00'],
                'thursday' => ['09:00-21:00'],
                'friday' => ['14:00-20:00'],
            ]),
        ]);

        $owner->update(['active_salon_id' => $this->salonId]);

        $this->command->info("✅ سالن 1043 ایجاد شد");
    }

    private function createStaff(): void
    {
        $staffData = [
            [
                'full_name' => 'زهرا احمدی',
                'specialty' => 'آرایشگر',
                'phone_number' => '09121111043',
                'commission_type' => 'percentage',
                'commission_value' => 30.00,
                'hire_date' => Carbon::now()->subYears(2),
                'is_active' => true,
            ],
            [
                'full_name' => 'مریم حسینی',
                'specialty' => 'ناخن کار',
                'phone_number' => '09122222043',
                'commission_type' => 'percentage',
                'commission_value' => 25.00,
                'hire_date' => Carbon::now()->subYear(),
                'is_active' => true,
            ],
            [
                'full_name' => 'فاطمه کریمی',
                'specialty' => 'آرایشگر و میکاپ',
                'phone_number' => '09123333043',
                'commission_type' => 'fixed',
                'commission_value' => 500000.00,
                'hire_date' => Carbon::now()->subMonths(6),
                'is_active' => true,
            ],
            [
                'full_name' => 'سارا رضایی',
                'specialty' => 'ابرو کار',
                'phone_number' => '09124444043',
                'commission_type' => 'percentage',
                'commission_value' => 20.00,
                'hire_date' => Carbon::now()->subMonths(3),
                'is_active' => true,
            ],
        ];

        foreach ($staffData as $data) {
            $this->staff[] = Staff::create(array_merge(['salon_id' => $this->salonId], $data));
        }

        $this->command->info("✅ تعداد " . count($this->staff) . " نفر پرسنل ایجاد شد");
    }

    private function createServices(): void
    {
        $servicesData = [
            ['name' => 'کوتاهی مو', 'price' => 500000, 'duration_minutes' => 45],
            ['name' => 'رنگ مو', 'price' => 1500000, 'duration_minutes' => 120],
            ['name' => 'هایلایت', 'price' => 2000000, 'duration_minutes' => 150],
            ['name' => 'فر مو', 'price' => 1000000, 'duration_minutes' => 90],
            ['name' => 'کراتینه', 'price' => 3000000, 'duration_minutes' => 180],
            ['name' => 'اکستنشن', 'price' => 2500000, 'duration_minutes' => 240],
            ['name' => 'میکاپ عروس', 'price' => 5000000, 'duration_minutes' => 180],
            ['name' => 'میکاپ مهمانی', 'price' => 1500000, 'duration_minutes' => 90],
            ['name' => 'مانیکور', 'price' => 400000, 'duration_minutes' => 60],
            ['name' => 'پدیکور', 'price' => 500000, 'duration_minutes' => 60],
            ['name' => 'ژلیش', 'price' => 600000, 'duration_minutes' => 75],
            ['name' => 'کاشت ناخن', 'price' => 800000, 'duration_minutes' => 90],
            ['name' => 'اصلاح ابرو', 'price' => 200000, 'duration_minutes' => 30],
            ['name' => 'لیفت ابرو', 'price' => 800000, 'duration_minutes' => 45],
            ['name' => 'میکروبلیدینگ', 'price' => 3500000, 'duration_minutes' => 120],
            ['name' => 'لمینت مژه', 'price' => 1200000, 'duration_minutes' => 60],
            ['name' => 'اکستنشن مژه', 'price' => 1500000, 'duration_minutes' => 90],
            ['name' => 'فیشیال پوست', 'price' => 800000, 'duration_minutes' => 60],
            ['name' => 'پاکسازی پوست', 'price' => 600000, 'duration_minutes' => 45],
            ['name' => 'لیزر موهای زائد', 'price' => 1000000, 'duration_minutes' => 30],
        ];

        foreach ($servicesData as $data) {
            $this->services[] = Service::create(array_merge([
                'salon_id' => $this->salonId,
                'is_active' => true,
                'is_online_bookable' => true,
            ], $data));
        }

        $this->command->info("✅ تعداد " . count($this->services) . " سرویس ایجاد شد");
    }

    private function createCustomers(): void
    {
        $firstNames = ['سارا', 'مریم', 'فاطمه', 'زهرا', 'ریحانه', 'الهه', 'نیلوفر', 'پریسا', 'مهسا', 'یاسمن', 'شقایق', 'نرگس', 'ندا', 'نازنین', 'سمیرا', 'مینا', 'آتنا', 'پانیذ', 'سمانه', 'دریا'];
        $lastNames = ['احمدی', 'محمدی', 'حسینی', 'رضایی', 'کریمی', 'عباسی', 'نوری', 'صفری', 'موسوی', 'اکبری', 'نجفی', 'قاسمی', 'یوسفی', 'مرادی', 'شریفی', 'جعفری', 'میرزایی', 'ملکی', 'سلطانی', 'کاظمی'];
        
        for ($i = 0; $i < 50; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            
            $this->customers[] = Customer::create([
                'salon_id' => $this->salonId,
                'name' => $firstName . ' ' . $lastName,
                'phone_number' => '0912' . str_pad((1043000 + $i + 1), 7, '0', STR_PAD_LEFT),
                'gender' => 'female',
                'birth_date' => Carbon::now()->subYears(rand(20, 50))->subDays(rand(0, 365)),
                'address' => 'تهران، منطقه ' . rand(1, 22),
                'notes' => rand(0, 1) ? 'مشتری وفادار' : null,
            ]);
        }

        $this->command->info("✅ تعداد " . count($this->customers) . " مشتری ایجاد شد");
    }

    private function createAppointmentsWithPayments(): void
    {
        $statuses = ['completed', 'completed', 'completed', 'completed', 'completed', 'canceled', 'no_show'];
        $paymentMethods = ['cash', 'card', 'online', 'wallet'];
        $sources = ['web_portal', 'mobile_app', 'phone_call', 'walk_in'];
        
        $appointmentCount = 0;
        $paymentCount = 0;

        // Create appointments for the last 6 months
        for ($monthOffset = 0; $monthOffset < 6; $monthOffset++) {
            $appointmentsThisMonth = rand(80, 150); // Random appointments per month
            
            for ($i = 0; $i < $appointmentsThisMonth; $i++) {
                $customer = $this->customers[array_rand($this->customers)];
                $staff = $this->staff[array_rand($this->staff)];
                $status = $statuses[array_rand($statuses)];
                
                // Random date within the month
                $appointmentDate = Carbon::now()
                    ->subMonths($monthOffset)
                    ->setDay(rand(1, 28))
                    ->setHour(rand(9, 19))
                    ->setMinute([0, 15, 30, 45][rand(0, 3)]);
                
                // Select 1-3 random services
                $selectedServices = [];
                $serviceCount = rand(1, 3);
                $totalPrice = 0;
                $totalDuration = 0;
                
                for ($j = 0; $j < $serviceCount; $j++) {
                    $service = $this->services[array_rand($this->services)];
                    if (!in_array($service->id, array_column($selectedServices, 'id'))) {
                        $selectedServices[] = $service;
                        $totalPrice += $service->price;
                        $totalDuration += $service->duration_minutes;
                    }
                }
                
                // Apply random discount
                $discount = rand(0, 20); // 0-20% discount
                if ($discount > 0) {
                    $totalPrice = $totalPrice * (1 - $discount / 100);
                }
                
                $startTime = $appointmentDate->format('H:i');
                $endTime = $appointmentDate->copy()->addMinutes($totalDuration)->format('H:i');
                
                $appointment = Appointment::create([
                    'salon_id' => $this->salonId,
                    'customer_id' => $customer->id,
                    'staff_id' => $staff->id,
                    'appointment_date' => $appointmentDate->toDateString(),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'total_price' => $totalPrice,
                    'total_duration' => $totalDuration,
                    'status' => $status,
                    'source' => $sources[array_rand($sources)],
                    'completed_at' => $status === 'completed' ? $appointmentDate : null,
                    'deposit_required' => rand(0, 1) === 1,
                    'deposit_paid' => rand(0, 1) === 1,
                    'notes' => rand(0, 1) ? 'یادداشت نوبت ' . ($appointmentCount + 1) : null,
                ]);
                
                // Attach services
                foreach ($selectedServices as $service) {
                    $appointment->services()->attach($service->id, [
                        'price_at_booking' => $service->price,
                    ]);
                }
                
                // Create payment for completed appointments
                if ($status === 'completed') {
                    Payment::create([
                        'salon_id' => $this->salonId,
                        'customer_id' => $customer->id,
                        'appointment_id' => $appointment->id,
                        'staff_id' => $staff->id,
                        'date' => $appointmentDate->toDateString(),
                        'amount' => $totalPrice,
                        'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                        'description' => 'پرداخت نوبت شماره ' . $appointment->id,
                    ]);
                    $paymentCount++;
                    
                    // Store completed appointments for feedback creation later
                    $this->completedAppointments[] = [
                        'appointment' => $appointment,
                        'services' => $selectedServices,
                        'date' => $appointmentDate,
                    ];
                }
                
                $appointmentCount++;
            }
        }

        $this->command->info("✅ تعداد $appointmentCount نوبت ایجاد شد");
        $this->command->info("✅ تعداد $paymentCount پرداخت ایجاد شد");
    }

    private function createCustomerFeedbacks(): void
    {
        // Default strength and weakness tags matching satisfaction form
        $strengthOptions = [
            ['key' => md5('خدمات بی نقص'), 'label' => 'خدمات بی نقص'],
            ['key' => md5('برخورد حرفه ای پرسنل'), 'label' => 'برخورد حرفه ای پرسنل'],
            ['key' => md5('محیط آرام و منظم'), 'label' => 'محیط آرام و منظم'],
            ['key' => md5('پرسنل با دقت و با حوصله'), 'label' => 'پرسنل با دقت و با حوصله'],
            ['key' => md5('نوبت دهی آسان'), 'label' => 'نوبت دهی آسان'],
            ['key' => md5('قیمت منصفانه'), 'label' => 'قیمت منصفانه'],
            ['key' => md5('مواد مصرفی با کیفیت'), 'label' => 'مواد مصرفی با کیفیت'],
            ['key' => md5('رعایت بهداشت و نظافت'), 'label' => 'رعایت بهداشت و نظافت'],
            ['key' => md5('انرژی مثبت پرسنل و سالن'), 'label' => 'انرژی مثبت پرسنل و سالن'],
        ];

        $weaknessOptions = [
            ['key' => md5('معطلی زیاد'), 'label' => 'معطلی زیاد'],
            ['key' => md5('رفتار نامناسب پرسنل'), 'label' => 'رفتار نامناسب پرسنل'],
            ['key' => md5('محیط شلوغ و بی نظم'), 'label' => 'محیط شلوغ و بی نظم'],
            ['key' => md5('کیفیت پایین خدمات'), 'label' => 'کیفیت پایین خدمات'],
            ['key' => md5('عدم راهنمایی مناسب'), 'label' => 'عدم راهنمایی مناسب'],
            ['key' => md5('قیمت بالا'), 'label' => 'قیمت بالا'],
            ['key' => md5('مواد مصرفی نامرغوب'), 'label' => 'مواد مصرفی نامرغوب'],
            ['key' => md5('عدم رعایت بهداشت و نظافت'), 'label' => 'عدم رعایت بهداشت و نظافت'],
        ];

        $feedbackCount = 0;

        // Create feedback for 60-70% of completed appointments
        foreach ($this->completedAppointments as $item) {
            // 60-70% chance of having feedback
            if (rand(1, 10) <= 7) {
                $appointment = $item['appointment'];
                $services = $item['services'];
                $appointmentDate = $item['date'];

                // Random rating between 3-5 (mostly positive feedback)
                $rating = rand(3, 5);

                // Select 2-4 random strengths
                $selectedStrengths = [];
                $strengthCount = rand(2, 4);
                $shuffledStrengths = $strengthOptions;
                shuffle($shuffledStrengths);
                for ($i = 0; $i < $strengthCount && $i < count($shuffledStrengths); $i++) {
                    $selectedStrengths[] = $shuffledStrengths[$i];
                }

                // Select 0-2 weaknesses (fewer weaknesses)
                $selectedWeaknesses = [];
                if ($rating < 5) {
                    $weaknessCount = rand(0, 2);
                    $shuffledWeaknesses = $weaknessOptions;
                    shuffle($shuffledWeaknesses);
                    for ($i = 0; $i < $weaknessCount && $i < count($shuffledWeaknesses); $i++) {
                        $selectedWeaknesses[] = $shuffledWeaknesses[$i];
                    }
                }

                // Build text feedback
                $textFeedback = "";
                if (count($selectedStrengths) > 0) {
                    $textFeedback .= "نقاط قوت: " . implode('، ', array_column($selectedStrengths, 'label')) . "\n";
                }
                if (count($selectedWeaknesses) > 0) {
                    $textFeedback .= "نقاط ضعف: " . implode('، ', array_column($selectedWeaknesses, 'label'));
                }

                // Select first service for service_id
                $selectedService = $services[0] ?? null;

                // Feedback submitted 1-3 days after appointment
                $submittedAt = $appointmentDate->copy()->addDays(rand(1, 3))->addHours(rand(10, 20));

                CustomerFeedback::create([
                    'appointment_id' => $appointment->id,
                    'staff_id' => $appointment->staff_id,
                    'service_id' => $selectedService ? $selectedService->id : null,
                    'rating' => $rating,
                    'text_feedback' => $textFeedback,
                    'strengths_selected' => $selectedStrengths,
                    'weaknesses_selected' => $selectedWeaknesses,
                    'is_submitted' => true,
                    'submitted_at' => $submittedAt,
                ]);

                $feedbackCount++;
            }
        }

        $this->command->info("✅ تعداد $feedbackCount بازخورد رضایت‌سنجی ایجاد شد");
    }

    private function createExpenses(): void
    {
        $expenseCategories = [
            'اجاره',
            'آب و برق و گاز',
            'خرید لوازم و مواد',
            'حقوق پرسنل',
            'تعمیرات',
            'بازاریابی و تبلیغات',
            'مالیات',
            'بیمه',
            'تلفن و اینترنت',
            'سایر',
        ];
        
        $expenseCount = 0;

        // Create expenses for the last 6 months
        for ($monthOffset = 0; $monthOffset < 6; $monthOffset++) {
            $expensesThisMonth = rand(15, 30);
            
            for ($i = 0; $i < $expensesThisMonth; $i++) {
                $expenseDate = Carbon::now()
                    ->subMonths($monthOffset)
                    ->setDay(rand(1, 28));
                
                $category = $expenseCategories[array_rand($expenseCategories)];
                $amount = 0;
                
                // Set realistic amounts based on category
                switch ($category) {
                    case 'اجاره':
                        $amount = rand(20000000, 50000000);
                        break;
                    case 'آب و برق و گاز':
                        $amount = rand(2000000, 8000000);
                        break;
                    case 'خرید لوازم و مواد':
                        $amount = rand(1000000, 15000000);
                        break;
                    case 'حقوق پرسنل':
                        $amount = rand(10000000, 50000000);
                        break;
                    case 'تعمیرات':
                        $amount = rand(500000, 10000000);
                        break;
                    case 'بازاریابی و تبلیغات':
                        $amount = rand(2000000, 20000000);
                        break;
                    case 'مالیات':
                        $amount = rand(5000000, 15000000);
                        break;
                    case 'بیمه':
                        $amount = rand(3000000, 10000000);
                        break;
                    case 'تلفن و اینترنت':
                        $amount = rand(500000, 2000000);
                        break;
                    default:
                        $amount = rand(500000, 5000000);
                }
                
                Expense::create([
                    'salon_id' => $this->salonId,
                    'date' => $expenseDate->toDateString(),
                    'amount' => $amount,
                    'category' => $category,
                    'description' => $category . ' - ' . $expenseDate->format('Y/m'),
                ]);
                
                $expenseCount++;
            }
        }

        $this->command->info("✅ تعداد $expenseCount هزینه ایجاد شد");
    }

    private function createSharedReports(): void
    {
        $owner = $this->salon->owner;
        
        $reportTypes = [
            'customers' => 'گزارش مشتریان',
            'reservations' => 'گزارش نوبت‌ها',
            'finance' => 'گزارش مالی',
            'personnel' => 'گزارش پرسنل',
            'sms' => 'گزارش پیامک',
        ];
        
        $reportCount = 0;
        
        foreach ($reportTypes as $type => $title) {
            // Create a few sample shared reports
            for ($i = 0; $i < 3; $i++) {
                $createdAt = Carbon::now()->subDays(rand(1, 30));
                
                SharedReport::create([
                    'salon_id' => $this->salonId,
                    'created_by' => $owner->id,
                    'report_type' => $type,
                    'token' => \Illuminate\Support\Str::random(32),
                    'filters' => [
                        'date_from' => Carbon::now()->subMonth()->toDateString(),
                        'date_to' => Carbon::now()->toDateString(),
                        'period' => 'monthly',
                    ],
                    'data' => [
                        'title' => $title,
                        'generated_at' => $createdAt->toIso8601String(),
                        'total_records' => rand(50, 500),
                        'summary' => [
                            'total' => rand(10000000, 100000000),
                            'count' => rand(50, 500),
                        ],
                    ],
                    'expires_at' => Carbon::now()->addDays(30),
                    'view_count' => rand(0, 10),
                    'created_at' => $createdAt,
                ]);
                
                $reportCount++;
            }
        }

        $this->command->info("✅ تعداد $reportCount گزارش اشتراکی ایجاد شد");
    }
}
