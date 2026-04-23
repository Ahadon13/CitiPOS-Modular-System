<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Product\CategoryType;
use App\Enums\Role as RoleEnum;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ModuleAccess
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function modules(): array
    {
        return [
            ['value' => CategoryType::Pharmacy->value, 'label' => 'Pharmacy'],
            ['value' => CategoryType::Grocery->value, 'label' => 'Grocery'],
            ['value' => CategoryType::MotorShop->value, 'label' => 'Motor Shop'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function moduleValues(): array
    {
        return array_column(self::modules(), 'value');
    }

    public static function moduleLabel(string $module): string
    {
        return collect(self::modules())->firstWhere('value', $module)['label'] ?? str($module)->headline()->toString();
    }

    public static function dashboardRouteForModule(string $module): ?string
    {
        return match ($module) {
            CategoryType::Pharmacy->value => 'inventory.pharmacy.dashboard',
            CategoryType::Grocery->value => 'inventory.grocery.dashboard',
            CategoryType::MotorShop->value => 'inventory.motor-shop.dashboard',
            default => null,
        };
    }

    public static function posRouteForModule(string $module): ?string
    {
        return match ($module) {
            CategoryType::Pharmacy->value => 'pos.pharmacy.process-sale',
            CategoryType::Grocery->value => 'pos.grocery.process-sale',
            CategoryType::MotorShop->value => 'pos.motor-shop.process-sale',
            default => null,
        };
    }

    public static function moduleForBranch(Branch $branch): ?string
    {
        return $branch->productCategory?->name;
    }

    /**
     * @return Collection<int, string>
     */
    public static function roleModules(User $user): Collection
    {
        if (self::isSuperAdmin($user)) {
            return collect(self::moduleValues());
        }

        $roleIds = $user->roles()->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return collect();
        }

        return DB::table('role_module_accesses')
            ->whereIn('role_id', $roleIds)
            ->pluck('module')
            ->unique()
            ->values();
    }

    public static function canAccessModule(User $user, string $module): bool
    {
        return self::roleModules($user)->contains($module);
    }

    public static function canAccessBranch(User $user, Branch $branch): bool
    {
        if (self::isSuperAdmin($user)) {
            return true;
        }

        return self::branchIds($user)->contains($branch->id);
    }

    public static function canAccessBranchModule(User $user, Branch $branch, string $module): bool
    {
        return $branch->is_active
            && self::canAccessModule($user, $module)
            && self::canAccessBranch($user, $branch)
            && self::moduleForBranch($branch) === $module;
    }

    public static function accessibleBranches(User $user, ?string $module = null): EloquentCollection
    {
        $query = Branch::query()
            ->with('productCategory')
            ->where('is_active', true)
            ->orderBy('name');

        if ($module) {
            $query->whereHas('productCategory', fn ($q) => $q->where('name', $module));
        }

        if (! self::isSuperAdmin($user)) {
            $branchIds = self::branchIds($user);
            $modules = self::roleModules($user);

            if ($branchIds->isEmpty() || $modules->isEmpty()) {
                return new EloquentCollection();
            }

            $query->whereIn('id', $branchIds)
                ->whereHas('productCategory', fn ($q) => $q->whereIn('name', $modules));
        }

        return $query->get();
    }

    public static function activeBranch(User $user): ?Branch
    {
        if (! $user->branch_id) {
            return null;
        }

        return Branch::with('productCategory')->find($user->branch_id);
    }

    public static function setActiveBranch(User $user, Branch $branch): void
    {
        if ((int) $user->branch_id === (int) $branch->id) {
            return;
        }

        $user->forceFill(['branch_id' => $branch->id])->save();
        $user->setRelation('branch', $branch);
    }

    public static function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin->value);
    }

    /**
     * @return Collection<int, int>
     */
    private static function branchIds(User $user): Collection
    {
        $ids = $user->accessibleBranches()->pluck('branches.id');

        if ($user->branch_id) {
            $ids->push((int) $user->branch_id);
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->values();
    }
}
