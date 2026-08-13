<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\InventoryBatch;
use App\Support\BarcodeResolver;
use Illuminate\Database\Eloquent\Builder;

/**
 * Optional barcode-scanner support for a POS screen.
 *
 * The feature is inert unless the active branch has it switched on, and it
 * never intercepts anything the existing flow depends on -- a scan is just
 * another way to reach the same "add this packaging" path a click already
 * takes.
 *
 * Using components must provide:
 *   - barcodeModuleScope(): applies the module filter to a query
 *   - mapProductForPos(): the product payload shape the cart expects
 *
 * @property-read int|null $currentBranchId
 * @property-read array{enabled: bool, min_length: int, threshold_ms: int} $scannerConfig
 */
trait HandlesBarcodeScanning
{
    use HasScannerConfig;

    /**
     * Resolve a scanned code and hand the front-end the same product payload
     * the product grid uses, so the cart code path is identical to a click.
     *
     * Returning the whole payload (rather than an id) matters: the product
     * grid is paginated, and a scanned item is frequently not on the page
     * currently rendered.
     */
    public function scanBarcode(string $code): void
    {
        if (! $this->scannerConfig['enabled']) {
            return;
        }

        // With the price-check window open, a scan is a question, not a sale:
        // route it to the lookup so it can never add to the cart behind it.
        if (property_exists($this, 'lookupOpen') && $this->lookupOpen) {
            $this->lookupByBarcode($code);

            return;
        }

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

        $product = $packaging->product;
        $product->loadMissing(['productPackagings.unit', 'productPackagings.partnerships', 'baseUnit']);
        $product->setAttribute('total_stock', $this->barcodeProductStock((int) $product->id));

        $this->dispatch(
            'barcode-resolved',
            product: $this->mapProductForPos($product),
            packagingId: $packaging->id,
        );
    }

    /**
     * On-hand quantity for the scanned product in the active branch. The grid
     * gets this from a withSum(); a single scanned product needs its own read.
     */
    protected function barcodeProductStock(int $productId): float
    {
        return (float) InventoryBatch::query()
            ->where('product_id', $productId)
            ->where('branch_id', $this->currentBranchId)
            ->sum('quantity_on_hand');
    }
}
