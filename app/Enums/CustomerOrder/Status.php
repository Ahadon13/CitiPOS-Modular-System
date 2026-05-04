<?php

declare(strict_types=1);

namespace App\Enums\CustomerOrder;

enum Status: string
{
    case PendingSupplierOrder = 'pending_supplier_order';
    case Ordered = 'ordered';
    case Received = 'received';
    case Released = 'released';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingSupplierOrder => 'Pending Supplier Order',
            self::Ordered => 'Ordered From Supplier',
            self::Received => 'Received',
            self::Released => 'Released',
            self::Cancelled => 'Cancelled',
        };
    }
}
