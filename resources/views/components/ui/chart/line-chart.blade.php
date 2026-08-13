@props([
'categories' => [],
'data' => [],
'names' => [], // Array of names for the lines (e.g. ['Income', 'Expenses'])
'series' => [], // Alternative to data/names: [['name' => 'LGU', 'data' => [...]], ...]
'colors' => ['#4f46e5', '#e11d48'], // Indigo-600, Rose-600
'darkColors' => null, // Falls back to $colors when the palette needs no dark steps
'height' => 430,
'enable_tool_tip' => false,
'dispatch_name' => 'default',
])

<div x-data="{
        categories: @js($categories),
        seriesData: @js($data),
        names: @js($names),
        explicitSeries: @js($series),
        lightColors: @js($colors),
        darkColors: @js($darkColors ?? $colors),
        chart: null,

        colorSchemeQuery: null,
        onColorSchemeChange: null,

        init() {
            this.renderChart()

            // Automatically react to system-level dark mode changes
            this.colorSchemeQuery = window.matchMedia('(prefers-color-scheme: dark)')
            this.onColorSchemeChange = () => this.updateTheme()
            this.colorSchemeQuery.addEventListener('change', this.onColorSchemeChange)
        },

        /**
         * Alpine calls this when the element is removed -- including on a
         * wire:navigate page swap. Without it, every visit would leak an
         * ApexCharts instance and a matchMedia listener.
         */
        destroy() {
            this.colorSchemeQuery?.removeEventListener('change', this.onColorSchemeChange)
            this.chart?.destroy()
            this.chart = null
        },

        // Explicit series win; otherwise pair the parallel data/names arrays.
        get resolvedSeries() {
            if (Array.isArray(this.explicitSeries) && this.explicitSeries.length) {
                return this.explicitSeries.map((s, index) => ({
                    name: s.name ?? `Series ${index + 1}`,
                    data: s.data ?? [],
                }))
            }

            return this.seriesData.map((dataSet, index) => ({
                name: this.names[index] ?? `Series ${index + 1}`,
                data: dataSet,
            }))
        },

        get paletteForTheme() {
            return $theme.isResolvedToDark ? this.darkColors : this.lightColors
        },

        renderChart() {
            this.chart = new ApexCharts(this.$refs.chart, this.options)
            this.chart.render()
        },

        updateTheme() {
            const isDark = $theme.isResolvedToDark
            const textColor = isDark ? '#E5E7EB' : '#374151' // gray-200 vs gray-700

            this.chart?.updateOptions({
                colors: this.paletteForTheme,
                xaxis: {
                    type: 'category',
                    categories: this.categories,
                    labels: { style: { colors: textColor, fontSize: '13px', } },
                    axisBorder: { color: textColor },
                    axisTicks: { color: textColor },
                },
                yaxis: {
                    labels: { style: { colors: textColor } },
                },
                legend: {
                    labels: { colors: textColor },
                },
                grid: {
                    borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                },
            })
        },

        /**
         * Accepts either the flat payload Livewire dispatches
         * ({ categories, data, names } or { categories, series }) or the
         * nested { data: { labels, datasets } } shape.
         */
        updateChartData(event) {
            const detail = event?.detail
            if (!detail) return

            const nested = detail.data && !Array.isArray(detail.data) ? detail.data : null
            const labels = detail.categories ?? nested?.labels
            if (labels) {
                this.categories = labels
            }

            if (Array.isArray(detail.series)) {
                this.explicitSeries = detail.series
            } else if (Array.isArray(nested?.datasets)) {
                this.explicitSeries = nested.datasets.map(ds => ({
                    name: ds.label ?? 'Data',
                    data: ds.data ?? [],
                }))
            } else if (Array.isArray(detail.data)) {
                this.explicitSeries = []
                this.seriesData = detail.data
                if (Array.isArray(detail.names)) {
                    this.names = detail.names
                }
            }

            // Series count can change between renders (a partner with no sales
            // in the new range drops out), so options and series both refresh.
            this.chart?.updateOptions({
                colors: this.paletteForTheme,
                xaxis: { categories: this.categories },
            })
            this.chart?.updateSeries(this.resolvedSeries, true)
        },

        get options() {
            const isDark = $theme.isResolvedToDark // document.documentElement.classList.contains('dark')
            const textColor = isDark ? '#E5E7EB' : '#374151' // gray-200 vs gray-700

            return {
                series: this.resolvedSeries,
                chart: {
                    type: 'line',
                    height: {{ $height }},
                    toolbar: { show: {{ $enable_tool_tip ? 'true' : 'false' }} },
                    zoom: { enabled: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 600,
                    },
                },
                stroke: {
                    curve: 'smooth',
                    width: 3,
                },
                colors: this.paletteForTheme,
                dataLabels: { enabled: false },
                markers: {
                    size: 4,
                    hover: { sizeOffset: 3 },
                },
                grid: {
                    borderColor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                    row: { colors: ['transparent', 'transparent'], opacity: 0.5 },
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    // Crosshair-style shared tooltip so every line's value at a
                    // point is readable in one hover.
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (val) {
                            return val === null ? '-' : val.toLocaleString()
                        },
                    },
                },
                xaxis: {
                    type: 'category',
                    categories: this.categories,
                    // Keep the axis readable when a wide range produces many buckets.
                    tickAmount: 12,
                    labels: {
                        rotate: -45,
                        rotateAlways: false,
                        hideOverlappingLabels: true,
                        trim: true,
                        style: {
                            colors: textColor,
                            fontSize: '13px',
                        },
                    },
                    axisBorder: { color: textColor },
                    axisTicks: { color: textColor },
                    crosshairs: { show: true },
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: textColor,
                            fontSize: '13px',
                        },
                    },
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    labels: {
                        colors: textColor,
                    },
                },
            }
        },
    }" x-on:{{ $dispatch_name }}.window="updateChartData($event)" x-effect="updateTheme()" class="w-full">
    <div x-ref='chart' class="w-full"></div>
</div>
