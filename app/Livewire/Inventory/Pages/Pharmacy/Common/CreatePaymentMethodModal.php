<?php

namespace App\Livewire\Inventory\Pages\Pharmacy\Common;

use App\Livewire\Concerns\HasToast;
use App\Models\PaymentMethod; // Adjust this if your model namespace is different
use App\Traits\HasDataTable;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class CreatePaymentMethodModal extends Component
{
    use HasToast, HasDataTable, WithPagination;

    public ?int $payment_method_id = null;
    public string $name = '';
    public bool $is_active = true;
    public bool $requires_reference = false;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:payment_methods,name,' . $this->payment_method_id,
            'is_active' => 'boolean',
            'requires_reference' => 'boolean',
        ];
    }

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . trim($this->search) . '%');
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        try {
            $validated = $this->validate();

            if ($this->payment_method_id) {
                // Update existing
                $method = PaymentMethod::findOrFail($this->payment_method_id);
                $method->update($validated);
                $this->toastSuccess("Payment Method '{$method->name}' updated successfully!");
            } else {
                // Create new
                $method = PaymentMethod::create($validated);
                $this->toastSuccess("Payment Method '{$method->name}' created successfully!");
            }

            $this->resetForm();
            $this->dispatch('page-reset'); // Refresh parent components if they listen for this

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->toastError('Please fix the errors before saving.');
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Failed to save payment method: ' . $e->getMessage());
        }
    }

    public function edit(int $id): void
    {
        $method = PaymentMethod::findOrFail($id);
        $this->payment_method_id = $method->id;
        $this->name = $method->name;
        $this->is_active = (bool) $method->is_active;
        $this->requires_reference = (bool) $method->requires_reference;
    }

    public function delete(int $id): void
    {
        try {
            $method = PaymentMethod::findOrFail($id);

            if ($errorMessage = $method->checkInUse(['payments', 'transactions'])) {
                $this->toastError($errorMessage);
                $this->resetForm();
                return;
            }

            $method->delete();

            $this->toastSuccess("Payment method deleted successfully!");
            $this->dispatch('page-reset');

            if ($this->payment_method_id === $id) {
                $this->resetForm();
            }
        } catch (\Exception $e) {
            $this->toastError('Failed to delete payment method. It might be attached to existing transactions.');
        }
    }

    public function resetForm(): void
    {
        $this->reset(['payment_method_id', 'name']);
        $this->is_active = true; // Reset back to default true
        $this->requires_reference = false; // Reset back to default false
        $this->resetValidation();
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return [];
    }
}