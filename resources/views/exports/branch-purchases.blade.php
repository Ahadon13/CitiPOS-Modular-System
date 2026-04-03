<table>
    <tbody>
        @foreach($purchases as $purchase)
            <tr>
                <th colspan="5" style="font-weight: bold; font-size: 14px; text-align: center; background-color: #d1d5db;">
                    PURCHASE ORDER: {{ $purchase->reference_no }}
                </th>
            </tr>
            <tr>
                <th colspan="2"><strong>Date:</strong> {{ $purchase->created_at->format('M d, Y') }}</th>
                <th colspan="3" style="text-align: right;"><strong>Status:</strong> {{ strtoupper($purchase->status->value) }}</th>
            </tr>
            <tr>
                <th colspan="2"><strong>Supplier:</strong> {{ $purchase->supplier->name ?? 'Unknown' }}</th>
                <th colspan="3" style="text-align: right;">
                    <strong>Expected Delivery:</strong>
                    {{ $purchase->expected_delivery_date ? \Carbon\Carbon::parse($purchase->expected_delivery_date)->format('M d, Y') : 'Not specified' }}
                </th>
            </tr>

            {{-- Item Headers --}}
            <tr>
                <th style="font-weight: bold; border: 1px solid #000;">Product</th>
                <th style="font-weight: bold; border: 1px solid #000; text-align: center;">Unit</th>
                <th style="font-weight: bold; border: 1px solid #000; text-align: right;">Qty Ordered / Received</th>
                <th style="font-weight: bold; border: 1px solid #000; text-align: right;">Unit Cost</th>
                <th style="font-weight: bold; border: 1px solid #000; text-align: right;">Subtotal</th>
            </tr>

            {{-- Items Loop --}}
            @foreach($purchase->purchaseItems as $item)
                <tr>
                    <td>{{ $item->product->brand_name ?? 'Unknown' }} ({{ $item->product->dosage ?? '' }}) - {{ $item->product->generic_name ?? '' }}</td>
                    <td style="text-align: center;">{{ $item->unit->name ?? '' }}</td>
                    <td style="text-align: right;">{{ (float) $item->quantity_ordered }} / {{ (float) $item->quantity_received }}</td>
                    <td style="text-align: right;">{{ number_format($item->getRawOriginal('cost_per_unit') / 100, 2) }}</td>
                    <td style="text-align: right;">
                        @php
                            $qty = $item->quantity_received > 0 ? $item->quantity_received : $item->quantity_ordered;
                        @endphp
                        {{ number_format(($qty * ($item->getRawOriginal('cost_per_unit') / 100)), 2) }}
                    </td>
                </tr>
            @endforeach

            {{-- PO Total --}}
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold; color: #1e3a8a;">PO TOTAL:</td>
                <td style="font-weight: bold; text-align: right; color: #1e3a8a;">{{ number_format($purchase->getRawOriginal('total_cost') / 100, 2) }}</td>
            </tr>

            {{-- Blank Spacer Row Before Next PO --}}
            <tr><td colspan="5"></td></tr>
            <tr><td colspan="5"></td></tr>
        @endforeach
    </tbody>
</table>
