<?php

namespace App\Livewire\Forms\Inventory;

use App\Imports\ProductsImport;
use App\Enums\Product\CategoryType;
use Illuminate\Support\Facades\Log;
use Livewire\Form;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class ProductImportPharmacyForm extends Form
{
    public $product_file;
    public array $importErrors = [];

    public function requiredHeaders(): array
    {
        return [
            'product_code',
            'brand_name',
            'generic_name',
            'dosage',
            // 'form',
            'category',
            'supplier',
            'unit',
            'conversion',
            'cost_price',
            'selling_price',
            'quantity_on_hand',
            'reorder_level',
            'expiration_date',
            // 'barcode',
            // 'requires_prescription',
            // 'batch_number',
            // 'description',
        ];
    }

    public function import(int $branch_id, int $user_id): bool
    {
        $this->importErrors = [];

        $this->validate([
            'product_file' => ['required', 'file', 'mimes:csv,xls,xlsx', 'max:10240'],
        ], [
            'product_file.required' => 'Please upload a file.',
            'product_file.file' => 'The upload must be a file.',
            'product_file.mimes' => 'Invalid file type. Only CSV, XLS, and XLSX are allowed.',
            'product_file.max' => 'File size exceeds the maximum limit of 10MB.',
        ]);

        if (!$this->validateHeadersOnly($this->product_file)) {
            $this->addError('product_file', 'Invalid template. Please review the missing or misnamed columns.');
            return false;
        }

        try {
            Excel::import(
                new ProductsImport($branch_id, $user_id, CategoryType::Pharmacy),
                $this->product_file
            );

            $this->product_file = null;

            return true;
        } catch (\Throwable $e) {
            Log::error('Product Import Failed', [
                'message' => $e->getMessage(),
                'file' => method_exists($this->product_file, 'getClientOriginalName')
                    ? $this->product_file->getClientOriginalName()
                    : null,
                'user_id' => $user_id,
                'branch_id' => $branch_id,
            ]);

            $this->addError('product_file', 'An unexpected error occurred during import. Please try again or contact support.');

            return false;
        }
    }

    public function validateHeadersOnly($file): bool
    {
        $this->importErrors = [];

        try {
            $headings = (new HeadingRowImport)->toArray($file);
            $actualHeaders = $headings[0][0] ?? [];

            if (empty($actualHeaders)) {
                $this->importErrors[] = 'The uploaded file is empty or has no readable header row.';
                return false;
            }

            $missingHeaders = array_diff($this->requiredHeaders(), $actualHeaders);

            if (!empty($missingHeaders)) {
                $this->importErrors[] = 'Missing or misnamed columns: ' . implode(', ', $missingHeaders);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Header validation failed', [
                'message' => $e->getMessage(),
            ]);

            $this->importErrors[] = 'Unable to read the uploaded file.';
            return false;
        }
    }
}
