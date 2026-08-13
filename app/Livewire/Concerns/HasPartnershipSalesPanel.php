<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Exports\PartnershipSalesExport;
use App\Models\Branch;
use App\Models\CustomerType;
use App\Models\Partnership;
use App\Support\PartnershipSales;
use App\Support\PartnershipSalesFilters;
use App\Support\ReportPdf;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Partnership-priced sales panel for a module's Sales screen.
 *
 * Partnership pricing is configured per branch, so this is module-agnostic by
 * construction: it scopes to the active branch and that branch's own module.
 * In practice only pharmacy branches configure partnerships, and the panel
 * hides itself where none exist -- so dropping this trait into the grocery or
 * motor-shop Sales page is safe and shows nothing until that branch actually
 * sets a partner price.
 *
 * Figures come from the same query layer as the admin reports, so the store
 * screen and head office can never disagree.
 *
 * @property-read int|null $currentBranchId
 */
trait HasPartnershipSalesPanel
{
    public const PARTNERSHIP_PAGE_NAME = 'partnershipPage';

    public string $partnershipSearch = '';

    public ?int $partnershipCustomerTypeId = null;

    /**
     * This panel's own page size and paginator name.
     *
     * Both are deliberately separate from the host page's `perPage` / `page`:
     * the panel shares a screen with another table, and paging or resizing one
     * table must never move the other.
     */
    public int $partnershipPerPage = 10;

    /** @var int[] */
    public array $partnershipPerPageOptions = [10, 25, 50, 100];

    /**
     * Whether this branch uses partnership pricing at all. Drives whether the
     * panel renders, keeping it out of the way of branches that never use it.
     */
    #[Computed]
    public function showPartnershipPanel(): bool
    {
        return Partnership::where('branch_id', $this->partnershipBranchId())->exists();
    }

    public function partnershipFilters(): PartnershipSalesFilters
    {
        [$start, $end] = $this->partnershipDateRange();

        return new PartnershipSalesFilters(
            start: $start,
            end: $end,
            branchId: $this->partnershipBranchId(),
            categoryId: $this->partnershipCategoryId(),
            customerTypeId: $this->partnershipCustomerTypeId,
            search: $this->partnershipSearch,
        );
    }

    #[Computed]
    public function partnershipCustomerTypes(): array
    {
        return CustomerType::orderBy('name')
            ->get()
            ->map(fn ($type) => ['value' => $type->id, 'label' => $type->name])
            ->toArray();
    }

    #[Computed]
    public function partnershipSummary(): array
    {
        return PartnershipSales::summary($this->partnershipFilters());
    }

    #[Computed]
    public function partnershipByPartner(): array
    {
        return PartnershipSales::byPartner($this->partnershipFilters())->get()->all();
    }

    #[Computed]
    public function partnershipItems()
    {
        return PartnershipSales::items($this->partnershipFilters())
            ->paginate($this->partnershipPerPage, ['*'], self::PARTNERSHIP_PAGE_NAME);
    }

    public function updatedPartnershipSearch(): void
    {
        $this->resetPartnershipPage();
    }

    public function updatedPartnershipCustomerTypeId(): void
    {
        $this->resetPartnershipPage();
    }

    public function updatedPartnershipPerPage(): void
    {
        $this->resetPartnershipPage();
    }

    public function exportPartnershipSales()
    {
        return Excel::download(
            new PartnershipSalesExport($this->partnershipFilters()),
            'Partnership_Sales_'.now()->format('Y_m_d_Hi').'.xlsx'
        );
    }

    public function exportPartnershipSalesPdf()
    {
        return ReportPdf::partnershipSales($this->partnershipFilters());
    }

    /**
     * Resets only this panel's paginator. The host page's resetPage() targets
     * the default "page" name, so the two never disturb each other.
     */
    protected function resetPartnershipPage(): void
    {
        $this->resetPage(self::PARTNERSHIP_PAGE_NAME);
    }

    /**
     * Mirrors the date logic the rest of the Sales page filters by, so the
     * partnership panel always covers the same period as the figures above it.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function partnershipDateRange(): array
    {
        return match ($this->dateFilter ?? '') {
            'today' => [Carbon::today(), Carbon::now()],
            'yesterday' => [Carbon::yesterday(), Carbon::yesterday()->endOfDay()],
            '7days' => [Carbon::today()->subDays(7), Carbon::now()],
            '30days' => [Carbon::today()->subDays(30), Carbon::now()],
            // "All" on this page means no date constraint at all.
            default => [Carbon::create(2000, 1, 1), Carbon::now()],
        };
    }

    /**
     * The branch this panel reports on.
     *
     * Defaults to the signed-in user's active branch, which is what a store
     * screen wants. An admin screen that views an arbitrary branch overrides
     * this to return the branch it is displaying.
     */
    protected function partnershipBranchId(): ?int
    {
        return $this->currentBranchId;
    }

    /**
     * The module of the reported branch. A branch belongs to exactly one
     * module, so this needs no per-module override.
     */
    protected function partnershipCategoryId(): ?int
    {
        return Branch::whereKey($this->partnershipBranchId())->value('product_category_id');
    }
}
