<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * getProvinces
     *
     * @return JsonResponse
     */
    public function getProvinces(): JsonResponse
    {
        try {
            $provinces = Province::orderBy('name')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'استان‌ها با موفقیت دریافت شدند.',
                'data' => $provinces
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
     * getCities
     *
     * @param int $provinceId
     * @return JsonResponse
     */
    public function getCities(int $provinceId): JsonResponse
    {
        try {
            $cities = City::where('province_id', $provinceId)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'شهرها با موفقیت دریافت شدند.',
                'data' => $cities
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
     * getAllProvincesWithCitiesا
     *
     * @return JsonResponse
     */
    public function getAllProvincesWithCities(): JsonResponse
    {
        try {
            $provinces = Province::with('cities')->orderBy('name')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'استان‌ها و شهرها با موفقیت دریافت شدند.',
                'data' => $provinces
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
