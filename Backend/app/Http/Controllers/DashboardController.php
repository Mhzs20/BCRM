<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Salon;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\SmsTransaction;
use App\Models\UserSmsBalance;
use App\Models\SmsPackage;
use App\Models\ActivityLog;
use App\Services\SmsService;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
            $totalSalons = Salon::count();
            $totalAppointments = Appointment::count();
            $totalCustomers = Customer::count();
            return response()->json([
                'total_salons' => $totalSalons,
                // 'total_users' => $totalUsers,
                'total_appointments' => $totalAppointments,
                'total_customers' => $totalCustomers,
            ]);
        }


        $salon = Salon::where('user_id', $user->id)->first();
        if (!$salon && method_exists($user, 'salons') && $user->salons()->count() > 0) {

            $salon = $user->salons()->first();
        }

        if ($salon) {
            $customersCount = Customer::where('salon_id', $salon->id)->whereNull('deleted_at')->count();
            $appointmentsCount = Appointment::where('salon_id', $salon->id)->count();
            return response()->json([
                'salon_name' => $salon->name,
                'customers_count' => $customersCount,
                'appointments_count' => $appointmentsCount,

            ]);
        }


        return response()->json(['message' => 'به داشبورد خوش آمدید. اطلاعاتی برای نمایش وجود ندارد.'], 200);
    }

    // ============================================================

    // ============================================================

    /**
     * Get key statistics for a specific salon's overview page.
     * این متد باید در روتی مانند /salons/{salon_id}/overview/stats قرار گیرد.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $salon_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSalonStats(Request $request, $salon_id)
    {

        $targetSalon = Salon::find($salon_id);
        if (!$targetSalon) {
            return response()->json(['message' => 'سالن مورد نظر یافت نشد.'], 404);
        }


        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
           $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
           $endOfWeek = Carbon::now()->endOfWeek()->toDateString();
           $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
           $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $todayAppointmentsCount = Appointment::where('salon_id', $salon_id)
            ->whereDate('appointment_date', $today->toDateString())
            ->whereIn('status', ['confirmed', 'pending_confirmation'])
            ->count();

        $tomorrowAppointmentsCount = Appointment::where('salon_id', $salon_id)
            ->whereDate('appointment_date', $tomorrow->toDateString())
            ->whereIn('status', ['confirmed', 'pending_confirmation'])
            ->count();

        $totalActiveCustomersCount = Customer::where('salon_id', $salon_id)
            ->whereNull('deleted_at')
            ->count();

        $newCustomersTodayCount = Customer::where('salon_id', $salon_id)
            ->whereDate('created_at', $today->toDateString())
            ->whereNull('deleted_at')
            ->count();

        $upcomingAppointments = Appointment::where('salon_id', $salon_id)
            ->where('appointment_date', '>=', $today->toDateString())
            ->where(function ($query) use ($today) {
                $query->where('appointment_date', '>', $today->toDateString())
                    ->orWhere(function($q) use ($today){
                        $q->whereDate('appointment_date', $today->toDateString())
                            ->whereTime('start_time', '>=', Carbon::now()->format('H:i:s'));
                    });
            })
            ->whereIn('status', ['confirmed', 'pending_confirmation'])
            ->with(['customer:id,name,phone_number', 'staff:id,full_name', 'services:id,name'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->take(5)
            ->get();



        $stats = [
            'today_appointments_count' => $todayAppointmentsCount,
            'tomorrow_appointments_count' => $tomorrowAppointmentsCount,
            'total_active_customers_count' => $totalActiveCustomersCount,
            'new_customers_today_count' => $newCustomersTodayCount,
            'upcoming_appointments' => $upcomingAppointments,
            // 'today_revenue' => $todayRevenue,
        ];

        return response()->json(['data' => $stats]);
    }


    /**
     * Get dashboard data for a salon user.
     */
    public function getSalonUserDashboard(Request $request)
    {
        $user = Auth::user();
        $salon = Salon::where('user_id', $user->id)->first();

        if (!$salon) {
            return response()->json(['error' => 'No salon associated with this user or user is not a salon owner.'], 404);
        }

         $data = [
            'total_customers' => Customer::where('salon_id', $salon->id)->count(),
            'total_appointments' => Appointment::where('salon_id', $salon->id)->count(),
            'upcoming_appointments_count' => Appointment::where('salon_id', $salon->id)
                ->where('appointment_date', '>=', Carbon::today()->toDateString())
                ->whereIn('status', ['confirmed', 'pending_confirmation'])
                ->count(),
        ];

        return response()->json($data);
    }

    /**
     * Get salon user information.
     */
    public function getSalonUser(Request $request)
    {
        $user = Auth::user();
        // $salon = $user->salons()->with('businessCategory', 'businessSubcategory')->first();
        $salon = Salon::where('user_id', $user->id)->with(['businessCategory', 'businessSubcategory', 'province', 'city'])->first();

        if (!$user) {
            return response()->json(['error'  => 'User not authenticated'], 401);
        }

        return response()->json([
            'user' => $user->only(['id', 'name', 'phone_number', 'email', 'profile_image_url', 'is_profile_completed']),
            'salon' => $salon ? $salon->toArray() : null,
        ]);
    }
    private function mapSmsStatus(string $status): string
    {
        switch ($status) {
            case 'sent_simulated':
            case 'sent_production_simulated':
            case 'sent_otp':
                return 'ارسال شده';
            case 'failed':
            case 'error':
            case 'error_otp':
                return 'خطا در ارسال';
            default:
                return 'در حال بررسی'; // Default for any other unexpected status
        }
    }

    public function allSalonAppointments(Request $request)
    {
        // 1. Get the authenticated user
        $user = Auth::user();

        // 2. Get the IDs of all salons owned by this user
        $salonIds = $user->salons()->pluck('id');

        // 3. Start building the query for appointments in those salons
        $query = Appointment::whereIn('salon_id', $salonIds)
            ->with(['salon:id,name', 'customer:id,name', 'staff:id,full_name', 'services:id,name']);

        // 4. (Optional) Add filters based on request parameters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            // You might need to convert Jalali dates here if they are sent from the frontend
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $query->whereBetween('appointment_date', [$startDate, $endDate]);
        }

        // 5. Order and paginate the results
        $appointments = $query->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json($appointments);
    }

    public function showSmsBalance(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'کاربر احراز هویت نشده است.'], 401);
        }

        // 1. Current SMS Balance
        $userSmsBalance = UserSmsBalance::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => env('INITIAL_SMS_BALANCE', 0)]
        );
        $currentBalance = $userSmsBalance->balance;

        // 2. SMS Transactions Report
        $query = SmsTransaction::where('user_id', $user->id)
            ->orderBy('sent_at', 'desc');

        // Apply month and year filters
        if ($request->has('year')) {
            $year = (int)$request->input('year');
            $query->whereYear('sent_at', $year);
            if ($request->has('month')) {
                $month = (int)$request->input('month');
                $query->whereMonth('sent_at', $month);
            }
        }

        $transactions = $query->paginate($request->input('per_page', 10));

        $formattedTransactions = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'recipient_count' => 1, // Each transaction is for one recipient
                'sms_type' => $transaction->sms_type,
                'content' => $transaction->content,
                'sent_at_jalali' => Jalalian::fromCarbon($transaction->sent_at)->format('Y/m/d H:i:s'),
                'status' => $this->mapSmsStatus($transaction->status),
                'sms_count_deducted' => $this->smsService->calculateSmsCount($transaction->content),
            ];
        });

        // 3. Total SMS Consumed & Daily Average
        $totalConsumedSms = 0;
        $filteredTransactions = SmsTransaction::where('user_id', $user->id);

        if ($request->has('year')) {
            $year = (int)$request->input('year');
            $filteredTransactions->whereYear('sent_at', $year);
            if ($request->has('month')) {
                $month = (int)$request->input('month');
                $filteredTransactions->whereMonth('sent_at', $month);
            }
        }

        $filteredTransactions->get()->each(function ($transaction) use (&$totalConsumedSms) {
            $totalConsumedSms += $this->smsService->calculateSmsCount($transaction->content);
        });

        $dailyAverageConsumption = 0;
        $numberOfDays = 0;

        if ($request->has('year') && $request->has('month')) {
            $year = (int)$request->input('year');
            $month = (int)$request->input('month');
            $startOfMonth = Carbon::create($year, $month, 1, 0, 0, 0);
            $endOfMonth = $startOfMonth->copy()->endOfMonth();
            $numberOfDays = $endOfMonth->diffInDays($startOfMonth) + 1;
        } elseif ($request->has('year')) {
            $year = (int)$request->input('year');
            $startOfYear = Carbon::create($year, 1, 1, 0, 0, 0);
            $endOfYear = $startOfYear->copy()->endOfYear();
            $numberOfDays = $endOfYear->diffInDays($startOfYear) + 1;
        } else {
            // If no filter, calculate average over all days since first transaction
            $firstTransaction = SmsTransaction::where('user_id', $user->id)->orderBy('sent_at', 'asc')->first();
            if ($firstTransaction) {
                $numberOfDays = Carbon::parse($firstTransaction->sent_at)->diffInDays(now()) + 1;
            }
        }

        if ($numberOfDays > 0) {
            $dailyAverageConsumption = round($totalConsumedSms / $numberOfDays, 2);
        }

        // 4. Active Package Name (infer from latest purchase activity)
        $activePackageName = 'نامشخص';
        $latestPurchaseLog = ActivityLog::where('user_id', $user->id)
            ->where('activity_type', 'sms_package_purchased')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestPurchaseLog && $latestPurchaseLog->loggable_type === SmsPackage::class) {
            $package = SmsPackage::find($latestPurchaseLog->loggable_id);
            if ($package) {
                $activePackageName = $package->name;
            }
        }

        return response()->json([
            'current_sms_balance' => $currentBalance,
            'active_package_name' => $activePackageName,
            'total_sms_consumed' => $totalConsumedSms,
            'daily_average_consumption' => $dailyAverageConsumption,
            'sms_transactions' => $formattedTransactions,
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
        ]);
    }
}
