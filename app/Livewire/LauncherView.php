<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Branch;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.base', ['title' => 'Select Work'])]
final class LauncherView extends Component
{
    #[Computed]
    public function branches()
    {
        return ModuleAccess::accessibleBranches(Auth::user());
    }

    #[Computed]
    public function canAccessAdmin(): bool
    {
        return Auth::user()?->hasAnyRole(['super-admin', 'admin']) ?? false;
    }

    public function moduleLabel(?string $module): string
    {
        return $module ? ModuleAccess::moduleLabel($module) : 'Unknown Module';
    }

    public function chooseBranch(int $branchId, string $destination = 'inventory')
    {
        $user = Auth::user();
        $branch = Branch::with('productCategory')->findOrFail($branchId);
        $module = ModuleAccess::moduleForBranch($branch);

        if (! $module || ! ModuleAccess::canAccessBranchModule($user, $branch, $module)) {
            abort(403, 'You are not allowed to access this branch.');
        }

        ModuleAccess::setActiveBranch($user, $branch);

        $route = $destination === 'pos'
            ? ModuleAccess::posRouteForModule($module)
            : ModuleAccess::dashboardRouteForModule($module);

        abort_unless($route, 403, 'Unsupported module.');

        return redirect()->route($route);
    }
}
