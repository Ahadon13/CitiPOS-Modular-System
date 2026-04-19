<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\MotorShop;

use App\Enums\Product\CategoryType;
use App\Exports\PurchasesExport;
use App\Livewire\Concerns\HasToast;
use App\Models\Purchase as PurchaseModel;
use App\Models\Supplier;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('components.layouts.motor-shop', ['title' => 'Inventory Purchase', 'inventory' => true])]
final class Purchase extends Component
{
    use HasAuth, HasToast, HasDataTable, WithPagination;

    public ?array $selected_purchase = null;
    public ?array $view_purchase = null;
    public string $statusFilter = 'all';
    public ?int $supplierFilter = null;
    public $listeners = ['page-reset' => '$refresh'];


    #[Computed]
    public function baseQuery(): Builder|PurchaseModel
    {
        $query = PurchaseModel::where('branch_id', $this->currentBranchId);
        $query->whereHas('purchaseItems.product.productCategory', fn (Builder $q) => $q->where('name', CategoryType::MotorShop->value));

        // Apply Status Filter from Tabs
        $query->when($this->statusFilter !== 'all', function ($q) {
            $q->where('status', $this->statusFilter);
        });

        // Apply Supplier Filter
        $query->when($this->supplierFilter !== null, function ($q) {
            $q->where('supplier_id', $this->supplierFilter);
        });

        if (!empty($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('reference_no', 'like', $searchTerm)
                  ->orWhereHas('supplier', function ($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  });
            });
        }

        return $query;
    }

    #[Computed]
    public function stats(): array
    {
        // For stats, we look at the whole branch, ignoring the current search/tab filter
        $query = PurchaseModel::where('branch_id', $this->currentBranchId)
            ->whereHas('purchaseItems.product.productCategory', fn (Builder $q) => $q->where('name', CategoryType::MotorShop->value));

        $result = $query->selectRaw('
            COUNT(id) as total_count,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as receiving_count,
            SUM(total_cost) as total_value
        ')->first();

        return [
            'total_orders'    => (int) ($result->total_count ?? 0),
            'pending_orders'  => (int) ($result->pending_count ?? 0),
            'receiving_orders'=> (int) ($result->receiving_count ?? 0),
            'total_suppliers' => Supplier::count(), // Global supplier count
        ];
    }

    #[Computed]
    public function supplierOptions(): array
    {
        return Supplier::get()->map(function ($supplier) {
            return [
                'label' => $supplier->name,
                'value' => $supplier->id,
            ];
        })->toArray();
    }

    #[Computed]
    public function purchases()
    {
        return clone $this->baseQuery
            ->with(['supplier', 'user', 'purchaseItems.product', 'purchaseItems.unit'])
            ->withCount('purchaseItems')
            ->orderBy($this->sort['column'] ?? 'created_at', $this->sort['direction'] ?? 'desc')
            ->paginate($this->perPage);
    }

    public function exportPurchases()
    {
        try {
            $fileName = 'Purchases_Report_' . now()->format('Y_m_d_His') . '.xlsx';

            return Excel::download(
                new PurchasesExport(
                    $this->currentBranchId,
                    $this->statusFilter,
                    $this->supplierFilter,
                    $this->search ?? '',
                    [CategoryType::MotorShop->value]
                ),
                'Motor Shop_' . $fileName
            );
        } catch (\Exception $e) {
            $this->toastError('Failed to generate export: ' . $e->getMessage());
        }
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['statusFilter'];
    }
}
