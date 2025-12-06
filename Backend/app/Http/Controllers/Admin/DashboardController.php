<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Salon;
use App\Models\SmsTransaction;
use App\Models\User;
use App\Models\SmsPackage;
use App\Models\DiscountCode;
use App\Models\Order;
use Carbon\Carbon;
use App\Models\Setting;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;
use Kavenegar\KavenegarApi;

class DashboardController extends Controller
{
    public function getSmsStatsForHeader()
    {
        $today = Carbon::today();
        
        $smsSentToday = SmsTransaction::whereHas('salon', function($query) {
                $query->whereHas('user', function($subQuery) {
                    $subQuery->whereNotNull('active_salon_id');
                });
            })->whereDate('created_at', $today)->count();
            
        $smsSentThisMonth = SmsTransaction::whereHas('salon', function($query) {
                $query->whereHas('user', function($subQuery) {
                    $subQuery->whereNotNull('active_salon_id');
                });
            })->whereMonth('created_at', $today->month)->count();
        
        try {
            $kavenegarApi = new KavenegarApi(config('services.kavenegar.apikey'));
            $totalSmsBalanceToman = $kavenegarApi->AccountInfo()->remaincredit;
        } catch (\Exception $e) {
            $totalSmsBalanceToman = 0;
        }
        
        // محاسبه تعداد پیامک بر اساس قیمت خرید
        $smsPurchasePricePerPart = Setting::where('key', 'sms_purchase_price_per_part')->first();
        $smsPurchasePricePerPartValue = $smsPurchasePricePerPart ? (float)$smsPurchasePricePerPart->value : 135; // میانگین قیمت: 135 تومان
        
        $totalSmsCount = $smsPurchasePricePerPartValue > 0 ? floor($totalSmsBalanceToman / $smsPurchasePricePerPartValue) : 0;
        
        return [
            'today' => $smsSentToday,
            'month' => $smsSentThisMonth,
            'balance_toman' => $totalSmsBalanceToman,
            'balance_count' => $totalSmsCount,
        ];
    }
    
    public function index()
    {
        // User stats
        $totalUsers = User::whereNotNull('active_salon_id')->count();
        $activeUsers = User::whereNotNull('active_salon_id')->count();
        $inactiveUsers = User::whereNull('active_salon_id')->count();
        $pendingUsers = User::whereNotNull('active_salon_id')->whereNotNull('otp_code')->count();

        // Salons and clinics
        $totalSalons = Salon::whereHas('user', function($query) {
                $query->whereNotNull('active_salon_id');
            })->count();

        // Appointments for today (filtered by appointment_date)
        $today = Carbon::today();
        $appointmentsToday = Appointment::whereDate('appointment_date', $today)->get();

        $totalAppointmentsToday = $appointmentsToday->count();
        $completedAppointmentsToday = $appointmentsToday->where('status', 'completed')->count();
        $cancelledAppointmentsToday = $appointmentsToday->where('status', 'canceled')->count();
        $pendingAppointmentsToday = $appointmentsToday->whereIn('status', ['pending', 'pending_confirmation'])->count();

        // Total appointments stats (all time)
        $totalAppointments = Appointment::count();
        $totalCompletedAppointments = Appointment::where('status', 'completed')->count();
        $totalCancelledAppointments = Appointment::where('status', 'canceled')->count();
        $totalPendingAppointments = Appointment::whereIn('status', ['pending', 'pending_confirmation', 'notconfirmed'])->count();

        // SMS stats
        $smsSentToday = SmsTransaction::whereHas('salon', function($query) {
                $query->whereHas('user', function($subQuery) {
                    $subQuery->whereNotNull('active_salon_id');
                });
            })->whereDate('created_at', $today)->count();
        $smsSentThisMonth = SmsTransaction::whereHas('salon', function($query) {
                $query->whereHas('user', function($subQuery) {
                    $subQuery->whereNotNull('active_salon_id');
                });
            })->whereMonth('created_at', $today->month)->count();
        
        // SMS Profitability Stats
        $smsPurchasePricePerPart = Setting::where('key', 'sms_purchase_price_per_part')->first();
        $smsPurchasePricePerPartValue = $smsPurchasePricePerPart ? (float)$smsPurchasePricePerPart->value : 140; // میانگین قیمت: 140 تومان
        
        // Get Kavenegar balance with error handling
        try {
            $kavenegarApi = new KavenegarApi(config('services.kavenegar.apikey'));
            $totalSmsBalanceToman = $kavenegarApi->AccountInfo()->remaincredit;
        } catch (\Exception $e) {
            // Log the error and set balance to 0 if API is unreachable
            \Log::warning('Kavenegar API error: ' . $e->getMessage());
            $totalSmsBalanceToman = 0;
        }
        
        // محاسبه تعداد پیامک بر اساس قیمت خرید
        $totalSmsBalance = $smsPurchasePricePerPartValue > 0 ? floor($totalSmsBalanceToman / $smsPurchasePricePerPartValue) : 0;

        $totalSmsPartsSold = SmsTransaction::whereHas('salon', function($query) {
                $query->whereHas('user', function($subQuery) {
                    $subQuery->whereNotNull('active_salon_id');
                });
            })->where('status', 'completed')->sum('sms_count');
        $totalSmsCost = $totalSmsPartsSold * $smsPurchasePricePerPartValue;

        // Income stats - درآمد پیامک از جدول orders
        $dailySmsIncome = Order::whereHas('salon', function($query) {
                $query->whereHas('user', function($subQuery) {
                    $subQuery->whereNotNull('active_salon_id');
                });
            })
            ->whereNotNull('sms_package_id')
            ->whereIn('status', ['completed', 'paid'])
            ->whereDate('created_at', $today)
            ->sum('amount');
            
        $monthlySmsIncome = Order::whereHas('salon', function($query) {
                $query->whereHas('user', function($subQuery) {
                    $subQuery->whereNotNull('active_salon_id');
                });
            })
            ->whereNotNull('sms_package_id')
            ->whereIn('status', ['completed', 'paid'])
            ->whereMonth('created_at', $today->month)
            ->sum('amount');
            
        $yearlySmsIncome = Order::whereHas('salon', function($query) {
                $query->whereHas('user', function($subQuery) {
                    $subQuery->whereNotNull('active_salon_id');
                });
            })
            ->whereNotNull('sms_package_id')
            ->whereIn('status', ['completed', 'paid'])
            ->whereYear('created_at', $today->year)
            ->sum('amount');
            
        $totalSmsIncome = Order::whereHas('salon', function($query) {
                $query->whereHas('user', function($subQuery) {
                    $subQuery->whereNotNull('active_salon_id');
                });
            })
            ->whereNotNull('sms_package_id')
            ->whereIn('status', ['completed', 'paid'])
            ->sum('amount');

        $dailyPaymentIncome = Payment::whereDate('date', $today)->sum('amount');
        $monthlyPaymentIncome = Payment::whereMonth('date', $today->month)->sum('amount');
        $yearlyPaymentIncome = Payment::whereYear('date', $today->year)->sum('amount');
        $totalPaymentIncome = Payment::sum('amount');

        $dailyIncome = $dailySmsIncome + $dailyPaymentIncome;
        $monthlyIncome = $monthlySmsIncome + $monthlyPaymentIncome;
        $yearlyIncome = $yearlySmsIncome + $yearlyPaymentIncome;
        $totalIncome = $totalSmsIncome + $totalPaymentIncome;

        $netSmsProfit = $totalSmsIncome - $totalSmsCost;
        $smsProfitPercentage = $totalSmsIncome > 0 ? ($netSmsProfit / $totalSmsIncome) * 100 : 0;
        $averageSmsSellingPrice = $totalSmsPartsSold > 0 ? $totalSmsIncome / $totalSmsPartsSold : 0;

        // Discount codes stats
        $totalDiscountCodes = DiscountCode::count();
        $activeDiscountCodes = DiscountCode::where('is_active', true)->count();
        $expiredDiscountCodes = DiscountCode::where('expires_at', '<', now())->where('is_active', true)->count();
        $usedDiscountCodes = DiscountCode::whereHas('orders')->count();
        $totalDiscountUsage = Order::whereNotNull('discount_code')->count();
        $totalDiscountAmount = Order::whereNotNull('discount_code')->sum(DB::raw('original_amount - amount'));

        // Top discount codes by usage
        $topDiscountCodes = DiscountCode::withCount('orders')
            ->orderBy('orders_count', 'desc')
            ->take(5)
            ->get();

        // Top 10 salons by SMS sales or activity
        $topSalons = Salon::whereHas('user', function($query) {
                $query->whereNotNull('active_salon_id');
            })
            ->with('user')
            ->withCount('smsTransactions')
            ->orderBy('sms_transactions_count', 'desc')
            ->take(10)
            ->get();

        // User growth and sales chart data
        $userGrowthData = User::whereNotNull('active_salon_id')
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Jalalian::fromCarbon(Carbon::parse($item->month))->format('F Y'),
                    'count' => $item->count,
                ];
            })
            ->toArray();

        $smsProfitData = Order::whereHas('salon', function($query) {
                $query->whereHas('user', function($subQuery) {
                    $subQuery->whereNotNull('active_salon_id');
                });
            })
            ->whereNotNull('sms_package_id')
            ->whereIn('status', ['completed', 'paid'])
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('SUM(sms_count) as total_sms_parts')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) use ($smsPurchasePricePerPartValue) {
                $totalCost = $item->total_sms_parts * $smsPurchasePricePerPartValue;
                $netProfit = $item->total_revenue - $totalCost;
                return [
                    'month' => Jalalian::fromCarbon(Carbon::parse($item->month))->format('F Y'),
                    'profit' => $netProfit,
                ];
            })
            ->toArray();

        $smsSalesData = Order::whereHas('salon', function($query) {
                $query->whereHas('user', function($subQuery) {
                    $subQuery->whereNotNull('active_salon_id');
                });
            })
            ->whereNotNull('sms_package_id')
            ->whereIn('status', ['completed', 'paid'])
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('sum(amount) as sum'))
            ->groupBy('month');

        $paymentSalesData = Payment::select(DB::raw('DATE_FORMAT(date, "%Y-%m") as month'), DB::raw('sum(amount) as sum'))
            ->groupBy('month');

        $salesDataQuery = $smsSalesData->unionAll($paymentSalesData);

        $salesData = DB::table(DB::raw("({$salesDataQuery->toSql()}) as sales"))
            ->mergeBindings($salesDataQuery->getQuery())
            ->select('month', DB::raw('sum(sum) as total_sum'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Jalalian::fromCarbon(Carbon::parse($item->month))->format('F Y'),
                    'sum' => $item->total_sum,
                ];
            })
            ->toArray();

        // Appointments chart data - نمایش وضعیت فعلی appointments
        $appointmentsData = Appointment::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => Jalalian::fromCarbon(Carbon::parse($item->month))->format('F Y'),
                    'count' => $item->count,
                ];
            })
            ->toArray();

        if (empty($appointmentsData)) {
            $appointmentsData = [
                ['month' => 'مهر ۱۴۰۳', 'count' => 10],
                ['month' => 'آبان ۱۴۰۳', 'count' => 15],
                ['month' => 'آذر ۱۴۰۳', 'count' => 8],
            ];
        }

         \Log::info('Appointments Data:', $appointmentsData);

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeUsers',
            'inactiveUsers',
            'pendingUsers',
            'totalSalons',
            'totalAppointmentsToday',
            'completedAppointmentsToday',
            'cancelledAppointmentsToday',
            'pendingAppointmentsToday',
            'totalAppointments',
            'totalCompletedAppointments',
            'totalCancelledAppointments',
            'totalPendingAppointments',
            'smsSentToday',
            'smsSentThisMonth',
            'totalSmsBalance',
            'totalSmsBalanceToman',
            'dailyIncome',
            'monthlyIncome',
            'yearlyIncome',
            'totalIncome',
            'topSalons',
            'userGrowthData',
            'salesData',
            'appointmentsData',
            'totalSmsPartsSold',
            'totalSmsCost',
            'netSmsProfit',
            'smsProfitPercentage',
            'averageSmsSellingPrice',
            'smsProfitData',
            'totalSmsIncome',
            'totalDiscountCodes',
            'activeDiscountCodes',
            'expiredDiscountCodes',
            'usedDiscountCodes',
            'totalDiscountUsage',
            'totalDiscountAmount',
            'topDiscountCodes'
        ));
    }
}
