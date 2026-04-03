<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Pharmacy;

use App\Exports\InventoryStocksExport;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\Inventory\UpdateBatchForm;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Money\Money;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('components.layouts.app', ['title' => 'Inventory Stocks', 'inventory' => true])]
final class Stocks extends Component
{
    use HasAuth, HasToast, HasDataTable, WithPagination;

    public UpdateBatchForm $form;

    public string $stockFilter = 'all';
    public ?int $selectedProduct = null;
    public ?string $expirationDateFilter = null;

    protected array $targetCategories = ['Pharmacy', 'Medicine'];

    // --- Computed Properties for Cards ---

    #[Computed]
    public function totalStockValue(): Money
    {
        $query = InventoryBatch::where('branch_id', $this->currentBranchId)
            ->where('quantity_on_hand', '>', 0);
        $this->applyPharmacyScope($query, 'product.productCategory');

        $totalCents = $query->sum(DB::raw('quantity_on_hand * cost_per_unit'));
        return Money::PHP((string) round((float) $totalCents));
    }

    #[Computed]
    public function activeBatchesCount(): int
    {
        $query = InventoryBatch::where('branch_id', $this->currentBranchId)
            ->where('quantity_on_hand', '>', 0);
        $this->applyPharmacyScope($query, 'product.productCategory');

        return $query->count();
    }

    #[Computed]
    public function criticalExpiryCount(): int
    {
        $query = InventoryBatch::where('branch_id', $this->currentBranchId)
            ->where('quantity_on_hand', '>', 0)
            ->where('expiration_date', '<=', Carbon::now()->addMonths(3))
            ->where('expiration_date', '>', Carbon::now());
        $this->applyPharmacyScope($query, 'product.productCategory');

        return $query->count();
    }

    #[Computed]
    public function totalProductsCount(): int
    {
        $query = Product::query()->where('branch_id', $this->currentBranchId);
        $this->applyPharmacyScope($query, 'productCategory');
        return $query->count();
    }

    // --- Computed Properties for Dropdowns and Tables ---

    #[Computed]
    public function availableProducts()
    {
        $query = Product::query()->where('branch_id', $this->currentBranchId)->orderBy('brand_name');
        $this->applyPharmacyScope($query, 'productCategory');

        return $query->get()->map(function ($product) {
            return [
                'value' => $product->id,
                'label' => $product->brand_name  . ' - ' . $product->dosage . ' (' . $product->generic_name . ')',
            ];
        })->toArray();
    }

    #[Computed]
    public function inventoryBatches()
    {
        $query = InventoryBatch::query()
            ->with(['product.productCategory', 'product.baseUnit'])
            ->where('branch_id', $this->currentBranchId);

        $this->applyPharmacyScope($query, 'product.productCategory');

        // Product Dropdown Filter
        $query->when($this->selectedProduct, function ($q) {
            $q->where('product_id', $this->selectedProduct);
        });

        // Expiration Date Exact Match Filter
        $query->when(!empty($this->expirationDateFilter), function ($q) {
            try {
                // Parse the selected month/year string (e.g., "March 2026")
                $date = Carbon::parse($this->expirationDateFilter);

                // Filter where the database year and month match the selection
                $q->whereYear('expiration_date', $date->year)
                  ->whereMonth('expiration_date', $date->month);
            } catch (\Exception $e) {
                // Failsafe in case of bad formatting
            }
        });

        // Text Search
        $query->when($this->search, function ($q) {
            $searchTerm = '%' . trim($this->search) . '%';
            $q->where(function ($sub) use ($searchTerm) {
                $sub->where('batch_number', 'like', $searchTerm)
                    ->orWhereHas('product', function ($prodQuery) use ($searchTerm) {
                        $prodQuery->where('brand_name', 'like', $searchTerm)
                                  ->orWhere('generic_name', 'like', $searchTerm);
                    });
            });
        });

        // Tabs Filter Logic
        if ($this->stockFilter === 'expiring') {
            $query->where('quantity_on_hand', '>', 0)
                  ->where('expiration_date', '<=', Carbon::now()->addMonths(3))
                  ->where('expiration_date', '>', Carbon::now());
        } elseif ($this->stockFilter === 'expired') {
            $query->where('quantity_on_hand', '>', 0)
                  ->where('expiration_date', '<=', Carbon::now());
        } elseif ($this->stockFilter === 'out_of_stock') {
             // To accurately find OOS in a flat batch list, we usually look for batches that hit 0.
             // If you want products with NO batches at all, that's harder in a batch-centric query.
            $query->where('quantity_on_hand', '<=', 0);
        } else {
             // By default, only show active batches (qty > 0)
            $query->where('quantity_on_hand', '>', 0);
        }

        return $query->orderBy('expiration_date', 'asc')->paginate($this->perPage);
    }

    // --- Actions ---

    // The button will dispatch an event with the ID. We catch it here.
    #[On('load-edit-batch')]
    public function loadEditBatch(int $id): void
    {
        $batch = InventoryBatch::with('product')->findOrFail($id);

        $this->form->setBatch($batch);

        // Dispatch to Alpine to open the modal and set the product name
        $this->dispatch('open-edit-batch-modal',
            productName: $batch->product->brand_name . ' (' . $batch->product->generic_name . ')'
        );
    }

    public function updateBatch(): void
    {
        try {
            $this->form->update();
            $this->toastSuccess('Batch updated successfully.');

            // Tell Alpine to close the modal
            $this->dispatch('close-modal', id: 'edit-batch');

        } catch (\Exception $e) {
            $this->toastError('Failed to update batch: ' . $e->getMessage());
        }
    }

    public function deleteBatch(int $id): void
    {
        try {
            InventoryBatch::findOrFail($id)->delete();
            $this->toastSuccess('Batch removed from inventory.');

            if ($this->inventoryBatches()->isEmpty() && $this->page > 1) {
                $this->previousPage();
            }
        } catch (\Exception $e) {
            $this->toastError('Cannot delete this batch. It may be tied to existing sales records.');
        }
    }

    public function exportLedger()
    {
        try {
            $fileName = 'Pharmacy_Stocks_' . now()->format('Y_m_d_His') . '.xlsx';

            return Excel::download(
                new InventoryStocksExport(
                    $this->currentBranchId,
                    $this->search,
                    $this->stockFilter,
                    $this->selectedProduct,
                    $this->expirationDateFilter,
                    $this->targetCategories
                ),
                $fileName
            );
        } catch (\Exception $e) {
            $this->toastError('Failed to generate export: ' . $e->getMessage());
        }
    }

    protected function applyPharmacyScope(Builder $query, string $relationPathToCategory = 'product.productCategory'): void
    {
        $query->whereHas($relationPathToCategory, function ($q) {
            $q->whereIn('name', $this->targetCategories);
        });
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['selectedProduct', 'stockFilter', 'expirationDateFilter'];
    }
}
