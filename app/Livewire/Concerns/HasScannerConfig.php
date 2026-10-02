<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Branch;
use Livewire\Attributes\Computed;

/**
 * Exposes the active branch's scanner settings to a screen.
 *
 * Screens that only need to route a scan into a search box (inventory) use
 * this on its own; the POS additionally uses HandlesBarcodeScanning to resolve
 * a code into a cart line.
 *
 * @property-read int|null $currentBranchId
 */
trait HasScannerConfig
{
    /**
     * `enabled: false` means the front-end listener never attaches at all.
     *
     * @return array{enabled: bool, connection: string, usb_vendor_id: string, usb_product_id: string, baud_rate: int, min_length: int, threshold_ms: int, suffix: string, prefix: string, allowed_types: list<string>|null, sound: bool, profile: string|null}
     */
    #[Computed]
    public function scannerConfig(): array
    {
        $branch = $this->currentBranchId
            ? Branch::find($this->currentBranchId)
            : null;

        return $branch
            ? $branch->barcodeScannerConfig()
            : ['enabled' => false, 'connection' => 'keyboard', 'usb_vendor_id' => '', 'usb_product_id' => '', 'baud_rate' => 9600, 'min_length' => 6, 'threshold_ms' => 50, 'suffix' => 'enter', 'prefix' => '', 'allowed_types' => null, 'sound' => true, 'profile' => null];
    }
}
