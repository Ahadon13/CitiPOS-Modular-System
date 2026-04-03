<?php

namespace App\Livewire\Admin\Common;

use App\Livewire\Concerns\HasToast;
use App\Modules\Main\User\Enums\Permission as AppPermission;
use App\Traits\HasDataTable;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

final class ManageRoleModal extends Component
{
    use HasToast, HasDataTable, WithPagination;

    public ?int $role_id = null;
    public string $name = '';
    public function rules(): array
    {
        return [
            // Spatie roles require a unique name
            'name' => 'required|string|max:255|unique:roles,name,' . $this->role_id,
        ];
    }

    #[Computed]
    public function roles()
    {
        return Role::with('permissions')
            ->when($this->search, function ($query) {
                $searchTerm = '%' . trim($this->search) . '%';
                $query->where('name', 'like', $searchTerm);
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        try {
            $this->validate();

            // Format the name to be lowercase and dash-separated (standard Spatie practice)
            $formattedName = strtolower(str_replace(' ', '-', $this->name));

            if ($this->role_id) {
                // Update existing
                $role = Role::findOrFail($this->role_id);

                // Prevent editing system-critical roles
                if (in_array($role->name, ['super-admin', 'admin'])) {
                    $this->toastError("You cannot modify core system roles.");
                    return;
                }

                $role->update(['name' => $formattedName]);
                $this->toastSuccess("Role '{$role->name}' updated successfully!");
            } else {
                // Create new
                $role = Role::create(['name' => $formattedName, 'guard_name' => 'web']);
                $this->toastSuccess("Role '{$role->name}' created successfully!");
            }

            $this->resetForm();
            $this->dispatch('page-reset');

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->toastError('Please fix the errors before saving.');
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Failed to save role: ' . $e->getMessage());
        }
    }

    public function edit(int $id): void
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, ['super-admin', 'admin'])) {
            $this->toastError("Core system roles cannot be edited.");
            return;
        }

        $this->role_id = $role->id;
        // Convert 'store-manager' back to 'Store Manager' for display
        $this->name = ucwords(str_replace('-', ' ', $role->name));

    }

    public function delete(int $id): void
    {
        try {
            $role = Role::findOrFail($id);

            if (in_array($role->name, ['super-admin', 'admin'])) {
                $this->toastError("Core system roles cannot be deleted.");
                return;
            }

            // Optional: Prevent deletion if users are currently assigned to this role
            if ($role->users()->exists()) {
                $this->toastError("Cannot delete role '{$role->name}' because users are assigned to it.");
                return;
            }

            $role->delete();

            $this->toastSuccess("Role deleted successfully!");
            $this->dispatch('page-reset');

            if ($this->role_id === $id) {
                $this->resetForm();
            }
        } catch (\Exception $e) {
            $this->toastError('Failed to delete role.');
        }
    }

    public function resetForm(): void
    {
        $this->reset(['role_id', 'name', 'permissions']);
        $this->resetValidation();
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return [];
    }
}
