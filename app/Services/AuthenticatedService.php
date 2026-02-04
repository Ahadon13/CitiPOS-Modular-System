<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthenticatedService
{
    /**
     * Get the currently authenticated user.
     * Abort 401 if not logged in.
     */
    public function user(): User
    {
        /** @var User|null $user */
        $user = Auth::user();

        // 401 = "I don't know who you are"
        abort_if(! $user, 401, __('messages.auth.unauthenticated'));

        return $user;
    }

    /**
     * Get the user if they are an Admin or SuperAdmin.
     * Abort 403 if they are logged in but have the wrong role.
     */
    public function admin(): User
    {
        $user = $this->user();

        // Check against Spatie Roles or your Enum column
        $isAdmin = $user->hasRole([Role::Admin->value, Role::SuperAdmin->value])
                   || $user->role === Role::Admin->value; // Fallback if using simple column

        // 403 = "I know who you are, but you are not allowed here"
        abort_if(! $isAdmin, 403, __('messages.auth.forbidden'));

        return $user;
    }

    /**
     * Get the user if they are a Cashier.
     */
    public function cashier(): User
    {
        $user = $this->user();

        // Assuming 'cashier' is a role
        $isCashier = $user->hasRole(Role::Cashier->value)
                     || $user->role === Role::Cashier->value;

        abort_if(! $isCashier, 403, __('messages.auth.forbidden'));

        return $user;
    }

    /**
     * Get the user if they are a Pharmacist.
     */
    public function pharmacist(): User
    {
        $user = $this->user();

        $isPharmacist = $user->hasRole(Role::Pharmacist->value)
                        || $user->role === Role::Pharmacist->value;

        abort_if(! $isPharmacist, 403, __('messages.auth.forbidden'));

        return $user;
    }
}
