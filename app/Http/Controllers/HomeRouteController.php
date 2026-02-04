<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Controllers\Controller;
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
        // Not authenticated -> redirect home
        if (! $user) {
            return redirect('/');
        }

        // Authenticated -> route by role
        if ($user->hasRole([Role::Admin->value, Role::SuperAdmin->value])) {
            return to_route('dashboard');
        }

        if ($user->hasRole([Role::Cashier->value, Role::Pharmacist->value])) {
            return to_route('select-work');
        }

        // Authenticated but no matching role -> redirect home (or handle differently)
        return redirect('/');
    }
}
