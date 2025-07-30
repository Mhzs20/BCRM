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
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource for a specific salon.
     */
    public function index(Request $request, Salon $salon)
    {
        $this->authorize('viewAny', [Customer::class, $salon]);

        $query = $salon->customers()->with(['howIntroduced', 'customerGroup', 'profession', 'ageRange']);

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
            $validatedData = $request->validated();

            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('profiles', 'public');
                $validatedData['profile_image'] = $path;
            }

            $customer = $salon->customers()->create($validatedData);

            $customer->load(['howIntroduced', 'customerGroup', 'profession', 'ageRange']);
            return response()->json(['message' => 'مشتری با موفقیت ایجاد شد.', 'data' => $customer], 201);
        } catch (\Exception $e) {
            Log::error('خطا در ایجاد مشتری: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در ایجاد مشتری.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Salon $salon, Customer $customer)
    {
        $this->authorize('view', $customer);

        $customer->load(['howIntroduced', 'customerGroup', 'profession', 'ageRange', 'appointments']);
        return response()->json($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Salon $salon, Customer $customer)
    {
        $this->authorize('update', $customer);

        try {
            $validatedData = $request->validated();

            if ($request->hasFile('profile_image')) {
                // Delete old image if it exists
                if ($customer->profile_image) {
                    Storage::disk('public')->delete($customer->profile_image);
                }
                $path = $request->file('profile_image')->store('profiles', 'public');
                $validatedData['profile_image'] = $path;
            }

            // Filter validated data to only include keys present in the request input.
            $updateData = collect($validatedData)->filter(function ($value, $key) use ($request) {
                return $request->exists($key) || $request->hasFile($key);
            })->toArray();


            if (!empty($updateData)) {
                $customer->update($updateData);
            }
            $customer->refresh()->load(['howIntroduced', 'customerGroup', 'profession', 'ageRange']);
            return response()->json(['message' => 'اطلاعات مشتری با موفقیت به‌روزرسانی شد.', 'data' => $customer]);

        } catch (\Exception $e) {
            Log::error('خطا در به‌روزرسانی اطلاعات مشتری: ' . $e->getMessage());
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
            // Find active appointments and cancel them
            $customer->appointments()->whereIn('status', ['confirmed', 'pending_confirmation'])->update(['status' => 'canceled']);

            $customer->delete();
            return response()->json(['message' => 'مشتری و نوبت‌های فعال او با موفقیت لغو شدند.'], 200);
        } catch (\Exception $e) {
            Log::error('خطا در حذف مشتری: ' . $e->getMessage());
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

        $customers = Customer::where('salon_id', $salon->id)
            ->whereIn('id', $validated['customer_ids'])
            ->get();

        foreach ($customers as $customer) {
            $customer->appointments()->whereIn('status', ['confirmed', 'pending_confirmation'])->update(['status' => 'canceled']);
            $customer->delete();
        }

        return response()->json(['message' => count($customers) . ' مشتری و نوبت‌های فعال آن‌ها با موفقیت لغو شدند.']);
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

            $importedCount = $import->getImportedCount();
            $skippedRows = $import->getSkippedRows();

            if ($importedCount === 0 && count($skippedRows) > 0) {
                return response()->json([
                    'message' => 'هیچ مشتری جدیدی اضافه نشد. ممکن است مشتریان در فایل اکسل از قبل در سیستم موجود باشند.',
                    'imported_count' => 0,
                    'skipped_rows_count' => count($skippedRows),
                    'skipped_details' => $skippedRows,
                ], 409);
            }

            return response()->json([
                'message' => 'ایمپورت مشتریان از فایل اکسل انجام شد.',
                'imported_count' => $importedCount,
                'skipped_rows_count' => count($skippedRows),
                'skipped_details' => $skippedRows,
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollBack();
            // ... مدیریت خطا
            return response()->json(['message' => 'خطا در اعتبارسنجی فایل اکسل.', 'errors' => $e->failures()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در هنگام ایمپورت فایل اکسل: ' . $e->getMessage());
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
                    $skippedContacts[] = ['contact_data' => $contact, 'reason' => 'مشتری با شماره تلفن ' . $contact['phone_number'] . ' از قبل موجود است.'];
                    continue;
                }
                $salon->customers()->create($contact);
                $importedCount++;
            }
            DB::commit();

            if ($importedCount === 0 && count($skippedContacts) > 0) {
                return response()->json([
                    'message' => 'هیچ مخاطب جدیدی اضافه نشد. ممکن است این مخاطبین از قبل در سیستم موجود باشند.',
                    'imported_count' => 0,
                    'skipped_contacts_count' => count($skippedContacts),
                    'skipped_details' => $skippedContacts,
                ], 409);
            }

            return response()->json([
                'message' => 'ایمپورت مخاطبین با موفقیت انجام شد.',
                'imported_count' => $importedCount,
                'skipped_contacts_count' => count($skippedContacts),
                'skipped_details' => $skippedContacts,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در هنگام ایمپورت مخاطبین: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در هنگام ایمپورت مخاطبین رخ داد.'], 500);
        }
    }

    public function listCustomerAppointments(Salon $salon, Customer $customer)
    {
        $this->authorize('view', $customer);

        $appointments = $customer->appointments()
            ->with(['services', 'staff'])
            ->orderBy('appointment_date', 'desc')
            ->get();

        // As requested, ensure all relevant fields are returned in the response.
        // The 'notes' field corresponds to 'internal_notes'.
        // There is no 'deposit_amount' field, but 'total_price', 'deposit_required', and 'deposit_paid' are available.
        // We make all model attributes visible to override any potential default hiding.
        if ($appointments->isNotEmpty()) {
            $allAttributes = array_keys($appointments->first()->getAttributes());
            $appointments->each->makeVisible($allAttributes);
        }

        return response()->json($appointments);
    }
}
