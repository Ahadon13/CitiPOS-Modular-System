<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Livewire\Component\View;

final class Home extends Component
{
    public function render()
    {
        /** @var View $view */
        $view = view('home');

        return $view->layout('components.layouts.guest');
    }
}
