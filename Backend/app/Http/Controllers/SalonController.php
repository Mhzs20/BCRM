<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSalonRequest;
use App\Http\Requests\UpdateSalonRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SalonController extends Controller
{
    /**

     */
    public function getUserSalons(): JsonResponse
    {
        try {
            $user = Auth::user();
            $salons = $user->salons()->with(['businessCategory', 'businessSubcategory', 'province', 'city'])->get();

            return response()->json([
                'status' => 'success',
                'message' => 'لیست سالن‌ها با موفقیت دریافت شد.',
                'data' => $salons
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching user salons for user ID " . (Auth::id() ?? 'unknown') . ": " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در دریافت لیست سالن‌ها.',
                'data' => null
            ], 500);
        }
    }

    /**
     */
    public function createSalon(StoreSalonRequest $request): JsonResponse // استفاده از FormRequest
    {


        DB::beginTransaction();
        try {
            $user = Auth::user();
            $validatedData = $request->validated();
            $validatedData['user_id'] = $user->id; // مالکیت به کاربر فعلی اختصاص داده می‌شود
            $validatedData['credit_score'] = $validatedData['credit_score'] ?? 0;

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $path = $request->file('image')->store('public/salon_images');
                $validatedData['image'] = Storage::url($path);
            }

            $salon = Salon::create($validatedData);

            if (!$user->active_salon_id && $salon) {
                $user->active_salon_id = $salon->id;
                $user->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'سالن با موفقیت ایجاد شد.',
                'data' => $salon->load(['businessCategory', 'businessSubcategory', 'province', 'city'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Salon creation failed for user ID " . (Auth::id() ?? 'unknown') . ": " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ایجاد سالن: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     */
    public function updateSalon(UpdateSalonRequest $request, Salon $salon): JsonResponse // استفاده از FormRequest و Route Model Binding
    {


        DB::beginTransaction();
        try {
            $validatedData = $request->validated();

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                if ($salon->image) {
                    $oldImagePath = str_replace(Storage::url(''), 'public/', $salon->image);
                    if (Storage::exists($oldImagePath)) {
                        Storage::delete($oldImagePath);
                    }
                }
                $path = $request->file('image')->store('public/salon_images');
                $validatedData['image'] = Storage::url($path);
            } elseif ($request->boolean('remove_image') && $salon->image) {
                $oldImagePath = str_replace(Storage::url(''), 'public/', $salon->image);
                if (Storage::exists($oldImagePath)) {
                    Storage::delete($oldImagePath);
                }
                $validatedData['image'] = null;
            }

            $salon->update($validatedData);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'سالن با موفقیت ویرایش شد.',
                'data' => $salon->fresh()->load(['businessCategory', 'businessSubcategory', 'province', 'city'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Salon update failed for ID {$salon->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ویرایش سالن: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**

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
            return response()->json([
                'status' => 'success',
                'data' => $salon->load(['businessCategory', 'businessSubcategory', 'province', 'city'])
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
