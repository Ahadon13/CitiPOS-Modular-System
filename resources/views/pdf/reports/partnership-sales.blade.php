@use('App\Support\MoneyHelper')

@extends('pdf.layout')

@section('content')
    <table class="cards">
        <tr>
            <td class="card">
                <div class="card-label">Partnership Revenue</div>
                <div class="card-value">{{ MoneyHelper::formatCents($summary['revenue']) }}</div>
            </td>
            <td class="card">
                <div class="card-label">Value at Regular Price</div>
                <div class="card-value">{{ MoneyHelper::formatCents($summary['regular_value']) }}</div>
            </td>
            <td class="card">
                <div class="card-label">Total Partner Savings</div>
                <div class="card-value">{{ MoneyHelper::formatCents($summary['savings']) }}</div>
            </td>
            <td class="card">
                <div class="card-label">Partners / Lines</div>
                <div class="card-value">{{ $summary['partners'] }} / {{ number_format($summary['lines']) }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Summary by partner</div>
    <table class="data">
        <thead>
            <tr>
                <th>Partner</th>
                <th class="num">Orders</th>
                <th class="num">Qty</th>
                <th class="num">Revenue</th>
                <th class="num">Savings Given</th>
            </tr>
        </thead>
        <tbody>
            @forelse($byPartner as $partner)
                <tr>
                    <td>{{ $partner->partner_name ?? 'Unknown partner' }}</td>
                    <td class="num">{{ number_format((int) $partner->orders) }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format((float) $partner->quantity, 2), '0'), '.') }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($partner->revenue) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($partner->savings) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No partnership sales in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Transaction detail</div>
    <table class="data">
        <thead>
            <tr>
                <th>Date</th>
                <th>Sale #</th>
                <th>Branch</th>
                <th>Partner</th>
                <th>Customer</th>
                <th>Product</th>
                <th class="num">Qty</th>
                <th class="num">Regular</th>
                <th class="num">Partner Price</th>
                <th class="num">Line Total</th>
                <th class="num">Saved</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->sold_at)->format('Y-m-d') }}</td>
                    <td>#{{ $row->sale_id }}</td>
                    <td>{{ $row->branch_name }}</td>
                    <td>{{ $row->partner_name ?? '-' }}</td>
                    <td>{{ $row->customer_name ?? 'Walk-in' }}</td>
                    <td>{{ $row->brand_name ?: ($row->product_name ?: $row->product_code) }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format((float) $row->quantity, 2), '0'), '.') }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($row->regular_price_at_moment ?? $row->price_at_moment) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($row->price_at_moment) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($row->subtotal) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($row->partner_savings) }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="muted">No partnership-priced sale lines matched these filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($truncated)
        <div class="note">
            This PDF is capped at 2,000 detail lines. Narrow the date range, or use the Excel export for the complete data set.
        </div>
    @endif
@endsection
