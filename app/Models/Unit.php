<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Product;
use App\Models\ProductPackaging;
use App\Models\InventoryBatch;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Traits\ChecksIfInUse;
use Illuminate\Database\Eloquent\Model;

final class Unit extends Model
{
    use ChecksIfInUse;
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

    public function products()
    {
        return $this->hasMany(Product::class, 'base_unit_id');
    }

    public function productPackagings()
    {
        return $this->hasMany(ProductPackaging::class, 'unit_id');
    }

     /**
     * Define the relationships that should be checked before allowing deletion.
     *
     * @return array
     */

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
