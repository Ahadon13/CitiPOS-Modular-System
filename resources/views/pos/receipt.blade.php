<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $moduleLabel }} Receipt #{{ $sale->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 12px;
        }
        .page {
            max-width: 420px;
            margin: 24px auto;
            padding: 16px;
        }
        .toolbar {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        button, a {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #fff;
            color: #111827;
            padding: 8px 10px;
            text-decoration: none;
            cursor: pointer;
            font: inherit;
        }
        .receipt {
            background: #fff;
            padding: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
        .bold { font-weight: 700; }
        .line { border-top: 1px dashed #9ca3af; margin: 10px 0; }
        .row { display: flex; justify-content: space-between; gap: 12px; }
        .item { margin-bottom: 8px; }
        .item-name { font-weight: 700; }
        .sub { font-size: 11px; color: #4b5563; }
        .total { font-size: 15px; font-weight: 800; }
        @media print {
            @page { size: 80mm auto; margin: 4mm; }
            body { background: #fff; }
            .page { max-width: none; margin: 0; padding: 0; }
            .toolbar { display: none; }
            .receipt { border: 0; box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>
    @php
        $money = fn (int|float|null $cents) => 'PHP ' . number_format(((int) $cents) / 100, 2);
    @endphp
    <div class="page">
        <div class="toolbar">
            <button type="button" onclick="window.print()">Print Receipt</button>
            <a href="{{ url()->previous() }}">Back</a>
        </div>

        <div class="receipt">
            <div class="center">
                <div class="bold">{{ $sale->branch->name ?? 'Branch' }}</div>
                <div class="muted">{{ $sale->branch->address ?? '' }}</div>
                <div class="muted">{{ $moduleLabel }} POS Receipt</div>
            </div>

            <div class="line"></div>

            <div class="row"><span>Receipt</span><span>#{{ $sale->id }}</span></div>
            <div class="row"><span>Date</span><span>{{ $sale->created_at->format('M d, Y h:i A') }}</span></div>
            <div class="row"><span>Cashier</span><span>{{ $sale->user->name ?? 'Unknown' }}</span></div>
            <div class="row"><span>Customer</span><span>{{ $sale->customer->name ?? 'Walk-in' }}</span></div>
            @if($sale->paymentMethod)
                <div class="row"><span>Payment</span><span>{{ $sale->paymentMethod->name }}</span></div>
            @endif
            @if($sale->payment_reference)
                <div class="row"><span>Reference</span><span>{{ $sale->payment_reference }}</span></div>
            @endif

            <div class="line"></div>

            @foreach($sale->saleItems as $item)
                @php
                    $unitPrice = (int) $item->getRawOriginal('price_at_moment');
                    $regularPrice = $item->getRawOriginal('regular_price_at_moment');
                    $subtotal = (int) $item->getRawOriginal('subtotal');
                    $isPartnership = $item->price_source === 'partnership' && $regularPrice;
                @endphp
                <div class="item">
                    <div class="item-name">{{ $item->product->brand_name ?? $item->product->name ?? 'Product' }}</div>
                    <div class="row">
                        <span>{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} {{ $item->unit->abbreviation ?? $item->unit->name ?? 'unit' }} x {{ $money($unitPrice) }}</span>
                        <span>{{ $money($subtotal) }}</span>
                    </div>
                    @if($isPartnership)
                        <div class="sub">
                            Regular: {{ $money((int) $regularPrice) }} / Partnership: {{ $money($unitPrice) }}
                            @if($item->partnership?->customerType)
                                ({{ $item->partnership->customerType->name }})
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach

            @foreach($sale->motorShopServices as $service)
                @php($serviceSubtotal = (int) $service->getRawOriginal('subtotal'))
                <div class="item">
                    <div class="item-name">SERVICE: {{ $service->service_name }}</div>
                    <div class="row">
                        <span>{{ rtrim(rtrim(number_format((float) $service->quantity, 2), '0'), '.') }} x {{ $money((int) $service->getRawOriginal('price_at_moment')) }}</span>
                        <span>{{ $money($serviceSubtotal) }}</span>
                    </div>
                    @if($service->mechanic)
                        <div class="sub">Mechanic: {{ $service->mechanic->name }}</div>
                    @endif
                    @if($service->description)
                        <div class="sub">{{ $service->description }}</div>
                    @endif
                </div>
            @endforeach

            <div class="line"></div>

            <div class="row"><span>Subtotal</span><span>{{ $money((int) $sale->getRawOriginal('subtotal')) }}</span></div>
            <div class="row"><span>Discount</span><span>-{{ $money((int) $sale->getRawOriginal('discount_amount')) }}</span></div>
            @if($sale->discountType)
                <div class="sub right">Discount type: {{ $sale->discountType->name }} ({{ number_format((float) $sale->discountType->discount_percentage, 2) }}%)</div>
            @endif
            <div class="row total"><span>Total</span><span>{{ $money((int) $sale->getRawOriginal('grand_total')) }}</span></div>
            <div class="row"><span>Amount Paid</span><span>{{ $money((int) $sale->getRawOriginal('amount_tendered')) }}</span></div>
            <div class="row"><span>Change</span><span>{{ $money((int) $sale->getRawOriginal('change_amount')) }}</span></div>

            <div class="line"></div>

            <div class="center muted">
                Thank you for your purchase.<br>
                Please keep this receipt for your records.
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 250);
        });
    </script>
</body>
</html>
