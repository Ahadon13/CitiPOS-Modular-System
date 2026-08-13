@use('App\Support\MoneyHelper')

@extends('pdf.layout')

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th>Cashier</th>
                <th>Branch</th>
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
                    <td>{{ $row->cashier_name }}</td>
                    <td class="muted">{{ $row->branch_name }}</td>
                    <td class="num">{{ number_format((int) $row->orders) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($row->revenue) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($row->discounts) }}</td>
                    <td class="num">{{ MoneyHelper::formatCents($row->average_order) }}</td>
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
@endsection
