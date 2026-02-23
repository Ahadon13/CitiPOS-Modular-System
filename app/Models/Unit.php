<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\InventoryBatch;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Model;

final class Unit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'abbreviation',
        'allow_decimal',
    ];

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function inventoryBatches()
    {
        return $this->hasMany(InventoryBatch::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allow_decimal' => 'boolean',
        ];
    }
}
