<?php

declare(strict_types=1);

use App\Enums\Sale\Status;
use App\Support\DateBucket;
use App\Support\PartnershipSales;
use App\Support\PartnershipSalesFilters;
use App\Support\ReportPdf;
use App\Support\SalesAudit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * @return array{branch: int, product: int, packaging: int, partnership: int, customerType: int, user: int, unit: int}
 */
/**
 * The default-system-data migration already seeds product categories, customer
 * types and payment methods, so look those up rather than inserting duplicates.
 */
function lookupOrCreate(string $table, array $match, array $extra = []): int
{
    $existing = DB::table($table)->where($match)->value('id');

    return $existing ? (int) $existing : (int) DB::table($table)->insertGetId([...$match, ...$extra]);
}

function makePartnershipWorld(): array
{
    $categoryId = lookupOrCreate('product_categories', ['name' => 'pharmacy']);
    $branchId = DB::table('branches')->insertGetId([
        'name' => 'Main Pharmacy',
        'product_category_id' => $categoryId,
        'is_active' => true,
    ]);
    $unitId = lookupOrCreate('units', ['name' => 'Piece'], ['abbreviation' => 'pc']);
    $supplierId = DB::table('suppliers')->insertGetId(['name' => 'Acme']);
    $userId = DB::table('users')->insertGetId([
        'branch_id' => $branchId,
        'name' => 'Cashier One',
        'username' => 'cashier-'.uniqid(),
        'password' => 'secret',
    ]);
    $productId = DB::table('products')->insertGetId([
        'supplier_id' => $supplierId,
        'branch_id' => $branchId,
        'product_category_id' => $categoryId,
        'base_unit_id' => $unitId,
        'product_code' => 'RPT-'.uniqid(),
        'name' => 'Paracetamol',
        'brand_name' => 'Biogesic',
    ]);
    $packagingId = DB::table('product_packagings')->insertGetId([
        'product_id' => $productId,
        'unit_id' => $unitId,
        'conversion_factor' => 1,
        'price' => 1000, // regular price: 10.00
    ]);
    $customerTypeId = lookupOrCreate('customer_types', ['name' => 'LGU'], ['discount_percentage' => 0]);
    $partnershipId = DB::table('partnerships')->insertGetId([
        'branch_id' => $branchId,
        'customer_type_id' => $customerTypeId,
        'product_packaging_id' => $packagingId,
        'special_price' => 700, // partner price: 7.00
    ]);
    $paymentMethodId = lookupOrCreate('payment_methods', ['name' => 'Cash'], ['is_active' => true]);

    return compact('branchId', 'productId', 'packagingId', 'partnershipId', 'customerTypeId', 'userId', 'unitId', 'paymentMethodId');
}

function recordPartnerSale(array $world, string $soldAt, float $quantity = 10, string $priceSource = 'partnership'): int
{
    $saleId = DB::table('sales')->insertGetId([
        'branch_id' => $world['branchId'],
        'user_id' => $world['userId'],
        'payment_method_id' => $world['paymentMethodId'],
        'grand_total' => (int) ($quantity * ($priceSource === 'partnership' ? 700 : 1000)),
        'status' => Status::Completed->value,
        'created_at' => $soldAt,
        'updated_at' => $soldAt,
    ]);

    DB::table('sale_items')->insert([
        'sale_id' => $saleId,
        'product_id' => $world['productId'],
        'unit_id' => $world['unitId'],
        'product_packaging_id' => $world['packagingId'],
        'quantity' => $quantity,
        'price_at_moment' => $priceSource === 'partnership' ? 700 : 1000,
        'regular_price_at_moment' => 1000,
        'price_source' => $priceSource,
        'partnership_id' => $priceSource === 'partnership' ? $world['partnershipId'] : null,
        'cost_at_moment' => 400,
        'subtotal' => (int) ($quantity * ($priceSource === 'partnership' ? 700 : 1000)),
        'created_at' => $soldAt,
        'updated_at' => $soldAt,
    ]);

    return $saleId;
}

function filtersFor(string $start = '2026-01-01', string $end = '2026-12-31'): PartnershipSalesFilters
{
    return new PartnershipSalesFilters(
        start: CarbonImmutable::parse($start),
        end: CarbonImmutable::parse($end),
    );
}

it('reports the discount a partner received against the regular price', function () {
    $world = makePartnershipWorld();
    recordPartnerSale($world, '2026-03-05 10:00:00', quantity: 10);

    $summary = PartnershipSales::summary(filtersFor());

    // 10 units: partner paid 7.00 each (7,000c), regular is 10.00 each (10,000c).
    expect($summary['lines'])->toBe(1);
    expect($summary['partners'])->toBe(1);
    expect($summary['revenue'])->toBe(7000);
    expect($summary['regular_value'])->toBe(10000);
    expect($summary['savings'])->toBe(3000);
});

it('excludes regular-priced lines from the partnership report', function () {
    $world = makePartnershipWorld();
    recordPartnerSale($world, '2026-03-05 10:00:00', quantity: 10, priceSource: 'partnership');
    recordPartnerSale($world, '2026-03-06 10:00:00', quantity: 10, priceSource: 'regular');

    expect(PartnershipSales::summary(filtersFor())['lines'])->toBe(1);
    expect(PartnershipSales::items(filtersFor())->count())->toBe(1);

    // The mix, by contrast, counts both so the share can be computed.
    $mix = PartnershipSales::priceSourceMix(filtersFor());
    expect($mix['partnership'])->toBe(7000);
    expect($mix['regular'])->toBe(10000);
});

it('keeps a decade-wide partnership trend under the chart point budget', function () {
    $world = makePartnershipWorld();

    foreach (['2015-02-01', '2019-06-15', '2023-09-30', '2026-01-10'] as $date) {
        recordPartnerSale($world, $date.' 09:00:00', quantity: 5);
    }

    $trend = PartnershipSales::trend(filtersFor('2015-01-01', '2026-12-31'));

    expect($trend['granularity'])->toBe('Quarterly');
    expect($trend['labels'])->toHaveCount(48);
    expect($trend['series'])->toHaveCount(1);
    expect($trend['series'][0]['name'])->toBe('LGU');
    expect($trend['series'][0]['data'])->toHaveCount(48);

    // Every sale still lands somewhere in the folded series.
    expect(array_sum($trend['series'][0]['data']))->toBe(4 * 5 * 7.0);
});

it('caps the partnership trend at the palette size and folds the rest', function () {
    $world = makePartnershipWorld();
    recordPartnerSale($world, '2026-03-05 10:00:00');

    // Ten partners in total: eight keep a hue, the rest fold into "Other".
    for ($i = 0; $i < 9; $i++) {
        $typeId = DB::table('customer_types')->insertGetId([
            'name' => 'Partner '.$i,
            'discount_percentage' => 0,
        ]);
        $partnershipId = DB::table('partnerships')->insertGetId([
            'branch_id' => $world['branchId'],
            'customer_type_id' => $typeId,
            'product_packaging_id' => $world['packagingId'],
            'special_price' => 700,
        ]);

        recordPartnerSale(
            [...$world, 'partnershipId' => $partnershipId],
            '2026-03-0'.($i + 1).' 10:00:00',
            quantity: 10 - $i,
        );
    }

    $trend = PartnershipSales::trend(filtersFor());

    expect($trend['has_other'])->toBeTrue();
    expect($trend['series'])->toHaveCount(9); // 8 named + Other
    expect(end($trend['series'])['name'])->toBe('Other partners');
});

it('breaks sales down by branch, cashier and payment method', function () {
    $world = makePartnershipWorld();
    recordPartnerSale($world, '2026-03-05 10:00:00', quantity: 10);

    $filters = filtersFor();

    expect(SalesAudit::byBranch($filters)->get())->toHaveCount(1);
    expect(SalesAudit::byCashier($filters)->get()->first()->cashier_name)->toBe('Cashier One');
    expect(SalesAudit::paymentMix($filters)['labels'])->toBe(['Cash']);
    expect(SalesAudit::branchTrend($filters)['series'][0]['name'])->toBe('Main Pharmacy');
});

it('renders each report as a downloadable PDF', function () {
    $world = makePartnershipWorld();
    recordPartnerSale($world, '2026-03-05 10:00:00');

    $filters = filtersFor();

    foreach ([
        ReportPdf::partnershipSales($filters),
        ReportPdf::branchPerformance($filters),
        ReportPdf::cashierPerformance($filters),
    ] as $response) {
        // Livewire only converts a StreamedResponse (or BinaryFileResponse)
        // into a download; anything else is emitted as the AJAX body and the
        // download silently fails in the browser.
        expect($response)->toBeInstanceOf(Symfony\Component\HttpFoundation\StreamedResponse::class);
        expect($response->headers->get('content-type'))->toContain('application/pdf');
        expect($response->headers->get('content-disposition'))->toContain('attachment');

        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        expect($body)->toStartWith('%PDF-');
    }
});

it('picks a coarser bucket as the range widens', function () {
    $cases = [
        ['2026-08-01', '2026-08-31', DateBucket::DAY],
        ['2026-01-01', '2026-12-31', DateBucket::WEEK],
        ['2024-01-01', '2026-12-31', DateBucket::MONTH],
        ['2015-01-01', '2026-12-31', DateBucket::QUARTER],
        ['1999-01-01', '2026-12-31', DateBucket::YEAR],
    ];

    foreach ($cases as [$start, $end, $expected]) {
        $bucket = DateBucket::resolve(CarbonImmutable::parse($start), CarbonImmutable::parse($end));

        expect($bucket->granularity)->toBe($expected);
        // The whole point: no range may exceed the chart point budget.
        expect(count($bucket->buckets()))->toBeLessThanOrEqual(70);
    }
});
