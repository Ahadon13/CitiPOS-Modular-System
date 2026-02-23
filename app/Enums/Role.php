<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case MotorShopCashier = 'motor-shop-cashier';
    case GroceryCashier = 'grocery-cashier';
    case Pharmacist = 'pharmacist';

    /**
     * Get all admin roles.
     *
     * @return array<string>
     */
    public static function adminRoles(): array
    {
        return [
            self::SuperAdmin->value,
            self::Admin->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::MotorShopCashier => 'Motor Shop Cashier',
            self::Pharmacist => 'Pharmacist',
        };
    }
}
