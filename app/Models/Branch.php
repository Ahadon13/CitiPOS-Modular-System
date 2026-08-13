<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\ChecksIfInUse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Branch extends Model
{
    use ChecksIfInUse, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_category_id',
        'name',
        'address',
        'is_active',
        'barcode_scanner_enabled',
        'barcode_min_length',
        'barcode_keystroke_threshold_ms',
    ];

    /**
     * Scanner configuration handed to the front-end listener.
     *
     * @return array{enabled: bool, min_length: int, threshold_ms: int}
     */
    public function barcodeScannerConfig(): array
    {
        return [
            'enabled' => (bool) $this->barcode_scanner_enabled,
            'min_length' => (int) ($this->barcode_min_length ?: 6),
            'threshold_ms' => (int) ($this->barcode_keystroke_threshold_ms ?: 50),
        ];
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function accessibleUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user')->withTimestamps();
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function partnerships()
    {
        return $this->hasMany(Partnership::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
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
            'product_category_id' => 'integer',
            'is_active' => 'boolean',
            'barcode_scanner_enabled' => 'boolean',
            'barcode_min_length' => 'integer',
            'barcode_keystroke_threshold_ms' => 'integer',
        ];
    }
}
