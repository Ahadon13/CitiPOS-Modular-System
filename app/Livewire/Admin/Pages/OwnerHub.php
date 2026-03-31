<?php

namespace App\Livewire\Admin\Pages;

use App\Actions\Common\SwitchBranch;
use App\Models\Branch; // Adjust to your actual Branch model
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
        // Replace with your actual logic. E.g., Auth::user()->branches
        return Branch::orderBy('created_at', 'desc')->orderBy('name')->get();
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
