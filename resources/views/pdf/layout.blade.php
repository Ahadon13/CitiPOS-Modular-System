{{-- Standalone document for dompdf: CSS 2.1 only, no flexbox/grid/custom properties. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 26px 24px 42px 24px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111827;
            margin: 0;
        }

        .doc-header { border-bottom: 2px solid #111827; padding-bottom: 8px; margin-bottom: 12px; }
        .doc-title { font-size: 16px; font-weight: bold; margin: 0 0 2px 0; }
        .doc-subtitle { font-size: 9px; color: #6b7280; margin: 0; }

        .context { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .context td { font-size: 8px; padding: 2px 10px 2px 0; color: #374151; }
        .context .context-key { color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data thead th {
            background: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
            text-align: left;
            padding: 5px 6px;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #374151;
        }
        table.data tbody td { border-bottom: 1px solid #e5e7eb; padding: 4px 6px; }
        table.data tbody tr:nth-child(even) td { background: #fafafa; }
        table.data tfoot td { border-top: 2px solid #111827; padding: 5px 6px; font-weight: bold; }

        .num { text-align: right; }
        .muted { color: #6b7280; }
        .section-title { font-size: 11px; font-weight: bold; margin: 16px 0 6px 0; }

        .cards { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 6px; }
        .card {
            border: 1px solid #e5e7eb;
            background: #fafafa;
            padding: 7px 9px;
            width: 25%;
        }
        .card-label { font-size: 7px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; }
        .card-value { font-size: 13px; font-weight: bold; margin-top: 2px; }

        .note { font-size: 8px; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; padding: 5px 7px; margin-top: 10px; }

        .footer { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 7px; color: #9ca3af; }
        .footer .right { float: right; }
    </style>
</head>
<body>
    <div class="footer">
        Generated {{ $generatedAt->format('M j, Y g:i A') }}
        <span class="right">Page <script type="text/php">
            if (isset($pdf)) { $pdf->text(($pdf->get_width() - 60), ($pdf->get_height() - 26), $PAGE_NUM . " / " . $PAGE_COUNT, $fontMetrics->getFont("DejaVu Sans"), 7, [0.6,0.6,0.6]); }
        </script></span>
    </div>

    <div class="doc-header">
        <p class="doc-title">{{ $title }}</p>
        <p class="doc-subtitle">{{ $subtitle ?? '' }}</p>
    </div>

    <table class="context">
        <tr>
            @foreach($context as $key => $value)
                <td><span class="context-key">{{ $key }}:</span> <strong>{{ $value }}</strong></td>
            @endforeach
        </tr>
    </table>

    @yield('content')
</body>
</html>
