<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
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

        // 2. Redirect based on Role Enum
        // We assume your User model casts 'role' to the Role Enum
        return match ($user->role) {

            // Specific Roles -> Specific Dashboards
            Role::Pharmacist->value => redirect()->route('inventory.pharmacy.dashboard'),
            Role::GroceryCashier->value => redirect()->route('inventory.grocery.dashboard'),

            // Admin / SuperAdmin -> Main Dashboard (Overview)
            Role::SuperAdmin->value,
            Role::Admin->value => redirect()->route('admin.hub'),

            // Fallback for anyone else (e.g. Regular User)
            default => redirect()->route('login'),
        };
    }
}