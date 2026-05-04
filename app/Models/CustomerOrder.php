<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\CustomerOrder\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CustomerOrder extends Model
{
    protected $fillable = [
        'branch_id',
        'customer_id',
        'sale_id',
        'purchase_id',
        'user_id',
        'reference_no',
        'status',
        'total_amount',
        'remarks',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerOrderItem::class);
    }

    protected function casts(): array
    {
        return [
            'branch_id' => 'integer',
            'customer_id' => 'integer',
            'sale_id' => 'integer',
            'purchase_id' => 'integer',
            'user_id' => 'integer',
            'status' => Status::class,
            'total_amount' => MoneyCast::class,
        ];
    }
}
