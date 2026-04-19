<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Components;

use Livewire\Component;

final class Sidebar extends Component {
    public string $module = 'pharmacy'; // Default to pharmacy, can be set when including the component
}