<table>
    <thead>
        <tr>
            <th colspan="5" style="font-weight: bold; font-size: 16px; text-align: center;">PURCHASE ORDER</th>
        </tr>
        <tr>
            <th colspan="2"><strong>PO Number:</strong> {{ $purchase->reference_no }}</th>
            <th colspan="3" style="text-align: right;"><strong>Date:</strong> {{ $purchase->created_at->format('M d, Y') }}</th>
        </tr>
        <tr>
            <th colspan="2"><strong>Supplier:</strong> {{ $purchase->supplier->name }}</th>
            <th colspan="3" style="text-align: right;"><strong>Status:</strong> {{ strtoupper($purchase->status->value) }}</th>
        </tr>
        <tr>
            <th colspan="2"></th>
            <th colspan="3" style="text-align: right; color: #d97706;">
                <strong>Expected Delivery:</strong>
                {{ $purchase->expected_delivery_date ? \Carbon\Carbon::parse($purchase->expected_delivery_date)->format('M d, Y') : 'Not specified' }}
            </th>
        </tr>
        <tr>
            <th colspan="5"></th> {{-- Empty row for spacing --}}
        </tr>
        <tr>
            <th style="font-weight: bold; border: 1px solid #000;">Product</th>
            <th style="font-weight: bold; border: 1px solid #000; text-align: center;">Unit</th>
            <th style="font-weight: bold; border: 1px solid #000; text-align: right;">Qty Ordered</th>
            <th style="font-weight: bold; border: 1px solid #000; text-align: right;">Unit Cost</th>
            <th style="font-weight: bold; border: 1px solid #000; text-align: right;">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @foreach($purchase->purchaseItems as $item)
            <tr>
                <td style="border: 1px solid #000;">{{ $item->product->brand_name }} ({{ $item->product->generic_name }})</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item->unit->name }}</td>
                <td style="border: 1px solid #000; text-align: right;">{{ number_format($item->quantity_ordered, 2) }}</td>
                <td style="border: 1px solid #000; text-align: right;">{{ number_format($item->getRawOriginal('cost_per_unit') / 100, 2) }}</td>
                <td style="border: 1px solid #000; text-align: right;">
                    {{ number_format(($item->quantity_ordered * ($item->getRawOriginal('cost_per_unit') / 100)), 2) }}
                </td>
            </tr>
        @endforeach
        <tr>
            <td colspan="4" style="text-align: right; font-weight: bold;">GRAND TOTAL:</td>
            <td style="font-weight: bold; text-align: right;">{{ number_format($purchase->getRawOriginal('total_cost') / 100, 2) }}</td>
        </tr>
    </tbody>
</table>
