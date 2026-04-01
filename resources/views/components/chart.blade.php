@props([
    'type' => 'bar',
    'data' => ['labels' => [], 'data' => []],
    'label' => 'Dataset',
    'height' => '280px',
    'lazy' => false,
])

@php
    $chartId = 'chart_' . str_replace('-', '', (string) \Illuminate\Support\Str::uuid());
    $labels = $data['labels'] ?? [];
    $values = $data['data'] ?? [];
    $numericValues = collect($values)->map(fn($value) => is_numeric($value) ? (float) $value : 0.0)->values();
    $hasNonZeroValue = $numericValues->contains(fn(float $value): bool => $value > 0.0);
    $isCircularChart = in_array($type, ['pie', 'doughnut'], true);
    $showNoDataOverlay = !$hasNonZeroValue;
    $shouldRenderChart = $isCircularChart || $hasNonZeroValue;

    $resolvedLabels = $isCircularChart && !$hasNonZeroValue ? ['No data'] : $labels;
    $resolvedValues = $isCircularChart && !$hasNonZeroValue ? [1] : $numericValues->all();
@endphp

<div class="relative" style="height: {{ $height }};">
    <canvas id="{{ $chartId }}"></canvas>
    @if ($showNoDataOverlay)
        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
            <span
                class="rounded-md border border-slate-200 bg-slate-50/90 px-2.5 py-1 text-xs font-medium text-slate-500 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-400">
                No data in selected range
            </span>
        </div>
    @endif
</div>

<script>
    (function() {
        const lazy = @js((bool) $lazy);
        const shouldRenderChart = @js((bool) $shouldRenderChart);
        let chartInstance = null;
        let resizeObserver = null;

        const getCanvas = () => document.getElementById(@js($chartId));

        const getContainer = () => {
            const canvas = getCanvas();
            return canvas ? canvas.parentElement : null;
        };

        const canRender = () => {
            const container = getContainer();
            if (!container) {
                return false;
            }

            // Avoid initializing while the chart is hidden/collapsed.
            return container.offsetParent !== null && container.clientWidth > 120 && container.clientHeight > 0;
        };

        const scheduleResize = () => {
            if (!chartInstance) {
                return;
            }

            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(() => {
                    chartInstance.resize();
                });
            });
        };

        const isChartLibraryReady = () => typeof window.Chart === 'function';

        const mountChart = function() {
            if (!shouldRenderChart) {
                return true;
            }

            if (!isChartLibraryReady()) {
                return false;
            }

            const canvas = getCanvas();
            if (!canvas || canvas.dataset.chartMounted === '1') {
                return false;
            }

            if (!canRender()) {
                return false;
            }

            canvas.dataset.chartMounted = '1';
            const ctx = canvas.getContext('2d');

            chartInstance = new window.Chart(ctx, {
                type: @js($type),
                data: {
                    labels: @js(array_values($resolvedLabels)),
                    datasets: [{
                        label: @js($label),
                        data: @js(array_values($resolvedValues)),
                        borderColor: '#0f766e',
                        backgroundColor: [
                            '#0f766ecc',
                            '#0284c7cc',
                            '#16a34acc',
                            '#f59e0bcc',
                            '#dc2626cc',
                            '#7c3aedcc',
                        ],
                        pointBackgroundColor: '#0f766e',
                        pointRadius: 2,
                        borderWidth: 2,
                        tension: 0.35,
                        fill: @js($type === 'line'),
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                                padding: 10,
                            },
                        },
                        tooltip: {
                            enabled: true,
                        },
                    },
                    scales: @js(
    $type === 'pie'
        ? (object) []
        : [
            'y' => [
                'beginAtZero' => true,
                'suggestedMax' => $hasNonZeroValue ? null : 1,
                'ticks' => [
                    'precision' => 0,
                ],
            ],
        ],
),
                },
            });

            const container = getContainer();
            if (container && 'ResizeObserver' in window) {
                resizeObserver = new ResizeObserver(() => scheduleResize());
                resizeObserver.observe(container);
            }

            scheduleResize();
            return true;
        };

        const mountWhenReady = function(maxAttempts = 60) {
            let attempts = 0;

            const tryMount = () => {
                if (chartInstance) {
                    return;
                }

                // Vite module scripts can populate window.Chart slightly later than this inline script.
                if (!isChartLibraryReady()) {
                    attempts += 1;
                    if (attempts < maxAttempts) {
                        window.setTimeout(tryMount, 100);
                    }
                    return;
                }

                if (!canRender()) {
                    attempts += 1;
                    if (attempts < maxAttempts) {
                        window.setTimeout(tryMount, 80);
                    }
                    return;
                }

                const mounted = mountChart();
                if (!mounted) {
                    attempts += 1;
                    if (attempts < maxAttempts) {
                        window.setTimeout(tryMount, 80);
                    }
                }
            };

            tryMount();
        };

        const init = function() {
            const canvas = getCanvas();
            if (!canvas) {
                return;
            }

            if (!lazy || !('IntersectionObserver' in window)) {
                mountWhenReady();
                return;
            }

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        mountWhenReady();
                        observer.disconnect();
                    }
                });
            }, {
                rootMargin: '100px 0px',
                threshold: 0.1,
            });

            observer.observe(canvas);
        };

        window.addEventListener('dashboard:section-visibility-changed', function(event) {
            if (!event.detail || !event.detail.open) {
                return;
            }

            mountWhenReady(10);
            scheduleResize();
        });

        window.addEventListener('resize', scheduleResize);

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init, {
                once: true
            });
        } else {
            init();
        }
    })();
</script>
