<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductPackaging extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'unit_id',
        'conversion_factor',
        'price',
        'barcode',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function partnerships(): HasMany
    {
        return $this->hasMany(Partnership::class);
    }

    public function findPartnershipForCustomer(?int $customerTypeId = null, ?int $branchId = null): ?Partnership
    {
        if (! $customerTypeId || ! $branchId) {
            return null;
        }

        return $this->partnerships()
            ->where('branch_id', $branchId)
            ->where('customer_type_id', $customerTypeId)
            ->first();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:4',
            'price' => MoneyCast::class,
        ];
    }

    /**
     * Instantly get the correct price based on the Customer Type AND Branch.
     */
    public function getPriceForCustomer(?int $customerTypeId = null, ?int $branchId = null): int
    {
        $partnership = $this->findPartnershipForCustomer($customerTypeId, $branchId);

        return $partnership
            ? (int) $partnership->getRawOriginal('special_price')
            : (int) $this->getRawOriginal('price');
    }
}
