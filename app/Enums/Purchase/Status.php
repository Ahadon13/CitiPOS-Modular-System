<?php

namespace App\Enums\Purchase;

enum Status: string
{
    case Pending = 'pending';
    case Receiving = 'receiving';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Receiving => 'Receiving',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}