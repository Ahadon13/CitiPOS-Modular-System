<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Components;

use App\Models\Branch;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class Sidebar extends Component {
    public string $module = 'pharmacy'; // Default to pharmacy, can be set when including the component

    #[Computed]
    public function branches()
    {
        return ModuleAccess::accessibleBranches(Auth::user());
    }

    public function switchBranch(int $branchId)
    {
        $user = Auth::user();
        $branch = Branch::with('productCategory')->findOrFail($branchId);
        $module = ModuleAccess::moduleForBranch($branch);

        if (! $module || ! ModuleAccess::canAccessBranchModule($user, $branch, $module)) {
            abort(403, 'You are not allowed to access this branch.');
        }

        ModuleAccess::setActiveBranch($user, $branch);

        $route = ModuleAccess::dashboardRouteForModule($module);

        abort_unless($route, 403, 'Unsupported module.');

        return redirect()->route($route);
    }
}
