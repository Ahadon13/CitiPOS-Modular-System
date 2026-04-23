<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Support\ModuleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class InventoryRoleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = Auth::user();

        // 1. Safety Check: If not logged in, send to login
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasRole(Role::SuperAdmin->value)) {
            return redirect()->route('admin.hub');
        }

        $branches = ModuleAccess::accessibleBranches($user);

        if ($branches->count() > 1) {
            return redirect()->route('select-work');
        }

        if ($branches->count() === 1) {
            $branch = $branches->first();
            ModuleAccess::setActiveBranch($user, $branch);

            $route = ModuleAccess::dashboardRouteForModule(ModuleAccess::moduleForBranch($branch) ?? '');

            if ($route) {
                return redirect()->route($route);
            }
        }

        if ($user->hasRole(Role::Admin->value)) {
            return redirect()->route('admin.hub');
        }

        abort(403, 'Unauthorized access to Inventory module.');
    }
}
