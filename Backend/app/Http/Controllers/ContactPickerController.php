<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Salon;
use App\Http\Requests\ContactPickerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ContactPickerController extends Controller
{
    /**
     * Get available contacts from the salon's existing customers for selection
     * This endpoint allows the user to see their existing customers as potential contacts
     */
    public function getAvailableContacts(Request $request, Salon $salon)
    {
        $this->authorize('viewAny', [Customer::class, $salon]);

        $query = $salon->customers()->select(['id', 'name', 'phone_number', 'profile_image']);

        // Add search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('phone_number', 'like', "%{$searchTerm}%");
            });
        }

        $contacts = $query->orderBy('name', 'asc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'message' => 'لیست مخاطبین موجود بازیابی شد.',
            'data' => $contacts
        ]);
    }

    /**
     * Pick and import contacts from external sources (like phone contacts)
     * This is the main API picker functionality
     */
    public function pickAndImportContacts(ContactPickerRequest $request, Salon $salon)
    {
        $this->authorize('create', [Customer::class, $salon]);

        $contacts = $request->validated()['contacts'];
        $importedCount = 0;
        $updatedCount = 0;
        $skippedContacts = [];
        $newCustomers = [];
        $updatedCustomers = [];

        DB::beginTransaction();
        try {
            foreach ($contacts as $contact) {
                // Check for existing customer including soft-deleted ones
                $existingCustomer = $salon->customers()
                    ->withTrashed()
                    ->where('phone_number', $contact['phone_number'])
                    ->first();

                if ($existingCustomer) {
                    if ($existingCustomer->trashed()) {
                        // Restore soft-deleted customer and update data
                        $existingCustomer->restore();
                        $existingCustomer->update($contact);
                        $updatedCustomers[] = $existingCustomer->fresh();
                        $updatedCount++;
                    } else {
                        // Customer already exists and is not deleted
                        if ($request->has('update_existing') && $request->update_existing) {
                            // Update existing customer if flag is set
                            $existingCustomer->update(array_filter($contact)); // Only update non-null values
                            $updatedCustomers[] = $existingCustomer->fresh();
                            $updatedCount++;
                        } else {
                            // Skip existing customer
                            $skippedContacts[] = [
                                'contact_data' => $contact,
                                'reason' => 'مشتری با شماره تلفن ' . $contact['phone_number'] . ' از قبل موجود است.'
                            ];
                        }
                    }
                } else {
                    // Create new customer
                    $newCustomer = $salon->customers()->create($contact);
                    $newCustomers[] = $newCustomer;
                    $importedCount++;
                }
            }

            DB::commit();

            // Prepare response
            $response = [
                'message' => 'عملیات انتخاب و درون‌ریزی مخاطبین با موفقیت انجام شد.',
                'summary' => [
                    'total_processed' => count($contacts),
                    'imported_count' => $importedCount,
                    'updated_count' => $updatedCount,
                    'skipped_count' => count($skippedContacts),
                ],
                'data' => [
                    'new_customers' => $newCustomers,
                    'updated_customers' => $updatedCustomers,
                    'skipped_contacts' => $skippedContacts,
                ]
            ];

            // If no changes were made
            if ($importedCount === 0 && $updatedCount === 0 && count($skippedContacts) > 0) {
                $response['message'] = 'هیچ مخاطب جدیدی اضافه نشد. ممکن است این مخاطبین از قبل در سیستم موجود باشند.';
                return response()->json($response, 409);
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در هنگام انتخاب و درون‌ریزی مخاطبین: ' . $e->getMessage());
            return response()->json([
                'message' => 'خطا در هنگام انتخاب و درون‌ریزی مخاطبین رخ داد.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import history for the salon
     */
    public function getImportHistory(Request $request, Salon $salon)
    {
        $this->authorize('viewAny', [Customer::class, $salon]);

        // Get recently added customers (within last 30 days) as a simple import history
        $recentCustomers = $salon->customers()
            ->select(['id', 'name', 'phone_number', 'created_at'])
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'message' => 'تاریخچه درون‌ریزی مخاطبین بازیابی شد.',
            'data' => $recentCustomers
        ]);
    }

    /**
     * Validate contacts before import (dry run)
     */
    public function validateContacts(ContactPickerRequest $request, Salon $salon)
    {
        $this->authorize('create', [Customer::class, $salon]);

        $contacts = $request->validated()['contacts'];
        $validContacts = [];
        $invalidContacts = [];
        $duplicateContacts = [];

        foreach ($contacts as $index => $contact) {
            // Check if contact already exists in salon
            $existingCustomer = $salon->customers()
                ->where('phone_number', $contact['phone_number'])
                ->first();

            if ($existingCustomer) {
                $duplicateContacts[] = [
                    'index' => $index,
                    'contact_data' => $contact,
                    'existing_customer' => [
                        'id' => $existingCustomer->id,
                        'name' => $existingCustomer->name,
                        'phone_number' => $existingCustomer->phone_number
                    ]
                ];
            } else {
                $validContacts[] = [
                    'index' => $index,
                    'contact_data' => $contact
                ];
            }
        }

        return response()->json([
            'message' => 'اعتبارسنجی مخاطبین انجام شد.',
            'validation_summary' => [
                'total_contacts' => count($contacts),
                'valid_contacts' => count($validContacts),
                'duplicate_contacts' => count($duplicateContacts),
                'invalid_contacts' => count($invalidContacts),
            ],
            'data' => [
                'valid_contacts' => $validContacts,
                'duplicate_contacts' => $duplicateContacts,
                'invalid_contacts' => $invalidContacts,
            ]
        ]);
    }

    /**
     * Bulk select contacts by phone numbers for quick import
     */
    public function bulkSelectByPhoneNumbers(Request $request, Salon $salon)
    {
        $this->authorize('create', [Customer::class, $salon]);

        $validated = $request->validate([
            'phone_numbers' => 'required|array|min:1',
            'phone_numbers.*' => 'required|string|max:20',
            'default_name_prefix' => 'nullable|string|max:50', // Optional prefix for auto-generated names
        ]);

        $phoneNumbers = $validated['phone_numbers'];
        $namePrefix = $validated['default_name_prefix'] ?? 'مشتری';
        
        $contacts = [];
        foreach ($phoneNumbers as $index => $phoneNumber) {
            $contacts[] = [
                'name' => $namePrefix . ' ' . ($index + 1),
                'phone_number' => $phoneNumber,
            ];
        }

        // Use the existing pickAndImportContacts method
        $request->merge(['contacts' => $contacts]);
        $request->merge(['update_existing' => false]);
        
        return $this->pickAndImportContacts(new ContactPickerRequest($request->all()), $salon);
    }
}
