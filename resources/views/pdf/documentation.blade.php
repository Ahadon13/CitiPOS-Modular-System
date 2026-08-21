@php
    /**
     * A4 print layout for the user manual.
     *
     * Written for dompdf, which has no flexbox or grid -- every column here is
     * a table, and every accent bar is a border. DejaVu Sans throughout because
     * it is the only bundled face carrying the peso sign the content uses.
     */
    $tl = $lang === 'tl';
    $sectionCount = array_sum(array_map(fn ($c) => count($c['sections']), $chapters));
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <title>{{ $tl ? 'CitiPOS Gabay ng Gumagamit' : 'CitiPOS User Manual' }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 14mm 16mm 14mm; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9pt;
            line-height: 1.42;
            color: #1f2937;
        }

        p { margin: 0 0 5pt; }
        b, strong { font-weight: bold; color: #111827; }
        kbd {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 8pt;
            border: 0.6pt solid #9ca3af;
            border-radius: 2pt;
            padding: 0.5pt 2.5pt;
            background: #f3f4f6;
            color: #111827;
        }

        /* ---------- running header and footer ---------- */

        .runner {
            position: fixed;
            left: 0;
            width: 182mm;
            font-size: 7.5pt;
            color: #9ca3af;
        }
        #runner-top { top: -10mm; border-bottom: 0.5pt solid #e5e7eb; padding-bottom: 1.5mm; }
        #runner-bottom { bottom: -10mm; border-top: 0.5pt solid #e5e7eb; padding-top: 1.5mm; }
        /* Laid out with a table rather than floats: a float inside a fixed
           element leaks out in dompdf and shunts the page content sideways. */
        .runner table { width: 100%; border-collapse: collapse; }
        .runner td { padding: 0; color: #9ca3af; }
        .runner td.right { text-align: right; }
        .runner .brand { color: #2563eb; font-weight: bold; }
        #folio:after { content: counter(page); }

        /* ---------- cover ---------- */

        .cover {
            position: absolute;
            top: -18mm; left: -14mm;
            width: 210mm; height: 297mm;
            background: #050609;
            color: #e5e7eb;
            z-index: 20;
        }
        .cover-inner { padding: 58mm 22mm 0 22mm; }
        .cover .eyebrow {
            font-size: 8.5pt; letter-spacing: 3pt; color: #60a5fa;
            text-transform: uppercase; margin: 0 0 6mm;
        }
        .cover h1 { font-size: 40pt; margin: 0; color: #ffffff; letter-spacing: -1pt; }
        .cover .rule { width: 34mm; height: 1.6mm; background: #2563eb; margin: 6mm 0; }
        .cover h2 { font-size: 15pt; margin: 0 0 3mm; color: #93c5fd; font-weight: normal; }
        .cover .lede { font-size: 10.5pt; color: #9ca3af; line-height: 1.7; width: 120mm; margin: 0 0 12mm; }
        .cover .chip {
            display: inline-block; border: 0.7pt solid #3b82f6; color: #93c5fd;
            border-radius: 10pt; padding: 1.6mm 5mm; margin: 0 2mm 2mm 0; font-size: 8.5pt;
        }
        .cover .foot {
            position: absolute; bottom: 24mm; left: 22mm; width: 166mm;
            font-size: 8.5pt; color: #6b7280; border-top: 0.5pt solid #1f2937; padding-top: 4mm;
        }
        .cover .foot table { width: 100%; border-collapse: collapse; }
        .cover .foot td { padding: 0; color: #6b7280; }
        .cover .foot td.right { text-align: right; }

        /* ---------- contents ---------- */

        .page-title { font-size: 20pt; color: #111827; margin: 0 0 2mm; letter-spacing: -0.5pt; }
        .page-kicker {
            font-size: 8pt; letter-spacing: 2.4pt; color: #2563eb;
            text-transform: uppercase; margin: 0 0 2mm;
        }
        .title-rule { width: 20mm; height: 1.2mm; background: #2563eb; margin: 0 0 4mm; }
        .lede { color: #4b5563; margin: 0 0 5mm; }

        table.toc-columns { width: 100%; border-collapse: collapse; }
        table.toc-columns td { padding: 0; vertical-align: top; width: 50%; }
        table.toc-columns td.gutter { width: 10mm; }

        table.toc { width: 100%; border-collapse: collapse; }
        table.toc td { padding: 0; vertical-align: top; }
        .toc .chapter-row td {
            padding: 2.6mm 0 0.8mm; font-size: 10.5pt; font-weight: bold; color: #111827;
            border-bottom: 0.5pt solid #e5e7eb;
        }
        .toc .chapter-row .num { color: #2563eb; width: 10mm; }
        .toc .section-row td { padding: 0.5mm 0; color: #4b5563; font-size: 9pt; }
        .toc .section-row .num { color: #9ca3af; width: 10mm; font-family: "DejaVu Sans Mono", monospace; font-size: 7.5pt; }

        /* ---------- chapter opener ---------- */

        .chapter { page-break-before: always; }
        .chapter-head { border-left: 2mm solid #2563eb; padding: 0 0 0 6mm; margin: 0 0 7mm; }
        .chapter-head .num {
            font-family: "DejaVu Sans Mono", monospace; font-size: 8pt;
            letter-spacing: 2pt; color: #2563eb; text-transform: uppercase;
        }
        .chapter-head h2 { font-size: 20pt; margin: 1mm 0 1.5mm; color: #111827; letter-spacing: -0.5pt; }
        .chapter-head .blurb { color: #4b5563; margin: 0; }

        /* ---------- sections ---------- */

        .section { margin: 0 0 6mm; }
        .section-head { margin: 0 0 3mm; border-bottom: 0.5pt solid #e5e7eb; padding-bottom: 1.5mm; }
        .section-head .num {
            font-family: "DejaVu Sans Mono", monospace; font-size: 9pt;
            color: #2563eb; font-weight: bold; padding-right: 3mm;
        }
        .section-head .title { font-size: 12pt; font-weight: bold; color: #111827; }

        /* ---------- blocks ---------- */

        table.steps, table.bullets { width: 100%; border-collapse: collapse; margin: 0 0 5pt; }
        table.steps td, table.bullets td { padding: 0 0 1.8mm; vertical-align: top; }
        table.steps .marker { width: 8mm; }
        /* Centred with padding rather than line-height: dompdf does not
           vertically centre a line box inside a fixed-height block. */
        table.steps .marker div {
            width: 5.4mm; height: 5.4mm; border-radius: 3mm;
            background: #2563eb; color: #ffffff;
            font-size: 7.5pt; font-weight: bold; text-align: center;
            line-height: 1; padding-top: 1.5mm;
        }
        table.bullets .marker { width: 5mm; color: #2563eb; font-weight: bold; }

        .callout {
            border: 0.5pt solid #bfdbfe; border-left: 1.6mm solid #2563eb;
            background: #eff6ff; padding: 2.4mm 3.4mm; margin: 0 0 5pt;
            page-break-inside: avoid;
        }
        .callout .label {
            font-size: 7pt; letter-spacing: 1.6pt; text-transform: uppercase;
            color: #2563eb; font-weight: bold; margin: 0 0 1mm;
        }
        .callout p { margin: 0; }
        .callout.warn { border-color: #fde68a; border-left-color: #d97706; background: #fffbeb; }
        .callout.warn .label { color: #b45309; }

        figure { margin: 0 0 5mm; page-break-inside: avoid; }
        figure img { border: 0.5pt solid #d1d5db; }
        figcaption { font-size: 7.5pt; color: #6b7280; margin-top: 1.2mm; line-height: 1.4; }

        table.data {
            width: 100%; border-collapse: collapse; margin: 0 0 5pt; font-size: 8pt;
        }
        table.data th {
            background: #f3f4f6; color: #374151; text-align: left;
            font-size: 7pt; letter-spacing: 1.2pt; text-transform: uppercase;
            padding: 1.8mm 2.6mm; border: 0.5pt solid #e5e7eb;
        }
        table.data td {
            padding: 1.8mm 2.6mm; border: 0.5pt solid #e5e7eb; vertical-align: top; color: #374151;
        }
    </style>
</head>
<body>

{{-- Repeats on every page; the cover sits on top of it with a higher z-index. --}}
<div class="runner" id="runner-top">
    <table><tr>
        <td><span class="brand">CitiPOS</span> &nbsp;{{ $tl ? 'Gabay ng Gumagamit' : 'User Manual' }}</td>
        <td class="right">{{ $tl ? 'Bersyon 1.0' : 'Version 1.0' }}</td>
    </tr></table>
</div>
<div class="runner" id="runner-bottom">
    <table><tr>
        <td>{{ $tl ? 'Gabay ng Gumagamit ng CitiPOS' : 'CitiPOS User Manual' }}</td>
        <td class="right"><span id="folio"></span></td>
    </tr></table>
</div>

{{-- ============ COVER ============ --}}
<div class="cover">
    <div class="cover-inner">
        <p class="eyebrow">{{ $tl ? 'Point of Sale at Imbentaryo' : 'Point of Sale & Inventory' }}</p>
        <h1>CitiPOS</h1>
        <div class="rule"></div>
        <h2>{{ $tl ? 'Kumpletong Gabay ng Gumagamit' : 'Complete User Manual' }}</h2>
        <p class="lede">
            {{ $tl
                ? 'Bawat feature ng POS at inventory, hakbang-hakbang, may totoong screenshot mula mismo sa sistema — para sa administrador, pharmacist, at cashier.'
                : 'Every POS and inventory feature, step by step, with real screenshots from the system itself — for administrators, pharmacists and cashiers.' }}
        </p>
        <span class="chip">PHARMACY</span>
        <span class="chip">GROCERY</span>
        <span class="chip">MOTOR SHOP</span>
        <span class="chip">{{ $tl ? 'PARTNERSHIP NA PRESYO' : 'PARTNERSHIP PRICING' }}</span>
    </div>
    <div class="foot">
        <table><tr>
            <td>
                {{ count($chapters) }} {{ $tl ? 'kabanata' : 'chapters' }} &nbsp;·&nbsp;
                {{ $sectionCount }} {{ $tl ? 'seksyon' : 'sections' }}
            </td>
            <td class="right">{{ $generatedOn }}</td>
        </tr></table>
    </div>
</div>

{{-- ============ CONTENTS ============ --}}
<div style="page-break-before: always;">
    <p class="page-kicker">{{ $tl ? 'Tungkol sa gabay na ito' : 'About this manual' }}</p>
    <h1 class="page-title">{{ $tl ? 'Nilalaman' : 'Contents' }}</h1>
    <div class="title-rule"></div>
    <p class="lede">
        {{ $tl
            ? 'Nakaayos ayon sa trabaho. Hanapin ang kabanatang tumutugma sa ginagawa mo — hindi kailangang basahin ang buong gabay.'
            : 'Organised by job. Find the chapter that matches what you do — there is no need to read the whole thing.' }}
    </p>

    @php
        // Two balanced columns, so the whole contents fits on one page.
        $rowsFor = fn (array $chapter): int => count($chapter['sections']) + 1;
        $target = array_sum(array_map($rowsFor, $chapters)) / 2;
        $left = [];
        $right = [];
        $filled = 0;
        foreach ($chapters as $chapter) {
            if ($filled < $target) {
                $left[] = $chapter;
                $filled += $rowsFor($chapter);
            } else {
                $right[] = $chapter;
            }
        }
    @endphp

    <table class="toc-columns">
        <tr>
            @foreach([$left, $right] as $column)
                @if($loop->index === 1)<td class="gutter"></td>@endif
                <td>
                    <table class="toc">
                        @foreach($column as $chapter)
                            <tr class="chapter-row">
                                <td class="num">{{ $chapter['number'] }}</td>
                                <td>{{ $chapter['title'] }}</td>
                            </tr>
                            @foreach($chapter['sections'] as $section)
                                <tr class="section-row">
                                    <td class="num">{{ $section['number'] }}</td>
                                    <td>{{ $section['title'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </table>
                </td>
            @endforeach
        </tr>
    </table>
</div>

{{-- ============ CHAPTERS ============ --}}
@foreach($chapters as $chapter)
    <div class="chapter">
        <div class="chapter-head">
            <div class="num">{{ $tl ? 'Kabanata' : 'Chapter' }} {{ $chapter['number'] }}</div>
            <h2>{{ $chapter['title'] }}</h2>
            <p class="blurb">{{ $chapter['blurb'] }}</p>
        </div>

        @foreach($chapter['sections'] as $section)
            <div class="section">
                <div class="section-head">
                    <span class="num">{{ $section['number'] }}</span><span class="title">{{ $section['title'] }}</span>
                </div>

                @foreach($section['blocks'] as $block)
                    @switch($block['type'])

                        @case('text')
                            <p>{!! $block['text'] !!}</p>
                            @break

                        @case('steps')
                            <table class="steps">
                                @foreach($block['items'] as $n => $item)
                                    <tr>
                                        <td class="marker"><div>{{ $n + 1 }}</div></td>
                                        <td>{!! $item !!}</td>
                                    </tr>
                                @endforeach
                            </table>
                            @break

                        @case('list')
                            <table class="bullets">
                                @foreach($block['items'] as $item)
                                    <tr>
                                        <td class="marker">&bull;</td>
                                        <td>{!! $item !!}</td>
                                    </tr>
                                @endforeach
                            </table>
                            @break

                        @case('note')
                            <div class="callout">
                                <p class="label">{{ $tl ? 'Tandaan' : 'Note' }}</p>
                                <p>{!! $block['text'] !!}</p>
                            </div>
                            @break

                        @case('warn')
                            <div class="callout warn">
                                <p class="label">{{ $tl ? 'Mag-ingat' : 'Careful' }}</p>
                                <p>{!! $block['text'] !!}</p>
                            </div>
                            @break

                        @case('image')
                            <figure>
                                <img src="{{ $block['path'] }}" alt="" width="{{ $block['width'] }}mm" height="{{ $block['height'] }}mm" style="width: {{ $block['width'] }}mm; height: {{ $block['height'] }}mm;">
                                <figcaption>{!! $block['text'] !!}</figcaption>
                            </figure>
                            @break

                        @case('table')
                            <table class="data">
                                <thead>
                                    <tr>
                                        @foreach($block['head'] as $cell)
                                            <th>{{ $cell }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($block['rows'] as $row)
                                        <tr>
                                            @foreach($row as $cell)
                                                <td>{!! $cell !!}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @break

                    @endswitch
                @endforeach
            </div>
        @endforeach
    </div>
@endforeach

</body>
</html>
