@extends('layouts.app')

@section('title', 'Venopunciones | Hospital Dashboard')
@section('body-class', 'page-venopunctures')

@section('head')
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/heatmap.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
@endsection

@section('styles')
<style>
    .venopuncture-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin: 0 0 18px;
        flex-wrap: wrap;
    }

    .venopuncture-title {
        margin: 0;
        color: var(--inst-text, #102142);
        font-size: clamp(28px, 2.4vw, 38px);
        line-height: 1.02;
        font-weight: 950;
        letter-spacing: -.035em;
    }

    .venopuncture-subtitle {
        margin: 8px 0 0;
        color: var(--inst-muted, #65738d);
        font-size: 13px;
        font-weight: 700;
        max-width: 780px;
    }

    .venopuncture-period-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 46px;
        max-width: 100%;
        padding: 10px 16px;
        border-radius: 16px;
        color: var(--inst-navy-800, #0a3476);
        background: #ffffff;
        border: 1px solid var(--inst-line, #e3e9f2);
        box-shadow: var(--inst-shadow, 0 8px 24px rgba(19,49,91,.07));
        font-size: 12px;
        font-weight: 900;
    }

    .venopuncture-period-pill svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        flex: 0 0 auto;
    }

    .venopuncture-card {
        background: rgba(255,255,255,.98);
        border: 1px solid var(--inst-line, #e3e9f2);
        border-radius: 18px;
        box-shadow: var(--inst-shadow, 0 8px 24px rgba(19,49,91,.07));
    }

    .venopuncture-filter-card {
        padding: 18px 20px;
        margin-bottom: 16px;
    }

    .venopuncture-filter-head {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: flex-start;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .venopuncture-filter-head h2 {
        margin: 0;
        color: #0f172a;
        font-size: 19px;
        font-weight: 950;
    }

    .venopuncture-filter-head p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .venopuncture-preview-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 12px;
        border-radius: 14px;
        color: #1e40af;
        background: #f8fbff;
        border: 1px solid #d8e4f6;
        font-size: 11px;
        font-weight: 850;
        white-space: nowrap;
    }

    .venopuncture-preview-badge::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: #2563eb;
        box-shadow: 0 0 0 4px rgba(37,99,235,.12);
    }

    .venopuncture-filter-grid {
        display: grid;
        grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) auto;
        align-items: end;
        gap: 12px;
    }

    .venopuncture-field label {
        display: block;
        margin-bottom: 6px;
        color: #334155;
        font-size: 11px;
        font-weight: 900;
    }

    .venopuncture-field input {
        width: 100%;
        min-height: 44px;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 10px 12px;
        color: #0f172a;
        background: #ffffff;
        font: inherit;
        font-size: 12px;
        font-weight: 750;
        outline: none;
    }

    .venopuncture-field input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,.12);
    }

    .venopuncture-actions {
        display: flex;
        align-items: center;
        gap: 9px;
        flex-wrap: wrap;
    }

    .venopuncture-button {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 12px;
        padding: 10px 14px;
        border: 1px solid transparent;
        text-decoration: none;
        font: inherit;
        font-size: 11px;
        font-weight: 900;
        cursor: pointer;
        transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
    }

    .venopuncture-button:hover { transform: translateY(-1px); }

    .venopuncture-button-primary {
        color: #ffffff;
        background: linear-gradient(135deg, #2478fa, #0d5fe8);
        box-shadow: 0 8px 18px rgba(37,99,235,.2);
    }

    .venopuncture-button-secondary {
        color: #1d4ed8;
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    .venopuncture-button-icon {
        width: 46px;
        min-width: 46px;
        padding-inline: 0;
        background: #ffffff;
        color: #47658a;
        border-color: #d6e0ef;
        box-shadow: 0 8px 18px rgba(15,23,42,.05);
    }

    .venopuncture-button svg {
        width: 16px;
        height: 16px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .venopuncture-heatmap-card {
        padding: 0;
        overflow: hidden;
    }

    .venopuncture-chart-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 18px 20px;
        border-bottom: 1px solid #e2e8f0;
        flex-wrap: wrap;
    }

    .venopuncture-chart-head h2 {
        margin: 0;
        color: #0f172a;
        font-size: 18px;
        font-weight: 950;
    }

    .venopuncture-chart-head p {
        margin: 5px 0 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.45;
        font-weight: 700;
    }

    .venopuncture-chart-tools {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .venopuncture-chart-shell {
        padding: 16px 18px 18px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
    }

    .venopuncture-chart-frame {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 720px;
        border-radius: 20px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #dce6f3;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.92);
        scrollbar-width: thin;
        scrollbar-color: #b7c9e6 #eef4fb;
    }

    .venopuncture-chart-frame::-webkit-scrollbar {
        height: 10px;
    }

    .venopuncture-chart-frame::-webkit-scrollbar-track {
        background: #eef4fb;
    }

    .venopuncture-chart-frame::-webkit-scrollbar-thumb {
        background: #b7c9e6;
        border-radius: 999px;
        border: 2px solid #eef4fb;
    }

    #venopunctureHeatmap {
        width: 100%;
        min-width: 900px;
        min-height: 620px;
    }

    .venopuncture-reference {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-top: 14px;
        padding: 18px 20px;
        background: #ffffff;
        border: 1px solid #dce6f3;
        border-radius: 18px;
        color: #51627f;
        flex-wrap: wrap;
    }

    .venopuncture-reference-title {
        color: #0f172a;
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .venopuncture-reference-items {
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }

    .venopuncture-reference-item {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: #51627f;
        font-size: 12px;
        font-weight: 800;
    }

    .venopuncture-reference-item strong {
        display: block;
        color: #1f2a44;
        font-size: 11px;
        font-weight: 900;
    }

    .venopuncture-reference-item small {
        display: block;
        color: #6b7b95;
        font-size: 11px;
        font-weight: 700;
        margin-top: 2px;
    }

    .venopuncture-reference-color {
        width: 44px;
        height: 36px;
        border-radius: 8px;
        border: 1px solid rgba(148,163,184,.2);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.55);
        flex: 0 0 auto;
    }

    .venopuncture-footer-note {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 2px 0;
        color: #6b7b95;
        font-size: 12px;
        font-weight: 700;
    }

    .venopuncture-footer-note svg {
        width: 16px;
        height: 16px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        flex: 0 0 auto;
    }

    .venopuncture-heatmap-tooltip {
        min-width: 220px;
        padding: 11px 12px;
        color: #0f172a;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #dbe4f0;
        box-shadow: 0 16px 34px rgba(2,12,27,.14);
        font-family: inherit;
    }

    .venopuncture-heatmap-tooltip strong {
        display: block;
        margin-bottom: 7px;
        color: #0a3476;
        font-size: 13px;
        font-weight: 950;
    }

    .venopuncture-heatmap-tooltip div {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        margin-top: 5px;
        color: #64748b;
        font-size: 11px;
        font-weight: 750;
    }

    .venopuncture-heatmap-tooltip span {
        color: #0f172a;
        font-weight: 900;
        text-align: right;
    }

    @media (max-width: 760px) {
        .venopuncture-filter-grid { grid-template-columns: 1fr; }
        .venopuncture-actions { width: 100%; }
        .venopuncture-button { flex: 1; }
        .venopuncture-chart-shell { padding: 12px; }
        .venopuncture-chart-head,
        .venopuncture-reference { padding-inline: 14px; }
        .venopuncture-chart-tools { width: 100%; justify-content: flex-end; }
        #venopunctureHeatmap { min-width: 760px; }
    }
</style>
@endsection

@section('content')
    <div class="venopuncture-header dashboard-header">
        <div>
            <h1 class="venopuncture-title dashboard-title">Venopunciones</h1>
            <p class="venopuncture-subtitle dashboard-subtitle">Consulta independiente de pacientes, órdenes, perfiles y estudios que requieren una segunda toma.</p>
        </div>

        <div class="venopuncture-period-pill">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
            <span>{{ implode(', ', $periodLabels) }}</span>
        </div>
    </div>

    <section class="venopuncture-card venopuncture-filter-card filter-card">
        <div class="venopuncture-filter-head">
            <div>
                <h2 class="filter-title">Periodo de consulta</h2>
                <p class="filter-helper">Puedes conservar este periodo como referencia visual mientras el endpoint definitivo queda disponible.</p>
            </div>
            <span class="venopuncture-preview-badge">Mockup listo para conectar a la API</span>
        </div>

        <form method="GET" action="{{ route('dashboards.venopunciones') }}">
            <div class="venopuncture-filter-grid">
                <div class="venopuncture-field">
                    <label for="date_from">Fecha inicial</label>
                    <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] }}" required>
                </div>

                <div class="venopuncture-field">
                    <label for="date_to">Fecha final</label>
                    <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] }}" required>
                </div>

                <div class="venopuncture-actions">
                    <button type="submit" class="venopuncture-button venopuncture-button-primary btn btn-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                        Actualizar periodo
                    </button>
                    <a href="{{ route('dashboards.venopunciones') }}" class="venopuncture-button venopuncture-button-secondary btn btn-secondary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
                        Limpiar
                    </a>
                </div>
            </div>
        </form>
    </section>

    <section class="venopuncture-card venopuncture-heatmap-card">
        <header class="venopuncture-chart-head">
            <div>
                <h2>Venopunciones por cama × día de la semana</h2>
                <p>Los días se muestran horizontalmente y las camas verticalmente. La intensidad del color representa el número de venopunciones registradas.</p>
            </div>
            <div class="venopuncture-chart-tools">
                <button type="button" class="venopuncture-button venopuncture-button-icon" title="Exportar vista">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                </button>
                <button type="button" class="venopuncture-button venopuncture-button-icon" title="Aplicar filtros visuales">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v5l-4 2v-7L4 5z"/></svg>
                </button>
            </div>
        </header>

        <div class="venopuncture-chart-shell">
            <div class="venopuncture-chart-frame" id="venopunctureChartFrame">
                <div id="venopunctureHeatmap" aria-label="Mapa de calor de venopunciones por cama y día de la semana"></div>
            </div>
            <div class="venopuncture-reference">
                <span class="venopuncture-reference-title">Referencia rápida</span>
                <div class="venopuncture-reference-items">
                    <span class="venopuncture-reference-item"><i class="venopuncture-reference-color" style="background:#eef2f7"></i><span><strong>Sin registro</strong><small>Sin dato</small></span></span>
                    <span class="venopuncture-reference-item"><i class="venopuncture-reference-color" style="background:#dbe8f8"></i><span><strong>1 – 2</strong><small>Baja frecuencia</small></span></span>
                    <span class="venopuncture-reference-item"><i class="venopuncture-reference-color" style="background:#9ec8f7"></i><span><strong>3 – 4</strong><small>Frecuencia moderada</small></span></span>
                    <span class="venopuncture-reference-item"><i class="venopuncture-reference-color" style="background:#f8c777"></i><span><strong>5 – 6</strong><small>Alta frecuencia</small></span></span>
                    <span class="venopuncture-reference-item"><i class="venopuncture-reference-color" style="background:#f68b82"></i><span><strong>7 – 8</strong><small>Muy alta frecuencia</small></span></span>
                </div>
            </div>
            <div class="venopuncture-footer-note">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 10v6"/><path d="M12 7h.01"/></svg>
                <span>Los datos se actualizan diariamente a las 06:00 a. m. Última actualización: {{ now()->format('d/m/Y') }} 06:00 a. m.</span>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
<script>
    const venopunctureDateFrom = @json($filters['date_from']);
    const venopunctureDateTo = @json($filters['date_to']);

    /*
     * Mientras no exista el endpoint se utiliza una matriz de demostración.
     * Formato sugerido para la futura API:
     * {
     *   "beds": ["Cama 01", "Cama 02"],
     *   "days": ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"],
     *   "data": [{ "bed": "Cama 01", "day": "Lunes", "count": 3 }]
     * }
     */

    document.addEventListener('DOMContentLoaded', function () {
        const demoPayload = buildVenopunctureDemoData(venopunctureDateFrom, venopunctureDateTo);
        renderVenopunctureHeatmap(demoPayload);
    });

    function buildVenopunctureDemoData(dateFrom, dateTo) {
        const days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        const beds = Array.from({ length: 20 }, (_, index) => `Cama ${String(index + 1).padStart(2, '0')}`);
        const seed = hashText(`${dateFrom}|${dateTo}|vcare-venopunctures`);
        const random = seededRandom(seed);
        const data = [];

        days.forEach(function (day, dayIndex) {
            beds.forEach(function (bed, bedIndex) {
                const weekdayWeight = dayIndex <= 4 ? 1.15 : 0.52;
                const bedWeight = 0.72 + ((bedIndex % 5) * 0.12);
                const emptyChance = dayIndex >= 5 ? 0.48 : 0.20;
                const hasRecord = random() > emptyChance;
                let count = null;

                if (hasRecord) {
                    count = Math.max(1, Math.min(8, Math.round((random() * 5.6 + random() * 2.4) * weekdayWeight * bedWeight)));
                }

                data.push([dayIndex, bedIndex, count]);
            });
        });

        return { beds, days, data };
    }

    function renderVenopunctureHeatmap(payload) {
        const beds = Array.isArray(payload.beds)
            ? payload.beds
            : (Array.isArray(payload.camas)
                ? payload.camas
                : (Array.isArray(payload.patients) ? payload.patients : []));
        const days = Array.isArray(payload.days) ? payload.days : [];
        const data = Array.isArray(payload.data) ? payload.data : [];
        const chartElement = document.getElementById('venopunctureHeatmap');

        if (!chartElement) {
            return;
        }

        const chartHeight = Math.max(620, (beds.length * 38) + 180);
        chartElement.style.width = '100%';
        chartElement.style.height = `${chartHeight}px`;

        Highcharts.chart(chartElement, {
            chart: {
                type: 'heatmap',
                backgroundColor: 'transparent',
                spacing: [28, 26, 16, 22],
                height: chartHeight,
                style: {
                    fontFamily: 'inherit'
                }
            },

            title: {
                text: 'Venopunciones por Cama × Día de la Semana',
                align: 'left',
                margin: 10,
                style: {
                    color: '#0f172a',
                    fontSize: '18px',
                    fontWeight: '900'
                }
            },

            subtitle: {
                text: `Periodo seleccionado: ${formatDateMx(venopunctureDateFrom)} al ${formatDateMx(venopunctureDateTo)}`,
                align: 'left',
                style: {
                    color: '#6b7b95',
                    fontSize: '10px',
                    fontWeight: '800'
                }
            },

            xAxis: {
                categories: days,
                opposite: true,
                lineColor: '#dce6f3',
                tickColor: '#dce6f3',
                labels: {
                    rotation: 0,
                    y: -4,
                    style: {
                        color: '#52627f',
                        fontSize: '9px',
                        fontWeight: '800'
                    }
                }
            },

            yAxis: {
                categories: beds,
                title: null,
                reversed: true,
                lineWidth: 0,
                gridLineWidth: 0,
                labels: {
                    style: {
                        color: '#1f2a44',
                        fontSize: '11px',
                        fontWeight: '900'
                    }
                }
            },

            colorAxis: {
                min: 1,
                max: 8,
                tickPositions: [1, 2, 3, 4, 5, 6, 7, 8],
                stops: [
                    [0, '#dbe8f8'],
                    [0.34, '#8dbbf1'],
                    [0.58, '#f8c777'],
                    [0.78, '#f8a45f'],
                    [1, '#f6766e']
                ],
                labels: {
                    style: {
                        color: '#6b7b95',
                        fontSize: '9px',
                        fontWeight: '800'
                    }
                }
            },

            legend: {
                align: 'center',
                layout: 'horizontal',
                verticalAlign: 'bottom',
                symbolWidth: 480,
                symbolHeight: 12,
                margin: 20,
                padding: 0,
                itemStyle: {
                    color: '#1f2a44'
                },
                title: {
                    text: 'Número de venopunciones por cama',
                    style: {
                        color: '#1f2a44',
                        fontSize: '10px',
                        fontWeight: '800'
                    }
                }
            },

            tooltip: {
                useHTML: true,
                backgroundColor: 'transparent',
                borderWidth: 0,
                shadow: false,
                formatter: function () {
                    const value = this.point.value;
                    const countLabel = value === null ? 'Sin registro' : `${value} venopunción${value === 1 ? '' : 'es'}`;

                    return `
                        <div class="venopuncture-heatmap-tooltip">
                            <strong>${escapeHtml(beds[this.point.y] || 'Cama')}</strong>
                            <div>Día <span>${escapeHtml(days[this.point.x] || '-')}</span></div>
                            <div>Frecuencia <span>${escapeHtml(countLabel)}</span></div>
                        </div>
                    `;
                }
            },

            exporting: {
                buttons: {
                    contextButton: {
                        enabled: false
                    }
                }
            },

            credits: {
                enabled: false
            },

            plotOptions: {
                series: {
                    animation: false,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    borderRadius: 10,
                    nullColor: '#eef2f7',
                    states: {
                        hover: {
                            brightness: 0.04,
                            borderColor: '#ffffff',
                            borderWidth: 2
                        },
                        inactive: {
                            opacity: 1
                        }
                    }
                }
            },

            series: [{
                name: 'Venopunciones',
                data: data,
                turboThreshold: 0,
                dataLabels: {
                    enabled: true,
                    useHTML: false,
                    inside: true,
                    padding: 0,
                    crop: true,
                    overflow: 'none',
                    formatter: function () {
                        if (this.point.value === null) {
                            return null;
                        }

                        return String(this.point.value);
                    },
                    style: {
                        color: '#0f172a',
                        fontSize: '11px',
                        fontWeight: '900',
                        textOutline: 'none'
                    }
                }
            }]
        });
    }

    function hashText(value) {
        let hash = 2166136261;
        const text = String(value || '');

        for (let index = 0; index < text.length; index++) {
            hash ^= text.charCodeAt(index);
            hash = Math.imul(hash, 16777619);
        }

        return hash >>> 0;
    }

    function seededRandom(seed) {
        let state = seed >>> 0;

        return function () {
            state += 0x6D2B79F5;
            let value = state;
            value = Math.imul(value ^ (value >>> 15), value | 1);
            value ^= value + Math.imul(value ^ (value >>> 7), value | 61);
            return ((value ^ (value >>> 14)) >>> 0) / 4294967296;
        };
    }

    function formatDateMx(value) {
        const parts = String(value || '').split('-');
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : String(value || '');
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
</script>
@endsection
