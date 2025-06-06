<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Salon;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Requests\ImportCustomersExcelRequest;
use App\Http\Requests\ImportCustomersContactsRequest;
use App\Imports\CustomersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource for a specific salon.
     */
    public function index(Request $request, Salon $salon)
    {
        $this->authorize('viewAny', [Customer::class, $salon]);

        $query = $salon->customers()->with(['howIntroduced', 'customerGroup', 'job', 'ageRange']);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('phone_number', 'like', "%{$searchTerm}%");
            });
        }

        $customers = $query->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_direction', 'desc'))
            ->paginate($request->input('per_page', 15));

        return response()->json($customers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request, Salon $salon)
    {
        $this->authorize('create', [Customer::class, $salon]);

        try {
            $customer = $salon->customers()->create($request->validated());

            $customer->load(['howIntroduced', 'customerGroup', 'job', 'ageRange']);
            return response()->json(['message' => 'مشتری با موفقیت ایجاد شد.', 'data' => $customer], 201);
        } catch (\Exception $e) {
            Log::error('Customer store failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در ایجاد مشتری.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Salon $salon, Customer $customer)
    {
        $this->authorize('view', $customer);

        $customer->load(['howIntroduced', 'customerGroup', 'job', 'ageRange', 'appointments']);
        return response()->json($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Salon $salon, Customer $customer)
    {
        $this->authorize('update', $customer);

        try {
            $customer->update($request->validated());
            $customer->refresh()->load(['howIntroduced', 'customerGroup', 'job', 'ageRange']);
            return response()->json(['message' => 'اطلاعات مشتری با موفقیت به‌روزرسانی شد.', 'data' => $customer]);
        } catch (\Exception $e) {
            Log::error('Customer update failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در به‌روزرسانی اطلاعات مشتری.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Salon $salon, Customer $customer)
    {
        $this->authorize('delete', $customer);

        try {
            if ($customer->appointments()->whereIn('status', ['confirmed', 'pending_confirmation'])->exists()) {
                return response()->json(['message' => 'این مشتری دارای نوبت‌های فعال است و قابل حذف نیست.'], 403);
            }
            $customer->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Customer delete failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در حذف مشتری.'], 500);
        }
    }

    /**
     * Bulk delete customers for a salon.
     */
    public function bulkDelete(Request $request, Salon $salon)
    {
        $this->authorize('deleteAny', [Customer::class, $salon]);
        $validated = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'integer|exists:customers,id',
        ]);

        $count = Customer::where('salon_id', $salon->id)
            ->whereIn('id', $validated['customer_ids'])
            ->delete();
        return response()->json(['message' => "$count مشتری با موفقیت حذف شدند."]);
    }

    /**
     * Import customers from an Excel file.
     */
    public function importExcel(ImportCustomersExcelRequest $request, Salon $salon)
    {
        $this->authorize('create', [Customer::class, $salon]);

        $file = $request->file('file');
        $import = new CustomersImport($salon->id);

        DB::beginTransaction();
        try {
            Excel::import($import, $file);
            DB::commit();

            return response()->json([
                'message' => 'ایمپورت مشتریان از فایل اکسل انجام شد.',
                'imported_count' => $import->getImportedCount(),
                'skipped_rows_count' => count($import->getSkippedRows()),
                'skipped_details' => $import->getSkippedRows(),
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollBack();
            // ... مدیریت خطا
            return response()->json(['message' => 'خطا در اعتبارسنجی فایل اکسل.', 'errors' => $e->failures()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Excel import failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در هنگام ایمپورت فایل اکسل رخ داد.'], 500);
        }
    }

    /**
     * Import customers from a list of contacts.
     */
    public function importContacts(ImportCustomersContactsRequest $request, Salon $salon)
    {
        $this->authorize('create', [Customer::class, $salon]);

        $contacts = $request->validated()['contacts'];
        $importedCount = 0;
        $skippedContacts = [];

        DB::beginTransaction();
        try {
            foreach ($contacts as $contact) {
                $existingCustomer = $salon->customers()->where('phone_number', $contact['phone_number'])->exists();

                if ($existingCustomer) {
                    $skippedContacts[] = ['contact_data' => $contact, 'reason' => 'مشتری با این شماره تلفن از قبل موجود است.'];
                    continue;
                }
                $salon->customers()->create($contact);
                $importedCount++;
            }
            DB::commit();
            return response()->json([
                'message' => 'ایمپورت مخاطبین با موفقیت انجام شد.',
                'imported_count' => $importedCount,
                'skipped_contacts_count' => count($skippedContacts),
                'skipped_details' => $skippedContacts,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Contacts import failed: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در هنگام ایمپورت مخاطبین رخ داد.'], 500);
        }
    }
}
