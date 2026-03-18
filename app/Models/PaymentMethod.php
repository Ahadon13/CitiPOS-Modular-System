<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\ChecksIfInUse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PaymentMethod extends Model
{
    use HasFactory, ChecksIfInUse;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'is_active',
        'requires_reference',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'is_active' => 'boolean',
            'requires_reference' => 'boolean',
        ];
    }

    /**
     * A Payment Method can be associated with many Sales.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}