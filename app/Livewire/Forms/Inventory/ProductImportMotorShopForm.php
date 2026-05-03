<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Inventory;

use App\Enums\Product\CategoryType;
use App\Imports\ProductsImport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

final class ProductImportMotorShopForm extends ProductImportPharmacyForm
{
    public function requiredHeaders(): array
    {
        return [
            'product_code',
            'brand_name',
            'category',
            'supplier',
            'unit',
            'conversion',
            'cost_price',
            'selling_price',
            'quantity_on_hand',
            'reorder_level',
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

        if (! $this->validateHeadersOnly($this->product_file)) {
            $this->addError('product_file', 'Invalid template. Please review the missing or misnamed columns.');

            return false;
        }

        if (! $this->validateRowsBeforeQueue($this->product_file)) {
            $this->addError('product_file', 'Invalid data. Please review the row errors below.');

            return false;
        }

        try {
            Excel::import(
                new ProductsImport($branch_id, $user_id, CategoryType::MotorShop),
                $this->product_file
            );

            $this->product_file = null;

            return true;
        } catch (Throwable $e) {
            Log::error('Motor Shop Product Import Failed', [
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
}
