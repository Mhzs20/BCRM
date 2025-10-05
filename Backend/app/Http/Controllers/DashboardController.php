<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Salon;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\SmsTransaction;
use App\Models\ActivityLog;
use App\Services\SmsService;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Imports\CustomersImport;
use Illuminate\Database\QueryException;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

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

        // Total appointments stats for this salon
        $totalAppointmentsCount = Appointment::where('salon_id', $salon_id)->count();
        $completedAppointmentsCount = Appointment::where('salon_id', $salon_id)->where('status', 'completed')->count();
        $cancelledAppointmentsCount = Appointment::where('salon_id', $salon_id)->where('status', 'canceled')->count();
        $pendingAppointmentsCount = Appointment::where('salon_id', $salon_id)
            ->whereIn('status', ['pending', 'pending_confirmation', 'notconfirmed', 'confirmed'])
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
            'total_appointments_count' => $totalAppointmentsCount,
            'completed_appointments_count' => $completedAppointmentsCount,
            'cancelled_appointments_count' => $cancelledAppointmentsCount,
            'pending_appointments_count' => $pendingAppointmentsCount,
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
            'completed_appointments' => Appointment::where('salon_id', $salon->id)->where('status', 'completed')->count(),
            'cancelled_appointments' => Appointment::where('salon_id', $salon->id)->where('status', 'canceled')->count(),
            'pending_appointments' => Appointment::where('salon_id', $salon->id)
                ->whereIn('status', ['pending', 'pending_confirmation', 'notconfirmed', 'confirmed'])
                ->count(),
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
        // $salon = $user->salons()->with('businessCategory', 'businessSubcategories')->first();
        $salon = Salon::where('user_id', $user->id)->with(['businessCategory', 'businessSubcategories', 'province', 'city'])->first();

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

    public function recentActivities(Request $request)
{
    $user = Auth::user();
    $activeSalon = $user->activeSalon;

    if (!$activeSalon) {
        return response()->json(['message' => 'No active salon found.'], 404);
    }

    $logs = ActivityLog::where('salon_id', $activeSalon->id)
        ->with(['loggable', 'user', 'salon'])
        ->orderBy('created_at', 'desc')
        ->take(20)
        ->get();

    $formattedLogs = $logs->map(function ($log) {
        if (!$log->loggable) {
            return null; 
        }

        $loggableType = class_basename($log->loggable_type);
        $details = "فعالیت نامشخص";
        $userName = optional($log->user)->name ?: 'کاربر سیستم';
        $customerName = '';

        switch ($loggableType) {
            case 'Appointment':
                $customerName = optional($log->loggable->customer)->name;
                $appointmentDate = Jalalian::fromCarbon($log->loggable->appointment_date)->format('Y/m/d');
                switch ($log->activity_type) {
                    case 'created':
                        $details = "نوبت جدید برای {$customerName} در تاریخ {$appointmentDate} ثبت شد.";
                        break;
                    case 'updated':
                        $details = "نوبت {$customerName} در تاریخ {$appointmentDate} به‌روزرسانی شد.";
                        break;
                    case 'cancelled':
                        $details = "نوبت {$customerName} در تاریخ {$appointmentDate} لغو شد.";
                        break;
                    default:
                        $details = "فعالیتی در مورد نوبت {$customerName} رخ داد.";
                }
                break;
            case 'Customer':
                $details = "مشتری جدید با نام {$log->loggable->name} اضافه شد.";
                break;
            case 'SmsPackage':
                $details = "پکیج پیامک {$log->loggable->name} خریداری شد.";
                break;
            case 'Payment':
                $amount = number_format($log->loggable->amount) . ' تومان';
                $details = "یک پرداختی جدید با مبلغ {$amount} ثبت شد.";
                break;
        }

        return [
            'id' => $log->id,
            'user' => $userName,
            'activity' => $details,
            'salon' => optional($log->salon)->name,
            'time' => Jalalian::fromCarbon($log->created_at)->format('Y/m/d H:i'),
        ];
    })->filter()->values();

    return response()->json($formattedLogs);
}

    public function importCustomers(Request $request, $salon_id)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            $import = new CustomersImport((int)$salon_id);
            Excel::import($import, $request->file('file'));

            $importedCount = $import->getImportedCount();
            $skippedRows = $import->getSkippedRows();

            $message = "ایمپورت با موفقیت انجام شد. {$importedCount} مشتری جدید اضافه شد.";
            if (count($skippedRows) > 0) {
                $message .= " " . count($skippedRows) . " رکورد نادیده گرفته شد.";
            }

            return response()->json([
                'message' => $message,
                'imported_count' => $importedCount,
                'skipped_rows' => $skippedRows,
            ], 200);

        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values()
                ];
            }
            return response()->json([
                'message' => 'خطای اعتبارسنجی در فایل اکسل.',
                'errors' => $errors
            ], 422);
        } catch (\Exception $e) {
            Log::error('خطا در هنگام ایمپورت فایل اکسل: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در هنگام ایمپورت فایل اکسل رخ داد.'], 500);
        }
    }
}
