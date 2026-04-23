<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Support\ModuleAccess;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class HomeRouteController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @throws Exception
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        // 1. Safety Check: If not logged in, send to login
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasRole([Role::SuperAdmin->value, Role::Admin->value])) {
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

        return redirect()->route('login');
    }
}
