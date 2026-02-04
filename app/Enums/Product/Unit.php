<?php

namespace App\Enums\Product;

enum Unit: string
{
    case Piece = 'pc';
    case Box = 'box';
    case Packet = 'packet';
    case Kilogram = 'kg';
    case Gram = 'g';
    case Liter = 'l';
    case Milliliter = 'ml';

    public function label(): string
    {
        return match($this) {
            self::Piece => 'Piece',
            self::Box => 'Box',
            self::Packet => 'Packet',
            self::Kilogram => 'Kilogram',
            self::Gram => 'Gram',
            self::Liter => 'Liter',
            self::Milliliter => 'Milliliter',
        };
    }
}