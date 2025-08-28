<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreSalonRequest;
use App\Http\Requests\UpdateSalonRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Staff;
use Carbon\Carbon;
use Exception;

class SalonController extends Controller
{
    /**

     */
    public function getUserSalons(): JsonResponse
    {
        $user = Auth::user();
        $salons = $user->salons()->with(['businessCategory', 'businessSubcategories', 'province', 'city'])->get();
        return response()->json(['status' => 'success', 'data' => $salons]);
    }

    public function createSalon(StoreSalonRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();
            $validatedData['user_id'] = Auth::id();

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('salon_images', 'public');
                $validatedData['image'] = $path;
            }

            $salon = Salon::create($validatedData);

            // Create the salon owner as the first staff member
            $owner = Auth::user();
            Staff::create([
                'salon_id' => $salon->id,
                'full_name' => $owner->name, 
                'phone_number' => $owner->mobile,
                'specialty' => 'مدیر سالن', 
                'is_active' => true,
                'hire_date' => Carbon::now(),
            ]);

            if (!Auth::user()->active_salon_id) {
                Auth::user()->update(['active_salon_id' => $salon->id]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'سالن با موفقیت ایجاد شد.',
                'data' => $salon->load(['businessCategory', 'businessSubcategories', 'province', 'city'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("خطا در ایجاد سالن: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'مشکلی در هنگام ایجاد سالن رخ داد. لطفاً با پشتیبانی تماس بگیرید.'
            ], 500);
        }
    }
    public function updateSalon(UpdateSalonRequest $request, Salon $salon): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            if (empty($validatedData) && !$request->hasFile('image') && !$request->boolean('remove_image')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'هیچ اطلاعات جدیدی برای ویرایش ارسال نشده است.',
                    'data' => $salon->load(['businessCategory', 'businessSubcategories', 'province', 'city'])
                ]);
            }

            if ($request->boolean('remove_image')) {
                if ($salon->image) {
                    Storage::disk('public')->delete($salon->getRawOriginal('image'));
                    $validatedData['image'] = null;
                }
            }

            if ($request->hasFile('image')) {
                if ($salon->image) {
                    Storage::disk('public')->delete($salon->getRawOriginal('image'));
                }
                $imagePath = $request->file('image')->store('salon_images', 'public');
                $validatedData['image'] = $imagePath;
            }

            unset($validatedData['remove_image']);

            if (isset($validatedData['latitude'])) {
                $validatedData['lat'] = $validatedData['latitude'];
                unset($validatedData['latitude']);
            }

            if (isset($validatedData['longitude'])) {
                $validatedData['lang'] = $validatedData['longitude'];
                unset($validatedData['longitude']);
            }

            if (isset($validatedData['business_subcategory_ids'])) {
                $salon->businessSubcategories()->sync($validatedData['business_subcategory_ids']);
                unset($validatedData['business_subcategory_ids']);
            }

            $salon->update($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'اطلاعات سالن با موفقیت ویرایش شد.',
                'data' => $salon->load(['businessCategory', 'businessSubcategories', 'province', 'city'])
            ]);

        } catch (Exception $e) {
            Log::error("خطا در ویرایش سالن با شناسه {$salon->id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در هنگام ویرایش اطلاعات سالن رخ داد.'
            ], 500);
        }
    }    /**

     */
    public function getActiveSalon(): JsonResponse
    {
        try {
            $user = Auth::user();
            $activeSalon = $user->activeSalon()->with(['businessCategory', 'businessSubcategories', 'province', 'city'])->first();

            if (!$activeSalon) {
                $firstSalon = $user->salons()->with(['businessCategory', 'businessSubcategories', 'province', 'city'])->first();
                if (!$firstSalon) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'شما هیچ سالنی ندارید.',
                        'data' => null
                    ], 404);
                }
                $user->active_salon_id = $firstSalon->id;
                $user->save();
                $activeSalon = $firstSalon;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'سالن فعال با موفقیت دریافت شد.',
                'data' => $activeSalon
            ]);
        } catch (\Exception $e) {
            Log::error("خطا در دریافت سالن فعال برای کاربر با شناسه " . (Auth::id() ?? 'ناشناس') . ": " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت سالن فعال: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     */
    public function selectActiveSalon(Salon $salon): JsonResponse
    {
        $this->authorize('selectActive', $salon);

        try {
            $user = Auth::user();
            $user->active_salon_id = $salon->id;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'سالن فعال با موفقیت انتخاب شد.',
                'data' => $salon->load(['businessCategory', 'businessSubcategories', 'province', 'city'])
            ]);
        } catch (\Exception $e) {
            Log::error("خطا در انتخاب سالن فعال برای سالن با شناسه {$salon->id} و کاربر با شناسه " . (Auth::id() ?? 'ناشناس') . ": " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در انتخاب سالن فعال: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    /**
     */
    public function getSalon(Salon $salon): JsonResponse
    {
        $this->authorize('view', $salon);

        try {
            $salon->load(['businessCategory', 'businessSubcategories', 'province', 'city'])
                ->loadCount(['customers', 'appointments']);

            // Load the salon's SMS balance
            $salon->loadMissing('smsBalance');
            $salonSmsBalance = $salon->smsBalance;

            $expiresInDays = null;
            if ($salonSmsBalance && $salonSmsBalance->package_expires_at) {
                $expiresInDays = max(0, Carbon::now()->diffInDays($salonSmsBalance->package_expires_at, false));
            }

            $salonData = $salon->toArray();

            $salonData['sms_balance'] = $salonSmsBalance ? $salonSmsBalance->balance : 0;
            $salonData['sms_package_expires_in_days'] = $expiresInDays;

            return response()->json([
                'status' => 'success',
                'data' => $salonData
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning("دسترسی به متد getSalon در SalonController برای کاربر با شناسه " . Auth::id() . " به سالن با شناسه {$salon->id} رد شد: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'شما اجازه دسترسی به این سالن را ندارید.'], 403);
        } catch (\Exception $e) {
            Log::error("خطا در متد getSalon در SalonController برای سالن با شناسه {$salon->id}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'خطا در نمایش اطلاعات سالن.'], 500);
        }
    }

    /**
     */
    public function deleteSalon(Salon $salon): JsonResponse
    {
        $this->authorize('delete', $salon);
        DB::beginTransaction();
        try {
            User::where('active_salon_id', $salon->id)->update(['active_salon_id' => null]);

            if ($salon->image) {
                $imagePath = str_replace(Storage::url(''), 'public/', $salon->image);
                if (Storage::exists($imagePath)) {
                    Storage::delete($imagePath);
                }
            }
            // مدیریت حذف منابع وابسته (مشتریان، پرسنل، نوبت‌ها و ...) باید در اینجا یا با استفاده از model events انجام شود.
            // مثال:
            // $salon->appointments()->delete();
            // $salon->customers()->delete();
            // $salon->staff()->delete();
            // $salon->services()->delete();
            // $salon->payments()->delete();
            // $salon->smsTemplates()->delete();
            // $salon->activityLogs()->delete();

            $salon->delete();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'سالن مورد نظر با موفقیت حذف شد'
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            DB::rollBack();
            Log::warning("دسترسی به متد deleteSalon در SalonController برای کاربر با شناسه " . Auth::id() . " به سالن با شناسه {$salon->id} رد شد: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'شما اجازه حذف این سالن را ندارید.'], 403);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("خطا در حذف سالن با شناسه {$salon->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در حذف سالن: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
