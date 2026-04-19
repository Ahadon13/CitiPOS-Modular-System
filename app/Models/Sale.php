<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\Sale\Status;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Sale extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'branch_id',
        'user_id',
        'customer_id',
        'grand_total',
        'status',
        'payment_method_id',
        'payment_reference',
        'subtotal',
        'discount_amount',
        'discount_type_id',
        'amount_tendered',
        'change_amount',
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function discountType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class, 'discount_type_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get all inventory movements triggered by this specific sale receipt.
     */
    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'reference');
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
            'branch_id' => 'integer',
            'user_id' => 'integer',
            'customer_id' => 'integer',
            'discount_type_id' => 'integer',
            'status' => Status::class,
            'subtotal' => MoneyCast::class,
            'discount_amount' => MoneyCast::class,
            'grand_total' => MoneyCast::class,
            'amount_tendered' => MoneyCast::class,
            'change_amount' => MoneyCast::class,
        ];
    }
}
