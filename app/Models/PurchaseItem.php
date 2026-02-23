<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

final class PurchaseItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'purchase_id',
        'product_id',
        'unit_id',
        'quantity',
        'cost_per_unit',
        'batch_number',
        'expiration_date',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_id' => 'integer',
            'product_id' => 'integer',
            'unit_id' => 'integer',
            'quantity' => 'decimal:4',
            'cost_per_unit' => MoneyCast::class,
            'expiration_date' => 'date',
        ];
    }
}