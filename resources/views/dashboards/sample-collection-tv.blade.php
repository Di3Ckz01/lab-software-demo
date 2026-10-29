<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#06265f">
    <title>V-Care | Pantalla de toma de muestra</title>

    <style>
        :root {
            --navy: #08285f;
            --blue: #0b63f6;
            --text: #10213d;
            --muted: #64748b;
            --border: #dbe7f5;
            --surface: rgba(255, 255, 255, .95);
            --shadow: 0 16px 44px rgba(15, 35, 72, .12);
            --danger: #ef5362;
            --danger-bg: #fff0f2;
        }

        * { box-sizing: border-box; }
        html, body { width: 100%; height: 100%; margin: 0; }

        body {
            overflow: hidden;
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at 5% 5%, rgba(11, 99, 246, .12), transparent 28%),
                radial-gradient(circle at 95% 100%, rgba(37, 190, 131, .09), transparent 25%),
                #edf5ff;
        }

        button, input, select { font: inherit; }

        .screen-page {
            width: 100vw;
            height: 100vh;
            padding: 6px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            overflow: hidden;
        }

        .screen-header,
        .screen-panel,
        .summary-card,
        .queue-toolbar {
            border: 1px solid rgba(211, 225, 242, .98);
            background: var(--surface);
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
        }

        .screen-header {
            min-height: 58px;
            padding: 7px 10px;
            border-radius: 16px;
            display: grid;
            grid-template-columns: 96px 190px minmax(0, 1fr);
            align-items: center;
            gap: 8px;
        }

        .brand-area,
        .header-actions,
        .control,
        .toolbar-actions,
        .pagination,
        .footer-status {
            display: flex;
            align-items: center;
        }

        .brand-area { min-width: 0; }
        .brand-area img { width: 88px; height: auto; display: block; }
        .header-title { min-width: 0; overflow: visible; }
        .header-title h1 { margin: 0; color: var(--text); font-size: clamp(18px, 1.2vw, 22px); line-height: 1.05; letter-spacing: -.025em; white-space: nowrap; overflow: visible; }
        .header-summary {
            min-width: 0;
            display: grid;
            grid-template-columns: repeat(5, minmax(118px, 1fr));
            justify-content: start;
            gap: 6px;
        }
        .header-kpi {
            min-width: 0;
            height: 40px;
            padding: 5px 9px;
            border: 1px solid #dbe7f5;
            border-radius: 10px;
            background: #f8fbff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .header-kpi span {
            overflow: hidden;
            color: #64748b;
            font-size: 7px;
            font-weight: 950;
            letter-spacing: .035em;
            text-overflow: ellipsis;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .header-kpi strong {
            margin-top: 1px;
            color: #10213d;
            font-size: clamp(14px, .95vw, 17px);
            line-height: 1;
            white-space: nowrap;
        }
        .header-actions { gap: 7px; flex-wrap: nowrap; }
        .clock-box { min-width: 82px; text-align: right; }
        .clock-box strong { display: block; font-size: 10px; white-space: nowrap; }

        .icon-button,
        .action-button {
            height: 34px;
            border: 1px solid rgba(11, 99, 246, .25);
            border-radius: 12px;
            color: var(--blue);
            background: rgba(11, 99, 246, .07);
            cursor: pointer;
            font-weight: 900;
        }
        .icon-button {
            width: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .icon-button svg { width: 19px; height: 19px; fill: none; stroke: currentColor; stroke-width: 1.8; }
        .action-button { padding: 0 14px; }
        .action-button.primary { color: white; border-color: var(--blue); background: var(--blue); }
        .action-button:disabled { opacity: .42; cursor: not-allowed; }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }
        .summary-card {
            min-height: 34px;
            padding: 3px 7px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .summary-copy span {
            display: block;
            color: var(--muted);
            font-size: 7px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .045em;
        }
        .summary-copy strong {
            display: block;
            margin-top: 2px;
            color: var(--text);
            font-size: clamp(14px, 1vw, 18px);
            line-height: 1;
        }
        .summary-chip {
            padding: 3px 6px;
            border-radius: 999px;
            background: #edf5ff;
            color: #0b5edb;
            font-size: 7px;
            font-weight: 900;
            white-space: nowrap;
        }

        .queue-toolbar {
            padding: 8px 11px;
            border-radius: 14px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 14px;
        }
        .toolbar-controls,
        .toolbar-actions { display: flex; align-items: flex-end; gap: 9px; }
        .control { flex-direction: column; align-items: flex-start; gap: 4px; }
        .control label {
            color: #52637d;
            font-size: 9px;
            font-weight: 950;
            text-transform: uppercase;
        }
        .control input,
        .control select {
            height: 32px;
            min-width: 132px;
            padding: 0 10px;
            border: 1px solid var(--border);
            border-radius: 11px;
            color: var(--text);
            background: #f8fbff;
            outline: 0;
            font-size: 12px;
            font-weight: 850;
        }
        .control select { min-width: 96px; }
        .pagination { gap: 5px; }
        .page-indicator {
            min-width: 82px;
            text-align: center;
            color: #40516d;
            font-size: 10px;
            font-weight: 900;
        }


        .panel-top {
            padding: 0 1px 4px;
            display: flex;
            justify-content: flex-end;
            align-items: end;
        }
        .panel-tools {
            display: flex;
            flex-direction: row;
            align-items: flex-end;
            gap: 8px;
        }
        .panel-tools-row {
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            gap: 4px;
            flex-wrap: nowrap;
        }
        .panel-tools .control input { min-width: 105px; }
        .panel-tools .control select { min-width: 60px; }
        .panel-tools .control input,
        .panel-tools .control select,
        .panel-tools .action-button { height: 30px; font-size: 10px; }
        .bottom-pagination {
            min-height: 34px;
            padding: 5px 4px 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }
        .pagination-dots {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }
        .page-dot {
            width: 30px;
            height: 30px;
            padding: 0;
            border: 1px solid #cfe0f5;
            border-radius: 999px;
            color: #0b63f6;
            background: #f5f9ff;
            font-size: 11px;
            font-weight: 950;
            cursor: pointer;
        }
        .page-dot.active {
            color: #fff;
            border-color: #0b63f6;
            background: #0b63f6;
            box-shadow: 0 6px 14px rgba(11,99,246,.24);
            cursor: default;
        }
        .page-dot:disabled { opacity: .42; cursor: not-allowed; }
        .page-ellipsis { color: #7b8ca5; font-weight: 900; }

        .screen-panel {
            flex: 1;
            min-height: 0;
            padding: 8px;
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .panel-head {
            min-height: 53px;
            padding: 0 3px 8px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }
        .panel-head h2 {
            margin: 0;
            font-size: clamp(18px, 1.35vw, 25px);
            letter-spacing: -.035em;
        }
        .panel-head p {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 10px;
            font-weight: 700;
        }
        .score-legend { min-width: 330px; padding-top: 3px; }
        .score-legend-head {
            display: flex;
            justify-content: space-between;
            color: #52637d;
            font-size: 9px;
            font-weight: 900;
        }
        .score-gradient {
            height: 10px;
            margin-top: 3px;
            border: 1px solid rgba(111, 135, 165, .22);
            border-radius: 999px;
            background: linear-gradient(90deg, #22c55e 0%, #84cc16 25%, #eab308 50%, #f97316 75%, #ef4444 100%);
        }

        .error-banner {
            display: none;
            margin-bottom: 8px;
            padding: 7px 10px;
            border: 1px solid rgba(239,83,98,.25);
            border-radius: 10px;
            color: #b42435;
            background: var(--danger-bg);
            font-size: 10px;
            font-weight: 850;
        }
        .error-banner.visible { display: block; }

        .queue-shell {
            flex: 1;
            min-height: 0;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: white;
        }
        .queue-scroll { width: 100%; height: 100%; overflow: hidden; }
        table {
            width: 100%;
            min-width: 0;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }
        th, td {
            border-right: 1px solid #e1eaf5;
            border-bottom: 1px solid #e1eaf5;
            text-align: center;
        }
        thead th {
            position: sticky;
            top: 0;
            z-index: 4;
            height: 42px;
            padding: 6px 4px;
            color: #41536e;
            background: #f4f8fe;
            font-size: clamp(11px, .85vw, 14px);
            font-weight: 950;
            text-transform: uppercase;
        }
        tbody td {
            height: 82px;
            padding: 5px;
            background: #fbfdff;
            vertical-align: middle;
        }
        th:last-child, td:last-child { border-right: 0; }
        tbody tr:last-child td { border-bottom: 0; }

        .door-heading {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            white-space: nowrap;
        }
        .door-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #22324d;
            font-size: 15px;
            font-weight: 950;
            line-height: 1;
        }
        .door-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            color: #2f6cf6;
            flex: 0 0 auto;
        }
        .door-icon svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        .door-label {
            color: #22324d;
            font-size: 20px;
            font-weight: 950;
            letter-spacing: -.01em;
        }
        .door-count {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            margin-top: 0;
            color: #728197;
            font-size: 10px;
            font-weight: 850;
            text-transform: none;
            text-align: right;
        }

        .order-card {
            width: 100%;
            min-height: 68px;
            padding: 7px 8px;
            border: 1px solid var(--score-border);
            border-radius: 12px;
            color: var(--score-text);
            background: var(--score-bg);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.38);
            text-align: left;
            overflow: hidden;
        }
        .order-top,
        .order-meta,
        .order-score-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 7px;
        }
        .order-number {
            min-width: 0;
            overflow: visible;
            text-overflow: clip;
            white-space: nowrap;
            font-size: clamp(14px, 1.02vw, 17px);
            font-weight: 950;
            font-variant-numeric: tabular-nums;
            letter-spacing: -.02em;
        }
        .order-score {
            flex: 0 0 auto;
            padding: 3px 7px;
            border-radius: 999px;
            background: rgba(255,255,255,.55);
            font-size: 10px;
            font-weight: 950;
        }
        .order-meta { margin-top: 4px; font-size: 12.5px; font-weight: 850; opacity: .95; }
        .order-times { margin-top: 6px; display: flex; flex-direction: column; align-items: flex-start; gap: 3px; font-size: 9px; font-weight: 850; opacity: .9; }
        .time-item { display: inline-flex; align-items: center; gap: 3px; white-space: nowrap; }
        .time-icon { width: 12px; height: 12px; fill: none; stroke: currentColor; stroke-width: 2; }
        .venopuncture-alert { padding: 2px 5px; border: 1px solid rgba(180,35,24,.32); border-radius: 999px; color: #9f231d; background: rgba(255,255,255,.62); box-shadow: 0 0 0 2px rgba(255,91,76,.08); }
        .venopuncture-alert .time-icon { animation: pulseClock 1.35s ease-in-out infinite; }
        @keyframes pulseClock { 0%,100% { transform: scale(1); } 50% { transform: scale(1.13); } }
        .empty-cell { color: #b0bdcc; font-size: 13px; font-weight: 800; }

        /* Densidades adaptativas para TV.
           Las alturas están sincronizadas con rowHeightFor() en JavaScript para
           evitar que la última tarjeta quede cortada en la parte inferior. */
        .queue-table.compact thead th { height: 40px; font-size: 10px; padding: 5px 4px; }
        .queue-table.compact tbody td { height: 82px; padding: 4px; }
        .queue-table.compact .order-card { min-height: 68px; padding: 6px 7px; }
        .queue-table.compact .order-number { font-size: 15px; font-weight: 950; letter-spacing: -.015em; }
        .queue-table.compact .order-score { font-size: 9px; }
        .queue-table.compact .order-meta { font-size: 13px; }
        .queue-table.compact .order-times { font-size: 9.5px; line-height: 1.15; }
        .queue-table.compact .door-badge { font-size: 15px; gap: 5px; }
        .queue-table.compact .door-icon { width: 18px; height: 18px; }
        .queue-table.compact .door-label { font-size: 18px; }
        .queue-table.compact .door-count { font-size: 9px; }

        .queue-table.ultra-compact thead th { height: 38px; font-size: 9px; padding: 4px 3px; }
        .queue-table.ultra-compact tbody td { height: 70px; padding: 3px; }
        .queue-table.ultra-compact .order-card { min-height: 58px; padding: 5px 6px; border-radius: 10px; }
        .queue-table.ultra-compact .order-number { font-size: 12px; letter-spacing: -.02em; }
        .queue-table.ultra-compact .order-score { padding: 2px 5px; font-size: 8px; }
        .queue-table.ultra-compact .order-meta { margin-top: 3px; font-size: 10px; }
        .queue-table.ultra-compact .order-times { margin-top: 4px; gap: 2px; font-size: 7.5px; }
        .queue-table.ultra-compact .time-icon { width: 10px; height: 10px; }
        .queue-table.ultra-compact .door-badge { font-size: 13px; gap: 4px; }
        .queue-table.ultra-compact .door-icon { width: 15px; height: 15px; }
        .queue-table.ultra-compact .door-label { font-size: 15px; }
        .queue-table.ultra-compact .door-count { font-size: 8px; }

        .queue-table.many-doors thead th { height: 36px; font-size: 8px; padding: 3px 2px; }
        .queue-table.many-doors tbody td { height: 62px; padding: 2px; }
        .queue-table.many-doors .door-heading { gap: 3px; }
        .queue-table.many-doors .door-badge { font-size: 11px; gap: 3px; }
        .queue-table.many-doors .door-icon { width: 13px; height: 13px; }
        .queue-table.many-doors .door-label { font-size: 13px; }
        .queue-table.many-doors .door-count { font-size: 7px; }
        .queue-table.many-doors .order-card { min-height: 50px; padding: 4px 5px; border-radius: 8px; }
        .queue-table.many-doors .order-number { font-size: 10px; letter-spacing: -.03em; }
        .queue-table.many-doors .order-score { padding: 2px 4px; font-size: 7px; }
        .queue-table.many-doors .order-meta { margin-top: 2px; font-size: 9px; }
        .queue-table.many-doors .order-times { margin-top: 3px; gap: 1px; font-size: 6.8px; line-height: 1.1; }
        .queue-table.many-doors .time-icon { width: 9px; height: 9px; }
        .queue-table.many-doors .venopuncture-alert { padding: 1px 3px; }


        .empty-state {
            height: 100%;
            min-height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 14px;
            font-weight: 850;
        }

        .panel-footer {
            min-height: 30px;
            padding: 5px 3px 0;
            display: grid;
            grid-template-columns: minmax(180px, 1fr) auto minmax(180px, 1fr);
            align-items: center;
            gap: 12px;
            color: var(--muted);
            font-size: 9px;
            font-weight: 750;
        }
        .footer-legend { width: min(300px, 38vw); }
        .footer-legend .score-gradient { height: 8px; margin-top: 2px; }
        .footer-legend .score-legend-head { font-size: 7px; }
        .footer-datetime {
            min-width: 390px;
            padding: 6px 16px;
            border: 1px solid #dbe7f5;
            border-radius: 10px;
            color: #20324f;
            background: #f8fbff;
            text-align: center;
            font-size: 16px;
            font-weight: 950;
            font-variant-numeric: tabular-nums;
            letter-spacing: .01em;
            white-space: nowrap;
        }
        .footer-status { gap: 8px; justify-content: flex-end; }
        .status-dot { width: 7px; height: 7px; border-radius: 999px; background: #19b979; }
        .screen-loading .queue-shell { opacity: .55; }


        @media (max-width: 1450px) {
            .screen-header { grid-template-columns: 88px 178px minmax(0, 1fr); }
            .brand-area img { width: 82px; }
            .header-summary { grid-template-columns: repeat(5, minmax(118px, 1fr)); gap: 5px; }
            .header-kpi { height: 38px; padding: 4px 7px; }
            .header-kpi span { font-size: 6.4px; }
            .header-kpi strong { font-size: 14px; }
        }

        @media (max-width: 1180px) {
            .screen-page { padding: 7px; }
            .screen-header { grid-template-columns: 82px 158px minmax(0,1fr); }
            .brand-area img { width: 76px; }
            .header-summary { grid-template-columns: repeat(5, minmax(62px,1fr)); gap: 3px; }
            .header-title h1 { font-size: 16px; }
            .header-kpi { padding: 4px; }
            .summary-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .queue-toolbar { align-items: stretch; }
            .toolbar-controls, .toolbar-actions { flex-wrap: wrap; }
            .score-legend { min-width: 240px; }
            .panel-top { grid-template-columns: 1fr; }
            .panel-tools { align-items: stretch; }
            .panel-tools-row { justify-content: flex-start; }
        }
    
        @media (max-width: 1350px) {
            .screen-header { grid-template-columns: 82px 168px minmax(0, 1fr); gap: 5px; }
            .brand-area img { width: 76px; }
            .header-title h1 { font-size: 16px; }
            .header-summary { grid-template-columns: repeat(5, minmax(92px, 1fr)); gap: 3px; }
            .header-kpi { height: 29px; padding: 2px 4px; }
            .header-kpi span { font-size: 4.9px; }
            .header-kpi strong { font-size: 11px; }
            .clock-box strong { font-size: 8px; }
        }

        /* En una TV o navegador con poca altura aprovechamos cada píxel disponible. */
        @media (max-height: 800px) {
            .screen-page { padding: 4px; gap: 4px; }
            .screen-header { min-height: 52px; padding: 5px 8px; border-radius: 14px; }
            .screen-panel { padding: 6px; border-radius: 16px; }
            .bottom-pagination { min-height: 28px; padding-top: 3px; }
            .page-dot { width: 26px; height: 26px; font-size: 10px; }
            .panel-footer { min-height: 24px; padding-top: 3px; }
            .footer-datetime { min-width: 340px; padding: 5px 12px; font-size: 14px; }
            .footer-legend .score-gradient { height: 7px; }
        }

        @media (max-height: 680px) {
            .screen-header { min-height: 48px; padding: 4px 7px; }
            .header-kpi { height: 32px; }
            .screen-panel { padding: 5px; }
            .bottom-pagination { min-height: 24px; padding-top: 2px; }
            .page-dot { width: 23px; height: 23px; font-size: 9px; }
            .panel-footer { min-height: 21px; padding-top: 2px; font-size: 8px; }
            .footer-datetime { min-width: 300px; padding: 4px 10px; font-size: 12px; }
            .footer-legend .score-gradient { height: 6px; }
        }
</style>
</head>
<body>
<div class="screen-page" id="sampleTvRoot"
     data-endpoint="{{ route('dashboards.sample-collection-tv.data') }}"
     data-login-url="{{ route('login') }}">
    <header class="screen-header">
        <div class="brand-area">
            <span aria-label="Laboratorio Demo" style="display:inline-block;padding:7px 12px;border-radius:9px;background:#e8f1ff;color:#174b92;font-weight:800;font-size:15px;letter-spacing:.07em;white-space:nowrap;">LAB DEMO</span>
        </div>

        <div class="header-title">
            <h1>Toma de muestra</h1>
        </div>

        <div class="header-summary" aria-label="Resumen diario">
            <article class="header-kpi"><span>Puertas activas</span><strong id="summaryDoors">0</strong></article>
            <article class="header-kpi"><span>T. espera promedio</span><strong id="summaryAverageWait">0 min</strong></article>
            <article class="header-kpi"><span>T. venopunción promedio</span><strong id="summaryAverageVenopuncture">0 min</strong></article>
            <article class="header-kpi"><span>Órdenes pendientes</span><strong id="summaryPendingOrders">0</strong></article>
            <article class="header-kpi"><span>Órdenes atendidas</span><strong id="summaryAttendedOrders">0</strong></article>
        </div>

    </header>

    <section class="screen-panel">
        <div class="error-banner {{ empty($errorMessage) ? '' : 'visible' }}" id="errorBanner">{{ $errorMessage ?? '' }}</div>
        <div class="queue-shell" id="queueShell">
            <div class="queue-scroll" id="queueContainer"></div>
        </div>

        <div class="bottom-pagination" aria-label="Paginación inferior">
            <div class="pagination-dots" id="paginationDots"></div>
        </div>

        <footer class="panel-footer">
            <div class="score-legend footer-legend" aria-label="Escala de complejidad">
                <div class="score-legend-head"><span>1</span><span>Complejidad</span><span>5</span></div>
                <div class="score-gradient"></div>
            </div>
            <div class="footer-datetime" id="screenDateTime">Jueves 10 de Septiembre de 2026 - 13:55:32</div>
            <span class="footer-status"><i class="status-dot"></i><span id="screenStatus">La pantalla avanza cada 10 segundos y se actualiza cada minuto.</span></span>
        </footer>
    </section>
</div>

<script>
    window.sampleCollectionTvInitialQueue = @json($initialQueue);
</script>
<script>
(function () {
    'use strict';

    const API_PAGE_SIZE = 50;
    const root = document.getElementById('sampleTvRoot');
    const endpoint = root.dataset.endpoint;
    const loginUrl = root.dataset.loginUrl;
    const queueShell = document.getElementById('queueShell');
    const queueContainer = document.getElementById('queueContainer');
    const errorBanner = document.getElementById('errorBanner');
    const screenStatus = document.getElementById('screenStatus');
    const paginationDots = document.getElementById('paginationDots');
    const numberFormat = new Intl.NumberFormat('es-MX');

    let currentData = window.sampleCollectionTvInitialQueue || null;
    let currentPage = 1;
    let totalPages = 1;
    let rowsPerPage = 1;
    let loading = false;
    let refreshToken = 0;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function numeric(value) {
        const number = Number(value);
        return Number.isFinite(number) ? number : 0;
    }

    function scoreColors(value) {
        const score = Math.max(1, Math.min(5, Math.round(numeric(value) || 1)));
        const palette = {
            1: { background: '#dcfce7', border: '#4ade80', text: '#14532d' },
            2: { background: '#ecfccb', border: '#84cc16', text: '#365314' },
            3: { background: '#fef9c3', border: '#eab308', text: '#713f12' },
            4: { background: '#ffedd5', border: '#f97316', text: '#9a3412' },
            5: { background: '#fee2e2', border: '#ef4444', text: '#991b1b' }
        };
        return palette[score];
    }

    function setError(message) {
        errorBanner.textContent = message || '';
        errorBanner.classList.toggle('visible', Boolean(message));
    }

    function formatAverage(value) {
        const amount = numeric(value);
        return numberFormat.format(Math.round(amount * 10) / 10) + ' min';
    }

    function renderSummary(data) {
        const summary = data && data.summary ? data.summary : {};
        const doors = Array.isArray(data && data.doors) ? data.doors : [];
        document.getElementById('summaryDoors').textContent = numberFormat.format(doors.length || numeric(summary.activeDoors));
        document.getElementById('summaryAverageWait').textContent = formatAverage(summary.averageWaitTime);
        document.getElementById('summaryAverageVenopuncture').textContent = formatAverage(summary.averageVenopunctureTime);
        document.getElementById('summaryPendingOrders').textContent = numberFormat.format(numeric(summary.pendingOrders));
        document.getElementById('summaryAttendedOrders').textContent = numberFormat.format(numeric(summary.attendedOrders));
    }

    function formatWaitTime(value) {
        if (value == null || numeric(value) < 0) return 'T. Espera: sin dato';
        return 'T. Espera: ' + numberFormat.format(numeric(value)) + ' min';
    }

    function formatVenopunctureTime(value) {
        if (value == null || numeric(value) < 0) return 'Venopunción: sin dato';
        return 'Venopunción: ' + numberFormat.format(numeric(value)) + ' min';
    }

    function clockIcon() {
        return '<svg class="time-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>';
    }

    function renderOrderCard(order) {
        const colors = scoreColors(order.score);
        const exams = numeric(order.studies);
        const examsText = exams === 1 ? '1 examen' : numberFormat.format(exams) + ' exámenes';
        const exceeded = Boolean(order.venopunctureTimeExceeded) || numeric(order.venopunctureTime) > 10;
        const venopunctureClass = exceeded ? 'time-item venopuncture-alert' : 'time-item';
        const venopunctureTitle = exceeded ? 'La venopunción superó los 10 minutos' : 'Tiempo de venopunción';

        return '<article class="order-card" style="--score-bg:' + colors.background + ';--score-border:' + colors.border + ';--score-text:' + colors.text + '">' +
            '<div class="order-top">' +
                '<span class="order-number" title="Orden ' + escapeHtml(order.order) + '">' + escapeHtml(order.order) + '</span>' +
                '<span class="order-score">' + Math.round(numeric(order.score)) + '/5</span>' +
            '</div>' +
            '<div class="order-meta"><span>' + escapeHtml(examsText) + '</span></div>' +
            '<div class="order-times">' +
                '<span class="time-item">' + clockIcon() + escapeHtml(formatWaitTime(order.waitTime)) + '</span>' +
                '<span class="' + venopunctureClass + '" title="' + escapeHtml(venopunctureTitle) + '">' + clockIcon() + escapeHtml(formatVenopunctureTime(order.venopunctureTime)) + '</span>' +
            '</div>' +
        '</article>';
    }

    function densityClass(doorCount) {
        if (doorCount > 14) return 'many-doors';
        if (doorCount > 12) return 'ultra-compact';
        if (doorCount > 8) return 'compact';
        return '';
    }

    function rowHeightFor(doorCount) {
        if (doorCount > 14) return 62;
        if (doorCount > 12) return 70;
        if (doorCount > 8) return 82;
        return 82;
    }

    function tableHeaderHeightFor(doorCount) {
        if (doorCount > 14) return 36;
        if (doorCount > 12) return 38;
        if (doorCount > 8) return 40;
        return 42;
    }

    function calculateRowsPerPage(doorCount) {
        // Se descuenta el encabezado real de la tabla y un pequeño margen de seguridad.
        // Así nunca se calcula una fila adicional que termine cortada por el borde inferior.
        const available = Math.max(1, queueShell.clientHeight - tableHeaderHeightFor(doorCount) - 4);
        return Math.max(1, Math.floor(available / rowHeightFor(doorCount)));
    }

    function groupOrders(data) {
        const doors = Array.isArray(data.doors) ? data.doors : [];
        const orders = Array.isArray(data.orders) ? data.orders : [];
        const grouped = {};
        doors.forEach(door => { grouped[String(door.doorID)] = []; });
        orders.forEach(order => {
            const key = String(order.doorID);
            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(order);
        });
        return { doors, grouped };
    }

    function renderQueue(data, forcedRowsPerPage) {
        const normalized = data || { doors: [], orders: [], counts: {}, summary: {} };
        const { doors, grouped } = groupOrders(normalized);

        if (!doors.length) {
            queueContainer.innerHTML = '<div class="empty-state">No hay órdenes disponibles en este momento.</div>';
            totalPages = 1;
            currentPage = 1;
            renderSummary(normalized);
            renderPaginationDots();
            return;
        }

        const maximumRows = Math.max(...doors.map(door => (grouped[String(door.doorID)] || []).length), 0);
        rowsPerPage = Number.isInteger(forcedRowsPerPage) && forcedRowsPerPage > 0
            ? forcedRowsPerPage
            : calculateRowsPerPage(doors.length);
        totalPages = Math.max(1, Math.ceil(maximumRows / rowsPerPage));
        currentPage = Math.min(Math.max(1, currentPage), totalPages);
        renderSummary(normalized);

        const firstRow = (currentPage - 1) * rowsPerPage;
        const lastRow = Math.min(maximumRows, firstRow + rowsPerPage);
        const counts = normalized.counts && typeof normalized.counts === 'object' ? normalized.counts : {};
        const tableDensityClass = densityClass(doors.length);

        const doorIconSvg = '<svg viewBox="0 0 64 64" aria-hidden="true"><path fill="#2f91e5" d="M0 11C0 4.925 4.925 0 11 0h24c6.075 0 11 4.925 11 11v42c0 6.075-4.925 11-11 11H11C4.925 64 0 59.075 0 53V11z"></path><path fill="#67a8df" d="M0 0h11c6.075 0 11 4.925 11 11v42c0 6.075-4.925 11-11 11H0V0z"></path><rect x="16" y="26" width="20" height="12" rx="6" fill="#111111"></rect><path fill="#6b8799" d="M50.585 20.586a4 4 0 0 1 5.656 0l10.173 10.173a4 4 0 0 1 0 5.656L56.24 46.588a4 4 0 0 1-5.656-5.656L53.93 37.59H33a4 4 0 0 1 0-8h20.93l-3.345-3.344a4 4 0 0 1 0-5.66Z"></path></svg>';
        const headers = doors.map(door => {
            const count = counts[String(door.doorID)] ?? (grouped[String(door.doorID)] || []).length;
            return '<th><div class="door-heading"><span class="door-badge"><span class="door-icon">' + doorIconSvg + '</span><span class="door-label">' + escapeHtml(String(door.doorID ?? '')) + '</span></span><span class="door-count">' + (count === 1 ? 'Orden ' : 'Órdenes ') + numberFormat.format(count) + '</span></div></th>';
        }).join('');

        let body = '';
        for (let rowIndex = firstRow; rowIndex < lastRow; rowIndex += 1) {
            const cells = doors.map(door => {
                const order = (grouped[String(door.doorID)] || [])[rowIndex];
                return '<td>' + (order ? renderOrderCard(order) : '<span class="empty-cell">—</span>') + '</td>';
            }).join('');
            body += '<tr>' + cells + '</tr>';
        }

        queueContainer.innerHTML = '<table class="queue-table ' + tableDensityClass + '" style="--door-count:' + doors.length + '"><thead><tr>' + headers + '</tr></thead><tbody>' + body + '</tbody></table>';

        // Comprobación final con las dimensiones reales del navegador.
        // Si por zoom, escalado de Windows o resolución de la TV la tabla rebasa el
        // contenedor, quitamos una fila de esta página y volvemos a renderizar.
        const renderedTable = queueContainer.querySelector('.queue-table');
        if (renderedTable && rowsPerPage > 1) {
            const tableHeight = renderedTable.getBoundingClientRect().height;
            const containerHeight = queueShell.getBoundingClientRect().height;
            if (tableHeight > containerHeight - 1) {
                renderQueue(normalized, rowsPerPage - 1);
                return;
            }
        }

        renderPaginationDots();
        screenStatus.textContent = 'Página ' + currentPage + ' de ' + totalPages + '. Cambio automático cada 10 s · actualización cada 60 s.';
    }

    function renderPaginationDots() {
        const items = [];
        const start = Math.max(1, currentPage - 2);
        const end = Math.min(totalPages, currentPage + 2);

        if (start > 1) {
            items.push('<button type="button" class="page-dot" data-page="1">1</button>');
            if (start > 2) items.push('<span class="page-ellipsis">…</span>');
        }
        for (let page = start; page <= end; page += 1) {
            const active = page === currentPage;
            items.push('<button type="button" class="page-dot' + (active ? ' active' : '') + '" data-page="' + page + '"' + (active ? ' disabled' : '') + '>' + page + '</button>');
        }
        if (end < totalPages) {
            if (end < totalPages - 1) items.push('<span class="page-ellipsis">…</span>');
            items.push('<button type="button" class="page-dot" data-page="' + totalPages + '">' + totalPages + '</button>');
        }
        paginationDots.innerHTML = items.join('');
    }

    function advancePageAutomatically() {
        if (loading || totalPages <= 1 || !currentData) return;
        currentPage = currentPage >= totalPages ? 1 : currentPage + 1;
        renderQueue(currentData);
    }

    function updateClock() {
        const now = new Date();
        const parts = new Intl.DateTimeFormat('es-MX', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).formatToParts(now);

        const value = (type) => parts.find(part => part.type === type)?.value || '';
        const capitalize = (text) => text ? text.charAt(0).toUpperCase() + text.slice(1) : '';

        const dayName = capitalize(value('weekday'));
        const day = value('day');
        const month = capitalize(value('month'));
        const year = value('year');

        const time = new Intl.DateTimeFormat('es-MX', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).format(now);

        const target = document.getElementById('screenDateTime');
        if (target) {
            target.textContent = dayName + ' ' + day + ' de ' + month + ' de ' + year + ' - ' + time;
        }
    }

    function updateUrl() {
        const url = new URL(window.location.href);
        url.searchParams.delete('date');
        url.searchParams.delete('pageSize');
        window.history.replaceState({}, '', url.toString());
    }

    async function loadAllOrders(options) {
        options = options || {};
        if (loading) return;
        const token = ++refreshToken;
        loading = true;
        root.classList.add('screen-loading');
        setError('');
        screenStatus.textContent = 'Cargando órdenes...';

        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('cursor', '0');
            url.searchParams.set('pageSize', String(API_PAGE_SIZE));
            url.searchParams.set('scope', 'doors');

            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            });
            const payload = await response.json().catch(() => ({}));

            if (payload.sessionExpired) {
                window.location.href = payload.redirect || loginUrl;
                return;
            }
            if (!response.ok || !payload.ok) {
                throw new Error(payload.message || ('Error HTTP ' + response.status));
            }
            if (token !== refreshToken) return;

            currentData = payload.data || {};
            if (!options.keepPage) currentPage = 1;
            renderQueue(currentData);
            updateUrl();
        } catch (error) {
            setError(error.message || 'No se pudo actualizar la fila.');
            screenStatus.textContent = 'Se conservaron los últimos datos disponibles.';
        } finally {
            loading = false;
            root.classList.remove('screen-loading');
        }
    }


    paginationDots.addEventListener('click', (event) => {
        const button = event.target.closest('[data-page]');
        if (!button || button.disabled) return;
        const targetPage = Number(button.dataset.page);
        if (!Number.isInteger(targetPage) || targetPage < 1 || targetPage > totalPages) return;
        currentPage = targetPage;
        renderQueue(currentData);
    });


    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (currentData) renderQueue(currentData);
        }, 120);
    });

    updateClock();
    updateUrl();
    loadAllOrders();
    setInterval(updateClock, 1000);
    setInterval(advancePageAutomatically, 10000);
    setInterval(() => loadAllOrders({ keepPage: true, polling: true }), 60000);
})();
</script>
</body>
</html>
