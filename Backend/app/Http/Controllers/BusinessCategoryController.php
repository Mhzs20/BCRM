<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\BusinessSubcategory;
use Illuminate\Http\JsonResponse;

class BusinessCategoryController extends Controller
{
    /**
     * دریافت تمام دسته‌بندی‌های کسب و کار
     *
     * @return JsonResponse
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = BusinessCategory::all();
            
            return response()->json([
                'status' => 'success',
                'message' => 'دسته‌بندی‌ها با موفقیت دریافت شدند.',
                'data' => $categories
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
     * دریافت زیرمجموعه‌های یک دسته‌بندی
     *
     * @param int $categoryId
     * @return JsonResponse
     */
    public function getSubcategories(int $categoryId): JsonResponse
    {
        try {
            $subcategories = BusinessSubcategory::where('category_id', $categoryId)->get();
            
            return response()->json([
                'status' => 'success',
                'message' => 'زیرمجموعه‌ها با موفقیت دریافت شدند.',
                'data' => $subcategories
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
     * دریافت تمام دسته‌بندی‌ها به همراه زیرمجموعه‌های آنها
     *
     * @return JsonResponse
     */
    public function getAllCategoriesWithSubcategories(): JsonResponse
    {
        try {
            $categories = BusinessCategory::with('subcategories')->get();
            
            return response()->json([
                'status' => 'success',
                'message' => 'دسته‌بندی‌ها و زیرمجموعه‌ها با موفقیت دریافت شدند.',
                'data' => $categories
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