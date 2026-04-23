<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! ModuleAccess::canAccessModule($user, $module)) {
            abort(403, 'Your role is not allowed to access the ' . ModuleAccess::moduleLabel($module) . ' module.');
        }

        $activeBranch = ModuleAccess::activeBranch($user);

        if ($activeBranch && ModuleAccess::canAccessBranchModule($user, $activeBranch, $module)) {
            return $next($request);
        }

        $fallbackBranch = ModuleAccess::accessibleBranches($user, $module)->first();

        if (! $fallbackBranch) {
            abort(403, 'No assigned branch is available for the ' . ModuleAccess::moduleLabel($module) . ' module.');
        }

        ModuleAccess::setActiveBranch($user, $fallbackBranch);

        return $next($request);
    }
}
