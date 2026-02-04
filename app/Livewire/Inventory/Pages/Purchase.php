<?php

namespace App\Livewire\Inventory\Pages;

use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Inventory Purchase', 'inventory' => true])]
class Purchase extends Component
{
    use HasAuth, WithPagination, HasDataTable;

    public function getAdditionalPageResetProperties(): array
    {
        return [];
    }
}
