<?php

declare(strict_types=1);

use App\Support\PartnershipSales;
use App\Support\PartnershipSalesFilters;
use App\Support\SalesAudit;
use Carbon\CarbonImmutable;

/**
 * The test database is SQLite but production is MySQL, so a query can pass the
 * suite and still be a syntax error in production. SQLite happily accepts
 * `COUNT(*) as lines`; MySQL rejects it, because LINES is reserved.
 *
 * This checks the column aliases the report queries generate against MySQL's
 * reserved word list, which is the specific class of bug that slips through an
 * SQLite-backed suite.
 */

/** @return list<string> */
function aliasesIn(string $sql): array
{
    preg_match_all('/\bas\s+`?([a-z_][a-z0-9_]*)`?/i', $sql, $matches);

    return array_values(array_unique(array_map('strtolower', $matches[1])));
}

/**
 * MySQL 8.0 reserved words that are plausible as a report column alias.
 *
 * @return list<string>
 */
function mysqlReservedWords(): array
{
    return [
        'lines', 'rank', 'rows', 'groups', 'system', 'range', 'order', 'group',
        'key', 'keys', 'primary', 'usage', 'read', 'write', 'left', 'right',
        'both', 'interval', 'is', 'in', 'as', 'by', 'to', 'from', 'where',
        'select', 'table', 'values', 'when', 'then', 'else', 'case', 'and',
        'or', 'not', 'null', 'true', 'false', 'like', 'between', 'exists',
        'having', 'union', 'distinct', 'into', 'for', 'with', 'over',
        'partition', 'window', 'recursive', 'lead', 'lag', 'first', 'last',
        'cume_dist', 'dense_rank', 'percent_rank', 'row_number', 'ntile',
        'grouping', 'condition', 'terminated', 'optionally', 'escaped',
        'starting', 'analyse', 'accessible', 'general', 'ignore', 'call',
        'blob', 'int', 'long', 'match', 'option', 'status', 'schema',
    ];
}

function filtersForDialectCheck(): PartnershipSalesFilters
{
    return new PartnershipSalesFilters(
        start: CarbonImmutable::parse('2026-01-01'),
        end: CarbonImmutable::parse('2026-12-31'),
    );
}

it('never aliases a report column to a MySQL reserved word', function () {
    $filters = filtersForDialectCheck();

    $queries = [
        'PartnershipSales::items' => PartnershipSales::items($filters)->toSql(),
        'PartnershipSales::byPartner' => PartnershipSales::byPartner($filters)->toSql(),
        'SalesAudit::byBranch' => SalesAudit::byBranch($filters)->toSql(),
        'SalesAudit::byCashier' => SalesAudit::byCashier($filters)->toSql(),
        'SalesAudit::topProducts' => SalesAudit::topProducts($filters)->toSql(),
    ];

    // summary() runs aggregates through first(), so build the same select here.
    $queries['PartnershipSales::summary'] = PartnershipSales::itemsQuery($filters)
        ->selectRaw('COUNT(*) as line_count')
        ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as quantity')
        ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
        ->selectRaw('COUNT(DISTINCT partnerships.customer_type_id) as partners')
        ->toSql();

    $reserved = mysqlReservedWords();

    foreach ($queries as $name => $sql) {
        $offenders = array_values(array_intersect(aliasesIn($sql), $reserved));

        expect($offenders)->toBe([], "{$name} aliases a MySQL reserved word: ".implode(', ', $offenders));
    }
});

it('still exposes the partnership line count under a stable key', function () {
    // The alias changed to dodge the reserved word; the array key callers and
    // Blade templates read must not have moved with it.
    $summary = PartnershipSales::summary(filtersForDialectCheck());

    expect($summary)->toHaveKeys(['lines', 'quantity', 'revenue', 'regular_value', 'savings', 'partners']);
    expect($summary['lines'])->toBeInt();
});
