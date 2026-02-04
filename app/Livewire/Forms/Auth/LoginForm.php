<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Auth;

use App\Models\User;
use App\Actions\Auth\LoginUser;
use App\Data\Auth\LoginUserData;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

final class LoginForm extends Form
{
    #[Validate('required|string')]
    public string $username = '';

    #[Validate('required|string')]
    public string $password = '';

    /**
     * @return User
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $data = new LoginUserData(
            username: $this->username,
            password: $this->password
        );

        $action = app(LoginUser::class);

        try {
            // Execute Action (Will throw specific exceptions if fails)
            $user = $action->execute($data);

            if (is_string($user)) {
                throw ValidationException::withMessages([
                    'form.username' => [$user === 'user-not-found' ? 'This username does not exist.' : 'The password provided is incorrect.'],
                ]);
            }

            // Clear limiter on success
            RateLimiter::clear($this->throttleKey());

            return $user;

        } catch (ValidationException $e) {
            // Hit limiter on failure
            RateLimiter::hit($this->throttleKey());

            // Re-throw so the frontend shows the error
            throw $e;
        }
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->username).'|'.request()->ip());
    }
}
