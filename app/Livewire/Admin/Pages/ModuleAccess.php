<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Pages;

use App\Enums\Role as RoleEnum;
use App\Livewire\Concerns\HasToast;
use App\Support\ModuleAccess as ModuleAccessSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.admin', ['title' => 'Module Access'])]
final class ModuleAccess extends Component
{
    use HasToast;

    public array $access = [];

    public function mount(): void
    {
        $this->access = $this->roles()
            ->mapWithKeys(function (Role $role) {
                return [
                    $role->id => DB::table('role_module_accesses')
                        ->where('role_id', $role->id)
                        ->pluck('module')
                        ->values()
                        ->all(),
                ];
            })
            ->toArray();
    }

    #[Computed]
    public function modules(): array
    {
        return ModuleAccessSupport::modules();
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->where('name', '!=', RoleEnum::SuperAdmin->value)
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        $validModules = ModuleAccessSupport::moduleValues();

        $this->validate([
            'access' => ['array'],
            'access.*' => ['array'],
            'access.*.*' => ['string', Rule::in($validModules)],
        ]);

        foreach ($this->roles() as $role) {
            $modules = collect($this->access[$role->id] ?? [])
                ->filter(fn ($module) => in_array($module, $validModules, true))
                ->unique()
                ->values();

            DB::table('role_module_accesses')->where('role_id', $role->id)->delete();

            foreach ($modules as $module) {
                DB::table('role_module_accesses')->insert([
                    'role_id' => $role->id,
                    'module' => $module,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->toastSuccess('Module access updated successfully.');
    }
}
