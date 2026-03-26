<?php

namespace App\Livewire\Forms\Inventory;

use App\Imports\ProductsImport;
use App\Models\ProductPackaging;
use App\Models\Unit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Livewire\Form;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProductImportPharmacyForm extends Form
{
    public $product_file;
    public array $importErrors = [];

    /**
     * Translated rules for a FLAT Excel row.
     */
    public function rowRules(): array
    {
        return [
            'product_code' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:255'],
            'form' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'string'],
            'conversion' => ['required', 'numeric', 'min:1'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:1'],
            'quantity_on_hand' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:1'],
            'expiration_date' => ['nullable', 'date:Y-m-d', 'after:today'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'requires_prescription' => ['nullable', 'string', 'in:yes,no,Yes,No'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function import(int $branch_id, int $user_id): bool
    {
        // VALIDATE FILE UPLOAD
        $this->validate([
            'product_file' => ['required', 'file', 'mimes:csv,xls,xlsx', 'max:10240'],
        ],[
            'product_file.required' => 'Please upload a file.',
            'product_file.file' => 'The upload must be a file.',
            'product_file.mimes' => 'Invalid file type. Only CSV, XLS, and XLSX are allowed.',
            'product_file.max' => 'File size exceeds the maximum limit of 10MB.',
        ]);

        // ROW-BY-ROW VALIDATION (Includes relational checks)
        if (!$this->validateExcel($this->product_file)) {
            $this->addError('product_file', 'There are issues with the uploaded file. Please review the errors and fix them before importing.');

            return false;
        }

        try {
            Excel::clearResolvedInstances(); // Clear memory after import
            Excel::import(
                new ProductsImport($branch_id, $user_id),
                $this->product_file
            );

            // Reset UI
            $this->product_file = null;
            return true;

        } catch (\Exception $e) {
            Log::error('Product Import Failed: ' . $e->getMessage(), [
                'file' => $this->product_file->getClientOriginalName(),
                'user_id' => $user_id,
                'branch_id' => $branch_id,
            ]);
            $this->addError('product_file', 'An unexpected error occurred during import. Please try again or contact support.');

            return false;
        }
    }


    public function validateExcel($file): bool
    {
        $this->importErrors = [];

        // 1. FAST HEADER VALIDATION
        $headings = (new HeadingRowImport)->toArray($file);
        $actualHeaders = $headings[0][0] ?? [];

        $requiredHeaders = [
            'product_code', 'brand_name', 'generic_name', 'dosage', 'category', 'supplier',
            'unit', 'conversion', 'cost_price', 'selling_price', 'quantity_on_hand'
        ];

        $missingHeaders = array_diff($requiredHeaders, $actualHeaders);

        if (!empty($missingHeaders)) {
            $missingList = implode(', ', $missingHeaders);
            $this->importErrors[] = "Invalid Template. Missing or Misnamed columns: [{$missingList}].";
            return false;
        }

        // 2. HIGH-SPEED MEMORY CACHES (The secret to validating 10k rows in seconds)
        $unitsMap = Unit::all()->flatMap(fn($u) => [
            strtolower(trim($u->name)) => true,
            strtolower(trim($u->abbreviation)) => true
        ])->toArray();

        // Cache existing barcodes to ensure uniqueness
        $existingBarcodes = ProductPackaging::whereNotNull('barcode')
            ->pluck('barcode')
            ->flip()
            ->toArray();

        // 3. READ AND VALIDATE ROWS
        $importConfig = new class implements \Maatwebsite\Excel\Concerns\WithHeadingRow {};
        $data = Excel::toArray($importConfig, $file);
        $rows = $data[0] ?? [];

        if (empty($rows)) {
            $this->importErrors[] = "The uploaded file is empty.";
            return false;
        }

        // Track barcodes within the file itself to prevent duplicates in the same upload
        $fileBarcodes = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +1 for 0-index, +1 for header row
            if (empty($row['product_code'])) continue;

            $code = $row['product_code'];

            // --- DATE TO Y-m-d ---
            if (!empty($row['expiration_date'])) {
                $expDateRaw = $row['expiration_date'];

                // Check if it is a numeric Excel Serial Date
                if (is_numeric($expDateRaw)) {
                    try {
                        // Convert Serial Date to PHP DateTime object, then format it
                        $row['expiration_date'] = ExcelDate::excelToDateTimeObject($expDateRaw)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $this->importErrors[] = "Row {$rowNum} ({$code}): Invalid date format for expiration_date.";
                        continue; // Skip further validation for this row to prevent crashes
                    }
                }
                // If it's a string like "12/12/2026" that Excel didn't parse as a serial
                elseif (is_string($expDateRaw)) {
                     try {
                         $row['expiration_date'] = \Carbon\Carbon::parse($expDateRaw)->format('Y-m-d');
                     } catch (\Exception $e) {
                         $this->importErrors[] = "Row {$rowNum} ({$code}): Invalid date format for expiration_date.";
                         continue;
                     }
                }
            }

            // Run Standard Data Rules
            $validator = Validator::make($row, $this->rowRules());

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->importErrors[] = "Row {$rowNum} ({$code}): {$error}";
                }
            }

            // Run High-Speed Relational Validations
            $unitRaw = strtolower(trim((string)($row['unit'] ?? '')));
            if (!isset($unitsMap[$unitRaw])) {
                $this->importErrors[] = "Row {$rowNum} ({$code}): Unit '{$row['unit']}' does not exist in the system.";
            }

            $barcode = $row['barcode'] ?? null;
            if ($barcode) {
                if (isset($existingBarcodes[$barcode]) || isset($fileBarcodes[$barcode])) {
                    $this->importErrors[] = "Row {$rowNum} ({$code}): Barcode '{$barcode}' is already in use.";
                }
                $fileBarcodes[$barcode] = true;
            }

            // UI Performance Protection (Don't render 10,000 errors to the screen)
            if (count($this->importErrors) >= 30) {
                $this->importErrors[] = "...and more errors. Please fix these top 30 issues first.";
                break;
            }
        }

        return count($this->importErrors) === 0;
    }
}