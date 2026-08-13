@use('App\Support\MoneyHelper')

@extends('pdf.layout')

@section('content')
    <div class="section-title">Revenue by branch</div>
    <table class="data">
        <thead>
            <tr>
                <th>Branch</th>
                <th>Module</th>
                <th class="num">Orders</th>
                <th class="num">Revenue</th>
                <th class="num">Discounts Given</th>
                <th class="num">Average Order</th>
            </tr>
        </thead>
        <tbody>
            @php($totalRevenue = 0)
            @php($totalOrders = 0)
            @forelse($rows as $row)
                @php($totalRevenue += (int) round((float) $row->revenue))
                @php($totalOrders += (int) $row->orders)
                <tr>
                    <td>{{ $row->branch_name }}</td>
                    <td class="muted">{{ $row->module ?? '-' }}</td>
                    <td class="num">{{ number_format((int) $row->orders) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($row->revenue) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($row->discounts) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents((int) $row->orders > 0 ? (float) $row->revenue / (int) $row->orders : 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No completed sales in this period.</td></tr>
            @endforelse
        </tbody>
        @if($totalOrders > 0)
            <tfoot>
                <tr>
                    <td colspan="2">Total</td>
                    <td class="num">{{ number_format($totalOrders) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($totalRevenue) }}</td>
                    <td class="num"></td>
                    <td class="num">{{ MoneyHelper::formatCents($totalRevenue / $totalOrders) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="section-title">Payment method reconciliation</div>
    <table class="data">
        <thead>
            <tr>
                <th>Payment Method</th>
                <th class="num">Orders</th>
                <th class="num">Amount Collected</th>
            </tr>
        </thead>
        <tbody>
            @forelse($paymentMix['rows'] as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="num">{{ number_format($row['orders']) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($row['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No payments recorded in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Sale status breakdown</div>
    <table class="data">
        <thead>
            <tr>
                <th>Status</th>
                <th class="num">Orders</th>
                <th class="num">Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statusBreakdown as $row)
                <tr>
                    <td>{{ ucfirst($row['status']) }}</td>
                    <td class="num">{{ number_format($row['orders']) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($row['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No sales in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
