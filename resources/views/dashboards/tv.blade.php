<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAB DEMO | Estados de la orden por área</title>

    <style>
        :root {
            --blue: #0b63f6;
            --blue-dark: #101b33;
            --bg: #eef6ff;
            --card: rgba(255, 255, 255, .92);
            --border: rgba(210, 224, 243, .95);
            --text: #101b33;
            --muted: #64748b;
            --success: #13b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow: 0 20px 55px rgba(15, 23, 42, .12);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
        }

        body {
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            background:
                linear-gradient(120deg, rgba(239, 247, 255, .92), rgba(232, 242, 255, .88)),
                url("{{ asset('images/login-lab-vcare.png') }}") center center / cover no-repeat;
            color: var(--text);
            overflow: hidden;
        }

        .tv-page {
            width: 100vw;
            height: 100vh;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            background:
                radial-gradient(circle at top left, rgba(11, 99, 246, .14), transparent 34%),
                radial-gradient(circle at bottom right, rgba(255, 64, 88, .08), transparent 28%),
                rgba(239, 247, 255, .58);
            backdrop-filter: blur(2px);
        }

        .tv-header {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 12px 18px;
            border: 1px solid var(--border);
            border-radius: 20px;
            background: var(--card);
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .header-left img {
            width: 145px;
            height: auto;
            display: block;
            flex: 0 0 auto;
        }

        .header-line {
            width: 1px;
            height: 38px;
            background: #d9e5f4;
        }

        .header-title h1 {
            margin: 0;
            font-size: clamp(23px, 2vw, 34px);
            line-height: 1;
            letter-spacing: -.05em;
            color: #101b33;
        }

        .header-title p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: clamp(11px, .82vw, 14px);
            font-weight: 650;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            font-size: 12px;
            font-weight: 850;
            color: #26364f;
        }

        .live-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 8px 12px;
            border-radius: 999px;
            color: #047857;
            background: rgba(19, 185, 129, .13);
            border: 1px solid rgba(19, 185, 129, .24);
        }

        .live-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: var(--success);
            box-shadow: 0 0 0 6px rgba(19, 185, 129, .14);
            animation: pulse 1.4s infinite;
        }

        @keyframes pulse {
            0% { opacity: .65; transform: scale(.9); }
            50% { opacity: 1; transform: scale(1); }
            100% { opacity: .65; transform: scale(.9); }
        }

        .status-panel {
            flex: 1;
            min-height: 0;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: var(--card);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
            display: flex;
            flex-direction: column;
        }

        .panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 10px;
        }

        .panel-head h2 {
            margin: 0;
            color: #101b33;
            font-size: clamp(22px, 1.65vw, 30px);
            line-height: 1.05;
            letter-spacing: -.045em;
        }

        .panel-head p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: clamp(11px, .8vw, 13px);
            font-weight: 650;
        }

        .summary-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .summary-pill {
            min-width: 104px;
            padding: 7px 10px;
            border-radius: 13px;
            background: rgba(239, 246, 255, .9);
            border: 1px solid #dce8f6;
            text-align: center;
        }

        .summary-pill strong {
            display: block;
            font-size: 17px;
            color: #101b33;
            line-height: 1;
        }

        .summary-pill span {
            display: block;
            margin-top: 3px;
            font-size: 9px;
            color: var(--muted);
            font-weight: 800;
        }

        .table-shell {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #dce8f6;
            border-radius: 16px;
            background: rgba(255, 255, 255, .78);
        }

        .table-scroll {
            flex: 1;
            min-height: 0;
            overflow: auto;
        }

        .table-pagination {
            min-height: 38px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 6px 10px;
            border-top: 1px solid #dce8f6;
            background: rgba(248, 251, 255, .98);
        }

        .pagination-info {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .page-indicator {
            min-width: 92px;
            color: #334155;
            font-size: 11px;
            font-weight: 900;
            text-align: center;
            white-space: nowrap;
        }

        .pagination-button {
            min-width: 86px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 12px;
            border: 1px solid rgba(11, 99, 246, .24);
            border-radius: 9px;
            color: #0757d8;
            background: rgba(11, 99, 246, .07);
            font-family: inherit;
            font-size: 10px;
            font-weight: 900;
            cursor: pointer;
            transition: background .18s ease, transform .18s ease, opacity .18s ease;
        }

        .pagination-button:hover:not(:disabled) {
            background: rgba(11, 99, 246, .13);
            transform: translateY(-1px);
        }

        .pagination-button:disabled {
            color: #94a3b8;
            background: rgba(148, 163, 184, .08);
            border-color: rgba(148, 163, 184, .18);
            cursor: not-allowed;
            opacity: .68;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1120px;
        }

        thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            padding: 8px 8px;
            color: #334155;
            background: #f4f8ff;
            border-bottom: 1px solid #dce8f6;
            font-size: clamp(10px, .72vw, 13px);
            font-weight: 950;
            text-align: center;
        }

        tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid rgba(220, 232, 246, .9);
            color: #172554;
            font-size: clamp(11px, .82vw, 15px);
            font-weight: 850;
            text-align: center;
            white-space: nowrap;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        tbody tr:hover {
            background: rgba(11, 99, 246, .05);
        }

        .order-cell {
            position: sticky;
            left: 0;
            z-index: 1;
            text-align: left;
            background: rgba(255, 255, 255, .96);
            color: #101b33;
            font-size: clamp(12px, .9vw, 16px);
            font-weight: 950;
        }

        .order-main-line {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
        }

        .order-number {
            color: #101b33;
            font-weight: 950;
            letter-spacing: -.015em;
        }

        .type-badge.type-compact {
            min-width: 25px;
            padding: 3px 6px;
            font-size: 10px;
            line-height: 1;
            flex: 0 0 auto;
        }

        thead th:first-child {
            position: sticky;
            left: 0;
            z-index: 3;
            text-align: left;
        }

        .meta-muted {
            color: #64748b;
            font-size: .68em;
            font-weight: 750;
        }

        .type-badge,
        .op-badge,
        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            padding: 4px 7px;
            border-radius: 999px;
            font-size: .76em;
            font-weight: 950;
        }

        .type-r {
            color: #0757d8;
            background: rgba(11, 99, 246, .11);
        }

        .type-s {
            color: #c2410c;
            background: rgba(249, 115, 22, .14);
        }

        .op-ok {
            color: #047857;
            background: rgba(19, 185, 129, .13);
        }

        .op-warning {
            color: #b45309;
            background: rgba(245, 158, 11, .15);
        }

        .op-danger {
            color: #b91c1c;
            background: rgba(239, 68, 68, .13);
        }

        .op-empty {
            color: #94a3b8;
            background: rgba(148, 163, 184, .13);
        }

        .status-empty {
            color: #94a3b8;
            background: rgba(148, 163, 184, .12);
        }

        .status-ordered {
            color: #0757d8;
            background: rgba(11, 99, 246, .11);
        }

        .status-repeat {
            color: #b45309;
            background: rgba(245, 158, 11, .15);
        }

        .status-result {
            color: #6d28d9;
            background: rgba(124, 58, 237, .13);
        }

        .status-validated {
            color: #047857;
            background: rgba(19, 185, 129, .13);
        }

        .status-review,
        .status-unknown {
            color: #b91c1c;
            background: rgba(239, 68, 68, .13);
        }

        .loading-state,
        .error-state {
            flex: 1;
            display: grid;
            place-items: center;
            text-align: center;
            color: var(--muted);
            border: 2px dashed rgba(11, 99, 246, .22);
            border-radius: 16px;
            background: rgba(255, 255, 255, .48);
        }

        .loading-state strong,
        .error-state strong {
            display: block;
            margin-bottom: 8px;
            color: #172554;
            font-size: clamp(24px, 2vw, 36px);
        }

        .error-state strong {
            color: #b91c1c;
        }

        .tv-footer {
            min-height: 22px;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 14px;
            color: rgba(51, 65, 85, .78);
            font-size: 12px;
            font-weight: 800;
            padding: 0 6px;
        }

        .abbreviation-legend {
            min-width: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: none;
            white-space: nowrap;
            color: #52627e;
            font-size: 9px;
            line-height: 1;
        }

        .abbreviation-legend::-webkit-scrollbar {
            display: none;
        }

        .legend-heading {
            color: #334155;
            font-weight: 950;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .legend-item strong {
            color: #102142;
            font-weight: 950;
        }

        .legend-separator {
            color: #b3bfd0;
            font-weight: 900;
        }


        @media (max-height: 850px) {
            .tv-page { padding: 10px; gap: 8px; }
            .tv-header { min-height: 62px; padding: 9px 14px; }
            .header-left img { width: 128px; }
            .status-panel { padding: 10px; }
            .panel-head { margin-bottom: 7px; }
            thead th { padding: 6px 7px; }
            tbody td { padding: 5px 7px; }
            .type-badge, .op-badge, .status-badge { padding: 3px 6px; }
            .table-pagination { min-height: 32px; padding: 4px 8px; }
            .pagination-button { height: 24px; min-width: 76px; }
            .tv-footer { min-height: 18px; font-size: 10px; gap: 8px; }
            .abbreviation-legend { gap: 7px; font-size: 8px; }
        }

        @media (max-width: 1280px) {
            .header-right { font-size: 10px; }
            .summary-pill { min-width: 88px; }
            table { min-width: 1020px; }
        }
    </style>
</head>

<body>
<main class="tv-page">
    <header class="tv-header">
        <div class="header-left">
            <span aria-label="Laboratorio Demo" style="display:inline-block;padding:7px 12px;border-radius:9px;background:#e8f1ff;color:#174b92;font-weight:800;font-size:15px;letter-spacing:.07em;white-space:nowrap;">LAB DEMO</span>

            <div class="header-line"></div>

            <div class="header-title">
                <h1>Estados de la orden por área</h1>
                <p>Vista de tiempo de oportunidad para pantalla completa</p>
            </div>
        </div>

        <div class="header-right">
            <div class="live-pill">
                <span class="live-dot"></span>
                En vivo
            </div>

            <div id="currentDateTime">Cargando fecha...</div>
        </div>
    </header>

    <section class="status-panel">
        <div class="panel-head">
            <div>
                <h2>Tiempo de oportunidad</h2>
                <p>Monitoreo de órdenes y estados operativos por área.</p>
            </div>

            <div class="summary-row" id="summaryRow">
                <div class="summary-pill">
                    <strong>--</strong>
                    <span>Órdenes</span>
                </div>
            </div>
        </div>

        <div id="tableContainer" class="loading-state">
            <div>
                <strong>Cargando información...</strong>
                <span>Consultando estados de la orden por área.</span>
            </div>
        </div>
    </section>

    <footer class="tv-footer">
        <span>LAB DEMO · Datos ficticios</span>

        <div class="abbreviation-legend" aria-label="Leyenda de abreviaturas">
            <span class="legend-heading">Abreviaturas:</span>
            <span class="legend-item"><strong>BIO</strong> Bioquímica</span>
            <span class="legend-item"><strong>COA</strong> Coagulación</span>
            <span class="legend-item"><strong>END</strong> Endocrinología</span>
            <span class="legend-item"><strong>HEM</strong> Hematología</span>
            <span class="legend-item"><strong>INM</strong> Inmunología</span>
            <span class="legend-item"><strong>MIC</strong> Microbiología</span>
            <span class="legend-item"><strong>TOX</strong> Toxicología</span>
            <span class="legend-item"><strong>REM</strong> Remisiones</span>
        </div>

        <span id="lastUpdated">Actualización pendiente</span>
    </footer>
</main>

<script>
    const dataUrl = "{{ route('dashboards.tv.opportunity-status-data') }}";
    const tableContainer = document.getElementById('tableContainer');
    const lastUpdated = document.getElementById('lastUpdated');
    const summaryRow = document.getElementById('summaryRow');
    const ROWS_PER_PAGE = 11;

    let currentPayload = null;
    let currentPage = 1;

    function updateDateTime() {
        const target = document.getElementById('currentDateTime');
        const now = new Date();

        const formatted = now.toLocaleString('es-MX', {
            weekday: 'long',
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        target.textContent = formatted.charAt(0).toUpperCase() + formatted.slice(1);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderSummary(summary) {
        summaryRow.innerHTML = `
            <div class="summary-pill">
                <strong>${summary.totalShown ?? 0}</strong>
                <span>Órdenes visibles</span>
            </div>

            <div class="summary-pill">
                <strong>${summary.routine ?? 0}</strong>
                <span>Rutina</span>
            </div>

            <div class="summary-pill">
                <strong>${summary.urgent ?? 0}</strong>
                <span>Urgencias</span>
            </div>

            <div class="summary-pill">
                <strong>${summary.compliance ?? 0}%</strong>
                <span>Cumplimiento</span>
            </div>
        `;
    }

    function renderTable(payload) {
        const rows = payload.rows || [];

        renderSummary(payload.summary || {});
        currentPayload = payload;

        if (!rows.length) {
            currentPage = 1;
            tableContainer.className = 'error-state';
            tableContainer.innerHTML = `
                <div>
                    <strong>Sin información disponible</strong>
                    <span>No se encontraron órdenes para mostrar.</span>
                </div>
            `;
            return;
        }

        const totalPages = Math.max(1, Math.ceil(rows.length / ROWS_PER_PAGE));
        currentPage = Math.min(Math.max(currentPage, 1), totalPages);

        renderCurrentPage();
    }

    function renderCurrentPage() {
        if (!currentPayload) {
            return;
        }

        const rows = currentPayload.rows || [];
        const areas = currentPayload.areas || [];
        const totalPages = Math.max(1, Math.ceil(rows.length / ROWS_PER_PAGE));
        const startIndex = (currentPage - 1) * ROWS_PER_PAGE;
        const endIndex = Math.min(startIndex + ROWS_PER_PAGE, rows.length);
        const pageRows = rows.slice(startIndex, endIndex);
        const areaHeaders = areas.map(area => `<th>${escapeHtml(area)}</th>`).join('');

        const bodyRows = pageRows.map(row => {
            const typeClass = row.type === 'S' ? 'type-s' : 'type-r';

            const areaCells = areas.map(area => {
                const status = row.areaStatuses?.[area] || {};
                const label = status.code === null || status.code === undefined
                    ? '-'
                    : escapeHtml(status.label);

                const className = status.class || 'status-empty';

                return `
                    <td>
                        <span class="status-badge ${escapeHtml(className)}">
                            ${label}
                        </span>
                    </td>
                `;
            }).join('');

            return `
                <tr>
                    <td class="order-cell">
                        <div class="order-main-line">
                            <span class="order-number">${escapeHtml(row.order)}</span>
                            <span class="type-badge type-compact ${typeClass}">${escapeHtml(row.type || '-')}</span>
                        </div>
                        <div class="meta-muted">${escapeHtml(row.createdAt)} · ${escapeHtml(row.service)}</div>
                    </td>

                    <td>
                        <span class="op-badge ${escapeHtml(row.opTimeClass)}">
                            ${escapeHtml(row.opTimeLabel)}
                        </span>
                    </td>

                    <td>
                        <span class="status-badge ${escapeHtml(row.statusClass)}">
                            ${escapeHtml(row.statusLabel)}
                        </span>
                    </td>

                    ${areaCells}
                </tr>
            `;
        }).join('');

        tableContainer.className = 'table-shell';
        tableContainer.innerHTML = `
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Orden / tipo</th>
                            <th>Tiempo</th>
                            <th>Estado general</th>
                            ${areaHeaders}
                        </tr>
                    </thead>
                    <tbody>
                        ${bodyRows}
                    </tbody>
                </table>
            </div>

            <div class="table-pagination" aria-label="Paginación de órdenes">
                <div class="pagination-info">
                    Mostrando ${startIndex + 1}–${endIndex} de ${rows.length} órdenes
                </div>

                <div class="pagination-controls">
                    <button
                        type="button"
                        class="pagination-button"
                        data-page-action="previous"
                        ${currentPage === 1 ? 'disabled' : ''}
                    >
                        Anterior
                    </button>

                    <span class="page-indicator">Página ${currentPage} de ${totalPages}</span>

                    <button
                        type="button"
                        class="pagination-button"
                        data-page-action="next"
                        ${currentPage === totalPages ? 'disabled' : ''}
                    >
                        Siguiente
                    </button>
                </div>
            </div>
        `;

        const scrollArea = tableContainer.querySelector('.table-scroll');
        if (scrollArea) {
            scrollArea.scrollTop = 0;
            scrollArea.scrollLeft = 0;
        }
    }

    tableContainer.addEventListener('click', event => {
        const button = event.target.closest('[data-page-action]');

        if (!button || button.disabled || !currentPayload) {
            return;
        }

        const rows = currentPayload.rows || [];
        const totalPages = Math.max(1, Math.ceil(rows.length / ROWS_PER_PAGE));
        const action = button.dataset.pageAction;

        if (action === 'previous') {
            currentPage = Math.max(1, currentPage - 1);
        }

        if (action === 'next') {
            currentPage = Math.min(totalPages, currentPage + 1);
        }

        renderCurrentPage();
    });

    async function loadData() {
        try {
            const response = await fetch(dataUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const payload = await response.json();

            if (!payload.ok) {
                throw new Error(payload.message || 'No se pudo cargar la información');
            }

            renderTable(payload);

            lastUpdated.textContent = `Última actualización: ${payload.updated_at || 'Ahora'}`;

        } catch (error) {
            tableContainer.className = 'error-state';
            tableContainer.innerHTML = `
                <div>
                    <strong>Error al cargar datos</strong>
                    <span>${escapeHtml(error.message)}</span>
                </div>
            `;
        }
    }

    updateDateTime();
    loadData();

    setInterval(updateDateTime, 30000);
    setInterval(loadData, 60000);
</script>
</body>
</html>