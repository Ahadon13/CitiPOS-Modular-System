@props([
'categories' => [],
'data' => [],
'names' => [], // Array of names for the lines (e.g. ['Income', 'Expenses'])
'height' => 430,
'enable_tool_tip' => false,
'dispatch_name' => 'default',
])

<div x-data="{
        categories: @js($categories),
        seriesData: @js($data),
        names: @js($names),
        chart: null,

        init() {
            this.renderChart()

            // Automatically react to system-level dark mode changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                this.updateTheme()
            })
        },

        renderChart() {
            this.chart = new ApexCharts(this.$refs.chart, this.options)
            this.chart.render()
        },

        updateTheme() {
            const isDark = $theme.isResolvedToDark
            const textColor = isDark ? '#E5E7EB' : '#374151' // gray-200 vs gray-700

            this.chart?.updateOptions({
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

        updateChartData(event) {
            const newData = event?.detail?.data
            if (!newData) return

            // Expect structure: { labels: [...], datasets: [{ label: '', data: [...] }, ...] }
            if (newData.labels) {
                this.categories = newData.labels
                this.chart.updateOptions({ xaxis: { categories: this.categories } })
            }

            if (newData.datasets && Array.isArray(newData.datasets)) {
                const series = newData.datasets.map(ds => ({
                    name: ds.label ?? 'Data',
                    data: ds.data ?? [],
                }))
                this.chart.updateSeries(series)
            }
        },

        get options() {
            const isDark = $theme.isResolvedToDark // document.documentElement.classList.contains('dark')
            const textColor = isDark ? '#E5E7EB' : '#374151' // gray-200 vs gray-700

            return {
                series: this.seriesData.map((dataSet, index) => ({
                    name: this.names[index] ?? `Series ${index + 1}`,
                    data: dataSet,
                })),
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
                colors: ['#4f46e5', '#e11d48'], // Indigo-600, Rose-600
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
                    y: {
                        formatter: function (val) {
                            return val.toLocaleString()
                        },
                    },
                },
                xaxis: {
                    type: 'category',
                    categories: this.categories,
                    labels: {
                        style: {
                            colors: textColor,
                            fontSize: '13px',
                        },
                    },
                    axisBorder: { color: textColor },
                    axisTicks: { color: textColor },
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

