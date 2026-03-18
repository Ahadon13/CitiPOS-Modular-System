<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Customer;
use App\Traits\ChecksIfInUse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CustomerType extends Model
{
    use HasFactory, ChecksIfInUse;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'discount_percentage',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'discount_percentage' => 'decimal:2',
        ];
    }
}