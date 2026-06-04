<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Pages;

use App\Enums\Role;
use App\Livewire\Concerns\HasToast;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Support\SalesFinancials;
use Exception;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Branches'])]

final class Branches extends Component
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
        return Branch::when($this->categoryId, fn ($q) => $q->where('product_category_id', $this->categoryId))
            ->withCount(['purchases as pending_po_count' => function ($query) {
                $query->where('status', '!=', 'completed');
            }])
            ->get()
            ->map(function ($branch) {
                $financials = SalesFinancials::calculate(branchId: $branch->id);

                $branch->profit = \Money\Money::PHP($financials['net_profit']);

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
        } catch (Exception $e) {
            $this->toastError('Failed to delete branch: '.$e->getMessage());
        }
    }

    #[On('page-reset')]
    public function refreshBranches(): void
    {
        unset($this->branches);
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
