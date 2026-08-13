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
     * @return array{enabled: bool, min_length: int, threshold_ms: int}
     */
    #[Computed]
    public function scannerConfig(): array
    {
        $branch = $this->currentBranchId
            ? Branch::find($this->currentBranchId)
            : null;

        return $branch
            ? $branch->barcodeScannerConfig()
            : ['enabled' => false, 'min_length' => 6, 'threshold_ms' => 50];
    }
}
