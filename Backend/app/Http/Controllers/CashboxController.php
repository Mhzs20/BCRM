<?php

namespace App\Http\Controllers;

use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Salon;
use App\Services\CashboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CashboxController extends Controller
{
    protected $cashboxService;

    public function __construct(CashboxService $cashboxService)
    {
        $this->cashboxService = $cashboxService;
    }

    /**
     * لیست صندوق‌ها
     */
    public function index(Request $request, Salon $salon)
    {
        $query = Cashbox::forSalon($salon->id)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        $cashboxes = $query->get();

        $summary = $this->cashboxService->getCashboxesSummary($salon->id);

        return response()->json([
            'success' => true,
            'cashboxes' => $cashboxes,
            'summary' => $summary,
        ]);
    }

    /**
     * ایجاد صندوق جدید
     */
    public function store(Request $request, Salon $salon)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:cash,pos,bank_account,online',
            'initial_balance' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ], [
            'name.required' => 'نام صندوق الزامی است',
            'type.required' => 'نوع صندوق الزامی است',
            'type.in' => 'نوع صندوق نامعتبر است',
            'initial_balance.numeric' => 'موجودی اولیه باید عدد باشد',
            'initial_balance.min' => 'موجودی اولیه نمی‌تواند منفی باشد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['salon_id'] = $salon->id;

        $cashbox = $this->cashboxService->createCashbox($data);

        return response()->json([
            'success' => true,
            'message' => 'صندوق با موفقیت ایجاد شد',
            'cashbox' => $cashbox,
        ], 201);
    }

    /**
     * نمایش جزئیات صندوق
     */
    public function show(Salon $salon, $id)
    {
        $cashbox = Cashbox::forSalon($salon->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'cashbox' => $cashbox,
        ]);
    }

    /**
     * ویرایش صندوق
     */
    public function update(Request $request, Salon $salon, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:cash,pos,bank_account,online',
            'initial_balance' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ], [
            'name.required' => 'نام صندوق الزامی است',
            'type.in' => 'نوع صندوق نامعتبر است',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $cashbox = Cashbox::forSalon($salon->id)->findOrFail($id);
        $cashbox->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'صندوق با موفقیت به‌روزرسانی شد',
            'cashbox' => $cashbox->fresh(),
        ]);
    }

    /**
     * حذف صندوق
     */
    public function destroy(Salon $salon, $id)
    {
        $cashbox = Cashbox::forSalon($salon->id)->findOrFail($id);

        // بررسی وجود تراکنش
        if ($cashbox->transactions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'امکان حذف صندوق با تراکنش وجود ندارد',
            ], 422);
        }

        $cashbox->delete();

        return response()->json([
            'success' => true,
            'message' => 'صندوق با موفقیت حذف شد',
        ]);
    }

    /**
     * ثبت دریافتی
     */
    public function recordIncome(Request $request, Salon $salon)
    {
        $validator = Validator::make($request->all(), [
            'cashbox_id' => 'required|exists:cashboxes,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:transaction_categories,id',
            'subcategory_id' => 'nullable|exists:transaction_subcategories,id',
            'category' => 'nullable|string',
            'subcategory' => 'nullable|string',
            'transaction_date' => 'nullable|date',
            'transaction_time' => 'nullable|string',
        ], [
            'cashbox_id.required' => 'انتخاب صندوق الزامی است',
            'cashbox_id.exists' => 'صندوق انتخابی یافت نشد',
            'amount.required' => 'مبلغ الزامی است',
            'amount.min' => 'مبلغ باید بیشتر از صفر باشد',
            'category_id.exists' => 'دسته‌بندی انتخابی یافت نشد',
            'subcategory_id.exists' => 'زیردسته انتخابی یافت نشد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = auth()->id();

        $result = $this->cashboxService->recordIncome($data);

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    /**
     * ثبت پرداختی
     */
    public function recordExpense(Request $request, Salon $salon)
    {
        $validator = Validator::make($request->all(), [
            'cashbox_id' => 'required|exists:cashboxes,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:transaction_categories,id',
            'subcategory_id' => 'nullable|exists:transaction_subcategories,id',
            'category' => 'nullable|string',
            'subcategory' => 'nullable|string',
            'transaction_date' => 'nullable|date',
            'transaction_time' => 'nullable|string',
        ], [
            'cashbox_id.required' => 'انتخاب صندوق الزامی است',
            'cashbox_id.exists' => 'صندوق انتخابی یافت نشد',
            'amount.required' => 'مبلغ الزامی است',
            'amount.min' => 'مبلغ باید بیشتر از صفر باشد',
            'category_id.exists' => 'دسته‌بندی انتخابی یافت نشد',
            'subcategory_id.exists' => 'زیردسته انتخابی یافت نشد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = auth()->id();

        $result = $this->cashboxService->recordExpense($data);

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    /**
     * انتقال موجودی
     */
    public function transfer(Request $request, Salon $salon)
    {
        $validator = Validator::make($request->all(), [
            'from_cashbox_id' => 'required|exists:cashboxes,id',
            'to_cashbox_id' => 'required|exists:cashboxes,id|different:from_cashbox_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'transaction_date' => 'nullable|date',
            'transaction_time' => 'nullable|string',
        ], [
            'from_cashbox_id.required' => 'انتخاب صندوق مبدأ الزامی است',
            'to_cashbox_id.required' => 'انتخاب صندوق مقصد الزامی است',
            'to_cashbox_id.different' => 'صندوق مبدأ و مقصد نمی‌توانند یکسان باشند',
            'amount.required' => 'مبلغ الزامی است',
            'amount.min' => 'مبلغ باید بیشتر از صفر باشد',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = auth()->id();

        $result = $this->cashboxService->transferBetweenCashboxes($data);

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    /**
     * گزارش تراکنش‌های صندوق
     */
    public function transactions(Request $request, Salon $salon, $id)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|in:income,expense,transfer',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $cashbox = Cashbox::forSalon($salon->id)->findOrFail($id);

        $query = CashboxTransaction::forCashbox($id)
            ->with(['fromCashbox', 'toCashbox', 'cashbox', 'payment', 'expense', 'commissionTransaction'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $perPage = $request->input('per_page', 20);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'cashbox' => $cashbox,
            'transactions' => $transactions,
        ]);
    }

    /**
     * لیست تراکنش‌های همه صندوق‌ها
     */
    public function allTransactions(Request $request, Salon $salon)
    {
        $validator = Validator::make($request->all(), [
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'type'        => 'nullable|in:income,expense,transfer',
            'category'    => 'nullable|string',
            'cashbox_id'  => 'nullable|integer',
            'per_page'    => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $query = CashboxTransaction::forSalon($salon->id)
            ->with(['cashbox', 'fromCashbox', 'toCashbox', 'payment', 'expense', 'commissionTransaction'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        } elseif ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('cashbox_id')) {
            $query->forCashbox($request->cashbox_id);
        }

        $perPage = $request->input('per_page', 20);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }

    /**
     * محاسبه مجدد موجودی
     */
    public function recalculateBalance(Salon $salon, $id)
    {
        $result = $this->cashboxService->recalculateCashboxBalance($id);

        return response()->json([
            'success' => true,
            'message' => 'موجودی مجدداً محاسبه شد',
            'result' => $result,
        ]);
    }

    /**
     * خلاصه داشبورد صندوق‌ها
     */
    public function dashboard(Salon $salon)
    {
        $summary = $this->cashboxService->getCashboxesSummary($salon->id);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }
}
