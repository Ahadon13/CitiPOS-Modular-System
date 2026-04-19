<?php

namespace App\Livewire\Admin\Common;

use App\Enums\Product\CategoryType;
use App\Enums\Role;
use App\Livewire\Concerns\HasToast;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Traits\HasDataTable;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

final class ManageBranchModal extends Component
{
    use HasToast, HasDataTable, WithPagination;

    public ?int $branch_id = null;
    public ?int $product_category_id = null;
    public string $name = '';
    public string $address = '';
    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'product_category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255|unique:branches,name,' . $this->branch_id,
            'address' => 'required|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    #[Computed]
    public function categories()
    {
        return ProductCategory::orderBy('name')
            ->get()
            ->map(fn($cat) => [
                'value' => $cat->id,
                'label' => CategoryType::tryFrom($cat->name)?->label() ?? $cat->name,
            ])
            ->toArray();
    }

    #[Computed]
    public function branches()
    {
        return Branch::with('productCategory')
            ->when($this->search, function ($query) {
                $searchTerm = '%' . trim($this->search) . '%';
                $query->where('name', 'like', $searchTerm)
                      ->orWhere('address', 'like', $searchTerm);
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        try {
            $validated = $this->validate();

            if ($this->branch_id) {
                // Update existing
                $branch = Branch::findOrFail($this->branch_id);
                $branch->update($validated);
                $this->toastSuccess("Branch '{$branch->name}' updated successfully!");
            } else {
                // Create new
                $branch = Branch::create($validated);
                $this->toastSuccess("Branch '{$branch->name}' created successfully!");
            }

            $this->resetForm();
            // Dispatch event to refresh the parent page grid/tables
            $this->dispatch('page-reset');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->toastError('Please fix the errors before saving.');
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Failed to save branch: ' . $e->getMessage());
        }
    }

    public function edit(int $id): void
    {
        $branch = Branch::findOrFail($id);
        $this->branch_id = $branch->id;
        $this->product_category_id = $branch->product_category_id;
        $this->name = $branch->name;
        $this->address = $branch->address;
        $this->is_active = $branch->is_active;
    }

    public function delete(int $id): void
    {
        try {
            if (! (auth()->user()?->hasAnyRole(Role::adminRoles()) ?? false)) {
                $this->toastError('You are not allowed to delete branches.');

                return;
            }

            $branch = Branch::findOrFail($id);

            if ($errorMessage = $branch->checkInUse([
                'users',
                'products',
                'inventoryBatches',
                'inventoryTransactions',
                'sales',
                'purchases',
                'expenses',
                'partnerships',
            ])) {
                $this->toastError($errorMessage);
                $this->resetForm();
                return;
            }

            $branch->delete();

            $this->toastSuccess("Branch deleted successfully!");
            $this->dispatch('page-reset');

            if ($this->branch_id === $id) {
                $this->resetForm();
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $this->toastError("Cannot delete '{$branch->name}'. It may have associated records.");
        } catch (\Exception $e) {
            $this->toastError('Failed to delete branch.');
        }
    }

    public function resetForm(): void
    {
        $this->reset(['branch_id', 'product_category_id', 'name', 'address']);
        $this->is_active = true; // Reset to default true
        $this->resetValidation();
        // Note: we don't close the modal here so the user can see the updated list
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return [];
    }
}
