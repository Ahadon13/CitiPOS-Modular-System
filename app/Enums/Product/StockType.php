<?php

declare(strict_types=1);

namespace App\Enums\Product;

enum StockType: string
{
    case Regular = 'regular';
    case SpecialOrder = 'special_order';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular Stock',
            self::SpecialOrder => 'Special Order',
        };
    }
}
