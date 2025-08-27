<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Models\User;
use App\Models\City;
use App\Models\Province;
use App\Models\BusinessCategory;
use App\Models\BusinessSubcategory;
use App\Models\SalonSmsBalance;
use Carbon\Carbon;
use App\Models\SmsTransaction;
use App\Models\SalonNote;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Hekmatinasser\Verta\Verta; // Using Hekmatinasser\Verta\Verta package

class AdminSalonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Salon::with(['user', 'city', 'province', 'businessCategory', 'businessSubcategories']);

        if ($request->filled('search')) {
            $query->whereSearch($request->input('search'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status'));
        }

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->input('province_id'));
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->input('city_id'));
        }

        if ($request->filled('business_category_id')) {
            $query->where('business_category_id', $request->input('business_category_id'));
        }

        if ($request->filled('business_subcategory_id')) {
            $subcategoryId = $request->input('business_subcategory_id');
            $query->whereHas('businessSubcategories', function ($q) use ($subcategoryId) {
                $q->where('business_subcategory_id', $subcategoryId);
            });
        }

        if ($request->filled('created_at_start')) {
            try {
                $jalaliDate = $request->input('created_at_start');
                $gregorianDate = Verta::parse($jalaliDate)->toCarbon()->startOfDay();
                $query->where('created_at', '>=', $gregorianDate);
            } catch (\Exception $e) {
                \Log::error('Invalid start date format: ' . $request->input('created_at_start'));
            }
        }

        if ($request->filled('created_at_end')) {
            try {
                $jalaliDate = $request->input('created_at_end');
                $gregorianDate = Verta::parse($jalaliDate)->toCarbon()->endOfDay();
                $query->where('created_at', '<=', $gregorianDate);
            } catch (\Exception $e) {
                \Log::error('Invalid end date format: ' . $request->input('created_at_end'));
            }
        }

        $salons = $query->paginate(10);

        $provinces = Province::all();
        $cities = City::all();
        $businessCategories = BusinessCategory::all();
        $businessSubcategories = BusinessSubcategory::all();

        return view('admin.salons.index', compact('salons', 'provinces', 'cities', 'businessCategories', 'businessSubcategories'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Salon $salon)
    {
        $businessCategories = BusinessCategory::all();
        $businessSubcategories = BusinessSubcategory::all();
        $provinces = Province::all();
        $cities = City::all(); // You might want to filter cities by province here

        $salon->load(['user', 'city', 'province', 'businessCategory', 'businessSubcategories', 'notes.user', 'smsBalance']);

        return view('admin.salons.show', compact('salon', 'businessCategories', 'businessSubcategories', 'provinces', 'cities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Salon $salon)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'mobile' => ['required', 'string', 'max:15', Rule::unique('salons')->ignore($salon->id)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('salons')->ignore($salon->id)],
            'business_category_id' => 'required|exists:business_categories,id',
            'business_subcategory_ids' => 'nullable|array',
            'business_subcategory_ids.*' => 'exists:business_subcategories,id',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'lang' => 'nullable|numeric',
            'whatsapp' => 'nullable|string|max:255',
            'telegram' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'support_phone_number' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'owner_name' => 'required|string|max:255',
            'owner_email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($salon->user->id)],
        ], [
            'owner_email.unique' => 'ایمیل مالک قبلاً توسط کاربر دیگری ثبت شده است.',
            'email.unique' => 'این ایمیل قبلاً توسط سالن دیگری ثبت شده است.',
            'business_subcategory_ids.*.exists' => 'یکی از زیرمجموعه‌های فعالیت انتخاب شده نامعتبر است.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $salon->update([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'business_category_id' => $request->business_category_id,
            'province_id' => $request->province_id,
            'city_id' => $request->city_id,
            'address' => $request->address,
            'lat' => $request->lat,
            'lang' => $request->lang,
            'whatsapp' => $request->whatsapp,
            'telegram' => $request->telegram,
            'instagram' => $request->instagram,
            'website' => $request->website,
            'support_phone_number' => $request->support_phone_number,
            'bio' => $request->bio,
        ]);

        // Sync business subcategories (for multi-select)
        $salon->businessSubcategories()->sync($request->input('business_subcategory_ids', []));

        // Update owner (user) information
        if ($salon->user) {
            $salon->user->update([
                'name' => $request->owner_name,
                'email' => $request->owner_email,
            ]);
            $salon->user->refresh();
        } else {
            // Handle case where owner might not exist (e.g., create a new user)
            // For now, we assume an owner always exists for an existing salon.
        }

        return redirect()->route('admin.salons.show', $salon->id)->with('success', 'اطلاعات سالن با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Toggle the active status of the specified salon.
     */
    public function toggleStatus(Salon $salon)
    {
        $salon->is_active = !$salon->is_active;
        $salon->save();

        return back()->with('success', 'وضعیت سالن با موفقیت تغییر یافت.');
    }

    /**
     * Reset the password for the salon's owner.
     */
    public function resetPassword(Request $request, Salon $salon)
    {
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'new_password.required' => 'رمز عبور جدید الزامی است.',
            'new_password.min' => 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد.',
            'new_password.confirmed' => 'تایید رمز عبور جدید با رمز عبور مطابقت ندارد.',
        ]);

        $salon->user->password = Hash::make($request->new_password);
        $salon->user->save();

        return back()->with('success', 'رمز عبور مالک سالن با موفقیت بازنشانی شد.');
    }

    /**
     * Display purchase history for the specified salon.
     */
    public function purchaseHistory(Salon $salon)
    {
        $purchaseTransactions = $salon->smsTransactions()
                                      ->whereNotNull('sms_package_id')
                                      ->where('status', 'completed')
                                      ->latest()
                                      ->paginate(10);

        return view('admin.salons.purchase_history', compact('salon', 'purchaseTransactions'));
    }

    /**
     * Send bulk SMS gift to filtered salons.
     */
    public function bulkSmsGift(Request $request, SmsService $smsService)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'message' => 'nullable|string|max:500',
            'filter_status' => 'nullable|boolean',
            'filter_city_id' => 'nullable|exists:cities,id',
            'filter_salon_id' => 'nullable|exists:salons,id',
        ]);

        $query = Salon::query();

        if ($request->filled('filter_salon_id')) {
            $query->where('id', $request->filter_salon_id);
        } else {
            if ($request->filled('filter_status')) {
                $query->where('is_active', $request->filter_status);
            }
            if ($request->filled('filter_city_id')) {
                $query->where('city_id', $request->filter_city_id);
            }
        }

        $salons = $query->get();
        $amount = $request->amount;
        $message = $request->message;
        $count = 0;

        foreach ($salons as $salon) {
            // Ensure salon has an SMS balance record, create if not
            $smsBalance = $salon->smsBalance()->firstOrCreate(['salon_id' => $salon->id], ['balance' => 0]);
            $smsBalance->balance += $amount;
            $smsBalance->save();

            // Record transaction
            SmsTransaction::create([
                'salon_id' => $salon->id,
                'type' => 'gift',
                'amount' => $amount,
                'description' => 'شارژ هدیه توسط ادمین' . ($message ? ': ' . $message : ''),
                'receptor' => $salon->user->mobile,
                'content' => $message,
                'status' => 'delivered',
            ]);

            // Optionally send a notification SMS
            if ($message && $salon->user->mobile) {
                $smsService->sendSms($salon->user->mobile, $message);
            }
            $count++;
        }

        return back()->with('success', "شارژ پیامک هدیه برای {$count} سالن با موفقیت انجام شد.");
    }

    /**
     * Store a new note for the specified salon.
     */
    public function storeNote(Request $request, Salon $salon)
    {
        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $salon->notes()->create([
            'user_id' => auth()->id(),
            'content' => $request->note,
        ]);

        return back()->with('success', 'یادداشت با موفقیت ثبت شد.');
    }

    /**
     * Add SMS credit to the specified salon.
     */
    public function addSmsCredit(Request $request, Salon $salon)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        $smsBalance = $salon->smsBalance()->firstOrCreate(
            ['salon_id' => $salon->id],
            ['balance' => 0]
        );
        $smsBalance->balance += $request->amount;
        $smsBalance->save();

        // Reload the salon and its smsBalance to ensure fresh data
        $salon->refresh();
        $salon->load('smsBalance');

        SmsTransaction::create([
            'salon_id' => $salon->id,
            'type' => 'gift',
            'amount' => $request->amount,
            'description' => $request->description ?? 'شارژ هدیه توسط ادمین',
            'status' => 'completed',
        ]);

        return back()->with('success', 'اعتبار پیامک با موفقیت به سالن اضافه شد.');
    }
}
