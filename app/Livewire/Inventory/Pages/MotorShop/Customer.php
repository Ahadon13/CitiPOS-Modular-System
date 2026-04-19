<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\MotorShop;

use App\Exports\CustomerExport;
use App\Models\Customer as CustomerModel;
use App\Models\CustomerType;
use App\Livewire\Concerns\HasToast;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('components.layouts.motor-shop', ['title' => 'Customers', 'inventory' => true])]
final class Customer extends Component
{
    use HasAuth, HasToast, WithPagination, HasDataTable;

    public ?int $typeFilter = null;

    // Modal State
    public ?int $editingCustomerId = null;

    // Form Fields
    public string $name = '';
    public ?int $customer_type_id = null;
    public string $id_card_number = '';
    public string $booklet_number = '';
    public string $contact_number = '';
    public string $address = '';

    // --- Computed Properties for Cards ---

    #[Computed]
    public function stats(): array
    {
        return [
            'total_customers' => CustomerModel::count(),
            'discounted_customers' => CustomerModel::whereHas('customerType', function ($q) {
                $q->where('discount_percentage', '>', 0);
            })->count(),
            'total_types' => CustomerType::count(),
            'customers_with_sales' => CustomerModel::has('sales')->count(),
        ];
    }

    // --- Computed Properties for Dropdowns and Tables ---

    #[Computed]
    public function availableCustomerTypes(): array
    {
        return CustomerType::orderBy('name')->get()->map(function ($type) {
            $discount = (float) $type->discount_percentage > 0 ? " ({$type->discount_percentage}% off)" : '';
            return [
                'value' => $type->id,
                'label' => $type->name . $discount,
            ];
        })->toArray();
    }

    #[Computed]
    public function customers()
    {
        $query = CustomerModel::query()->with(['customerType'])
            ->withCount('sales'); // Get total transactions per customer

        // Dropdown Filter
        $query->when($this->typeFilter, function (Builder $q) {
            $q->where('customer_type_id', $this->typeFilter);
        });

        // Text Search
        $query->when($this->search, function (Builder $q) {
            $searchTerm = '%' . trim($this->search) . '%';
            $q->where(function ($sub) use ($searchTerm) {
                $sub->where('name', 'like', $searchTerm)
                    ->orWhere('id_card_number', 'like', $searchTerm)
                    ->orWhere('contact_number', 'like', $searchTerm);
            });
        });

        return $query->orderBy('name', 'asc')->paginate($this->perPage);
    }

    // --- Actions ---

    public function exportCustomers()
    {
        try {
            $fileName = 'Customers_List_' . now()->format('Y_m_d_His') . '.xlsx';

            return Excel::download(
                new CustomerExport($this->search, $this->typeFilter),
                $fileName
            );
        } catch (\Exception $e) {
            $this->toastError('Failed to generate export: ' . $e->getMessage());
        }
    }

    public function editCustomer(int $id): void
    {
        $this->resetValidation();
        $customer = CustomerModel::findOrFail($id);

        $this->editingCustomerId = $customer->id;
        $this->name = $customer->name;
        $this->customer_type_id = $customer->customer_type_id;
        $this->id_card_number = $customer->id_card_number ?? '';
        $this->booklet_number = $customer->booklet_number ?? '';
        $this->contact_number = $customer->contact_number ?? '';
        $this->address = $customer->address ?? '';

    }

    public function saveCustomer(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'customer_type_id' => 'required|exists:customer_types,id',
            'id_card_number' => 'nullable|string|max:255',
            'booklet_number' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Customer name is required.',
            'id_card_number.unique' => 'A customer with this ID card number already exists.',
            'booklet_number.unique' => 'A customer with this booklet number already exists.',
            'customer_type_id.required' => 'Please select a customer type.',
            'customer_type_id.exists' => 'The selected customer type is invalid.',
            'contact_number.unique' => 'A customer with this contact number already exists.',
            'address.max' => 'Address cannot exceed 500 characters.',
        ]);

        try {
            CustomerModel::updateOrCreate(
                ['id' => $this->editingCustomerId],
                [
                    'name' => $this->name,
                    'customer_type_id' => $this->customer_type_id,
                    'id_card_number' => $this->id_card_number,
                    'booklet_number' => $this->booklet_number,
                    'contact_number' => $this->contact_number,
                    'address' => $this->address,
                ]
            );

            $this->toastSuccess($this->editingCustomerId ? 'Customer updated successfully.' : 'New customer added successfully.');
            $this->resetForm();
            $this->dispatch('close-modal', id: 'customer-form');

        } catch (\Exception $e) {
            $this->toastError('Failed to save customer: ' . $e->getMessage());
        }
    }

    public function deleteCustomer(int $id): void
    {
        try {
            $customer = CustomerModel::findOrFail($id);

            // Prevent deleting customers with existing sales
            if ($customer->sales()->exists()) {
                $this->toastError("Cannot delete {$customer->name} because they have existing transaction records.");
                return;
            }

            $customer->delete();
            $this->toastSuccess('Customer removed successfully.');

            if ($this->customers()->isEmpty() && $this->page > 1) {
                $this->previousPage();
            }

            $this->dispatch('close-modal', id: 'customer-form');

        } catch (\Exception $e) {
            $this->toastError('Failed to delete customer.');
        }
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingCustomerId', 'name', 'customer_type_id',
            'id_card_number', 'booklet_number', 'contact_number', 'address'
        ]);
        $this->resetValidation();
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['typeFilter'];
    }
}