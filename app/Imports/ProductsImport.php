<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\Product\CategoryType;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class ProductsImport implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue
{
    use SerializesModels;
    protected int $branchId;
    protected int $userId;
    protected int $pharmacyCategoryId;

    protected array $suppliersMap = [];
    protected array $unitsMap = [];

    public function __construct(int $branchId, int $userId)
    {
        $this->branchId = $branchId;
        $this->userId = $userId;
        $this->pharmacyCategoryId = ProductCategory::where('name', CategoryType::Pharmacy->value)->firstOrFail()->id;

        $this->suppliersMap = Supplier::pluck('id', 'name')
            ->mapWithKeys(fn($id, $name) => [strtolower(trim($name)) => $id])
            ->toArray();

        $this->unitsMap = Unit::all()->flatMap(function ($u) {
            return [
                strtolower(trim($u->name)) => $u->id,
                strtolower(trim($u->abbreviation)) => $u->id,
            ];
        })->toArray();
    }

    public function collection(Collection $rows)
    {

        if ($rows->isEmpty()) return;

        $now = now();

        // 1. BULK SUPPLIER CREATION
        $supplierNames = $rows->pluck('supplier')->map(fn($s) => trim((string)$s))->filter()->unique();
        $newSuppliers = [];

        foreach ($supplierNames as $name) {
            $key = strtolower($name);
            if (!isset($this->suppliersMap[$key])) {
                $newSuppliers[] = ['name' => $name, 'created_at' => $now, 'updated_at' => $now];
                $this->suppliersMap[$key] = true; // Prevent duplicate inserts in this loop
            }
        }

        if (!empty($newSuppliers)) {
            Supplier::insert($newSuppliers);
            // Refresh supplier cache with the newly inserted IDs
            $this->suppliersMap = Supplier::pluck('id', 'name')
                ->mapWithKeys(fn($id, $name) => [strtolower(trim($name)) => $id])
                ->toArray();
        }

        // 2. PREPARE BULK PRODUCTS
        $productCodes = $rows->pluck('product_code')->filter()->unique()->toArray();
        $existingProducts = Product::whereIn('product_code', $productCodes)->pluck('id', 'product_code')->toArray();

        $productsToInsert = [];

        foreach ($rows as $row) {
            $code = trim((string) $row['product_code']);
            $conversion = (float) ($row['conversion'] ?? 1);

            // Only prepare base products that DO NOT exist yet
            if ($conversion === 1.0 && !isset($existingProducts[$code]) && !isset($productsToInsert[$code])) {
                $supplierKey = strtolower(trim((string) $row['supplier']));

                $productsToInsert[$code] = [
                    'supplier_id' => $this->suppliersMap[$supplierKey] ?? null,
                    'category_id' => $this->pharmacyCategoryId,
                    'base_unit_id' => $this->unitsMap[strtolower(trim((string)$row['unit']))] ?? null,
                    'product_code' => $code,
                    'brand_name' => $row['brand_name'] ?? null,
                    'generic_name' => $row['generic_name'] ?? null,
                    'requires_prescription' => strtolower(trim((string) $row['requires_prescription'])) === 'yes',
                    'reorder_level' => (float) ($row['reorder_level'] ?? 20),
                    'attributes' => json_encode(['description' => $row['description'] ?? null]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Execute Product Bulk Insert
        if (!empty($productsToInsert)) {
            Product::insert(array_values($productsToInsert));
            // Re-fetch existing products so we have the IDs of the newly created ones
            $existingProducts = Product::whereIn('product_code', $productCodes)->pluck('id', 'product_code')->toArray();
        }

        // 3. PREPARE BULK PACKAGINGS AND BATCHES
        $packagingsToInsert = [];
        $batchesToInsert = [];

        foreach ($rows as $row) {
            $code = trim((string) $row['product_code']);
            $productId = $existingProducts[$code] ?? null;

            if (!$productId) continue;

            $conversion = (float) ($row['conversion'] ?? 1);
            $unitId = $this->unitsMap[strtolower(trim((string)$row['unit']))] ?? null;

            // Prepare Packaging
            $packagingsToInsert[] = [
                'product_id' => $productId,
                'unit_id' => $unitId,
                'conversion_factor' => $conversion,
                'price' => (int) round((float) $row['selling_price'] * 100),
                'barcode' => $row['barcode'] ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // If it's a base row, prepare the Inventory Batch (Restock Logic)
            if ($conversion === 1.0) {
                $qty = (float) ($row['quantity_on_hand'] ?? 0);
                if ($qty > 0) {
                    $batchesToInsert[] = [
                        'product_id' => $productId,
                        'branch_id' => $this->branchId,
                        'quantity_on_hand' => $qty,
                        'cost_per_unit' => (int) round((float) ($row['cost_price'] ?? 0) * 100),
                        'batch_number' => $row['batch_number'] ?: null,
                        'expiration_date' => $row['expiration_date'] ?: null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // Execute Final Bulk Inserts
        if (!empty($packagingsToInsert)) {
            // insertOrIgnore prevents database crashes if they accidentally upload the same packaging twice
            DB::table('product_packagings')->insertOrIgnore($packagingsToInsert);
        }
        if (!empty($batchesToInsert)) {
            DB::table('inventory_batches')->insert($batchesToInsert);
        }
    }

    public function chunkSize(): int
    {
        // Increased chunk size because bulk inserts are incredibly memory-efficient
        return 1000;
    }
}
