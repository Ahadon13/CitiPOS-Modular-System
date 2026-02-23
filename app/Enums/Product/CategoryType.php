<?php

declare(strict_types=1);

namespace App\Enums\Product;

enum CategoryType: string
{
    case Pharmacy = 'pharmacy';
    case Grocery = 'grocery';
    case MotorShop = 'motor-shop';

    public function label(): string
    {
        return match ($this) {
            self::Pharmacy => 'Pharmacy',
            self::Grocery => 'Grocery',
            self::MotorShop => 'Motor Shop',
        };
    }
}
