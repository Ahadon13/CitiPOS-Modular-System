<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MotorShopSaleService extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'mechanic_id',
        'service_name',
        'description',
        'quantity',
        'price_at_moment',
        'subtotal',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'sale_id' => 'integer',
            'mechanic_id' => 'integer',
            'quantity' => 'decimal:2',
            'price_at_moment' => MoneyCast::class,
            'subtotal' => MoneyCast::class,
        ];
    }
}
