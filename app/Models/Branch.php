<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DeviceSettings;
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
     * The on/off switch lives on the branch; everything model-specific comes
     * from the scanner profile assigned in Settings > Devices.
     *
     * @return array{enabled: bool, connection: string, usb_vendor_id: string, usb_product_id: string, baud_rate: int, min_length: int, threshold_ms: int, suffix: string, prefix: string, allowed_types: list<string>|null, sound: bool, profile: string|null}
     */
    public function barcodeScannerConfig(): array
    {
        return DeviceSettings::scannerConfigForBranch($this);
    }

    /**
     * Receipt printer profile assigned to this branch.
     *
     * @return array<string, mixed>
     */
    public function printerConfig(): array
    {
        return DeviceSettings::printerConfigForBranch($this);
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
