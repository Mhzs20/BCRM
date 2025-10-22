<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Rules\IranianPhoneNumber;


class CustomersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    private int $salon_id;
    private int $importedCount = 0;
    private array $skippedRows = [];


    public function __construct(int $salon_id)
    {
        $this->salon_id = $salon_id;
    }

    public function model(array $row)
    {
        $name = $row['name'] ?? $row['نام'] ?? null;
        $phoneNumber = $row['phone_number'] ?? $row['شماره_تلفن'] ?? $row['شماره تلفن'] ?? null;

        if (!$name || !$phoneNumber) {
            $this->skippedRows[] = ['row_data' => $row, 'reason' => 'ستون نام یا شماره تلفن یافت نشد یا خالی است.'];
            return null;
        }

        $customerData = [
            'salon_id'     => $this->salon_id,
            'name'         => $name,
            'phone_number' => normalizePhoneNumber($phoneNumber),
        ];

        // Validate phone number format after normalization
        if (!preg_match('/^98[0-9]{10}$/', $customerData['phone_number'])) {
            $this->skippedRows[] = ['row_data' => $row, 'reason' => 'فرمت شماره تلفن معتبر نیست.'];
            return null;
        }

        $customer = Customer::where('salon_id', $this->salon_id)
            ->withTrashed() // Include soft-deleted customers
            ->where('phone_number', $customerData['phone_number'])
            ->first();

        if ($customer) {
            if ($customer->trashed()) {
                $customer->restore();
                $customer->update($customerData);
                $this->importedCount++;
                return $customer;
            } else {
                $this->skippedRows[] = ['row_data' => $row, 'reason' => 'مشتری با این شماره تلفن (' . $phoneNumber . ') از قبل در این سالن موجود است.'];
                return null;
            }
        } else {
            $customer = Customer::create($customerData);
            $this->importedCount++;
            return $customer;
        }
    }

    public function rules(): array
    {
        return [
            '*.name' => ['required_unless:*.نام,null', 'string', 'max:255'],
            '*.phone_number' => ['required', 'string', new IranianPhoneNumber()],
        ];
    }

   public function customValidationMessages()
   {
       return [
           '*.name.required_unless' => 'فیلد نام در فایل اکسل الزامی است.',
       ];
   }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->skippedRows[] = [
                'row_number' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values()
            ];
            Log::warning("Excel import skipped row: " . $failure->row(), $failure->toArray());
        }
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedRows(): array
    {
        return $this->skippedRows;
    }
}
