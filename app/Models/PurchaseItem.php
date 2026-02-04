<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
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
            'cost_per_unit' => 'decimal:2',
            'expiration_date' => 'date',
        ];
    }

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
}