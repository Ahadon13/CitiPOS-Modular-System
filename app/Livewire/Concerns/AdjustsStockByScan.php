<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Product;
use App\Models\ProductPackaging;
use App\Support\BarcodeResolver;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

/**
 * Scan-driven Add / Remove Stock, shared by every module's Adjust Stock modal.
 *
 * "Scan to Adjust" opens the modal waiting for a scan. The scan picks the
 * product *and* the unit -- a box barcode counts boxes -- and scanning the
 * same barcode again adds one, so a shelf can be counted by scanning it.
 * Saving converts to base units, which is what the batches store.
 *
 * Using components must provide:
 *   - barcodeModuleScope(): restricts scans to the module's products
 *   - resetFormState(): clears the adjustment form
 *
 * @property-read int|null $currentBranchId
 * @property-read array{enabled: bool} $scannerConfig
 * @property-read list<array{value: int, label: string, factor: float}> $adjustPackagings
 */
trait AdjustsStockByScan
{
    use HasScannerConfig;

    /** Unit the quantity is counted in; null means the product's base unit. */
    public ?int $adjust_packaging_id = null;

    /** Opened from "Scan to Adjust": shows a scan prompt until a product is scanned. */
    public bool $awaitingScan = false;

    /** A scan session stays open after saving, ready for the next product. */
    public bool $scanSession = false;

    public ?string $lastScannedCode = null;

    #[On('open-adjust-stock-scan')]
    public function openForScan(): void
    {
        $this->adjust_product = null;
        $this->resetScanState();
        $this->awaitingScan = true;
        $this->scanSession = true;
        $this->resetFormState();

        $this->dispatch('open-modal', id: 'adjust-stock');
    }

    public function scanForAdjustment(string $code): void
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

        $current = $this->adjust_product;

        // One product per adjustment: never silently swap what is on screen.
        if ($current && (int) $current['id'] !== (int) $packaging->product_id) {
            $this->dispatch('barcode-unresolved', code: $code);
            $this->toastError("Confirm or cancel the adjustment for {$current['brand_name']} before scanning another product.");

            return;
        }

        $this->lastScannedCode = $code;
        $this->dispatch('adjust-stock-scanned');

        // Same barcode again: count one more.
        if ($current && $this->adjust_packaging_id === (int) $packaging->id) {
            $this->quantity = $this->formatQuantity((float) $this->quantity + 1);

            return;
        }

        if (! $current) {
            $this->resetFormState();
            $this->fillAdjustProduct($packaging->product);
        }

        $this->adjust_packaging_id = (int) $packaging->id;
        $this->quantity = '1';
        $this->awaitingScan = false;
    }

    /**
     * Units the quantity can be counted in.
     *
     * @return list<array{value: int, label: string, factor: float}>
     */
    #[Computed]
    public function adjustPackagings(): array
    {
        if (empty($this->adjust_product)) {
            return [];
        }

        $baseUnit = $this->adjust_product['base_unit'] ?? 'pcs';

        return ProductPackaging::query()
            ->with('unit')
            ->where('product_id', $this->adjust_product['id'])
            ->orderBy('conversion_factor')
            ->get()
            ->map(function (ProductPackaging $packaging) use ($baseUnit) {
                $factor = (float) $packaging->conversion_factor;
                $unit = $packaging->unit->abbreviation ?? $packaging->unit->name ?? 'Unit';

                return [
                    'value' => (int) $packaging->id,
                    'label' => $factor === 1.0 ? $unit : "{$unit} ({$this->formatQuantity($factor)} {$baseUnit})",
                    'factor' => $factor,
                ];
            })
            ->values()
            ->all();
    }

    public function adjustUnitLabel(): string
    {
        $selected = collect($this->adjustPackagings)->firstWhere('value', $this->adjust_packaging_id);

        return $selected
            ? (string) str($selected['label'])->before(' (')
            : ($this->adjust_product['base_unit'] ?? 'pcs');
    }

    public function adjustConversionFactor(): float
    {
        $selected = collect($this->adjustPackagings)->firstWhere('value', $this->adjust_packaging_id);

        return $selected && $selected['factor'] > 0 ? (float) $selected['factor'] : 1.0;
    }

    /**
     * The entered quantity in base units, which is what batches store.
     */
    public function baseQuantity(): float
    {
        return round((float) $this->quantity * $this->adjustConversionFactor(), 4);
    }

    /**
     * After a successful save: a scan session waits for the next product,
     * a normal adjustment closes as before.
     */
    protected function finishAdjustment(): void
    {
        if ($this->scanSession) {
            $this->adjust_product = null;
            $this->resetScanState();
            $this->awaitingScan = true;
            $this->resetFormState();

            return;
        }

        $this->dispatch('close-modal', id: 'adjust-stock');
        $this->resetFormState();
    }

    protected function fillAdjustProduct(Product $product): void
    {
        $product->loadMissing('baseUnit');

        $this->adjust_product = [
            'id' => $product->id,
            'brand_name' => $product->brand_name,
            'generic_name' => $product->generic_name,
            'base_unit' => $product->baseUnit->abbreviation ?? 'pcs',
        ];

        unset($this->adjustPackagings, $this->activeBatches);

        // Count in the base unit unless a scan says otherwise.
        $this->adjust_packaging_id = collect($this->adjustPackagings)
            ->sortBy('factor')
            ->first()['value'] ?? null;
    }

    protected function resetScanState(): void
    {
        $this->adjust_packaging_id = null;
        $this->awaitingScan = false;
        $this->scanSession = false;
        $this->lastScannedCode = null;
    }

    private function formatQuantity(float $value): string
    {
        return mb_rtrim(mb_rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }
}
