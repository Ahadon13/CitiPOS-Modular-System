<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ProductPackaging;
use App\Support\BarcodeResolver;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only product lookup, by scan or by typing.
 *
 * Used by two screens with the same need and different follow-up actions:
 *  - Inventory: look a product up and jump straight to editing it.
 *  - POS: check a price mid-transaction without touching the cart.
 *
 * Nothing here mutates anything. In the POS that is the whole point: a cashier
 * with a half-built cart can answer "how much is this for an LGU customer?"
 * and the cart is provably untouched, because this trait only ever reads.
 *
 * @property-read int|null $currentBranchId
 */
trait LooksUpProductDetails
{
    public string $lookupQuery = '';

    /** Resolved product details, or null when nothing is being shown. */
    public ?array $lookupProduct = null;

    public ?string $lookupError = null;

    /**
     * Tracked server-side so a scan can be routed to whichever surface the user
     * is actually looking at: the lookup window when it is open, the page's own
     * scan behaviour when it is not.
     */
    public bool $lookupOpen = false;

    public function openLookup(string $modalId): void
    {
        $this->lookupOpen = true;

        $this->dispatch('open-modal', id: $modalId);
    }

    public function closeLookup(): void
    {
        $this->lookupOpen = false;
    }

    /**
     * Resolve a scanned code. Exact barcode match only -- a scan is precise.
     */
    public function lookupByBarcode(string $code): void
    {
        $this->lookupQuery = $code;

        $packaging = BarcodeResolver::resolve(
            $code,
            (int) $this->currentBranchId,
            fn (Builder $query) => $this->barcodeModuleScope($query),
        );

        if (! $packaging) {
            $this->lookupProduct = null;
            $this->lookupError = "No product found for barcode {$code}.";

            return;
        }

        $this->lookupError = null;
        $this->lookupProduct = $this->buildLookupPayload($packaging->product, $packaging->id);
    }

    /**
     * Resolve typed input: an exact code first, then the best name match.
     */
    public function lookupBySearch(): void
    {
        $term = mb_trim($this->lookupQuery);

        if ($term === '') {
            $this->lookupProduct = null;
            $this->lookupError = null;

            return;
        }

        $packaging = BarcodeResolver::resolve(
            $term,
            (int) $this->currentBranchId,
            fn (Builder $query) => $this->barcodeModuleScope($query),
        );

        if ($packaging) {
            $this->lookupError = null;
            $this->lookupProduct = $this->buildLookupPayload($packaging->product, $packaging->id);

            return;
        }

        $product = $this->lookupBaseQuery()
            ->search($term)
            ->orderBy('brand_name')
            ->first();

        if (! $product) {
            $this->lookupProduct = null;
            $this->lookupError = "No product matched \"{$term}\".";

            return;
        }

        $this->lookupError = null;
        $this->lookupProduct = $this->buildLookupPayload($product);
    }

    public function clearLookup(): void
    {
        $this->lookupQuery = '';
        $this->lookupProduct = null;
        $this->lookupError = null;
    }

    /**
     * Single entry point for a scan on a page that has both a lookup window and
     * its own scan behaviour. Whoever the user is looking at wins, so a scan can
     * never quietly act on the surface behind the one on screen.
     */
    public function routeScannedCode(string $code): void
    {
        if ($this->lookupOpen) {
            $this->lookupByBarcode($code);

            return;
        }

        $this->handleScanOutsideLookup($code);
    }

    /**
     * What a scan does when the lookup window is closed. Inventory drops the
     * code into its search filter; the POS overrides this to add to the cart.
     */
    protected function handleScanOutsideLookup(string $code): void
    {
        $this->search = $code;
    }

    /**
     * Everything the details panel shows, including every packaging's regular
     * price and any partner prices set for it.
     *
     * @return array<string, mixed>
     */
    protected function buildLookupPayload(Product $product, ?int $matchedPackagingId = null): array
    {
        $product->loadMissing([
            'baseUnit',
            'category',
            'productPackagings.unit',
            'productPackagings.partnerships.customerType',
        ]);

        $stock = (float) InventoryBatch::query()
            ->where('product_id', $product->id)
            ->where('branch_id', $this->currentBranchId)
            ->sum('quantity_on_hand');

        $packagings = $product->productPackagings
            ->map(function (ProductPackaging $packaging) use ($matchedPackagingId) {
                $partnerPrices = $packaging->partnerships
                    ->where('branch_id', $this->currentBranchId)
                    ->map(fn ($partnership) => [
                        'partner' => $partnership->customerType?->name ?? 'Unknown partner',
                        'price' => (int) $partnership->getRawOriginal('special_price'),
                    ])
                    ->sortBy('partner')
                    ->values()
                    ->all();

                return [
                    'id' => $packaging->id,
                    'unit' => $packaging->unit->abbreviation ?? $packaging->unit->name ?? 'Unit',
                    'barcode' => $packaging->barcode,
                    'conversion_factor' => (float) $packaging->conversion_factor,
                    'regular_price' => (int) $packaging->getRawOriginal('price'),
                    'partner_prices' => $partnerPrices,
                    // Highlights the packaging whose barcode was actually scanned.
                    'scanned' => $matchedPackagingId !== null && $packaging->id === $matchedPackagingId,
                ];
            })
            ->values()
            ->all();

        return [
            'id' => $product->id,
            'name' => $product->brand_name ?: ($product->name ?: $product->product_code),
            'generic_name' => $product->generic_name,
            'product_code' => $product->product_code,
            'dosage' => $product->dosage,
            'form' => $product->form,
            'category' => $product->category->name ?? null,
            'requires_prescription' => (bool) $product->requires_prescription,
            'is_active' => (bool) $product->is_active,
            'stock_type' => $product->stock_type?->value,
            'base_unit' => $product->baseUnit->abbreviation ?? 'pcs',
            'image_url' => $product->imageUrl(),
            'stock' => $stock,
            'packagings' => $packagings,
        ];
    }

    /**
     * Branch- and module-scoped product query for the name search.
     */
    protected function lookupBaseQuery(): Builder
    {
        $query = Product::query()
            ->where('branch_id', $this->currentBranchId)
            ->where('is_active', true);

        return $this->barcodeModuleScope($query);
    }
}
