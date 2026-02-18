<?php

namespace App\Services;

use App\Models\Cashbox;
use App\Models\CashboxTransaction;
use App\Models\Payment;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashboxService
{
    /**
     * ایجاد صندوق جدید
     */
    public function createCashbox(array $data): Cashbox
    {
        $cashbox = Cashbox::create($data);
        
        // موجودی اولیه = موجودی فعلی در ابتدا
        $cashbox->current_balance = $cashbox->initial_balance;
        $cashbox->save();

        return $cashbox;
    }

    /**
     * ثبت دریافتی (Income) - ورود پول به صندوق
     */
    public function recordIncome(array $data): array
    {
        DB::beginTransaction();
        
        try {
            $cashbox = Cashbox::findOrFail($data['cashbox_id']);

            // اگر category_id ارسال شده، نام رو از دیتابیس بگیر
            if (!empty($data['category_id']) && empty($data['category'])) {
                $category = \App\Models\TransactionCategory::find($data['category_id']);
                $data['category'] = $category ? $category->name : null;
            }

            // اگر subcategory_id ارسال شده، نام رو از دیتابیس بگیر
            if (!empty($data['subcategory_id']) && empty($data['subcategory'])) {
                $subcategory = \App\Models\TransactionSubcategory::find($data['subcategory_id']);
                $data['subcategory'] = $subcategory ? $subcategory->name : null;
            }

            // ایجاد تراکنش صندوق
            $transaction = CashboxTransaction::create([
                'salon_id' => $cashbox->salon_id,
                'type' => CashboxTransaction::TYPE_INCOME,
                'cashbox_id' => $cashbox->id,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'category' => $data['category'] ?? null,
                'subcategory' => $data['subcategory'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'transaction_time' => $data['transaction_time'] ?? now()->format('H:i:s'),
                'created_by' => $data['created_by'] ?? null,
            ]);

            // افزایش موجودی صندوق
            $cashbox->increaseBalance($data['amount']);

            // اگر ارجاع به payment دارد
            if (!empty($data['payment_id'])) {
                $transaction->payment_id = $data['payment_id'];
                $transaction->save();
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'دریافتی با موفقیت ثبت شد',
                'transaction' => $transaction->fresh(['cashbox']),
                'new_balance' => $cashbox->fresh()->current_balance,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording income: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در ثبت دریافتی: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ثبت پرداختی (Expense) - خروج پول از صندوق
     */
    public function recordExpense(array $data): array
    {
        DB::beginTransaction();
        
        try {
            $cashbox = Cashbox::findOrFail($data['cashbox_id']);

            // بررسی موجودی کافی
            if (!$cashbox->hasSufficientBalance($data['amount'])) {
                return [
                    'success' => false,
                    'message' => 'موجودی صندوق کافی نیست',
                    'current_balance' => $cashbox->current_balance,
                    'required_amount' => $data['amount'],
                ];
            }

            // اگر category_id ارسال شده، نام رو از دیتابیس بگیر
            if (!empty($data['category_id']) && empty($data['category'])) {
                $category = \App\Models\TransactionCategory::find($data['category_id']);
                $data['category'] = $category ? $category->name : null;
            }

            // اگر subcategory_id ارسال شده، نام رو از دیتابیس بگیر
            if (!empty($data['subcategory_id']) && empty($data['subcategory'])) {
                $subcategory = \App\Models\TransactionSubcategory::find($data['subcategory_id']);
                $data['subcategory'] = $subcategory ? $subcategory->name : null;
            }

            // ایجاد تراکنش صندوق
            $transaction = CashboxTransaction::create([
                'salon_id' => $cashbox->salon_id,
                'type' => CashboxTransaction::TYPE_EXPENSE,
                'cashbox_id' => $cashbox->id,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'category' => $data['category'] ?? null,
                'subcategory' => $data['subcategory'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'transaction_time' => $data['transaction_time'] ?? now()->format('H:i:s'),
                'created_by' => $data['created_by'] ?? null,
            ]);

            // کاهش موجودی صندوق
            $cashbox->decreaseBalance($data['amount']);

            // اگر ارجاع به expense دارد
            if (!empty($data['expense_id'])) {
                $transaction->expense_id = $data['expense_id'];
                $transaction->save();
            }

            // اگر ارجاع به commission دارد
            if (!empty($data['commission_transaction_id'])) {
                $transaction->commission_transaction_id = $data['commission_transaction_id'];
                $transaction->save();
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'پرداختی با موفقیت ثبت شد',
                'transaction' => $transaction->fresh(['cashbox']),
                'new_balance' => $cashbox->fresh()->current_balance,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording expense: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در ثبت پرداختی: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * انتقال موجودی بین دو صندوق
     */
    public function transferBetweenCashboxes(array $data): array
    {
        DB::beginTransaction();
        
        try {
            $fromCashbox = Cashbox::findOrFail($data['from_cashbox_id']);
            $toCashbox = Cashbox::findOrFail($data['to_cashbox_id']);

            // بررسی موجودی کافی در صندوق مبدأ
            if (!$fromCashbox->hasSufficientBalance($data['amount'])) {
                return [
                    'success' => false,
                    'message' => 'موجودی صندوق مبدأ کافی نیست',
                    'current_balance' => $fromCashbox->current_balance,
                    'required_amount' => $data['amount'],
                ];
            }

            // ایجاد تراکنش انتقال
            $transaction = CashboxTransaction::create([
                'salon_id' => $fromCashbox->salon_id,
                'type' => CashboxTransaction::TYPE_TRANSFER,
                'from_cashbox_id' => $fromCashbox->id,
                'to_cashbox_id' => $toCashbox->id,
                'amount' => $data['amount'],
                'description' => $data['description'] ?? 'انتقال موجودی',
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'transaction_time' => $data['transaction_time'] ?? now()->format('H:i:s'),
                'created_by' => $data['created_by'] ?? null,
            ]);

            // کاهش موجودی از صندوق مبدأ
            $fromCashbox->decreaseBalance($data['amount']);

            // افزایش موجودی به صندوق مقصد
            $toCashbox->increaseBalance($data['amount']);

            DB::commit();

            return [
                'success' => true,
                'message' => 'انتقال موجودی با موفقیت انجام شد',
                'transaction' => $transaction->fresh(['fromCashbox', 'toCashbox']),
                'from_balance' => $fromCashbox->fresh()->current_balance,
                'to_balance' => $toCashbox->fresh()->current_balance,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error transferring between cashboxes: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در انتقال موجودی: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * همگام‌سازی Payment با Cashbox
     */
    public function syncPaymentWithCashbox(Payment $payment): ?CashboxTransaction
    {
        if (!$payment->cashbox_id) {
            return null;
        }

        // بررسی اینکه قبلاً sync شده یا نه
        $existingTransaction = CashboxTransaction::where('payment_id', $payment->id)->first();
        if ($existingTransaction) {
            return $existingTransaction;
        }

        $data = [
            'cashbox_id' => $payment->cashbox_id,
            'amount' => $payment->amount,
            'description' => 'دریافتی از مشتری: ' . ($payment->customer->name ?? ''),
            'category' => 'خدمات',
            'payment_id' => $payment->id,
            'transaction_date' => $payment->date,
            'created_by' => auth()->id(),
        ];

        $result = $this->recordIncome($data);
        
        return $result['success'] ? $result['transaction'] : null;
    }

    /**
     * همگام‌سازی Expense با Cashbox
     */
    public function syncExpenseWithCashbox(Expense $expense): ?CashboxTransaction
    {
        if (!$expense->cashbox_id) {
            return null;
        }

        // بررسی اینکه قبلاً sync شده یا نه
        $existingTransaction = CashboxTransaction::where('expense_id', $expense->id)->first();
        if ($existingTransaction) {
            return $existingTransaction;
        }

        $data = [
            'cashbox_id' => $expense->cashbox_id,
            'amount' => $expense->amount,
            'description' => $expense->description,
            'category' => $expense->category,
            'expense_id' => $expense->id,
            'transaction_date' => $expense->date,
            'created_by' => auth()->id(),
        ];

        $result = $this->recordExpense($data);
        
        return $result['success'] ? $result['transaction'] : null;
    }

    /**
     * دریافت خلاصه مالی تمام صندوق‌ها
     */
    public function getCashboxesSummary(int $salonId): array
    {
        $cashboxes = Cashbox::forSalon($salonId)
            ->active()
            ->orderBy('sort_order')
            ->get();

        $summary = [
            'cashboxes' => [],
            'total_balance' => 0,
        ];

        foreach ($cashboxes as $cashbox) {
            $summary['cashboxes'][] = [
                'id' => $cashbox->id,
                'name' => $cashbox->name,
                'type' => $cashbox->type,
                'current_balance' => (float) $cashbox->current_balance,
            ];
            
            $summary['total_balance'] += $cashbox->current_balance;
        }

        return $summary;
    }

    /**
     * گزارش تراکنش‌های یک صندوق
     */
    public function getCashboxReport(int $cashboxId, ?string $startDate = null, ?string $endDate = null, ?string $type = null): array
    {
        $query = CashboxTransaction::forCashbox($cashboxId)
            ->with(['fromCashbox', 'toCashbox', 'cashbox', 'payment', 'expense'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $transactions = $query->get();

        $cashbox = Cashbox::find($cashboxId);

        return [
            'cashbox' => $cashbox,
            'transactions' => $transactions,
            'summary' => [
                'total_income' => $transactions->where('type', CashboxTransaction::TYPE_INCOME)->sum('amount'),
                'total_expense' => $transactions->where('type', CashboxTransaction::TYPE_EXPENSE)->sum('amount'),
                'total_transfer_in' => $transactions->where('type', CashboxTransaction::TYPE_TRANSFER)
                    ->where('to_cashbox_id', $cashboxId)->sum('amount'),
                'total_transfer_out' => $transactions->where('type', CashboxTransaction::TYPE_TRANSFER)
                    ->where('from_cashbox_id', $cashboxId)->sum('amount'),
                'current_balance' => $cashbox->current_balance,
            ],
        ];
    }

    /**
     * محاسبه مجدد موجودی صندوق (برای اصلاح اختلاف)
     */
    public function recalculateCashboxBalance(int $cashboxId): array
    {
        $cashbox = Cashbox::findOrFail($cashboxId);
        $oldBalance = $cashbox->current_balance;
        
        $cashbox->updateBalance();
        $newBalance = $cashbox->current_balance;

        return [
            'cashbox_id' => $cashbox->id,
            'cashbox_name' => $cashbox->name,
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
            'difference' => $newBalance - $oldBalance,
        ];
    }
}
