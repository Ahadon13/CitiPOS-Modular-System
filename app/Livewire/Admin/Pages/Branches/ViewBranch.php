<?php

namespace App\Livewire\Admin\Pages\Branches;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Branches'])]
class ViewBranch extends Component
{
    public function render()
    {
        return view('livewire.admin.pages.branches.view-branch');
    }
}