<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Grocery;

use App\Enums\Enquiry\Status;
use App\Livewire\Concerns\HasToast;
use App\Models\Branch;
use App\Models\Inquiry;
use App\Traits\HasAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.grocery', ['title' => 'Settings', 'inventory' => true])]
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

    // Branch Transfer State
    public ?int $target_branch_id = null;
    public string $transfer_remarks = '';

    public function mount(): void
    {
        $this->name = $this->user->name;
        $this->username = $this->user->username;
    }

    #[Computed]
    public function availableBranches()
    {
        // Fetch all branches EXCEPT the user's current branch
        return Branch::where('id', '!=', $this->currentBranchId)
            ->orderBy('name')
            ->get()
            ->map(fn($b) => ['value' => $b->id, 'label' => $b->name])
            ->toArray();
    }

    #[Computed]
    public function pendingTransferRequest()
    {
        return Inquiry::where('user_id', $this->user->id)
            ->where('type', 'branch_transfer')
            ->where('status', Status::Pending->value)
            ->first();
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

    public function submitTransferRequest(): void
    {
        if ($this->pendingTransferRequest) {
            $this->toastError('You already have a pending transfer request.');
            return;
        }

        $this->validate([
            'target_branch_id' => ['required', 'exists:branches,id'],
            'transfer_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        Inquiry::create([
            'user_id' => $this->user->id,
            'type' => 'branch_transfer',
            'inquirable_id' => $this->target_branch_id,
            'inquirable_type' => Branch::class,
            'status' => 'pending',
            'remarks' => $this->transfer_remarks,
        ]);

        $this->reset(['target_branch_id', 'transfer_remarks']);
        $this->toastSuccess('Transfer request submitted to Admin for verification.');

        // TODO: Admin approval logic goes in the Admin Dashboard.
        // When the admin approves, they will update the user's branch_id, resolve the inquiry, and trigger a logout.
    }
}
