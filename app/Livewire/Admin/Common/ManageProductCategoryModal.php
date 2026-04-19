<?php

namespace App\Livewire\Admin\Common;

use App\Livewire\Concerns\HasToast;
use App\Models\ProductCategory; // Ensure this matches your actual Category model import
use App\Traits\HasDataTable;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

final class ManageProductCategoryModal extends Component
{
    use HasToast, HasDataTable, WithPagination;

    public ?int $product_category_id = null;
    public string $name = '';

    public function rules(): array
    {
        return [
            // Ensure 'product_categories' matches your actual database table name
            'name' => 'required|string|max:255|unique:product_categories,name,' . $this->product_category_id,
        ];
    }

    #[Computed]
    public function categories()
    {
        return ProductCategory::query()
            ->when($this->search, function ($query) {
                $searchTerm = '%' . trim($this->search) . '%';
                $query->where('name', 'like', $searchTerm);
            })
            // Optional: If you want to show how many products belong to this category
            // ->withCount('products')
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        try {
            $validated = $this->validate();

            if ($this->product_category_id) {
                // Update existing
                $category = ProductCategory::findOrFail($this->product_category_id);
                $category->update($validated);
                $this->toastSuccess("Category '{$category->name}' updated successfully!");
            } else {
                // Create new
                $category = ProductCategory::create($validated);
                $this->toastSuccess("Category '{$category->name}' created successfully!");
            }

            $this->resetForm();
            // Dispatch event to refresh dropdowns on the parent page
            $this->dispatch('page-reset');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->toastError('Please fix the errors before saving.');
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Failed to save category: ' . $e->getMessage());
        }
    }

    public function edit(int $id): void
    {
        $category = ProductCategory::findOrFail($id);
        $this->product_category_id = $category->id;
        $this->name = $category->name;
    }

    public function delete(int $id): void
    {
        try {
            $category = ProductCategory::findOrFail($id);

            // Optional: Prevent deletion if products are attached to this category
            if ($errorMessage = $category->checkInUse(['branches', 'products'])) {
                $this->toastError($errorMessage);
                $this->resetForm();
                return;
            }

            $category->delete();

            $this->toastSuccess("Category deleted successfully!");
            $this->dispatch('page-reset');

            if ($this->product_category_id === $id) {
                $this->resetForm();
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $this->toastError("Cannot delete '{$category->name}'. It is currently in use by other records.");
        } catch (\Exception $e) {
            $this->toastError('Failed to delete category.');
        }
    }

    public function resetForm(): void
    {
        $this->reset(['product_category_id', 'name']);
        $this->resetValidation();
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return [];
    }
}
