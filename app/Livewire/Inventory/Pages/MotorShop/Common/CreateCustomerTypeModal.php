<?php

namespace App\Livewire\Inventory\Pages\MotorShop\Common;

use App\Livewire\Concerns\HasToast;
use App\Models\CustomerType;
use App\Traits\HasDataTable;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class CreateCustomerTypeModal extends Component
{
   use HasToast, HasDataTable, WithPagination;

    public ?int $type_id = null;
    public string $name = '';
    public string|float|int $discount_percentage = 0;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:customer_types,name,' . $this->type_id,
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ];
    }

    #[Computed]
    public function customerTypes()
    {
        return CustomerType::query()
            ->when($this->search, function ($query) {
                $searchTerm = '%' . trim($this->search) . '%';
                $query->where('name', 'like', $searchTerm);
            })
            ->withCount('customers') // Count how many customers have this type
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        try {
            $validated = $this->validate();

            if ($this->type_id) {
                // Update existing
                $type = CustomerType::findOrFail($this->type_id);
                $type->update($validated);
                $this->toastSuccess("Customer Type '{$type->name}' updated successfully!");
            } else {
                // Create new
                $type = CustomerType::create($validated);
                $this->toastSuccess("Customer Type '{$type->name}' created successfully!");
            }

            $this->resetForm();

            // Dispatch event to refresh the parent Customer page dropdowns
            $this->dispatch('page-reset');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->toastError('Please fix the errors before saving.');
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Failed to save customer type: ' . $e->getMessage());
        }
    }

    public function edit(int $id): void
    {
        $type = CustomerType::findOrFail($id);
        $this->type_id = $type->id;
        $this->name = $type->name;
        $this->discount_percentage = (float) $type->discount_percentage;
    }

    public function delete(int $id): void
    {
        try {
            $type = CustomerType::withCount('customers')->findOrFail($id);

            if ($errorMessage = $type->checkInUse(['customers'])) {
                $this->toastError($errorMessage);
                $this->resetForm();
                return;
            }

            // Security: Prevent deleting a type that is currently in use
            if ($type->customers_count > 0) {
                $this->toastError("Cannot delete '{$type->name}'. It is assigned to {$type->customers_count} customer(s).");
                return;
            }

            $type->delete();

            $this->toastSuccess("Customer Type deleted successfully!");
            $this->dispatch('page-reset');

            if ($this->type_id === $id) {
                $this->resetForm();
            }
        } catch (\Exception $e) {
            $this->toastError('Failed to delete customer type.');
        }
    }

    public function resetForm(): void
    {
        $this->reset(['type_id', 'name', 'discount_percentage']);
        $this->dispatch('close-modal', id: 'create-customer-type');
        $this->resetValidation();
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return [];
    }
}