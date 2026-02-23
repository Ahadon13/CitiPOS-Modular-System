<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Money\Currency;
use Money\Money;

final class MoneyCast implements CastsAttributes
{
    private string $currency;

    public function __construct(string $currency = 'PHP')
    {
        $this->currency = $currency;
    }

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        return new Money($value, new Currency($this->currency));
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Money) {
            // Fallback: If you accidentally pass an integer (cents) directly
            return (int) $value;
        }

        return (int) $value->getAmount();
    }
}
