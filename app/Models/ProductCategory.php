<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Product;
use App\Traits\ChecksIfInUse;
use Illuminate\Database\Eloquent\Model;

final class ProductCategory extends Model
{
    use ChecksIfInUse;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [

        ];
    }
}
