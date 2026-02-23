<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Enums\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class RegisterUserData extends Data
{
    public function __construct(
        public string $name,
        public string $username,
        public string $password,
        public Role $role,
        public ?int $branch_id = null
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'enum:'.Role::class],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }

    public function modelAttributes(): array
    {
        return [
            'name' => $this->name,
            'username' => $this->username,
            'password' => Hash::make($this->password),
            'role' => $this->role->value,
            'branch_id' => $this->branch_id,
        ];
    }
}
