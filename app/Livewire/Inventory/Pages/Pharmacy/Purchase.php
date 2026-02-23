<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Pharmacy;

use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Inventory Purchase', 'inventory' => true])]
final class Purchase extends Component
{
    use HasAuth, HasDataTable, WithPagination;

    public function getAdditionalPageResetProperties(): array
    {
        return [];
    }
}
