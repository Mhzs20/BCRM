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

        $query = $salon->customers()->with(['howIntroduced', 'customerGroups', 'profession', 'ageRange'])
            ->withCount([
                'appointments as total_appointments',
                'appointments as completed_appointments' => function ($query) {
                    $query->where('status', 'completed');
                },
                'appointments as canceled_appointments' => function ($query) {
                    $query->where('status', 'canceled');
                }
            ]);

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

            // Check for existing customer including soft-deleted ones
            $customer = $salon->customers()->withTrashed()->where('phone_number', $validatedData['phone_number'])->first();

            if ($customer) {
                if ($customer->trashed()) {
                    $customer->restore();
                    $customer->update($validatedData); // Update with new data if restored
                    $message = 'مشتری با موفقیت بازیابی و به‌روزرسانی شد.';
                } else {
                    return response()->json(['message' => 'مشتری با این شماره تلفن از قبل موجود است.'], 409);
                }
            } else {
                $customer = $salon->customers()->create($validatedData);
                $message = 'مشتری با موفقیت ایجاد شد.';
            }

            // Sync customer groups
            if (isset($validatedData['customer_group_ids'])) {
                $syncData = [];
                foreach ($validatedData['customer_group_ids'] as $groupId) {
                    $syncData[$groupId] = ['salon_id' => $salon->id];
                }
                $customer->customerGroups()->sync($syncData);
            }

            $customer->load(['howIntroduced', 'customerGroups', 'profession', 'ageRange']);
            return response()->json(['message' => $message, 'data' => $customer], 201);
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

        $customer->load(['howIntroduced', 'customerGroups', 'profession', 'ageRange']);

        $appointments = $customer->appointments();

        $totalAppointments = $appointments->count();
        $completedAppointments = $appointments->where('status', 'completed')->count();
        $canceledAppointments = $appointments->where('status', 'canceled')->count();

        $customerData = $customer->toArray();
        $customerData['total_appointments'] = $totalAppointments;
        $customerData['completed_appointments'] = $completedAppointments;
        $customerData['canceled_appointments'] = $canceledAppointments;

        return response()->json($customerData);
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

            // Sync customer groups if provided
            if (array_key_exists('customer_group_ids', $validatedData)) {
                $syncData = [];
                foreach ($validatedData['customer_group_ids'] ?? [] as $groupId) {
                    $syncData[$groupId] = ['salon_id' => $salon->id];
                }
                $customer->customerGroups()->sync($syncData);
            }

            $customer->refresh()->load(['howIntroduced', 'customerGroups', 'profession', 'ageRange']);
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
            // Cancel future appointments for the customer
            $customer->appointments()->where('appointment_date', '>=', now())->update(['status' => 'canceled']);

            $customer->delete();
            return response()->json(['message' => 'مشتری و نوبت‌های آینده او با موفقیت لغو شدند.'], 200);
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
            // Cancel future appointments for the customer
            $customer->appointments()->where('appointment_date', '>=', now())->update(['status' => 'canceled']);
            $customer->delete();
        }

        return response()->json(['message' => count($customers) . ' مشتری و نوبت‌های آینده آن‌ها با موفقیت لغو شدند.']);
    }

    public function importExcel(ImportCustomersExcelRequest $request, Salon $salon)
    {
        $this->authorize('create', [Customer::class, $salon]);

        $dashboardController = new DashboardController(new \App\Services\SmsService());
        return $dashboardController->importCustomers($request, $salon->id);
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
                $customer = $salon->customers()->withTrashed()->where('phone_number', $contact['phone_number'])->first();

                if ($customer) {
                    if ($customer->trashed()) {
                        $customer->restore();
                        $customer->update($contact); // Update with new data if restored
                        $importedCount++;
                    } else {
                        $skippedContacts[] = ['contact_data' => $contact, 'reason' => 'مشتری با شماره تلفن ' . $contact['phone_number'] . ' از قبل موجود است.'];
                    }
                } else {
                    $salon->customers()->create($contact);
                    $importedCount++;
                }
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
        $this->authorize('view', $customer); // Re-enabled authorization

        $appointments = $customer->appointments()
            ->with(['services', 'staff'])
            ->orderBy('appointment_date', 'desc')
            ->get();

        return response()->json(App\Http\Resources\AppointmentResource::collection($appointments));
    }
        /**
         * لیست نوبت‌های مشتری سالن با قابلیت pagination
         */
        public function listCustomerAppointmentsPaginated(Request $request, Salon $salon, Customer $customer)
        {
            $this->authorize('view', $customer);

            $perPage = $request->input('per_page', 15);
            $appointments = $customer->appointments()
                ->with(['services', 'staff'])
                ->orderBy('appointment_date', 'desc')
                ->paginate($perPage);

            return response()->json(App\Http\Resources\AppointmentResource::collection($appointments));
        }
}
