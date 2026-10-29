@extends('layouts.app')

@section('title', 'Toma de muestra | Hospital Dashboard')
@section('body-class', 'page-sample-collection')

@section('head')
    <script src="https://code.highcharts.com/highcharts.js"></script>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/sample-collection.css') }}?v={{ filemtime(public_path('css/sample-collection.css')) }}">
@endsection

@section('content')
<div class="sample-page" id="sampleCollectionDashboard"
     data-endpoint="{{ route('dashboards.sample-collection.data') }}"
     data-order-trace-url-template="{{ route('dashboards.sample-collection.order-trace', ['orderNumber' => '__ORDER_NUMBER__']) }}"
     data-order-trace-pdf-url-template="{{ route('dashboards.sample-collection.order-trace.pdf', ['orderNumber' => '__ORDER_NUMBER__']) }}">
    <header class="dashboard-header sample-page-header">
        <div>
            <div class="sample-title-row">
                <h1 class="dashboard-title">Toma de muestra</h1>
            </div>
            <p class="dashboard-subtitle">
                Seguimiento de pacientes atendidos, oportunidad de servicio y desempeño por puerta.
            </p>
        </div>

        <div class="sample-header-meta" aria-label="Estado del tablero">
            <small id="sampleLastUpdated">Preparando información...</small>
        </div>
    </header>

    <section class="filter-card sample-filter-card" aria-labelledby="sampleFiltersTitle">
        <div class="sample-filter-heading">
            <span class="sample-heading-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 5h16l-6 7v5l-4 2v-7z"/></svg>
            </span>
            <div>
                <h2 id="sampleFiltersTitle">Filtros del tablero</h2>
                <p>Selecciona la fecha y, opcionalmente, una puerta. La API recibe el día y el tablero aplica el filtro por puerta con la información devuelta.</p>
            </div>
        </div>

        <form id="sampleCollectionFilters" class="sample-filter-form" data-no-loading="true">
            <div class="filter-grid sample-filter-grid">
                <div class="filter-group">
                    <label for="sampleDate">Fecha de atención</label>
                    <div class="sample-input-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                        <input type="date" id="sampleDate" name="date" value="{{ $selectedDate }}" required>
                    </div>
                </div>

                <div class="filter-group">
                    <label for="sampleDoor">Puerta</label>
                    <div class="sample-input-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h16M7 20V5l10-2v17M11 12h.01"/></svg>
                        <select id="sampleDoor" name="door">
                            <option value="all" @selected(($selectedDoor ?? 'all') === 'all')>Todas las puertas</option>
                            @foreach (($initialData['doors'] ?? []) as $door)
                                <option value="{{ $door['doorID'] }}" @selected(($selectedDoor ?? 'all') === (string) $door['doorID'])>
                                    {{ $door['doorName'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="sample-filter-context">
                    <span>Meta de oportunidad</span>
                    <strong>≤ 60 minutos</strong>
                    <small>Semáforo operativo configurado para esta vista.</small>
                </div>

                <div class="filter-actions sample-filter-actions">
                    <button type="submit" class="btn btn-primary" id="sampleFilterButton">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                        <span>Consultar</span>
                    </button>
                    <button type="button" class="btn btn-secondary" id="sampleResetButton">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
                        <span>Limpiar</span>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <div class="sample-inline-alert" id="sampleInlineAlert" role="alert" @if (empty($errorMessage)) hidden @endif>
        {{ $errorMessage ?? '' }}
    </div>

    <section class="sample-kpi-grid" aria-label="Indicadores de toma de muestra">
        <article class="kpi-card sample-kpi-card sample-kpi-total">
            <span class="sample-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 8h4M19 6v4"/></svg>
            </span>
            <div class="sample-kpi-copy">
                <span class="sample-kpi-label">Total de pacientes</span>
                <strong class="sample-kpi-value" id="sampleTotalPatients">0</strong>
                <small id="sampleTotalHelper">Atendidos en el periodo</small>
            </div>
            <span class="sample-kpi-chip">Volumen</span>
        </article>

        <article class="kpi-card sample-kpi-card sample-kpi-good">
            <span class="sample-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
            </span>
            <div class="sample-kpi-copy">
                <span class="sample-kpi-label">Atendidos en ≤ 60 min</span>
                <strong class="sample-kpi-value" id="sampleWithinPercent">0%</strong>
                <small id="sampleWithinHelper">0 pacientes dentro de meta</small>
            </div>
            <span class="sample-kpi-chip">En meta</span>
        </article>

        <article class="kpi-card sample-kpi-card sample-kpi-danger">
            <span class="sample-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01"/></svg>
            </span>
            <div class="sample-kpi-copy">
                <span class="sample-kpi-label">Atendidos en &gt; 60 min</span>
                <strong class="sample-kpi-value" id="sampleOverPercent">0%</strong>
                <small id="sampleOverHelper">0 pacientes fuera de meta</small>
            </div>
            <span class="sample-kpi-chip">Seguimiento</span>
        </article>

        <article class="kpi-card sample-kpi-card sample-kpi-time">
            <span class="sample-kpi-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div class="sample-kpi-copy">
                <span class="sample-kpi-label">Tiempo promedio</span>
                <strong class="sample-kpi-value" id="sampleAverageTime">0 min</strong>
                <small id="sampleAverageHelper">Promedio general de atención</small>
            </div>
            <span class="sample-kpi-chip" id="sampleAverageStatus">Sin datos</span>
        </article>
    </section>

    <section class="sample-chart-grid">
        <article class="chart-card sample-chart-card sample-hour-chart-card">
            <header class="sample-card-header">
                <div>
                    <div class="sample-card-eyebrow">Distribución operativa</div>
                    <h2>Pacientes atendidos por hora</h2>
                    <p>Total de pacientes registrados en cada bloque horario.</p>
                </div>
                <span class="sample-card-pill">Por hora</span>
            </header>
            <div class="sample-chart" id="samplePatientsByHourChart" aria-label="Gráfica de pacientes atendidos por hora"></div>
            <footer class="sample-card-footer">
                <span><i class="sample-legend-dot sample-dot-blue"></i> Pacientes atendidos</span>
                <span id="samplePeakHour">Hora de mayor demanda: —</span>
            </footer>
        </article>

        <article class="chart-card sample-chart-card sample-door-chart-card">
            <header class="sample-card-header">
                <div>
                    <div class="sample-card-eyebrow">Capacidad por punto</div>
                    <h2>Atención de pacientes por puerta</h2>
                    <p>Comparativo del volumen procesado por cada puerta.</p>
                </div>
                <span class="sample-card-pill">Puertas</span>
            </header>
            <div class="sample-chart" id="samplePatientsByDoorChart" aria-label="Gráfica de pacientes atendidos por puerta"></div>
            <footer class="sample-card-footer sample-card-footer-stack">
                <span id="sampleTopDoor">Mayor volumen: —</span>
                <span id="sampleDoorCoverage">0 puertas con actividad</span>
            </footer>
        </article>
    </section>

    <section class="table-card sample-order-card">
        <header class="sample-card-header sample-order-card-header">
            <div>
                <div class="sample-card-eyebrow">Consulta individual</div>
                <h2>Buscar una orden</h2>
                <p>Escribe o selecciona un número de orden de la fecha y puerta consultadas.</p>
            </div>
        </header>

        <div class="sample-order-search-row">
            <form id="sampleOrderSearchForm" class="sample-order-search-form" autocomplete="off">
                <div class="sample-order-search-field">
                    <label for="sampleOrderSearchInput">Número de orden</label>
                    <div class="sample-order-search-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                        <input id="sampleOrderSearchInput" name="orderNumber" type="text" inputmode="numeric" maxlength="12" placeholder="Ej. 202609143001" aria-controls="sampleOrderSearchSuggestions" aria-autocomplete="list">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </div>
                </div>
                <div class="sample-order-suggestions" id="sampleOrderSearchSuggestions" role="listbox" hidden></div>
            </form>

            <button type="button" class="btn btn-secondary sample-order-pdf-button" id="sampleOrderPdfButton" disabled title="Primero consulta una orden">
                <svg class="sample-pdf-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path class="pdf-file" d="M6 2.5h8.4L19 7.1V21H6z"/>
                    <path class="pdf-fold" d="M14.4 2.5v4.7H19"/>
                    <path class="pdf-mark" d="M8.4 16.4c1.7-3.8 2.6-6.5 2.8-8.1.2 2.2.8 4.3 1.8 6.1-1.7-.3-3.5.1-5.3 1.1 2.2.1 4.7.8 7.4 2"/>
                </svg>
                <span>Exportar</span>
            </button>
        </div>
        <p class="sample-order-search-status" id="sampleOrderSearchStatus" role="status" aria-live="polite"></p>
        <div class="sample-order-details" id="sampleOrderDetails" aria-live="polite">
            <div class="sample-order-empty-state">Busca un número de orden para consultar los datos del paciente, sus estudios y la flebotomía.</div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    window.sampleCollectionInitialData = @json($initialData);
</script>
<script src="{{ asset('js/sample-collection.js') }}?v={{ filemtime(public_path('js/sample-collection.js')) }}"></script>
@endsection
