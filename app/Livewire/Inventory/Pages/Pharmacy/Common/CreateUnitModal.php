<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Pharmacy\Common;

use App\Livewire\Concerns\HasToast;
use App\Models\Unit;
use App\Traits\HasDataTable;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

final class CreateUnitModal extends Component
{
    use HasToast, HasDataTable, WithPagination;

    public ?int $unit_id = null;
    public string $name = '';
    public string $abbreviation = '';

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:units,name,' . $this->unit_id,
            'abbreviation' => 'required|string|max:50|unique:units,abbreviation,' . $this->unit_id,
        ];
    }

    #[Computed]
    public function units()
    {
        return Unit::query()
            ->when($this->search, function ($query) {
                $searchTerm = '%' . trim($this->search) . '%';
                $query->where('name', 'like', $searchTerm)
                      ->orWhere('abbreviation', 'like', $searchTerm);
            })
            // If you have a products relationship, you can use withCount('products') here
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        try {
            $validated = $this->validate();

            if ($this->unit_id) {
                // Update existing
                $unit = Unit::findOrFail($this->unit_id);
                $unit->update($validated);
                $this->toastSuccess("Unit '{$unit->name}' updated successfully!");
            } else {
                // Create new
                $unit = Unit::create($validated);
                $this->toastSuccess("Unit '{$unit->name}' created successfully!");
            }

            $this->resetForm();

            // Dispatch event to refresh parent page dropdowns
            $this->dispatch('page-reset');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->toastError('Please fix the errors before saving.');
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Failed to save unit: ' . $e->getMessage());
        }
    }

    public function edit(int $id): void
    {
        $unit = Unit::findOrFail($id);
        $this->unit_id = $unit->id;
        $this->name = $unit->name;
        $this->abbreviation = $unit->abbreviation;
    }

    public function delete(int $id): void
    {
        try {
            $unit = Unit::findOrFail($id);

            // Proactively check if it's safe to delete before attempting deletion
            if ($errorMessage = $unit->checkInUse(['products', 'purchaseItems', 'saleItems', 'productPackagings'])) {
                $this->toastError($errorMessage);
                $this->resetForm();
                return;
            }

            $unit->delete();

            $this->toastSuccess("Unit deleted successfully!");
            $this->dispatch('page-reset');

            if ($this->unit_id === $id) {
                $this->resetForm();
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // This catches standard SQL foreign key constraint violations
            $this->toastError("Cannot delete '{$unit->name}'" . $e->getMessage());
        } catch (\Exception $e) {
            $this->toastError('Failed to delete unit.'. $e->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->reset(['unit_id', 'name', 'abbreviation']);
        $this->dispatch('close-modal', id: 'create-unit');
        $this->resetValidation();
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return [];
    }

}