@props([
'labels' => [],
'series' => [],
'colors' => ['#10b981', '#f59e0b', '#a1a1a1'],
'width' => 500,
'dispatch_name' => 'default',
'enable_tool_tip' => false,
])

<div x-data="{
        labels: @js($labels),
        seriesData: @js($series),
        width: @js($width),
        chart: null,

        init() {
            this.renderChart();

            // Watch for system or app dark mode toggle
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                this.updateTheme()
            });
        },

        renderChart() {
            this.chart = new ApexCharts(this.$refs.chart, this.options);
            this.chart.render();
        },

        updateChartData(event) {
            const newData = event?.detail?.data;
            if (!newData) return;

            if (newData.labels) {
                this.labels = newData.labels;
                this.chart.updateOptions({ labels: this.labels });
            }

            if (newData.series) {
                this.seriesData = newData.series;
                this.chart.updateSeries(this.seriesData);
            }
        },

        updateTheme() {
            const isDark = $theme.isResolvedToDark;
            const textColor = isDark ? '#E5E7EB' : '#374151'; // gray-200 vs gray-700
            const borderColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.9)';

            this.chart?.updateOptions({
                legend: { labels: { colors: textColor } },
                dataLabels: { style: { colors: ['#E5E7EB'] } },
                grid: { borderColor },
                tooltip: { theme: isDark ? 'dark' : 'light' },
            });
        },

        get options() {
            const isDark = $theme.isResolvedToDark;
            const textColor = isDark ? '#E5E7EB' : '#374151';

            return {
                series: this.seriesData,
                chart: {
                    width: this.width,
                    type: 'pie',
                    toolbar: {
                        show: @js($enable_tool_tip),
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 600,
                    },
                },
                labels: this.labels,
                colors: @js($colors),
                dataLabels: {
                    enabled: true,
                    formatter: (val, opts) => {
                        const total = opts.w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                        const percent = ((opts.w.globals.series[opts.seriesIndex] / total) * 100).toFixed(1);
                        const count = opts.w.globals.series[opts.seriesIndex];
                        return `${count}`;
                    },
                    style: {
                        colors: ['#E5E7EB'],
                        fontSize: '20px',
                    },
                },
                legend: {
                    position: 'right',
                    labels: { colors: textColor },
                    markers: { radius: 12 },
                    style: {
                        fontSize: '20px',
                    },
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: {
                        formatter: function (val) {
                            return val.toLocaleString();
                        },
                    },
                },
                responsive: [
                    {
                        breakpoint: 768,
                        options: {
                            chart: { width: '100%' },
                            legend: { position: 'bottom' },
                        },
                    },
                    {
                        breakpoint: 1360,
                        options: {
                            chart: { width: '100%' },
                            legend: { position: 'bottom' },
                        },
                    }
                ],
            };
        },
    }" x-on:{{ $dispatch_name }}.window="updateChartData($event)" x-effect="updateTheme()" class="w-full flex justify-center">
    <div x-ref="chart" class="w-full max-w-[500px]"></div>
</div>

