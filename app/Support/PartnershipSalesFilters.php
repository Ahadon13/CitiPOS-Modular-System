<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The filter set shared by the partnership report table, its charts and its
 * exports, so the screen and the downloaded file can never disagree.
 */
final class PartnershipSalesFilters
{
    public readonly CarbonImmutable $start;

    public readonly CarbonImmutable $end;

    public function __construct(
        CarbonInterface $start,
        CarbonInterface $end,
        public readonly ?int $branchId = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $customerTypeId = null,
        public readonly string $search = '',
    ) {
        $this->start = CarbonImmutable::parse($start->toDateTimeString());
        $this->end = CarbonImmutable::parse($end->toDateTimeString());
    }

    /**
     * Label describing the active filters, printed on exports so a downloaded
     * file is self-describing during an audit.
     */
    public function describe(): string
    {
        return $this->start->format('M j, Y').' - '.$this->end->format('M j, Y');
    }
}
