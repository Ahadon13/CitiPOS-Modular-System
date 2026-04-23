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
    public array $batchUsers = [];
    public ?int $branch_id = null;
    public string $name = '';
    public string $username = '';
    public string $password = '';
    public string $role = '';
    public string $roleFilter = '';
    public array $branch_ids = [];
    public array $permissions = [];

    public function mount(): void
    {
        $this->batchUsers = [$this->emptyUserRow()];
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $this->user_id,
            'password' => $this->user_id ? 'nullable|min:6' : 'required|min:6',
            'branch_id' => 'nullable|exists:branches,id',
            'branch_ids' => 'array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    #[Computed]
    public function users()
    {
        return User::with(['branch', 'accessibleBranches.productCategory', 'roles'])
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
            ->with('productCategory')
            ->get()
            ->map(fn($b) => [
                'value' => $b->id,
                'label' => $b->name . ' (' . ($b->productCategory->name ?? 'No module') . ')',
            ])
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
        try {
            if ($this->user_id) {
                $this->saveSingleUser();
            } else {
                $this->saveBatchUsers();
            }
        } catch (\Exception $e) {
            $this->toastError('Failed to save user: ' . $e->getMessage());
        }
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->resetValidation();
        $this->batchUsers = [$this->emptyUserRow()];
        $this->user_id = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->branch_id = $user->branch_id;
        $this->branch_ids = $user->accessibleBranches()
            ->pluck('branches.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($this->branch_id && ! in_array($this->branch_id, $this->branch_ids, true)) {
            $this->branch_ids[] = $this->branch_id;
        }

        $this->role = $user->roles->first()?->name ?? '';

        // Load direct user permissions (Pluck the names)
        $this->permissions = $user->getDirectPermissions()->pluck('name')->toArray();

        // Clear password so it doesn't accidentally get overwritten
        $this->password = '';

        $this->dispatch('open-modal', id: 'manage-user');
    }

    public function startCreate(): void
    {
        $this->resetFormState();
        $this->resetValidation();
        $this->dispatch('open-modal', id: 'manage-user');
    }

    public function addUserRow(): void
    {
        $this->batchUsers[] = $this->emptyUserRow();
    }

    public function removeUserRow(int $index): void
    {
        if (count($this->batchUsers) === 1) {
            $this->batchUsers = [$this->emptyUserRow()];
            return;
        }

        unset($this->batchUsers[$index]);
        $this->batchUsers = array_values($this->batchUsers);
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
        $this->resetFormState();
        $this->resetValidation();
        $this->dispatch('close-modal', id: 'manage-user');
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['roleFilter'];
    }

    private function saveSingleUser(): void
    {
        $this->validate($this->rules());

        $branchIds = collect($this->branch_ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($this->branch_id && ! $branchIds->contains((int) $this->branch_id)) {
            $branchIds->push((int) $this->branch_id);
        }

        if (! $this->branch_id && $branchIds->count() === 1) {
            $this->branch_id = $branchIds->first();
        }

        $data = [
            'name' => $this->name,
            'username' => $this->username,
            'branch_id' => $this->branch_id ?: null,
        ];

        if (! empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $user = User::findOrFail($this->user_id);
        $user->update($data);
        $user->syncRoles([$this->role]);
        $user->accessibleBranches()->sync($branchIds->all());
        $user->syncPermissions($this->permissions);

        $this->toastSuccess("User '{$user->name}' updated successfully!");
        $this->resetForm();
    }

    private function saveBatchUsers(): void
    {
        $validated = $this->validate($this->batchRules(), $this->batchMessages());

        $createdUsers = [];

        foreach ($validated['batchUsers'] as $entry) {
            $branchIds = collect($entry['branch_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $defaultBranchId = ! empty($entry['branch_id']) ? (int) $entry['branch_id'] : null;

            if ($defaultBranchId && ! $branchIds->contains($defaultBranchId)) {
                $branchIds->push($defaultBranchId);
            }

            if (! $defaultBranchId && $branchIds->count() === 1) {
                $defaultBranchId = $branchIds->first();
            }

            $user = User::create([
                'name' => $entry['name'],
                'username' => $entry['username'],
                'password' => Hash::make($entry['password']),
                'branch_id' => $defaultBranchId,
            ]);

            $user->syncRoles([$entry['role']]);
            $user->accessibleBranches()->sync($branchIds->all());
            $user->syncPermissions($entry['permissions'] ?? []);

            $createdUsers[] = $user->name;
        }

        $summary = count($createdUsers) === 1
            ? "User '{$createdUsers[0]}' created successfully!"
            : count($createdUsers) . ' users created successfully!';

        $this->toastSuccess($summary);
        $this->resetForm();
    }

    private function batchRules(): array
    {
        return [
            'batchUsers' => 'required|array|min:1',
            'batchUsers.*.name' => 'required|string|max:255',
            'batchUsers.*.username' => 'required|string|max:255|distinct|unique:users,username',
            'batchUsers.*.password' => 'required|string|min:6',
            'batchUsers.*.branch_id' => 'nullable|exists:branches,id',
            'batchUsers.*.branch_ids' => 'array',
            'batchUsers.*.branch_ids.*' => 'integer|exists:branches,id',
            'batchUsers.*.role' => 'required|string|exists:roles,name',
            'batchUsers.*.permissions' => 'array',
            'batchUsers.*.permissions.*' => 'string|exists:permissions,name',
        ];
    }

    private function batchMessages(): array
    {
        return [
            'batchUsers.required' => 'Add at least one user.',
            'batchUsers.min' => 'Add at least one user.',
            'batchUsers.*.name.required' => 'Full name is required for every user.',
            'batchUsers.*.username.required' => 'Username is required for every user.',
            'batchUsers.*.username.distinct' => 'Usernames in this batch must be unique.',
            'batchUsers.*.username.unique' => 'One of the usernames already exists.',
            'batchUsers.*.password.required' => 'Password is required for every user.',
            'batchUsers.*.password.min' => 'Each password must be at least 6 characters.',
            'batchUsers.*.role.required' => 'Select a role for every user.',
            'batchUsers.*.role.exists' => 'One of the selected roles is invalid.',
        ];
    }

    private function resetFormState(): void
    {
        $this->reset([
            'user_id',
            'branch_id',
            'branch_ids',
            'name',
            'username',
            'password',
            'role',
            'permissions',
            'batchUsers',
        ]);

        $this->batchUsers = [$this->emptyUserRow()];
    }

    private function emptyUserRow(): array
    {
        return [
            'name' => '',
            'username' => '',
            'password' => '',
            'branch_id' => null,
            'branch_ids' => [],
            'role' => '',
            'permissions' => [],
        ];
    }
}
