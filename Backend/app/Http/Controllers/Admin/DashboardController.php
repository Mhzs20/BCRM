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
    public function index()
    {
        // User stats
        $totalUsers = User::count();
        $activeUsers = User::whereNotNull('active_salon_id')->count();
        $inactiveUsers = User::whereNull('active_salon_id')->count();
        $pendingUsers = User::whereNotNull('otp_code')->count();

        // Salons and clinics
        $totalSalons = Salon::count();

        // Appointments for today
        $today = Carbon::today();
        $appointmentsToday = Appointment::whereDate('created_at', $today)->get();
        $totalAppointmentsToday = $appointmentsToday->count();
        $completedAppointmentsToday = $appointmentsToday->where('status', 'completed')->count();
        $cancelledAppointmentsToday = $appointmentsToday->where('status', 'cancelled')->count();
        $pendingAppointmentsToday = $appointmentsToday->where('status', 'pending')->count();

        // SMS stats
        $smsSentToday = SmsTransaction::whereDate('created_at', $today)->count();
        $smsSentThisMonth = SmsTransaction::whereMonth('created_at', $today->month)->count();
        $kavenegarApi = new KavenegarApi(config('services.kavenegar.apikey'));
        $totalSmsBalance = $kavenegarApi->AccountInfo()->remaincredit;

        // SMS Profitability Stats
        $smsPurchasePricePerPart = Setting::where('key', 'sms_purchase_price_per_part')->first();
        $smsPurchasePricePerPartValue = $smsPurchasePricePerPart ? (float)$smsPurchasePricePerPart->value : 0;

        $totalSmsPartsSold = SmsTransaction::where('status', 'completed')->sum('sms_count');
        $totalSmsCost = $totalSmsPartsSold * $smsPurchasePricePerPartValue;

        // Income stats
        $dailySmsIncome = SmsTransaction::where('status', 'completed')->whereDate('created_at', $today)->sum('amount');
        $monthlySmsIncome = SmsTransaction::where('status', 'completed')->whereMonth('created_at', $today->month)->sum('amount');
        $yearlySmsIncome = SmsTransaction::where('status', 'completed')->whereYear('created_at', $today->year)->sum('amount');
        $totalSmsIncome = SmsTransaction::where('status', 'completed')->sum('amount');

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
        $topSalons = Salon::with('user')->withCount('smsTransactions')
            ->orderBy('sms_transactions_count', 'desc')
            ->take(10)
            ->get();

        // User growth and sales chart data
        $userGrowthData = User::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('count(*) as count'))
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

        $smsProfitData = SmsTransaction::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('SUM(amount) as total_revenue'),
            DB::raw('SUM(sms_count) as total_sms_parts')
        )
            ->where('status', 'completed')
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

        $smsSalesData = SmsTransaction::where('status', 'completed')
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
            'smsSentToday',
            'smsSentThisMonth',
            'totalSmsBalance',
            'dailyIncome',
            'monthlyIncome',
            'yearlyIncome',
            'totalIncome',
            'topSalons',
            'userGrowthData',
            'salesData',
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
