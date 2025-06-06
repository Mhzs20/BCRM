<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Salon;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Salon $salon, Request $request): JsonResponse
    {
        $this->authorize('manageResources', $salon);

        try {
            $query = $salon->payments()->with('customer:id,name', 'appointment:id,appointment_date,appointment_time');

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            $paymentDateColumn = 'payment_date';

            if ($request->has('payment_date_jalali')) {
                try {
                    $jalaliDate = str_replace('/', '-', $request->input('payment_date_jalali'));
                    $gregorianDate = Jalalian::fromFormat('Y-m-d', $jalaliDate)->toCarbon()->format('Y-m-d');
                    $query->where($paymentDateColumn, $gregorianDate);
                } catch (\Exception $e) {
                    Log::info("Invalid Jalali date format in payment index: " . $request->input('payment_date_jalali'));
                }
            }
            if ($request->has('date_from_jalali') && $request->has('date_to_jalali')) {
                try {
                    $dateFrom = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $request->input('date_from_jalali')))->toCarbon()->format('Y-m-d');
                    $dateTo = Jalalian::fromFormat('Y-m-d', str_replace('/', '-', $request->input('date_to_jalali')))->toCarbon()->format('Y-m-d');
                    $query->whereBetween($paymentDateColumn, [$dateFrom, $dateTo]);
                } catch (\Exception $e) {
                    Log::info("Invalid Jalali date range format in payment index.");
                }
            }

            $payments = $query->orderBy($paymentDateColumn, 'desc')->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

            $payments->getCollection()->transform(function ($payment) use ($paymentDateColumn) {
                $payment->jalali_date = $payment->{$paymentDateColumn} ? Jalalian::fromCarbon(Carbon::parse($payment->{$paymentDateColumn}))->format('Y/m/d') : null;
                if($payment->appointment) {
                    $payment->appointment->jalali_appointment_date = $payment->appointment->appointment_date ? Jalalian::fromCarbon(Carbon::parse($payment->appointment->appointment_date))->format('Y/m/d') : null;
                }
                return $payment;
            });

            return response()->json($payments);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning("PaymentController@index access denied for user ID " . Auth::id() . " to salon ID {$salon->id}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'شما اجازه دسترسی به پرداخت‌های این سالن را ندارید.'], 403);
        } catch (\Exception $e) {
            Log::error("Error in PaymentController@index for salon ID {$salon->id}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'خطا در دریافت لیست پرداخت‌ها.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request, Salon $salon): JsonResponse
    {


        $validatedData = $request->validated();
        $validatedData['salon_id'] = $salon->id;
        $paymentDateColumn = 'payment_date';

        DB::beginTransaction();
        try {
            $jalaliDate = str_replace('/', '-', $validatedData[$paymentDateColumn]);
            $validatedData[$paymentDateColumn] = Jalalian::fromFormat('Y-m-d', $jalaliDate)->toCarbon()->format('Y-m-d');

            $payment = Payment::create($validatedData);
            $customerName = $payment->customer ? $payment->customer->name : 'ناشناس';

            ActivityLog::create([
                'user_id' => Auth::id(),
                'salon_id' => $salon->id,
                'activity_type' => 'payment_created',
                'description' => "پرداخت جدید به مبلغ {$payment->amount} برای مشتری {$customerName} ثبت شد.",
                'loggable_id' => $payment->id,
                'loggable_type' => Payment::class,
            ]);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'پرداخت با موفقیت ثبت شد.',
                'data' => $payment->load('customer:id,name')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error storing payment for salon ID {$salon->id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ثبت پرداخت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Salon $salon, Payment $payment): JsonResponse
    {
        $this->authorize('manageResources', $salon);

        if ($payment->salon_id !== $salon->id) {
            return response()->json(['message' => 'پرداخت یافت نشد یا به این سالن تعلق ندارد.'], 404);
        }

        try {
            $payment->load('customer:id,name', 'appointment');
            $paymentDateColumn = 'payment_date'; // یا 'date'
            $payment->jalali_date = $payment->{$paymentDateColumn} ? Jalalian::fromCarbon(Carbon::parse($payment->{$paymentDateColumn}))->format('Y/m/d') : null;
            if($payment->appointment) {
                $payment->appointment->jalali_appointment_date = $payment->appointment->appointment_date ? Jalalian::fromCarbon(Carbon::parse($payment->appointment->appointment_date))->format('Y/m/d') : null;
            }
            return response()->json($payment);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning("PaymentController@show access denied for user ID " . Auth::id() . " to salon ID {$salon->id} for payment ID {$payment->id}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'شما اجازه دسترسی به این پرداخت را ندارید.'], 403);
        } catch (\Exception $e) {
            Log::error("Error in PaymentController@show for payment ID {$payment->id}, salon ID {$salon->id}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'خطا در نمایش پرداخت.'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * ویرایش اطلاعات یک پرداخت
     */
    public function update(UpdatePaymentRequest $request, Salon $salon, Payment $payment): JsonResponse
    {

        $validatedData = $request->validated();
        $paymentDateColumn = 'payment_date';

        DB::beginTransaction();
        try {
            if (isset($validatedData[$paymentDateColumn])) {
                $jalaliDate = str_replace('/', '-', $validatedData[$paymentDateColumn]);
                $validatedData[$paymentDateColumn] = Jalalian::fromFormat('Y-m-d', $jalaliDate)->toCarbon()->format('Y-m-d');
            }

            $payment->update($validatedData);
            $customerName = $payment->customer ? $payment->customer->name : 'ناشناس';

            ActivityLog::create([
                'user_id' => Auth::id(),
                'salon_id' => $salon->id,
                'activity_type' => 'payment_updated',
                'description' => "پرداخت مشتری {$customerName} با شناسه {$payment->id} ویرایش شد.",
                'loggable_id' => $payment->id,
                'loggable_type' => Payment::class,
            ]);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'پرداخت با موفقیت ویرایش شد.',
                'data' => $payment->fresh()->load('customer:id,name')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating payment ID {$payment->id} for salon ID {$salon->id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ویرایش پرداخت: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Salon $salon, Payment $payment): JsonResponse
    {
        $this->authorize('manageResources', $salon);

        if ($payment->salon_id !== $salon->id) {
            return response()->json(['message' => 'پرداخت یافت نشد یا به این سالن تعلق ندارد.'], 404);
        }
        DB::beginTransaction();
        try {
            $paymentDescription = $payment->description;
            $paymentIdForLog = $payment->id;
            $payment->delete();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'salon_id' => $salon->id,
                'activity_type' => 'payment_deleted',
                'description' => "پرداخت با شناسه {$paymentIdForLog} و شرح \"{$paymentDescription}\" حذف شد.",
                'loggable_id' => $paymentIdForLog,
                'loggable_type' => Payment::class,
            ]);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'پرداخت با موفقیت حذف شد.'
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            DB::rollBack();
            Log::warning("PaymentController@destroy access denied for user ID " . Auth::id() . " to salon ID {$salon->id} for payment ID {$payment->id}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'شما اجازه حذف این پرداخت را ندارید.'], 403);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting payment ID {$payment->id} for salon ID {$salon->id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در حذف پرداخت: ' . $e->getMessage()
            ], 500);
        }
    }
}
