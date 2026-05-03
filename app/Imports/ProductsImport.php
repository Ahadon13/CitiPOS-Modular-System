<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\Product\CategoryType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

final class ProductsImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    private int $branchId;

    private int $userId;

    private int $productCategoryId;

    private CategoryType $productCategoryType;

    public function __construct(int $branchId, int $userId, CategoryType $productCategoryType = CategoryType::Pharmacy)
    {
        $this->branchId = $branchId;
        $this->userId = $userId;
        $this->productCategoryType = $productCategoryType;
    }

    public function collection(Collection $rows): void
    {
        try {
            if ($rows->isEmpty()) {
                return;
            }

            $now = now()->toDateTimeString();

            $productCategory = ProductCategory::where('name', $this->productCategoryType->value)->first();
            if (! $productCategory) {
                throw new RuntimeException($this->productCategoryType->label().' product category was not found.');
            }

            $this->productCategoryId = $productCategory->id;

            $branchProductCategoryId = Branch::whereKey($this->branchId)->value('product_category_id');
            if ((int) $branchProductCategoryId !== (int) $this->productCategoryId) {
                throw new RuntimeException("The selected branch is not assigned to the {$this->productCategoryType->label()} module.");
            }

            $suppliersMap = Supplier::select('id', 'name')
                ->get()
                ->mapWithKeys(fn ($row) => [mb_strtolower(mb_trim($row->name)) => $row->id])
                ->toArray();

            $categoriesMap = Category::select('id', 'name')
                ->get()
                ->mapWithKeys(fn ($row) => [mb_strtolower(mb_trim($row->name)) => $row->id])
                ->toArray();

            $unitsMap = Unit::select('id', 'name', 'abbreviation')
                ->get()
                ->flatMap(function ($u) {
                    $map = [];
                    if ($u->name) {
                        $map[mb_strtolower(mb_trim($u->name))] = $u->id;
                    }
                    if ($u->abbreviation) {
                        $map[mb_strtolower(mb_trim($u->abbreviation))] = $u->id;
                    }

                    return $map;
                })
                ->toArray();

            $validRows = [];

            foreach ($rows as $row) {
                $normalized = $this->normalizeRow($row);
                $isBaseRow = (float) ($normalized['conversion'] ?? 1) === 1.0;

                $validator = Validator::make($normalized, [
                    'product_code' => ['required', 'string', 'max:255'],
                    'brand_name' => ['nullable', 'string', 'max:255'],
                    'generic_name' => ['nullable', 'string', 'max:255'],
                    'dosage' => ['nullable', 'string', 'max:255'],
                    'form' => ['nullable', 'string', 'max:255'],
                    'category' => ['nullable', 'string', 'max:255'],
                    'supplier' => [$isBaseRow ? 'required' : 'nullable', 'string', 'max:255'],
                    'unit' => ['required', 'string'],
                    'conversion' => ['required', 'numeric', 'min:1'],
                    'cost_price' => ['nullable', 'numeric', 'min:0'],
                    'selling_price' => ['required', 'numeric', 'min:1'],
                    'quantity_on_hand' => ['nullable', 'numeric', 'min:0'],
                    'reorder_level' => ['nullable', 'numeric', 'min:1'],
                    'expiration_date' => ['nullable', 'date'],
                    'barcode' => ['nullable', 'string', 'max:255'],
                    'requires_prescription' => ['nullable', 'string'],
                    'batch_number' => ['nullable', 'string', 'max:255'],
                    'description' => ['nullable', 'string', 'max:1000'],
                    'part_number' => ['nullable', 'string', 'max:255'],
                    'vehicle_model' => ['nullable', 'string', 'max:255'],
                    'engine_type' => ['nullable', 'string', 'max:255'],
                    'year_range' => ['nullable', 'string', 'max:255'],
                    'oem_number' => ['nullable', 'string', 'max:255'],
                ]);

                if ($validator->fails()) {
                    Log::warning('Skipping invalid import row', [
                        'product_code' => $normalized['product_code'] ?? null,
                        'errors' => $validator->errors()->all(),
                    ]);

                    continue;
                }

                $unitKey = mb_strtolower(mb_trim((string) ($normalized['unit'] ?? '')));
                if (! isset($unitsMap[$unitKey])) {
                    Log::warning('Skipping row due to unknown unit', [
                        'product_code' => $normalized['product_code'] ?? null,
                        'unit' => $normalized['unit'] ?? null,
                    ]);

                    continue;
                }

                $validRows[] = $normalized;
            }

            if (empty($validRows)) {
                throw new RuntimeException('No valid product rows were found in the import file.');
            }

            $invalidProductGroups = collect($validRows)
                ->groupBy(fn (array $row) => mb_trim((string) $row['product_code']))
                ->filter(fn (Collection $group) => ! $group->contains(fn (array $row) => (float) ($row['conversion'] ?? 1) === 1.0))
                ->keys();

            if ($invalidProductGroups->isNotEmpty()) {
                throw new RuntimeException(
                    'These product codes are missing a base row with conversion 1: '.$invalidProductGroups->implode(', ')
                );
            }

            $supplierNames = collect($validRows)
                ->pluck('supplier')
                ->filter()
                ->map(fn ($v) => mb_trim((string) $v))
                ->unique()
                ->values();

            $newSuppliers = [];
            foreach ($supplierNames as $name) {
                $key = mb_strtolower($name);
                if (! isset($suppliersMap[$key])) {
                    $newSuppliers[] = [
                        'name' => $name,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $suppliersMap[$key] = true;
                }
            }

            if (! empty($newSuppliers)) {
                Supplier::insertOrIgnore($newSuppliers);

                $suppliersMap = Supplier::select('id', 'name')
                    ->get()
                    ->mapWithKeys(fn ($row) => [mb_strtolower(mb_trim($row->name)) => $row->id])
                    ->toArray();
            }

            $categoryNames = collect($validRows)
                ->pluck('category')
                ->filter()
                ->map(fn ($v) => mb_trim((string) $v))
                ->unique()
                ->values();

            $newCategories = [];
            foreach ($categoryNames as $name) {
                $key = mb_strtolower($name);
                if (! isset($categoriesMap[$key])) {
                    $newCategories[] = [
                        'name' => $name,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $categoriesMap[$key] = true;
                }
            }

            if (! empty($newCategories)) {
                Category::insertOrIgnore($newCategories);

                $categoriesMap = Category::select('id', 'name')
                    ->get()
                    ->mapWithKeys(fn ($row) => [mb_strtolower(mb_trim($row->name)) => $row->id])
                    ->toArray();
            }

            $productCodes = collect($validRows)
                ->pluck('product_code')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $existingProductRows = Product::query()
                ->whereIn('product_code', $productCodes)
                ->get(['id', 'product_code', 'branch_id', 'product_category_id']);

            $conflictingProducts = $existingProductRows
                ->filter(fn (Product $product) => (int) $product->branch_id !== $this->branchId || (int) $product->product_category_id !== $this->productCategoryId)
                ->pluck('product_code')
                ->values();

            if ($conflictingProducts->isNotEmpty()) {
                throw new RuntimeException(
                    'These product codes already belong to another branch or module: '.$conflictingProducts->implode(', ')
                );
            }

            $existingProducts = $existingProductRows
                ->pluck('id', 'product_code')
                ->toArray();

            $productsToInsert = [];
            $productBaseRows = collect($validRows)
                ->groupBy(fn (array $row) => mb_trim((string) $row['product_code']))
                ->map(function (Collection $group) {
                    return $group->first(fn (array $row) => (float) ($row['conversion'] ?? 1) === 1.0)
                        ?? $group->first();
                });

            foreach ($productBaseRows as $row) {
                $code = mb_trim((string) $row['product_code']);

                if (! isset($existingProducts[$code]) && ! isset($productsToInsert[$code])) {
                    $supplierKey = mb_strtolower(mb_trim((string) ($row['supplier'] ?? '')));
                    $categoryKey = mb_strtolower(mb_trim((string) ($row['category'] ?? '')));
                    $unitKey = mb_strtolower(mb_trim((string) $row['unit']));

                    $productsToInsert[$code] = [
                        'supplier_id' => $suppliersMap[$supplierKey] ?? null,
                        'category_id' => $categoriesMap[$categoryKey] ?? null,
                        'branch_id' => $this->branchId,
                        'product_category_id' => $this->productCategoryId,
                        'base_unit_id' => $unitsMap[$unitKey],
                        'product_code' => $code,
                        'name' => $row['brand_name'] ?? $code,
                        'brand_name' => $row['brand_name'] ?? null,
                        'generic_name' => $this->productCategoryType === CategoryType::Pharmacy ? ($row['generic_name'] ?? null) : null,
                        'dosage' => $this->productCategoryType === CategoryType::Pharmacy ? ($row['dosage'] ?? null) : 'N/A',
                        'form' => $row['form'] ?? null,
                        'requires_prescription' => $this->productCategoryType === CategoryType::Pharmacy && mb_strtolower(mb_trim((string) ($row['requires_prescription'] ?? ''))) === 'yes',
                        'reorder_level' => (float) ($row['reorder_level'] ?? 20),
                        'attributes' => json_encode([
                            'description' => $row['description'] ?? null,
                            'part_number' => $row['part_number'] ?? null,
                            'vehicle_model' => $row['vehicle_model'] ?? null,
                            'engine_type' => $row['engine_type'] ?? null,
                            'year_range' => $row['year_range'] ?? null,
                            'oem_number' => $row['oem_number'] ?? null,
                        ]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($productsToInsert)) {
                Product::insertOrIgnore(array_values($productsToInsert));

                $existingProducts = Product::whereIn('product_code', $productCodes)
                    ->where('branch_id', $this->branchId)
                    ->where('product_category_id', $this->productCategoryId)
                    ->pluck('id', 'product_code')
                    ->toArray();
            }

            $packagingsToInsert = [];
            $batchesToInsert = [];

            foreach ($validRows as $row) {
                $code = mb_trim((string) $row['product_code']);
                $productId = $existingProducts[$code] ?? null;

                if (! $productId) {
                    continue;
                }

                $unitKey = mb_strtolower(mb_trim((string) $row['unit']));
                $unitId = $unitsMap[$unitKey] ?? null;
                $conversion = (float) ($row['conversion'] ?? 1);

                $packagingsToInsert[] = [
                    'product_id' => $productId,
                    'unit_id' => $unitId,
                    'conversion_factor' => $conversion,
                    'price' => (int) round((float) ($row['selling_price'] ?? 0) * 100),
                    'barcode' => ! empty($row['barcode']) ? $row['barcode'] : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($conversion === 1.0) {
                    $qty = (float) ($row['quantity_on_hand'] ?? 0);

                    if ($qty > 0) {
                        $batchesToInsert[] = [
                            'product_id' => $productId,
                            'branch_id' => $this->branchId,
                            'quantity_on_hand' => $qty,
                            'cost_per_unit' => (int) round((float) ($row['cost_price'] ?? 0) * 100),
                            'batch_number' => ! empty($row['batch_number']) ? $row['batch_number'] : null,
                            'expiration_date' => $this->productCategoryType === CategoryType::MotorShop
                                ? null
                                : ($row['expiration_date'] ?? null),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }

            if (! empty($packagingsToInsert)) {
                DB::table('product_packagings')->insertOrIgnore($packagingsToInsert);
            }

            if (! empty($batchesToInsert)) {
                DB::table('inventory_batches')->insert($batchesToInsert);
            }
        } catch (Throwable $e) {
            Log::error('Import failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            throw $e;
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function normalizeRow($row): array
    {
        $row = is_array($row) ? $row : $row->toArray();

        $stringFields = [
            'product_code',
            'brand_name',
            'generic_name',
            'dosage',
            'form',
            'category',
            'supplier',
            'unit',
            'barcode',
            'requires_prescription',
            'batch_number',
            'description',
            'part_number',
            'vehicle_model',
            'engine_type',
            'year_range',
            'oem_number',
        ];

        foreach ($stringFields as $field) {
            if (array_key_exists($field, $row) && ! empty($row[$field])) {
                $row[$field] = mb_trim((string) $row[$field]);
            }
        }

        if (! empty($row['expiration_date'])) {
            $rawDate = $row['expiration_date'];

            try {
                if (is_numeric($rawDate)) {
                    $row['expiration_date'] = ExcelDate::excelToDateTimeObject((float) $rawDate)->format('Y-m-d');
                } elseif (is_string($rawDate)) {
                    $row['expiration_date'] = \Carbon\Carbon::parse($rawDate)->format('Y-m-d');
                }
            } catch (Throwable $e) {
                $row['expiration_date'] = null;
            }
        }

        return $row;
    }
}
