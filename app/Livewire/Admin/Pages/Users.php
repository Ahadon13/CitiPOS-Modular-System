<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Concerns\HasToast;
use App\Models\Branch;
use App\Models\User;
use App\Enums\Permission;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.admin', ['title' => 'Users'])]
class Users extends Component
{
    use HasAuth, HasToast, HasDataTable, WithPagination;

    // Form Properties
    public ?int $user_id = null;
    public ?int $branch_id = null;
    public string $name = '';
    public string $username = '';
    public string $password = '';
    public string $role = '';
    public string $roleFilter = '';
    public array $permissions = [];

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $this->user_id,
            'password' => $this->user_id ? 'nullable|min:6' : 'required|min:6',
            'branch_id' => 'nullable|exists:branches,id',
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'array',
        ];
    }

    #[Computed]
    public function users()
    {
        return User::with(['branch', 'roles'])
            ->whereHas('roles', function ($q) {
                $q->whereNotIn('name', [\App\Enums\Role::SuperAdmin->value]);
            })
            ->when($this->search, function ($query) {
                $term = '%' . trim($this->search) . '%';
                $query->where('name', 'like', $term)
                      ->orWhere('username', 'like', $term);
            })
            ->when($this->roleFilter, function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', $this->roleFilter);
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function branches()
    {
        return Branch::orderBy('name')
            ->get()
            ->map(fn($b) => ['value' => $b->id, 'label' => $b->name])
            ->toArray();
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')
            ->get()
            ->map(fn($r) => ['value' => $r->name, 'label' => ucfirst(str_replace('-', ' ', $r->name))])
            ->toArray();
    }

    // --- Alpine JS Permission Helpers ---

    #[Computed]
    public function grouped_permissions(): array
    {
        $grouped = [];
        foreach (Permission::groupedPermissions() as $groupName => $cases) {
            $grouped[$groupName] = array_map(fn($case) => [
                'value' => $case->value,
                'label' => $case->label()
            ], $cases);
        }
        return $grouped;
    }

    #[Computed]
    public function all_permission_values(): array
    {
        return array_column(Permission::cases(), 'value');
    }

    // --- CRUD Actions ---

    public function save(): void
    {
        $validated = $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'username' => $this->username,
                'branch_id' => $this->branch_id ?: null,
            ];

            // Only update password if provided
            if (!empty($this->password)) {
                $data['password'] = Hash::make($this->password);
            }

            if ($this->user_id) {
                $user = User::findOrFail($this->user_id);
                if ($user->branch_id !== null) {
                    $data['branch_id'] = $user->branch_id;
                }
                $user->update($data);
                $msg = 'updated';
            } else {
                $user = User::create($data);
                $msg = 'created';
            }

            // Sync Spatie Role (Replacing old roles)
            $user->syncRoles([$this->role]);

            // Sync Spatie Direct Permissions
            $user->syncPermissions($this->permissions);

            $this->toastSuccess("User '{$user->name}' {$msg} successfully!");
            $this->resetForm();

        } catch (\Exception $e) {
            $this->toastError('Failed to save user: ' . $e->getMessage());
        }
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->user_id = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->branch_id = $user->branch_id;
        $this->role = $user->roles->first()?->name ?? '';

        // Load direct user permissions (Pluck the names)
        $this->permissions = $user->getDirectPermissions()->pluck('name')->toArray();

        // Clear password so it doesn't accidentally get overwritten
        $this->password = '';

        $this->dispatch('open-modal', id: 'manage-user');
    }

    public function delete(int $id): void
    {
        try {
            if (auth()->id() === $id) {
                $this->toastError("You cannot delete your own account.");
                return;
            }

            User::findOrFail($id)->delete();
            $this->toastSuccess("User deleted successfully!");

            if ($this->user_id === $id) {
                $this->resetForm();
            }
        } catch (\Exception $e) {
            $this->toastError('Failed to delete user.');
        }
    }

    public function resetForm(): void
    {
        $this->reset(['user_id', 'branch_id', 'name', 'username', 'password', 'role', 'permissions']);
        $this->resetValidation();
        $this->dispatch('close-modal', id: 'manage-user');
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['roleFilter'];
    }
}
