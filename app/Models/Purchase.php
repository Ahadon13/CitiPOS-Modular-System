<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\Purchase\Status;
use App\Models\Branch;
use App\Models\User;
use App\Models\InventoryTransaction;
use App\Models\Supplier;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Model;

final class Purchase extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'branch_id',
        'supplier_id',
        'user_id',          // The staff who created the PO
        'reference_no',     // e.g., PO-20260305-001
        'status',           // pending, receiving (partial), completed, cancelled
        'expected_delivery_date',
        'total_cost',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function customerOrders()
    {
        return $this->hasMany(CustomerOrder::class);
    }

    /**
     * Get all inventory movements triggered by receiving this purchase order.
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
            'branch_id' => 'integer',
            'supplier_id' => 'integer',
            'user_id' => 'integer',
            'reference_no' => 'string',
            'status' => Status::class,
            'total_cost' => MoneyCast::class,
            'expected_delivery_date' => 'date',
        ];
    }
}
