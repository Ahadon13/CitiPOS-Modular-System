<?php

namespace App\Actions\Auth;

use App\Data\Auth\RegisterUserData;
use App\Models\User;
use App\Traits\HasDbTransaction;

class RegisterUser
{
    use HasDbTransaction;

    /**
     * @return User|false
     */
    public function execute(RegisterUserData $data): User|false
    {
        return $this->dbTransaction(function () use ($data) {
            return User::create($data->modelAttributes());
        });
    }
}