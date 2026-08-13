<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Branch;
use App\Models\CustomerType;
use App\Models\ProductCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDF renderings of the admin reports.
 *
 * dompdf is pure PHP -- no Chrome, no system binaries -- so this works inside
 * the deployment container without extra provisioning. Charts are not rendered
 * here: a PDF carries the tabular figures an auditor signs off on, and the
 * on-screen charts stay interactive.
 */
final class ReportPdf
{
    public static function partnershipSales(PartnershipSalesFilters $filters): StreamedResponse
    {
        $rows = PartnershipSales::items($filters)->limit(self::rowLimit())->get();

        return self::stream('pdf.reports.partnership-sales', [
            'title' => 'Partnership Sales Report',
            'subtitle' => 'Sale lines charged at a partnership price, against the regular price they were discounted from',
            'rows' => $rows,
            'summary' => PartnershipSales::summary($filters),
            'byPartner' => PartnershipSales::byPartner($filters)->get(),
            'truncated' => $rows->count() >= self::rowLimit(),
        ], $filters, 'Partnership_Sales', landscape: true);
    }

    public static function branchPerformance(PartnershipSalesFilters $filters): StreamedResponse
    {
        return self::stream('pdf.reports.branch-performance', [
            'title' => 'Branch Performance Report',
            'subtitle' => 'Completed sales, discounts and payment reconciliation per branch',
            'rows' => SalesAudit::byBranch($filters)->get(),
            'paymentMix' => SalesAudit::paymentMix($filters),
            'statusBreakdown' => SalesAudit::statusBreakdown($filters),
        ], $filters, 'Branch_Performance');
    }

    public static function cashierPerformance(PartnershipSalesFilters $filters): StreamedResponse
    {
        return self::stream('pdf.reports.cashier-performance', [
            'title' => 'Cashier Performance Report',
            'subtitle' => 'Sales rung up per user, for accountability during an audit',
            'rows' => SalesAudit::byCashier($filters)->get(),
        ], $filters, 'Cashier_Performance');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function stream(
        string $view,
        array $data,
        PartnershipSalesFilters $filters,
        string $fileName,
        bool $landscape = false,
    ): StreamedResponse {
        $pdf = Pdf::loadView($view, array_merge($data, [
            'filters' => $filters,
            'context' => self::context($filters),
            'generatedAt' => now(),
        ]))->setPaper('a4', $landscape ? 'landscape' : 'portrait');

        // Must be a StreamedResponse, not dompdf's own stream()/download().
        // Livewire only turns a StreamedResponse or BinaryFileResponse into a
        // download effect; anything else is returned as the AJAX body and the
        // browser gets a PDF where it expected JSON.
        return response()->streamDownload(
            fn () => print $pdf->output(),
            $fileName.'_'.now()->format('Y_m_d_Hi').'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * Human-readable filter summary printed on the PDF header, so a saved file
     * still says what it covers months later.
     *
     * @return array<string, string>
     */
    private static function context(PartnershipSalesFilters $filters): array
    {
        $context = ['Period' => $filters->describe()];

        $context['Branch'] = $filters->branchId
            ? (Branch::find($filters->branchId)?->name ?? 'Unknown branch')
            : 'All branches';

        $context['Module'] = $filters->categoryId
            ? (ProductCategory::find($filters->categoryId)?->name ?? 'Unknown module')
            : 'All modules';

        if ($filters->customerTypeId) {
            $context['Partner'] = CustomerType::find($filters->customerTypeId)?->name ?? 'Unknown partner';
        }

        if ($filters->search !== '') {
            $context['Search'] = $filters->search;
        }

        return $context;
    }

    /**
     * dompdf holds the whole document in memory, so a decade-wide unfiltered
     * range has to be capped. The Excel export has no such limit and is the
     * right tool for a full data dump.
     */
    private static function rowLimit(): int
    {
        return 2000;
    }
}
