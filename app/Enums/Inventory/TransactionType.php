<?php

declare(strict_types=1);

namespace App\Enums\Inventory;

enum TransactionType: string
{
    // ==========================================
    // STOCK IN (Additions)
    // ==========================================
    case Purchase = 'purchase';           // Goods received from supplier
    case TransferIn = 'transfer_in';      // Goods received from another branch
    case ReturnIn = 'return_in';          // Goods returned by a customer
    case AdjustmentIn = 'adjustment_in';  // Found physical stock / Audit correction

    // ==========================================
    // STOCK OUT (Deductions)
    // ==========================================
    case Sale = 'sale';                   // Goods sold via POS
    case TransferOut = 'transfer_out';    // Goods sent to another branch
    case ReturnOut = 'return_out';        // Goods returned to supplier
    case AdjustmentOut = 'adjustment_out';// Lost, stolen, or damaged goods
    case Expired = 'expired';             // Goods removed due to expiration


    /**
     * Get a human-readable label for the UI (Dropdowns, Tables, etc.)
     */
    public function label(): string
    {
        return match($this) {
            self::Purchase => 'Purchase Order',
            self::TransferIn => 'Transfer (In)',
            self::ReturnIn => 'Customer Return',
            self::AdjustmentIn => 'Adjustment (In)',

            self::Sale => 'POS Sale',
            self::TransferOut => 'Transfer (Out)',
            self::ReturnOut => 'Supplier Return',
            self::AdjustmentOut => 'Adjustment (Out)',
            self::Expired => 'Expired',
        };
    }

    /**
     * Determine if this transaction ADDS to the inventory
     */
    public function isAddition(): bool
    {
        return match($this) {
            self::Purchase,
            self::TransferIn,
            self::ReturnIn,
            self::AdjustmentIn => true,
            default => false,
        };
    }

    /**
     * Determine if this transaction SUBTRACTS from the inventory
     */
    public function isDeduction(): bool
    {
        return !$this->isAddition();
    }

    /**
     * Get a color theme for UI badges (Green for IN, Red for OUT)
     */
    public function colorBadge(): string
    {
        return $this->isAddition() ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
    }

    /**
     * Helper to get all cases grouped by In/Out (Useful for select dropdowns)
     */
    public static function groupedForSelect(): array
    {
        return [
            'Stock In' => [
                self::Purchase->value => self::Purchase->label(),
                self::TransferIn->value => self::TransferIn->label(),
                self::ReturnIn->value => self::ReturnIn->label(),
                self::AdjustmentIn->value => self::AdjustmentIn->label(),
            ],
            'Stock Out' => [
                self::Sale->value => self::Sale->label(),
                self::TransferOut->value => self::TransferOut->label(),
                self::ReturnOut->value => self::ReturnOut->label(),
                self::AdjustmentOut->value => self::AdjustmentOut->label(),
                self::Expired->value => self::Expired->label(),
            ]
        ];
    }
}
