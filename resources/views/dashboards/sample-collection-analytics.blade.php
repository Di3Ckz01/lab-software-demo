@extends('layouts.app')

@section('title', 'Analítica de Toma de muestra | V-Care')
@section('body-class', 'page-sample-analytics')

@section('head')
    <script src="https://code.highcharts.com/highcharts.js"></script>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/sample-collection-analytics.css') }}?v={{ filemtime(public_path('css/sample-collection-analytics.css')) }}">
@endsection

@section('content')
<div class="analytics-page"
     id="sampleAnalyticsDashboard"
     data-data-url="{{ route('dashboards.sample-collection-analytics.data') }}"
     data-login-url="{{ route('login') }}">

    <header class="dashboard-header analytics-page-header">
        <div>
            <h1 class="dashboard-title">Analítica de Toma de muestra</h1>
            <p class="dashboard-subtitle">
                Resumen ejecutivo de volumen, tiempos y comportamiento operativo por rango de fechas.
            </p>
        </div>
        <div class="analytics-header-meta">
            <span>Última consulta</span>
            <strong id="analyticsLastUpdated">Sin consultar</strong>
        </div>
    </header>

    <section class="analytics-filter-card" aria-labelledby="analyticsFiltersTitle">
        <div class="analytics-section-heading">
            <span class="analytics-heading-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 5h16l-6 7v5l-4 2v-7z"/></svg>
            </span>
            <div>
                <h2 id="analyticsFiltersTitle">Filtros de consulta</h2>
                <p>Selecciona el rango de fechas para generar el resumen ejecutivo.</p>
            </div>
        </div>

        <form id="analyticsFiltersForm" class="analytics-filter-grid" data-no-loading="true" autocomplete="off">
            <div class="analytics-filter-group">
                <label for="analyticsFrom">Fecha inicial</label>
                <div class="analytics-input-shell">
                    <input type="date" id="analyticsFrom" value="" autocomplete="off" required>
                </div>
            </div>

            <div class="analytics-filter-group">
                <label for="analyticsTo">Fecha final</label>
                <div class="analytics-input-shell">
                    <input type="date" id="analyticsTo" value="" autocomplete="off" required>
                </div>
            </div>

            <div class="analytics-filter-note">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                <span>El rango puede ser de hasta 366 días. La consulta mensual puede tardar alrededor de 10 segundos.</span>
            </div>

            <div class="analytics-filter-actions">
                <button type="submit" class="btn btn-primary" id="analyticsConsultButton">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                    <span>Consultar</span>
                </button>
                <button type="button" class="btn btn-secondary" id="analyticsClearButton">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
                    <span>Limpiar</span>
                </button>
            </div>
        </form>
    </section>

    <div class="analytics-alert analytics-alert-error" id="analyticsError" role="alert" hidden></div>
    <div class="analytics-alert analytics-alert-warning" id="analyticsPartialWarning" role="status" hidden></div>

    <section class="analytics-kpi-grid" aria-label="Indicadores ejecutivos">
        <article class="analytics-kpi analytics-kpi-blue">
            <span class="analytics-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M7 3h7l5 5v13H7zM14 3v6h6M10 13h6M10 17h6"/></svg>
            </span>
            <div>
                <small>Citas totales</small>
                <strong id="analyticsAppointmentsTotal">—</strong>
                <span>Periodo consultado</span>
            </div>
        </article>

        <article class="analytics-kpi analytics-kpi-green">
            <span class="analytics-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 8h4M19 6v4"/></svg>
            </span>
            <div>
                <small>Pacientes atendidos</small>
                <strong id="analyticsAttendedPatients">—</strong>
                <span>Atendidos en el periodo</span>
            </div>
        </article>

        <article class="analytics-kpi analytics-kpi-purple">
            <span class="analytics-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div>
                <small>Tiempo espera promedio</small>
                <strong id="analyticsWaitAverage">—</strong>
                <span>Desde activación hasta inicio</span>
            </div>
        </article>

        <article class="analytics-kpi analytics-kpi-orange">
            <span class="analytics-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 3s6 6.2 6 11a6 6 0 1 1-12 0c0-4.8 6-11 6-11Z"/><path d="M9 15h6M12 12v6"/></svg>
            </span>
            <div>
                <small>Tiempo promedio flebotomía</small>
                <strong id="analyticsPhlebotomyAverage">—</strong>
                <span>Duración promedio de flebotomía</span>
            </div>
        </article>

        <article class="analytics-kpi analytics-kpi-red">
            <span class="analytics-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/></svg>
            </span>
            <div>
                <small>Citas no asistidas</small>
                <strong id="analyticsNotArrivedTotal">—</strong>
                <span>Pendientes cuya hora programada ya pasó</span>
            </div>
        </article>
    </section>

    <section class="analytics-card analytics-chart-card analytics-full-card">
        <header class="analytics-card-header">
            <div>
                <span class="analytics-eyebrow">Citas</span>
                <h2>Estados de las citas por mes</h2>
                <p>Comparativo mensual de citas pendientes y atendidas dentro del periodo seleccionado.</p>
            </div>
        </header>
        <div class="analytics-chart analytics-states-chart" id="analyticsStatesChart"></div>
    </section>

    <section class="analytics-card analytics-chart-card analytics-full-card">
        <header class="analytics-card-header analytics-age-header">
            <div>
                <span class="analytics-eyebrow">Pacientes</span>
                <h2>Pacientes por grupo de edad</h2>
                <p>Comportamiento de pacientes atendidos por rango etario.</p>
            </div>

            <div class="analytics-age-average" aria-label="Promedio de edad">
                <span class="analytics-age-average-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4.5 20c.9-4 3.4-6 7.5-6s6.6 2 7.5 6"/>
                    </svg>
                </span>
                <div>
                    <small>Promedio de edad</small>
                    <strong id="analyticsAverageAge">—</strong>
                </div>
            </div>
        </header>
        <div class="analytics-chart analytics-chart-wide" id="analyticsAgeChart"></div>
    </section>

    <section class="analytics-card analytics-full-card analytics-phlebotomist-card">
        <header class="analytics-card-header">
            <div>
                <span class="analytics-eyebrow">Desempeño</span>
                <h2>Atenciones por flebotomista</h2>
                <p>Comparativo de flebotomistas con mayor número de atenciones registradas.</p>
            </div>
            <span class="analytics-card-pill">Top 10</span>
        </header>
        <div class="analytics-phlebotomist-list" id="analyticsPhlebotomistChart" aria-live="polite"></div>
    </section>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/sample-collection-analytics.js') }}?v={{ filemtime(public_path('js/sample-collection-analytics.js')) }}"></script>
@endsection
