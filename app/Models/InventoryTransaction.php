<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\Inventory\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'product_id',
        'inventory_batch_id',
        'user_id',
        'type',
        'quantity',
        'running_balance',
        'unit_cost',
        'unit_price',
        'reference_type',
        'reference_id',
        'remarks',
    ];

    /**
     * Cast attributes to their appropriate types.
     */
    protected $casts = [
        'type' => TransactionType::class, // Automatically casts to your Enum
        'quantity' => 'decimal:2',
        'running_balance' => 'decimal:2',
        'unit_cost' => MoneyCast::class,
        'unit_price' => MoneyCast::class,
    ];

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relationship.
     * This will automatically fetch the related Sale or Purchase model.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
