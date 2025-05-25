<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SalonController extends Controller
{
    /**
     *     /**
     * 
     * /** @var \App\Models\User $user
     *  */
    
    public function getUserSalons(): JsonResponse
    {
        try {
            $user = Auth::user();
            $salons = $user->salons;

            return response()->json([
                'status' => 'success',
                'message' => 'لیست سالن‌ها با موفقیت دریافت شد.',
                'data' => $salons
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * ایجاد سالن جدید
     */
    public function createSalon(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'business_category_id' => 'required|exists:business_categories,id',
                'business_subcategory_id' => 'required|exists:business_subcategories,id',
                'province_id' => 'required|exists:provinces,id',
                'city_id' => 'required|exists:cities,id',
                'image' => 'nullable|image|max:2048',
            ], [
                'name.required' => 'نام سالن الزامی است',
                'business_category_id.required' => 'انتخاب دسته‌بندی کسب و کار الزامی است',
                'business_category_id.exists' => 'دسته‌بندی کسب و کار انتخاب شده معتبر نیست',
                'business_subcategory_id.required' => 'انتخاب زیرمجموعه کسب و کار الزامی است',
                'business_subcategory_id.exists' => 'زیرمجموعه کسب و کار انتخاب شده معتبر نیست',
                'province_id.required' => 'انتخاب استان الزامی است',
                'province_id.exists' => 'استان انتخاب شده معتبر نیست',
                'city_id.required' => 'انتخاب شهر الزامی است',
                'city_id.exists' => 'شهر انتخاب شده معتبر نیست',
                'image.image' => 'فایل آپلود شده باید تصویر باشد',
                'image.max' => 'حداکثر حجم تصویر ۲ مگابایت است',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'اطلاعات وارد شده نامعتبر است',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 422);
            }
            
            // بررسی تطابق استان و شهر
            $city = City::find($request->city_id);
            if ($city->province_id != $request->province_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'شهر انتخاب شده با استان مطابقت ندارد',
                    'data' => null
                ], 422);
            }

            DB::beginTransaction();

            $user = Auth::user();
            $salonData = [
                'name' => $request->name,
                'user_id' => $user->id,
                'business_category_id' => $request->business_category_id,
                'business_subcategory_id' => $request->business_subcategory_id,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'credit_score' => 0,
            ];

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->storeAs('public/salon_images', $imageName);
                $salonData['image'] = 'salon_images/' . $imageName;
            }

            $salon = Salon::create($salonData);

            if (!$user->active_salon_id) {
                $user->active_salon_id = $salon->id;
                $user->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'سالن با موفقیت ایجاد شد.',
                'data' => $salon
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * ویرایش اطلاعات یک سالن
     */
    public function updateSalon(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'business_category_id' => 'required|exists:business_categories,id',
                'business_subcategory_id' => 'required|exists:business_subcategories,id',
                'province_id' => 'required|exists:provinces,id',
                'city_id' => 'required|exists:cities,id',
                'image' => 'nullable|image|max:2048',
            ], [
                'name.required' => 'نام سالن الزامی است',
                'business_category_id.required' => 'انتخاب دسته‌بندی کسب و کار الزامی است',
                'business_category_id.exists' => 'دسته‌بندی کسب و کار انتخاب شده معتبر نیست',
                'business_subcategory_id.required' => 'انتخاب زیرمجموعه کسب و کار الزامی است',
                'business_subcategory_id.exists' => 'زیرمجموعه کسب و کار انتخاب شده معتبر نیست',
                'province_id.required' => 'انتخاب استان الزامی است',
                'province_id.exists' => 'استان انتخاب شده معتبر نیست',
                'city_id.required' => 'انتخاب شهر الزامی است',
                'city_id.exists' => 'شهر انتخاب شده معتبر نیست',
                'image.image' => 'فایل آپلود شده باید تصویر باشد',
                'image.max' => 'حداکثر حجم تصویر ۲ مگابایت است',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'اطلاعات وارد شده نامعتبر است',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 422);
            }
            
            // بررسی تطابق استان و شهر
            $city = City::find($request->city_id);
            if ($city->province_id != $request->province_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'شهر انتخاب شده با استان مطابقت ندارد',
                    'data' => null
                ], 422);
            }

            DB::beginTransaction();

            $user = Auth::user();
            $salon = Salon::where('id', $id)->where('user_id', $user->id)->first();

            if (!$salon) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'سالن مورد نظر یافت نشد یا متعلق به شما نیست.',
                    'data' => null
                ], 404);
            }

            $salonData = [
                'name' => $request->name,
                'business_category_id' => $request->business_category_id,
                'business_subcategory_id' => $request->business_subcategory_id,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
            ];

            if ($request->hasFile('image')) {
                if ($salon->image) {
                    Storage::delete('public/' . $salon->image);
                }

                $image = $request->file('image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->storeAs('public/salon_images', $imageName);
                $salonData['image'] = 'salon_images/' . $imageName;
            }

            $salon->update($salonData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'سالن با موفقیت ویرایش شد.',
                'data' => $salon
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * دریافت سالن فعال کاربر
     */
    public function getActiveSalon(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->active_salon_id || !$user->salons()->where('id', $user->active_salon_id)->exists()) {
                $firstSalon = $user->salons()->first();
                if (!$firstSalon) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'شما هیچ سالنی ندارید.',
                        'data' => null
                    ], 404);
                }

                $user->active_salon_id = $firstSalon->id;
                $user->save();
                $salon = $firstSalon;
            } else {
                $salon = Salon::find($user->active_salon_id);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'سالن فعال با موفقیت دریافت شد.',
                'data' => $salon
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    /**
     * 
     * /** @var \App\Models\User $user
     *  */
     
    public function selectActiveSalon(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $salon = Salon::where('id', $id)->where('user_id', $user->id)->first();

            if (!$salon) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'سالن مورد نظر یافت نشد یا متعلق به شما نیست.',
                    'data' => null
                ], 404);
            }

            $user->active_salon_id = $salon->id;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'سالن فعال با موفقیت انتخاب شد.',
                'data' => $salon
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }
}
