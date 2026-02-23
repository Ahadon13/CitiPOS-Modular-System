<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\RegisterUserData;
use App\Models\User;
use App\Traits\HasDbTransaction;

final class RegisterUser
{
    use HasDbTransaction;

    public function execute(RegisterUserData $data): User|false
    {
        return $this->dbTransaction(function () use ($data) {
            return User::create($data->modelAttributes());
        });
    }
}
