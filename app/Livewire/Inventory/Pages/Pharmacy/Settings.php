<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Pharmacy;

use App\Livewire\Concerns\HasToast;
use App\Traits\HasAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Settings', 'inventory' => true])]
final class Settings extends Component
{
    use HasAuth, HasToast;

    // Profile State
    public string $name = '';
    public string $username = '';

    // Password State
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->name = $this->user->name;
        $this->username = $this->user->username;
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($this->user->id)],
        ],
        [
            'username.unique' => 'The username has already been taken.',
        ]);

        $this->user->update([
            'name' => $this->name,
            'username' => $this->username,
        ]);

        $this->toastSuccess('Profile updated successfully.');
        redirect(request()->header('Referer'));
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:8', 'confirmed'],
        ],
        [
            'current_password.required' => 'Please enter your current password.',
            'current_password.current_password' => 'The current password is incorrect.',
            'password.required' => 'Please enter a new password.',
            'password.min' => 'The new password must be at least 8 characters.',
            'password.confirmed' => 'The new password confirmation does not match.',
        ]);

        $this->user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->toastSuccess('Password updated successfully.');
        redirect(request()->header('Referer'));
    }
}
