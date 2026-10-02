<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Support\BarcodeResolver;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scan a product on the Stocks page to see only its batches.
 *
 * Using components must provide:
 *   - barcodeModuleScope(): restricts scans to the module's products
 *   - a $selectedProduct filter and $search (HasDataTable)
 *
 * @property-read int|null $currentBranchId
 * @property-read array{enabled: bool} $scannerConfig
 */
trait FiltersStocksByScan
{
    use HasScannerConfig;

    public function scanToFilter(string $code): void
    {
        if (! $this->scannerConfig['enabled']) {
            return;
        }

        $code = mb_trim($code);

        $packaging = BarcodeResolver::resolve(
            $code,
            (int) $this->currentBranchId,
            fn (Builder $query) => $this->barcodeModuleScope($query),
        );

        if (! $packaging) {
            $this->dispatch('barcode-unresolved', code: $code);
            $this->toastError("No product found for barcode {$code}.");

            return;
        }

        // The product filter is exact; a leftover text search could hide it.
        $this->search = '';
        $this->selectedProduct = (int) $packaging->product_id;
        $this->resetPage();
    }
}
