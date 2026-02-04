<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use App\Services\AuthenticatedService;
use Livewire\Attributes\Computed;

/**
 * @property-read User $user
 * @property-read User $admin
 * @property-read User $cashier
 * @property-read User $pharmacist
 */
trait HasAuth
{
    /**
     * Access the internal service.
     */
    #[Computed]
    protected function authService(): AuthenticatedService
    {
        return app(AuthenticatedService::class);
    }

    /**
     * Get current user (Throws 401 if guest).
     */
    #[Computed]
    public function user(): User
    {
        return $this->authService()->user();
    }

    /**
     * Get current user ONLY if they are Admin (Throws 403 otherwise).
     */
    #[Computed]
    public function admin(): User
    {
        return $this->authService()->admin();
    }

    /**
     * Get current user ONLY if they are Pharmacist (Throws 403 otherwise).
     */
    #[Computed]
    public function pharmacist(): User
    {
        return $this->authService()->pharmacist();
    }

    /**
     * Get current user ONLY if they are Cashier (Throws 403 otherwise).
     */
    #[Computed]
    public function cashier(): User
    {
        return $this->authService()->cashier();
    }

    /**
     * Check if current user is Super Admin.
     */
    #[Computed]
    public function isSuperAdmin(): bool
    {
        return is_null($this->user->branch_id);
    }

    /**
     * Get current user's branch ID (null if Super Admin).
     */
    #[Computed]
    public function currentBranchId(): ?int
    {
        return $this->user->branch_id;
    }
}
