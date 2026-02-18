<?php

namespace App\Http\Controllers;

use App\Models\TransactionCategory;
use App\Models\TransactionSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionCategoryController extends Controller
{
    /**
     * لیست دسته‌بندی‌ها
     */
    public function index(Request $request, $salon)
    {
        $query = TransactionCategory::forSalon($salon->id)
            ->with(['activeSubcategories'])
            ->ordered();

        // فیلتر بر اساس نوع (income, expense, both)
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // فیلتر بر اساس وضعیت
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // نمایش یا عدم نمایش دسته‌های سیستمی
        if ($request->has('include_system') && $request->include_system == false) {
            $query->where('is_system', false);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * ایجاد دسته‌بندی جدید
     */
    public function store(Request $request, $salon)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:both,income,expense',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'name.required' => 'نام دسته‌بندی الزامی است',
            'type.in' => 'نوع دسته‌بندی نامعتبر است',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['salon_id'] = $salon->id;
        $data['is_system'] = false; // دسته‌های کاربر همیشه غیرسیستمی هستند

        $category = TransactionCategory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'دسته‌بندی با موفقیت ایجاد شد',
            'category' => $category,
        ], 201);
    }

    /**
     * نمایش جزئیات دسته‌بندی
     */
    public function show($salon, $id)
    {
        $category = TransactionCategory::forSalon($salon->id)
            ->with(['subcategories' => function ($q) {
                $q->ordered();
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'category' => $category,
        ]);
    }

    /**
     * ویرایش دسته‌بندی
     */
    public function update(Request $request, $salon, $id)
    {
        $category = TransactionCategory::forSalon($salon->id)->findOrFail($id);

        // بررسی امکان ویرایش
        if ($category->isSystem()) {
            return response()->json([
                'success' => false,
                'message' => 'دسته‌بندی‌های سیستمی قابل ویرایش نیستند',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'nullable|in:both,income,expense',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'name.required' => 'نام دسته‌بندی الزامی است',
            'type.in' => 'نوع دسته‌بندی نامعتبر است',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $category->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'دسته‌بندی با موفقیت به‌روزرسانی شد',
            'category' => $category->fresh(),
        ]);
    }

    /**
     * حذف دسته‌بندی
     */
    public function destroy($salon, $id)
    {
        $category = TransactionCategory::forSalon($salon->id)->findOrFail($id);

        // بررسی امکان حذف
        if (!$category->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'دسته‌بندی‌های سیستمی قابل حذف نیستند',
            ], 403);
        }

        // حذف نرم - زیردسته‌ها هم به صورت خودکار حذف می‌شوند (cascade)
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'دسته‌بندی با موفقیت حذف شد',
        ]);
    }

    /**
     * لیست زیردسته‌های یک دسته‌بندی
     */
    public function subcategories(Request $request, $salon, $categoryId)
    {
        // بررسی وجود دسته‌بندی
        $category = TransactionCategory::forSalon($salon->id)->findOrFail($categoryId);

        $query = TransactionSubcategory::forCategory($categoryId)
            ->forSalon($salon->id)
            ->with(['service'])
            ->ordered();

        // فیلتر بر اساس وضعیت
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $subcategories = $query->get();

        return response()->json([
            'success' => true,
            'category' => $category,
            'subcategories' => $subcategories,
        ]);
    }

    /**
     * ایجاد زیردسته جدید
     */
    public function storeSubcategory(Request $request, $salon, $categoryId)
    {
        // بررسی وجود دسته‌بندی
        $category = TransactionCategory::forSalon($salon->id)->findOrFail($categoryId);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_id' => 'nullable|exists:services,id',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ], [
            'name.required' => 'نام زیردسته الزامی است',
            'service_id.exists' => 'خدمت انتخابی یافت نشد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['category_id'] = $categoryId;
        $data['salon_id'] = $salon->id;

        $subcategory = TransactionSubcategory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'زیردسته با موفقیت ایجاد شد',
            'subcategory' => $subcategory->load('service'),
        ], 201);
    }

    /**
     * نمایش جزئیات زیردسته
     */
    public function showSubcategory($salon, $categoryId, $subcategoryId)
    {
        $subcategory = TransactionSubcategory::forCategory($categoryId)
            ->forSalon($salon->id)
            ->with(['category', 'service'])
            ->findOrFail($subcategoryId);

        return response()->json([
            'success' => true,
            'subcategory' => $subcategory,
        ]);
    }

    /**
     * ویرایش زیردسته
     */
    public function updateSubcategory(Request $request, $salon, $categoryId, $subcategoryId)
    {
        $subcategory = TransactionSubcategory::forCategory($categoryId)
            ->forSalon($salon->id)
            ->findOrFail($subcategoryId);

        // اگر زیردسته به خدمات لینک است، نمی‌شود نام آن را تغییر داد
        $rules = [
            'description' => 'nullable|string',
            'service_id' => 'nullable|exists:services,id',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];

        if (!$subcategory->isLinkedToService()) {
            $rules['name'] = 'sometimes|required|string|max:255';
        }

        $validator = Validator::make($request->all(), $rules, [
            'name.required' => 'نام زیردسته الزامی است',
            'service_id.exists' => 'خدمت انتخابی یافت نشد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $subcategory->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'زیردسته با موفقیت به‌روزرسانی شد',
            'subcategory' => $subcategory->fresh(['service']),
        ]);
    }

    /**
     * حذف زیردسته
     */
    public function destroySubcategory($salon, $categoryId, $subcategoryId)
    {
        $subcategory = TransactionSubcategory::forCategory($categoryId)
            ->forSalon($salon->id)
            ->findOrFail($subcategoryId);

        // اگر زیردسته به خدمات لینک است، نباید حذف شود
        if ($subcategory->isLinkedToService()) {
            return response()->json([
                'success' => false,
                'message' => 'زیردسته‌های مرتبط با خدمات قابل حذف نیستند',
            ], 403);
        }

        $subcategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'زیردسته با موفقیت حذف شد',
        ]);
    }
}
