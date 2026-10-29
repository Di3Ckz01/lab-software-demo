@extends('layouts.app')

@section('title', 'Órdenes atendidas por mes | Hospital Dashboard')
@section('body-class', 'page-patients')

@section('head')
    <script src="https://code.highcharts.com/highcharts.js"></script>
@endsection

@section('content')
@php
    $routinePercent = $total > 0 ? round(($totalRutine / $total) * 100, 1) : 0;
    $urgentPercent = $total > 0 ? round(($totalUrgencias / $total) * 100, 1) : 0;

    $monthsWithValues = collect($monthlyRows)
        ->filter(fn ($row) => (int) ($row['selected'] ?? 0) > 0)
        ->values();

    $minMonth = $monthsWithValues->sortBy('selected')->first();
    $activeMonths = $monthsWithValues->count();

    $dominantService = collect($serviceCounts)->sortDesc()->keys()->first();
    $dominantServiceCount = $dominantService ? (int) ($serviceCounts[$dominantService] ?? 0) : 0;
    $dominantServicePercent = $total > 0
        ? round(($dominantServiceCount / $total) * 100, 1)
        : 0;

    $serviceColorMap = [
        'Servicio C' => '#6d5ce8',
        'Rutina' => '#2478fa',
        'Urgencias' => '#f97316',
    ];
@endphp

<div class="dashboard-header patients-page-header">
    <div>
        <h1 class="dashboard-title">Órdenes atendidas por mes</h1>
        <p class="dashboard-subtitle">
            Seguimiento mensual de atención hospitalaria y comportamiento anual de servicios.
        </p>
    </div>
</div>

@if (!empty($error))
    <div class="alert-error">
        {{ $error }}
    </div>
@endif

<section class="filter-card patients-filter-card">
    <div class="patients-section-heading">
        <span class="patients-heading-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 5h16l-6 7v5l-4 2v-7z"/></svg>
        </span>
        <div>
            <h2>Filtros del tablero</h2>
            <p>Selecciona los criterios para consultar la información anual.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('dashboards.pacientes-mes') }}">
        <div class="filter-grid patients-filter-grid">
            <div class="filter-group">
                <label for="year">Año</label>
                <select name="year" id="year">
                    @foreach ($availableYears as $yearOption)
                        <option value="{{ $yearOption }}" @selected((int) $year === (int) $yearOption)>
                            {{ $yearOption }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label for="service">Servicio</label>
                <select name="service" id="service">
                    <option value="">Todos</option>
                    @foreach ($availableServices as $serviceOption)
                        <option value="{{ $serviceOption['value'] }}" @selected($selectedService === $serviceOption['value'])>
                            {{ $serviceOption['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group patients-disabled-filter">
                <label for="area-preview">Área</label>
                <select id="area-preview" disabled title="La API actual todavía no entrega el desglose por área">
                    <option>Todas</option>
                </select>
            </div>

            <div class="filter-actions patients-filter-actions">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                    <span>Filtrar</span>
                </button>

                <a href="{{ route('dashboards.pacientes-mes') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
                    <span>Limpiar</span>
                </a>
            </div>
        </div>
    </form>
</section>

<section class="kpi-grid patients-kpi-grid" aria-label="Indicadores anuales">
    <article class="kpi-card patients-kpi-card patients-kpi-blue">
        <span class="patients-kpi-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M7 4h10v16H7zM9 2h6v4H9zM10 10h4M10 14h4"/></svg>
        </span>
        <div class="patients-kpi-copy">
            <div class="kpi-label">Total anual</div>
            <div class="kpi-value">{{ number_format($total) }}</div>
            <div class="kpi-helper">Órdenes atendidas</div>
        </div>
    </article>

    <article class="kpi-card patients-kpi-card patients-kpi-green">
        <span class="patients-kpi-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 18 9 13l3 3 7-8M18 8h1v5"/></svg>
        </span>
        <div class="patients-kpi-copy">
            <div class="kpi-label">Promedio mensual</div>
            <div class="kpi-value">{{ number_format($promedio) }}</div>
            <div class="kpi-helper">Órdenes por mes</div>
        </div>
    </article>

    <article class="kpi-card patients-kpi-card patients-kpi-purple">
        <span class="patients-kpi-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="5"/><path d="m8.5 12-2 9 5.5-3 5.5 3-2-9M9.5 8l1.5 1.5L14.5 6"/></svg>
        </span>
        <div class="patients-kpi-copy">
            <div class="kpi-label">Mes con mayor atención</div>
            <div class="kpi-value patients-kpi-month">{{ $maxMonthName }}</div>
            <div class="kpi-helper">{{ number_format($maxMonthValue) }} órdenes</div>
        </div>
    </article>

    <article class="kpi-card patients-kpi-card patients-kpi-routine">
        <span class="patients-kpi-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M9 3h6m-5 0v5l-5 9a2 2 0 0 0 1.7 3h10.6A2 2 0 0 0 19 17l-5-9V3M8 14h8"/></svg>
        </span>
        <div class="patients-kpi-copy">
            <div class="kpi-label">Rutina</div>
            <div class="kpi-value">{{ number_format($totalRutine) }}</div>
            <div class="kpi-helper">{{ number_format($routinePercent, 1) }}% del total</div>
        </div>
    </article>

    <article class="kpi-card patients-kpi-card patients-kpi-urgent">
        <span class="patients-kpi-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M12 3v3M5.6 5.6l2.1 2.1M3 12h3m12 0h3M16.3 7.7l2.1-2.1M7 17h10M9 21h6M8 13a4 4 0 1 1 8 0v4H8z"/></svg>
        </span>
        <div class="patients-kpi-copy">
            <div class="kpi-label">Urgencias</div>
            <div class="kpi-value">{{ number_format($totalUrgencias) }}</div>
            <div class="kpi-helper">{{ number_format($urgentPercent, 1) }}% del total</div>
        </div>
    </article>
</section>

<section class="patients-dashboard-grid">
    <article class="chart-card patients-main-chart-card">
        <div class="patients-card-header">
            <div>
                <h2>Órdenes atendidas por mes</h2>
                <p>Comparativo mensual de órdenes procesadas por tipo de servicio.</p>
            </div>
            <span class="patients-period-pill">Mensual</span>
        </div>

        <div id="pacientesChart" aria-label="Gráfica mensual de órdenes"></div>

        <div class="patients-chart-note">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
            <span>El total mensual incluye órdenes de rutina y urgencias.</span>
        </div>
    </article>

    <div class="patients-side-column">
        <article class="chart-card patients-distribution-card">
            <div class="patients-card-header patients-card-header-compact">
                <div>
                    <h2>Distribución por servicio</h2>
                    <p>Participación anual por clasificación.</p>
                </div>
            </div>

            <div class="patients-distribution-content">
                <div id="patientsServiceChart" aria-label="Distribución anual por servicio"></div>

                <div class="patients-service-list">
                    @forelse ($serviceCounts as $service => $count)
                        @php
                            $servicePercent = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                            $serviceColor = $serviceColorMap[$service] ?? '#94a3b8';
                        @endphp
                        <div class="patients-service-row">
                            <span class="patients-service-dot" style="background: {{ $serviceColor }}"></span>
                            <span class="patients-service-name">{{ $service }}</span>
                            <strong>{{ number_format($count) }}</strong>
                            <small>{{ number_format($servicePercent, 1) }}%</small>
                        </div>
                    @empty
                        <div class="global-empty-inline">Sin información disponible.</div>
                    @endforelse
                </div>
            </div>
        </article>

        <article class="chart-card patients-annual-summary">
            <div class="patients-card-header patients-card-header-compact">
                <div>
                    <h2>Resumen anual</h2>
                    <p>Aspectos destacados del periodo seleccionado.</p>
                </div>
            </div>

            <div class="patients-summary-list">
                <div class="patients-summary-row">
                    <span class="patients-summary-icon summary-up">
                        <svg viewBox="0 0 24 24"><path d="M5 15l5-5 3 3 6-7M15 6h4v4"/></svg>
                    </span>
                    <span>Mejor mes</span>
                    <strong>{{ $maxMonthName }}</strong>
                    <small>{{ number_format($maxMonthValue) }} órdenes</small>
                </div>

                <div class="patients-summary-row">
                    <span class="patients-summary-icon summary-down">
                        <svg viewBox="0 0 24 24"><path d="M5 9l5 5 3-3 6 7M15 18h4v-4"/></svg>
                    </span>
                    <span>Menor mes con actividad</span>
                    <strong>{{ $minMonth['month'] ?? 'Sin datos' }}</strong>
                    <small>{{ number_format((int) ($minMonth['selected'] ?? 0)) }} órdenes</small>
                </div>

                <div class="patients-summary-row">
                    <span class="patients-summary-icon summary-main">
                        <svg viewBox="0 0 24 24"><path d="M4 19V9m6 10V4m6 15v-7m4 7H2"/></svg>
                    </span>
                    <span>Servicio principal</span>
                    <strong>{{ $dominantService ?? 'Sin datos' }}</strong>
                    <small>{{ number_format($dominantServicePercent, 1) }}% del total</small>
                </div>

                <div class="patients-summary-row">
                    <span class="patients-summary-icon summary-calendar">
                        <svg viewBox="0 0 24 24"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                    </span>
                    <span>Meses con información</span>
                    <strong>{{ $activeMonths }} de 12</strong>
                    <small>Año {{ $year }}</small>
                </div>
            </div>
        </article>
    </div>
</section>

<div class="patients-last-update">
    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5l3 2"/></svg>
    <span>Información correspondiente al año {{ $year }} · Servicio: {{ $selectedServiceLabel }}</span>
</div>
@endsection

@section('scripts')
<script>
    const patientCategories = @json($categories);
    const patientChartSeries = @json($series);
    const patientServiceCounts = @json($serviceCounts);
    const patientTotal = @json($total);
    const patientSelectedServiceLabel = @json($selectedServiceLabel);

    const patientSeriesColors = {
        'Servicio C': '#6d5ce8',
        'Rutina': '#2478fa',
        'Urgencias': '#f97316',
        'Total órdenes': '#0b2f6b'
    };

    document.addEventListener('DOMContentLoaded', function () {
        const preparedSeries = patientChartSeries
            .filter(function (series) {
                // En la vista general conservamos el patrón visual del mockup:
                // Rutina, Urgencias y la línea de total.
                return patientSelectedServiceLabel !== 'Todos' || series.name !== 'Servicio C';
            })
            .map(function (series) {
                const prepared = {
                    ...series,
                    color: patientSeriesColors[series.name] || '#2478fa'
                };

                // El backend agrega la propiedad stack; la retiramos para que
                // las columnas aparezcan agrupadas y no apiladas.
                delete prepared.stack;

                return prepared;
            });

        Highcharts.chart('pacientesChart', {
            chart: {
                zoomType: 'xy',
                spacing: [18, 14, 8, 6]
            },
            title: { text: null },
            subtitle: { text: null },
            xAxis: {
                categories: patientCategories,
                crosshair: true,
                tickLength: 0,
                title: { text: null }
            },
            yAxis: {
                min: 0,
                title: { text: 'Órdenes' },
                labels: {
                    formatter: function () {
                        return Highcharts.numberFormat(this.value, 0);
                    }
                }
            },
            tooltip: {
                shared: true,
                valueSuffix: ' órdenes',
                valueDecimals: 0
            },
            legend: {
                align: 'center',
                verticalAlign: 'top',
                layout: 'horizontal',
                symbolRadius: 5,
                itemDistance: 22,
                margin: 18
            },
            plotOptions: {
                column: {
                    stacking: null,
                    grouping: true,
                    borderWidth: 0,
                    borderRadius: 3,
                    maxPointWidth: 28,
                    groupPadding: 0.19,
                    pointPadding: 0.08
                },
                spline: {
                    lineWidth: 2.4,
                    marker: {
                        enabled: true,
                        radius: 3.5,
                        lineWidth: 2,
                        lineColor: '#ffffff',
                        fillColor: '#0b2f6b'
                    },
                    dataLabels: {
                        enabled: patientSelectedServiceLabel === 'Todos',
                        allowOverlap: false,
                        crop: false,
                        overflow: 'allow',
                        y: -8,
                        formatter: function () {
                            return this.y > 0 ? Highcharts.numberFormat(this.y, 0) : null;
                        },
                        style: {
                            color: '#1c3154',
                            fontSize: '9px',
                            fontWeight: '700',
                            textOutline: '2px #ffffff'
                        }
                    }
                },
                series: {
                    animation: { duration: 450 },
                    states: {
                        inactive: { opacity: 0.35 }
                    }
                }
            },
            series: preparedSeries
        });

        const serviceData = Object.entries(patientServiceCounts).map(function ([name, value]) {
            return {
                name: name,
                y: Number(value),
                color: patientSeriesColors[name] || '#94a3b8'
            };
        });

        Highcharts.chart('patientsServiceChart', {
            chart: {
                type: 'pie',
                height: 220,
                spacing: [0, 0, 0, 0]
            },
            title: {
                text: `<span class="patients-donut-total">${Highcharts.numberFormat(patientTotal, 0)}</span><br><span class="patients-donut-label">Total anual</span>`,
                useHTML: true,
                align: 'center',
                verticalAlign: 'middle',
                floating: true,
                y: 6
            },
            tooltip: {
                pointFormat: '<b>{point.y:,.0f}</b> órdenes<br>{point.percentage:.1f}% del total'
            },
            legend: { enabled: false },
            plotOptions: {
                pie: {
                    innerSize: '67%',
                    size: '94%',
                    borderWidth: 0,
                    dataLabels: { enabled: false },
                    states: {
                        hover: { brightness: 0.04 }
                    }
                }
            },
            series: [{
                name: 'Órdenes',
                data: serviceData
            }]
        });
    });
</script>
@endsection
