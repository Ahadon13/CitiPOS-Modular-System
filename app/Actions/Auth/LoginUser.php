<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\LoginUserData;
use App\Models\User;
use App\Traits\HasDbTransaction;
use Illuminate\Support\Facades\Hash;

final class LoginUser
{
    use HasDbTransaction;

    public function execute(LoginUserData $data): User|string
    {
        return $this->dbTransaction(function () use ($data) {

            // 1. Check if username exists
            /** @var User|null $user */
            $user = User::firstWhere('username', $data->username);

            if (! $user|| $user->username !== $data->username) {
                return 'user-not-found';
            }

            // 2. Manual Password Check
            if (! Hash::check($data->password, $user->password)) {
                return 'invalid-credentials';
            }

            return $user;
        });
    }
}
