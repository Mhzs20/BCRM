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

class SalonController extends Controller
{
    /**

     */
    public function getUserSalons(): JsonResponse
    {
        $user = Auth::user();
        $salons = $user->salons()->with(['businessCategory', 'businessSubcategory', 'province', 'city'])->get();
        return response()->json(['status' => 'success', 'data' => $salons]);
    }

    public function createSalon(StoreSalonRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();
            $validatedData['user_id'] = Auth::id();

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('public/salon_images');
                $validatedData['image'] = Storage::url($path);
            }

            $salon = Salon::create($validatedData);

            if (!Auth::user()->active_salon_id) {
                Auth::user()->update(['active_salon_id' => $salon->id]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'سالن با موفقیت ایجاد شد.',
                'data' => $salon->load(['businessCategory', 'businessSubcategory', 'province', 'city'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Salon Creation Failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
                    'data' => $salon->load(['businessCategory', 'businessSubcategory', 'province', 'city'])
                ]);
            }

            if ($request->boolean('remove_image')) {
                if ($salon->image) {
                    Storage::delete(str_replace('/storage', 'public', $salon->image));
                    $validatedData['image'] = null;
                }
            }

            if ($request->hasFile('image')) {
                if ($salon->image) {
                    Storage::delete(str_replace('/storage', 'public', $salon->image));
                }
                $imagePath = $request->file('image')->store('public/salon_images');
                $validatedData['image'] = str_replace('public', '/storage', $imagePath);
            }

            unset($validatedData['remove_image']);

            $salon->update($validatedData);

            return response()->json([
                'status' => 'success',
                'message' => 'اطلاعات سالن با موفقیت ویرایش شد.',
                'data' => $salon->load(['businessCategory', 'businessSubcategory', 'province', 'city'])
            ]);

        } catch (Exception $e) {
            Log::error("Salon Update Failed for ID {$salon->id}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
            $activeSalon = $user->activeSalon()->with(['businessCategory', 'businessSubcategory', 'province', 'city'])->first();

            if (!$activeSalon) {
                $firstSalon = $user->salons()->with(['businessCategory', 'businessSubcategory', 'province', 'city'])->first();
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
            Log::error("Error fetching active salon for user ID " . (Auth::id() ?? 'unknown') . ": " . $e->getMessage());
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
                'data' => $salon->load(['businessCategory', 'businessSubcategory', 'province', 'city'])
            ]);
        } catch (\Exception $e) {
            Log::error("Select active salon failed for salon ID {$salon->id}, user ID " . (Auth::id() ?? 'unknown') . ": " . $e->getMessage());
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
            $salon->load(['businessCategory', 'businessSubcategory', 'province', 'city'])
                ->loadCount(['customers', 'appointments']);

            $user = Auth::user();
            $smsBalance = $user->smsBalance;

            $expiresInDays = null;
            if ($smsBalance && $smsBalance->package_expires_at) {
                $expiresInDays = max(0, Carbon::now()->diffInDays($smsBalance->package_expires_at, false));
            }

            $salonData = $salon->toArray();

            $salonData['sms_balance'] = $smsBalance ? $smsBalance->balance : 0;
            $salonData['sms_package_expires_in_days'] = $expiresInDays;

            return response()->json([
                'status' => 'success',
                'data' => $salonData
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::warning("SalonController@getSalon access denied for user ID " . Auth::id() . " to salon ID {$salon->id}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'شما اجازه دسترسی به این سالن را ندارید.'], 403);
        } catch (\Exception $e) {
            Log::error("Error in SalonController@getSalon for salon ID {$salon->id}: " . $e->getMessage());
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
            Log::warning("SalonController@deleteSalon access denied for user ID " . Auth::id() . " to salon ID {$salon->id}: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'شما اجازه حذف این سالن را ندارید.'], 403);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Salon deletion failed for ID {$salon->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در حذف سالن: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
