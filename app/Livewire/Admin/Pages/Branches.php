<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\Role;
use App\Livewire\Concerns\HasToast;
use App\Models\Branch;
use App\Models\ProductCategory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Branches'])]

class Branches extends Component
{
    use HasToast;

    public ?int $categoryId = null;

    #[Computed]
    public function productCategories()
    {
        return ProductCategory::orderBy('name')->get();
    }

    #[Computed]
    public function branches()
    {
        // Count purchases where status is not completed, aliased as 'pending_po_count'
        return Branch::when($this->categoryId, fn($q) => $q->where('product_category_id', $this->categoryId))
            ->withCount(['purchases as pending_po_count' => function ($query) {
                $query->where('status', '!=', 'completed');
            }])
            ->get()
            ->map(function ($branch) {
                // Aggregate financial data for this branch
                $stats = DB::table('sales')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sales.branch_id', $branch->id)
                    ->where('sales.status', 'completed') // Optional: only calculate profit from completed sales
                    ->select(
                        DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                        DB::raw('SUM(sale_items.cost_at_moment * sale_items.quantity) as total_cost')
                    )
                    ->first();

                $revenue = $stats->total_revenue ?? 0;
                $cost = $stats->total_cost ?? 0;

                // Calculate Profit
                $branch->profit = \Money\Money::PHP((string) round((float)($revenue - $cost)));

                // Fallback to 0 if null
                $branch->pending_po_count = $branch->pending_po_count ?? 0;

                return $branch;
            });
    }

    #[Computed]
    public function canDeleteBranches(): bool
    {
        return auth()->user()?->hasAnyRole(Role::adminRoles()) ?? false;
    }

    public function delete(int $branchId): void
    {
        if (! $this->canDeleteBranches) {
            $this->toastError('You are not allowed to delete branches.');

            return;
        }

        try {
            $branch = Branch::findOrFail($branchId);

            if ($errorMessage = $branch->checkInUse($this->branchUsageRelationships())) {
                $this->toastError($errorMessage);

                return;
            }

            $branchName = $branch->name;
            $branch->delete();

            unset($this->branches);

            $this->toastSuccess("Branch '{$branchName}' deleted successfully.");
        } catch (QueryException $e) {
            $this->toastError('Cannot delete this branch because it is still referenced by existing records.');
        } catch (\Exception $e) {
            $this->toastError('Failed to delete branch: ' . $e->getMessage());
        }
    }

    private function branchUsageRelationships(): array
    {
        return [
            'users',
            'products',
            'inventoryBatches',
            'inventoryTransactions',
            'sales',
            'purchases',
            'expenses',
            'partnerships',
        ];
    }
}
