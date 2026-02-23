<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

final class LoginUserData extends Data
{
    public function __construct(
        public string $username,
        public string $password,
        public string $device_name = 'web'
    ) {}
}
