<?php

namespace App\Livewire\Inventory\Pages\Grocery\Common;

use App\Livewire\Concerns\HasToast;
use App\Models\Supplier;
use App\Traits\HasDataTable;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class CreateSupplierModal extends Component
{
    use HasToast, HasDataTable, WithPagination;

    public ?int $supplier_id = null;
    public string $name = '';
    public string $contact_info = '';

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:suppliers,name,' . $this->supplier_id,
            'contact_info' => 'nullable|string|max:255',
        ];
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::query()->when($this->search, function ($query) {
                // Filter by name or contact info
                $searchTerm = '%' . trim($this->search) . '%';
                $query->where('name', 'like', $searchTerm)
                      ->orWhere('contact_info', 'like', $searchTerm);
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        try {
            $validated = $this->validate();

            if ($this->supplier_id) {
                // Update existing
                $supplier = Supplier::findOrFail($this->supplier_id);
                $supplier->update($validated);
                $this->toastSuccess("Supplier '{$supplier->name}' updated successfully!");
            } else {
                // Create new
                $supplier = Supplier::create($validated);
                $this->toastSuccess("Supplier '{$supplier->name}' created successfully!");
            }

            $this->resetForm();
            $this->dispatch('page-reset'); // Refresh parent table if needed

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->toastError('Please fix the errors before saving.');
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Failed to save supplier: ' . $e->getMessage());
        }
    }

    public function edit(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        $this->supplier_id = $supplier->id;
        $this->name = $supplier->name;
        $this->contact_info = $supplier->contact_info ?? '';
    }

    public function delete(int $id): void
    {
        try {
            $supplier = Supplier::findOrFail($id);

            // Proactively check if it's safe to delete!
            if ($errorMessage = $supplier->checkInUse(['products', 'purchases'])) {
                $this->toastError($errorMessage);
                $this->resetForm();
                return; // Stop execution
            }

            $supplier->delete();

            $this->toastSuccess("Supplier deleted successfully!");
            $this->dispatch('page-reset');

            if ($this->supplier_id === $id) {
                $this->resetForm();
            }
        } catch (\Exception $e) {
            $this->toastError('Failed to delete supplier. They might be attached to an existing Purchase Order.');
        }
    }

    public function resetForm(): void
    {
        $this->reset(['supplier_id', 'name', 'contact_info']);
        $this->dispatch('close-modal', id: 'create-supplier');
        $this->resetValidation();
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return [];
    }
}