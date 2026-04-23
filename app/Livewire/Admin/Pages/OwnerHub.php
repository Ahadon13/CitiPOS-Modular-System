<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\Sale\Status as SaleStatus;
use App\Models\Branch;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

// Using a clean layout without sidebars, since this is a gateway page
#[Layout('components.layouts.base', ['title' => 'Owner Hub'])]
class OwnerHub extends Component
{
    /**
     * Fetch all branches the owner has access to.
     */
    #[Computed]
    public function branches()
    {
        $query = Branch::query()
            ->with('productCategory')
            ->withCount([
                'sales',
                'purchases',
                'sales as today_orders' => function ($query) {
                    $query->whereDate('created_at', today());
                },
            ])
            ->withSum([
                'sales as today_sales' => function ($query) {
                    $query->whereDate('created_at', today())
                        ->where('status', SaleStatus::Completed->value);
                },
            ], 'grand_total')
            ->orderBy('created_at', 'desc')
            ->orderBy('name');

        $user = Auth::user();

        if ($user && ! ModuleAccess::isSuperAdmin($user)) {
            $query->whereIn('id', ModuleAccess::accessibleBranches($user)->pluck('id'));
        }

        return $query->get();
    }

    /**
     * Standard logout method.
     */
    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }
}
