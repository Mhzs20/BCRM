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

        $existingCustomer = Customer::where('salon_id', $this->salon_id)
            ->where('phone_number', $phoneNumber)
            ->whereNull('deleted_at')
            ->first();
        if ($existingCustomer) {
            $this->skippedRows[] = ['row_data' => $row, 'reason' => 'مشتری با این شماره تلفن (' . $phoneNumber . ') از قبل موجود است.'];
            return null;
        }

        $this->importedCount++;
        return new Customer([
            'salon_id'     => $this->salon_id,
            'name'         => $name,
            'phone_number' => $phoneNumber,

        ]);
    }

    public function rules(): array
    {
        return [
            '*.name' => ['required_unless:*.نام,null', 'string', 'max:255'],
            '*.phone_number' => ['required', 'string', 'max:20'],
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
