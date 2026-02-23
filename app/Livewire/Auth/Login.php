<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Livewire\Forms\Auth\LoginForm;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth', ['title' => 'Login'])]
final class Login extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        // 1. Validate Form
        $this->validate();

        // 2. Authenticate via Form -> Action
        // This returns the User model if successful
        $user = $this->form->authenticate();

        // 3. Perform the Session Login
        Auth::login($user, true);

        // 4. Regenerate Session (Security Best Practice)
        session()->regenerate();

        // 5. Redirect
        $this->redirect(route('select-work', absolute: false), navigate: true);
    }
}
