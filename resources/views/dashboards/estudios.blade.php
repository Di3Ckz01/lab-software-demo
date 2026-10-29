@extends('layouts.app')

@section('title', 'Tablero de estudios | Hospital Dashboard')
@section('body-class', 'page-studies')

@section('head')
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/highcharts-more.js"></script>
@endsection

@section('styles')
<style>
    .dashboard-header {
        margin-bottom: 24px;
    }

    .dashboard-title {
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 6px;
        color: #111827;
    }

    .dashboard-subtitle {
        font-size: 15px;
        color: #6b7280;
    }

    .filter-card,
    .chart-card,
    .table-card,
    .kpi-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }

    .filter-card {
        margin-bottom: 24px;
    }

    .filter-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 18px;
        color: #111827;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        align-items: end;
    }

    .filter-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .filters-summary {
        margin-top: 16px;
        font-size: 14px;
        color: #4b5563;
    }

    .filters-summary span {
        display: inline-block;
        background: #eef2ff;
        color: #3730a3;
        padding: 6px 10px;
        border-radius: 999px;
        margin-right: 8px;
        margin-top: 8px;
    }

    .summary-notice {
        margin-top: 14px;
        padding: 11px 13px;
        border-radius: 12px;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1e40af;
        font-size: 13px;
        line-height: 1.45;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 18px;
        margin-bottom: 24px;
    }

    .kpi-label {
        color: #6b7280;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .kpi-value {
        font-size: 30px;
        font-weight: 700;
        color: #111827;
    }

    .kpi-helper {
        margin-top: 6px;
        font-size: 13px;
        color: #6b7280;
    }

    .chart-card,
    .table-card {
        margin-bottom: 24px;
    }

    #studiesChart {
        width: 100%;
        height: 460px;
    }

    #areasChart {
        width: 100%;
        height: 620px;
        min-height: 620px;
    }

    @media (max-width: 1400px) {
        #areasChart {
            height: 560px;
            min-height: 560px;
        }
    }

    @media (max-width: 992px) {
        #areasChart {
            height: 480px;
            min-height: 480px;
        }
    }

    .chart-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    .chart-grid .chart-card {
        margin-bottom: 0;
    }

    .chart-card-full {
        width: 100%;
    }

    .badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .badge-ordered {
        background: #e0f2fe;
        color: #075985;
    }

    .badge-repeat {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-result {
        background: #ede9fe;
        color: #5b21b6;
    }

    .badge-validated {
        background: #dcfce7;
        color: #166534;
    }

    .badge-unknown {
        background: #f3f4f6;
        color: #374151;
    }

    .empty-text {
        color: #9ca3af;
        font-style: italic;
    }

    .search-input {
        min-width: 220px;
    }

    /* =============================================== */
    /* Etiqueta central y tooltip de la gráfica polar */
    /* =============================================== */

    .center-label {
        width: 132px;
        height: 132px;
        box-sizing: border-box;
        padding: 14px 10px;
        border: 1px solid #dbeafe;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.98);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.16);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .center-label p {
        width: 100%;
        padding: 0;
        margin: 0;
        text-align: center;
        white-space: nowrap;
    }

    .center-label .symbol {
        width: 9px;
        height: 9px;
        margin: 0 auto 5px;
        border-radius: 50%;
        background: #2563eb;
        color: transparent;
        font-size: 0;
        line-height: 0;
        box-shadow: 0 0 0 5px rgba(37, 99, 235, 0.10);
    }

    .center-label .uppercase {
        max-width: 108px;
        overflow: hidden;
        text-overflow: ellipsis;
        text-transform: uppercase;
        font-size: 12px;
        line-height: 1.25;
        font-weight: 900;
        color: #0f172a;
    }

    .center-label .label {
        margin-top: 3px;
        text-transform: uppercase;
        font-size: 9px;
        line-height: 1.1;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.08em;
    }

    .center-label .percentage {
        margin-top: 4px;
        font-weight: 900;
        font-size: 25px;
        line-height: 1;
        color: #2563eb;
        letter-spacing: -0.03em;
    }

    .area-tooltip {
        width: 255px;
        box-sizing: border-box;
        padding: 13px 14px;
        border-radius: 10px;
        background: #ffffff;
        color: #0f172a;
        font-family: inherit;
        text-align: left;
    }

    .area-tooltip-title {
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 9px;
        margin-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 14px;
        line-height: 1.3;
        font-weight: 900;
        color: #0f172a;
        overflow-wrap: anywhere;
    }

    .area-tooltip-dot {
        width: 10px;
        height: 10px;
        flex: 0 0 10px;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.18);
    }

    .area-tooltip-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-top: 7px;
        font-size: 12px;
        line-height: 1.35;
        color: #475569;
    }

    .area-tooltip-key {
        flex: 1 1 auto;
        color: #64748b;
    }

    .area-tooltip-value {
        flex: 0 0 auto;
        max-width: 145px;
        text-align: right;
        font-weight: 800;
        color: #0f172a;
        overflow-wrap: anywhere;
    }

    .wheel-area-tooltip.highcharts-tooltip,
    .wheel-area-tooltip.highcharts-label {
        opacity: 1 !important;
        filter: none !important;
    }

    .highcharts-tooltip-container .area-tooltip,
    .highcharts-tooltip .area-tooltip {
        opacity: 1 !important;
        color: #0f172a !important;
    }

    /* Tooltip de una orden y sus estudios */
    .order-tooltip {
        width: 410px;
        max-width: min(410px, calc(100vw - 32px));
        box-sizing: border-box;
        padding: 14px;
        border-radius: 12px;
        background: #ffffff;
        color: #0f172a;
        font-family: inherit;
        text-align: left;
    }

    .order-tooltip-title {
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 14px;
        line-height: 1.3;
        font-weight: 900;
        color: #0f172a;
    }

    .order-tooltip-title strong {
        color: #1d4ed8;
        overflow-wrap: anywhere;
    }

    .order-tooltip-meta {
        display: grid;
        grid-template-columns: 72px minmax(0, 1fr);
        gap: 8px;
        margin-top: 6px;
        font-size: 12px;
        line-height: 1.35;
    }

    .order-tooltip-meta span {
        color: #64748b;
        font-weight: 700;
    }

    .order-tooltip-meta strong {
        color: #0f172a;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .order-tooltip-tests-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 12px;
        padding: 9px 10px;
        border-radius: 8px 8px 0 0;
        background: #eff6ff;
        color: #1e3a8a;
        font-size: 12px;
        font-weight: 900;
    }

    .order-tooltip-tests {
        max-height: 285px;
        overflow-y: auto;
        border: 1px solid #dbeafe;
        border-top: 0;
        border-radius: 0 0 8px 8px;
        background: #ffffff;
        scrollbar-width: thin;
    }

    .order-tooltip-test {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(80px, auto) minmax(54px, auto);
        gap: 10px;
        align-items: start;
        padding: 9px 10px;
        border-top: 1px solid #eef2f7;
        font-size: 11px;
        line-height: 1.35;
    }

    .order-tooltip-test.is-name-only {
        grid-template-columns: minmax(0, 1fr);
    }

    .order-tooltip-tests-title.is-name-only {
        justify-content: space-between;
    }

    .order-tooltip-test:first-child {
        border-top: 0;
    }

    .order-tooltip-test-name {
        color: #0f172a;
        font-weight: 400;
        overflow-wrap: anywhere;
    }

    .order-tooltip-test-result {
        color: #166534;
        font-weight: 900;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .order-tooltip-test-unit {
        color: #475569;
        font-weight: 700;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .order-tooltip-empty {
        padding: 12px;
        color: #64748b;
        font-size: 12px;
        text-align: center;
    }

    .wheel-area-tooltip.highcharts-tooltip,
    .wheel-area-tooltip.highcharts-label,
    .highcharts-tooltip-container {
        pointer-events: auto !important;
    }

    @media (max-width: 640px) {
        .order-tooltip {
            width: min(360px, calc(100vw - 24px));
        }

        .order-tooltip-test {
            grid-template-columns: minmax(0, 1fr);
            gap: 4px;
        }

        .order-tooltip-test-result,
        .order-tooltip-test-unit {
            text-align: left;
        }
    }

    /* ============================= */
    /* Tabla tipo DataTable propia */
    /* ============================= */

    .datatable-card {
        padding: 0;
        overflow: hidden;
    }

    .datatable-header {
        padding: 24px 24px 10px;
    }

    .datatable-title {
        font-size: 20px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 4px;
    }

    .datatable-subtitle {
        font-size: 14px;
        color: #64748b;
    }

    .datatable-controls {
        padding: 18px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .datatable-left,
    .datatable-right {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #64748b;
        font-size: 13px;
    }

    .datatable-select {
        width: auto;
        min-width: 78px;
        border: 1px solid #dbe3ef;
        border-radius: 10px;
        padding: 9px 10px;
        background: #ffffff;
        color: #0f172a;
        outline: none;
        font-size: 14px;
    }

    .datatable-search {
        width: 280px;
        border: 1px solid #dbe3ef;
        border-radius: 12px;
        padding: 11px 14px;
        font-size: 14px;
        outline: none;
        background: #ffffff;
    }

    .datatable-search:focus,
    .datatable-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .datatable-table-wrapper {
        padding: 0 24px;
        overflow-x: auto;
    }

    .datatable-table {
        width: 100%;
        border-collapse: collapse;
    }

    .datatable-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 15px 14px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    .datatable-table tbody td {
        padding: 14px;
        font-size: 14px;
        color: #0f172a;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }

    .datatable-table tbody tr:hover {
        background: #f8fbff;
    }

    .datatable-sort {
        border: none;
        background: transparent;
        padding: 0;
        margin: 0;
        color: inherit;
        font: inherit;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .datatable-sort span {
        color: #cbd5e1;
        font-size: 11px;
    }

    .datatable-footer {
        padding: 18px 24px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .datatable-info {
        color: #64748b;
        font-size: 13px;
    }

    .datatable-pagination {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .datatable-page-btn {
        min-width: 34px;
        height: 34px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        background: #ffffff;
        cursor: pointer;
        padding: 0 10px;
    }

    .datatable-page-btn:hover,
    .datatable-page-btn.active {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }

    .datatable-page-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        background: #ffffff;
        color: #94a3b8;
        border-color: #e2e8f0;
    }

    .datatable-id {
        font-size: 12px;
        color: #475569;
        max-width: 180px;
        word-break: break-all;
    }

    .datatable-study {
        min-width: 320px;
    }

    /* ============================= */
    /* Semáforo tiempo de oportunidad */
    /* ============================= */

    .op-time-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 96px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
        border: 1px solid transparent;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .op-time-icon {
        width: 20px;
        height: 20px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 900;
        background: rgba(255, 255, 255, 0.72);
        flex: 0 0 20px;
    }

    .op-time-ok {
        background: #dcfce7;
        color: #166534;
        border-color: #bbf7d0;
    }

    .op-time-warning {
        background: #fef9c3;
        color: #854d0e;
        border-color: #fde68a;
    }

    .op-time-danger {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fecaca;
    }

    .op-time-empty {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }




    .studies-dashboard-grid.studies-dashboard-grid-single {
        grid-template-columns: minmax(0, 1fr) !important;
    }

    .studies-dashboard-grid.studies-dashboard-grid-single .studies-monthly-card {
        width: 100%;
        min-width: 0;
    }

    .studies-area-content {
        display: grid;
        grid-template-columns: minmax(0, 1.18fr) minmax(290px, 0.52fr);
        gap: 20px;
        align-items: start;
        margin-top: 18px;
    }

    .studies-area-legend {
        display: flex;
        flex-direction: column;
        gap: 16px;
        max-height: 640px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .studies-area-sidebar {
        display: flex;
        flex-direction: column;
        gap: 14px;
        min-width: 0;
        max-height: 640px;
        overflow: hidden;
    }

    .studies-area-sidebar .studies-area-legend {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none;
    }

    .studies-area-empty {
        padding: 20px 18px;
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
        background: #f8fafc;
        color: #64748b;
        font-size: 14px;
        line-height: 1.5;
        text-align: center;
    }

    .studies-wheel-legend {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .studies-legend-section {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: linear-gradient(180deg, #fbfdff 0%, #f8fbff 100%);
        padding: 16px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .studies-legend-section h3 {
        margin: 0;
        font-size: 15px;
        line-height: 1.2;
        font-weight: 900;
        color: #0f172a;
    }

    .studies-legend-section p {
        margin: 6px 0 0;
        font-size: 12px;
        line-height: 1.45;
        color: #64748b;
    }

    .studies-semaphore-list,
    .studies-hourly-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 14px;
    }

    .studies-semaphore-card {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 14px;
        border: 1px solid #dbe4f0;
        background: #ffffff;
    }

    .studies-semaphore-card.is-green {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .studies-semaphore-card.is-yellow {
        border-color: #fde68a;
        background: #fffbeb;
    }

    .studies-semaphore-card.is-red {
        border-color: #fecaca;
        background: #fef2f2;
    }

    .studies-semaphore-card.is-unknown {
        border-color: #dbe3ef;
        background: #f8fafc;
    }

    .studies-semaphore-dot {
        width: 14px;
        height: 14px;
        border-radius: 999px;
        box-shadow: 0 0 0 5px rgba(148, 163, 184, 0.15);
    }

    .studies-semaphore-card.is-green .studies-semaphore-dot {
        background: #22c55e;
        box-shadow: 0 0 0 5px rgba(34, 197, 94, 0.15);
    }

    .studies-semaphore-card.is-yellow .studies-semaphore-dot {
        background: #f59e0b;
        box-shadow: 0 0 0 5px rgba(245, 158, 11, 0.15);
    }

    .studies-semaphore-card.is-red .studies-semaphore-dot {
        background: #ef4444;
        box-shadow: 0 0 0 5px rgba(239, 68, 68, 0.16);
    }

    .studies-semaphore-card.is-unknown .studies-semaphore-dot {
        background: #94a3b8;
        box-shadow: 0 0 0 5px rgba(148, 163, 184, 0.14);
    }

    .studies-semaphore-copy {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    .studies-semaphore-copy strong {
        font-size: 14px;
        color: #0f172a;
    }

    .studies-semaphore-copy small {
        font-size: 12px;
        color: #64748b;
    }

    .studies-semaphore-value {
        font-size: 22px;
        line-height: 1;
        font-weight: 900;
        color: #0f172a;
    }

    .studies-hourly-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }

    .studies-hourly-row strong {
        font-size: 13px;
        line-height: 1.35;
        color: #0f172a;
    }

    .studies-hourly-row span {
        font-size: 12px;
        font-weight: 800;
        color: #3159e8;
        white-space: nowrap;
    }

    .wheel-slot-label {
        display: inline-block;
        max-width: 84px;
        line-height: 1.25;
        text-align: center;
        white-space: normal;
    }

    .order-tooltip-area-block {
        border-top: 1px solid #eef2f7;
    }

    .order-tooltip-area-block:first-child {
        border-top: 0;
    }

    .order-tooltip-area-name {
        padding: 10px 10px 2px;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #1e3a8a;
    }

    #areasChart {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        background: #f8fafc;
    }

    .wheel-svg-stage {
        display: block;
        width: 100%;
        height: 100%;
        min-height: inherit;
        overflow: visible;
    }

    .wheel-svg-point {
        cursor: pointer;
        transition: r 0.12s ease, opacity 0.12s ease, filter 0.12s ease;
    }

    .wheel-svg-point:hover {
        opacity: 1;
        filter: drop-shadow(0 0 5px rgba(15, 23, 42, 0.25));
    }

    .wheel-svg-point.is-dimmed {
        opacity: 0.075;
        filter: grayscale(1);
    }

    .wheel-svg-point.is-highlighted {
        opacity: 1;
        stroke-width: 1.8;
        filter: drop-shadow(0 0 7px rgba(37, 99, 235, 0.42));
    }

    .wheel-svg-point.is-selected {
        opacity: 1;
        stroke: #0f172a;
        stroke-width: 2.4;
        filter: drop-shadow(0 0 9px rgba(15, 23, 42, 0.48));
    }

    .wheel-svg-hour-label {
        fill: #475569;
        font-size: 11px;
        font-weight: 900;
        paint-order: stroke;
        stroke: #f8fafc;
        stroke-width: 4px;
        stroke-linejoin: round;
    }

    .wheel-svg-ring-label-bg {
        fill: rgba(255, 255, 255, 0.96);
        stroke: #dbe4f0;
        stroke-width: 1;
        filter: drop-shadow(0 2px 5px rgba(15, 23, 42, 0.08));
    }

    .wheel-svg-ring-label {
        fill: #334155;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: -0.01em;
        pointer-events: none;
    }

    .wheel-svg-loading {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        background: rgba(248, 250, 252, 0.88);
        color: #1e40af;
        font-size: 14px;
        font-weight: 800;
        z-index: 5;
    }

    .wheel-svg-tooltip {
        position: fixed;
        display: none;
        width: min(440px, calc(100vw - 28px));
        z-index: 99999;
        pointer-events: auto;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.99);
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22);
    }

    .wheel-svg-tooltip .order-tooltip {
        width: 100%;
        max-width: none;
    }

    .order-tooltip-area-toggle {
        width: 100%;
        min-height: 38px;
        display: grid;
        grid-template-columns: 26px minmax(0, 1fr);
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        border: 0;
        border-bottom: 1px solid #dbeafe;
        background: #eff6ff;
        color: #1e3a8a;
        text-align: left;
        cursor: pointer;
        transition: background .15s ease;
    }

    .order-tooltip-area-toggle:hover,
    .order-tooltip-area-toggle:focus-visible {
        background: #dbeafe;
        outline: none;
    }

    .order-tooltip-area-name {
        padding: 0;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: inherit;
    }

    .area-expand-icon {
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #bfdbfe;
        border-radius: 7px;
        background: #ffffff;
        color: #1d4ed8;
        font-size: 17px;
        line-height: 1;
        font-weight: 800;
    }

    .order-tooltip-area-toggle[aria-expanded="true"] {
        background: #dbeafe;
    }

    .order-tooltip-area-toggle[aria-expanded="true"] .area-expand-icon {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }

    .order-tooltip-area-studies {
        background: #ffffff;
    }

    .order-tooltip-area-studies[hidden] {
        display: none !important;
    }

    .order-tooltip-profile {
        border-top: 1px solid #eef2f7;
        background: #ffffff;
    }

    .order-tooltip-profile:first-child {
        border-top: 0;
    }

    .order-tooltip-profile-name {
        min-height: 38px;
        padding: 9px 12px;
        color: #0f172a;
        font-size: 12px;
        line-height: 1.45;
        font-weight: 400;
        overflow-wrap: anywhere;
    }

    .order-tooltip-profile-tests {
        margin: 0 10px 9px 18px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        background: #f8fafc;
    }

    .order-tooltip-study {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        gap: 8px;
        padding: 7px 9px;
        border-top: 1px solid #e2e8f0;
        color: #334155;
        font-size: 10.5px;
        line-height: 1.35;
    }

    .order-tooltip-study:first-child {
        border-top: 0;
    }

    .order-tooltip-study-name {
        font-weight: 400;
        overflow-wrap: anywhere;
    }

    .order-tooltip-study-result {
        color: #166534;
        font-weight: 700;
        text-align: right;
    }

    .order-tooltip-study-unit {
        color: #64748b;
        text-align: right;
    }

    .order-tooltip-study-empty {
        padding: 8px 9px;
        color: #64748b;
        font-size: 10.5px;
        font-weight: 400;
    }

    .studies-wheel-search-panel {
        display: none;
        grid-template-columns: minmax(320px, 1fr) auto;
        align-items: end;
        gap: 14px;
        margin: 12px 0 4px;
        padding: 14px;
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%);
    }

    .studies-area-card.is-expanded .studies-wheel-search-panel {
        display: grid;
    }

    .studies-wheel-search-field label {
        display: block;
        margin-bottom: 6px;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
    }

    .studies-wheel-search-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .studies-wheel-search-input {
        flex: 1 1 auto;
        min-width: 0;
        height: 40px;
        padding: 0 13px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #ffffff;
        color: #0f172a;
        font-size: 13px;
        outline: none;
    }

    .studies-wheel-search-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .studies-wheel-search-feedback {
        min-width: 250px;
        max-width: 420px;
        padding: 9px 11px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.82);
        color: #475569;
        font-size: 12px;
        line-height: 1.4;
        font-weight: 700;
    }

    .studies-wheel-search-feedback.is-success {
        background: #ecfdf5;
        color: #166534;
    }

    .studies-wheel-search-feedback.is-warning {
        background: #fffbeb;
        color: #92400e;
    }

    .studies-wheel-search-feedback.is-error {
        background: #fff1f2;
        color: #9f1239;
    }

    .wheel-selected-order-panel {
        flex: 0 0 auto;
        max-height: 52%;
        overflow-y: auto;
        border: 1px solid #bfdbfe;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.10);
    }

    .wheel-selected-order-panel[hidden] {
        display: none !important;
    }

    .wheel-selected-order-head {
        position: sticky;
        top: 0;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 13px;
        border-bottom: 1px solid #dbeafe;
        background: #eff6ff;
    }

    .wheel-selected-order-head strong {
        color: #1e3a8a;
        font-size: 13px;
        overflow-wrap: anywhere;
    }

    .wheel-selected-order-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 0 0 auto;
    }

    .wheel-selected-order-action {
        min-height: 30px;
        padding: 5px 9px;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: #ffffff;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 900;
        cursor: pointer;
    }

    .wheel-selected-order-action:hover,
    .wheel-selected-order-action:focus-visible {
        border-color: #2563eb;
        background: #dbeafe;
        outline: none;
    }

    .wheel-selected-order-body {
        padding: 12px;
    }

    .wheel-selected-order-body .order-tooltip-tests {
        max-height: none;
    }

    .wheel-selected-order-panel .order-tooltip-area-name {
        padding: 10px 10px 6px;
        border-bottom: 1px solid #dbeafe;
        background: #eff6ff;
    }

    .wheel-toolbar {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 10px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
    }

    .wheel-toolbar-copy {
        color: #64748b;
        font-size: 13px;
        line-height: 1.5;
    }

    .wheel-controls {
        display: flex;
        align-items: end;
        gap: 10px;
        flex-wrap: wrap;
    }

    .wheel-controls .form-group {
        margin: 0;
        min-width: 190px;
    }

    .wheel-status {
        display: flex;
        align-items: center;
        gap: 9px;
        min-height: 38px;
        margin: 10px 0 2px;
        padding: 9px 12px;
        border-radius: 12px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        font-size: 13px;
        font-weight: 700;
    }

    .wheel-status.is-success {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #166534;
    }

    .wheel-status.is-error {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #9f1239;
    }

    .wheel-status.is-warning {
        background: #fffbeb;
        border-color: #fde68a;
        color: #92400e;
    }

    .wheel-spinner {
        width: 15px;
        height: 15px;
        border: 2px solid rgba(37, 99, 235, 0.22);
        border-top-color: currentColor;
        border-radius: 999px;
        animation: wheelSpin 0.75s linear infinite;
        flex: 0 0 15px;
    }

    @keyframes wheelSpin {
        to { transform: rotate(360deg); }
    }

    @media (max-width: 1200px) {
        .filter-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .chart-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 1200px) {
        .studies-area-content {
            grid-template-columns: 1fr;
        }

        .studies-area-sidebar {
            max-height: none;
            overflow: visible;
        }

        .studies-area-legend {
            max-height: none;
        }
    }

    @media (max-width: 768px) {
        .wheel-toolbar,
        .wheel-controls {
            flex-direction: column;
            align-items: stretch;
        }

        .wheel-controls .form-group {
            width: 100%;
        }

        .studies-wheel-search-panel {
            grid-template-columns: 1fr;
        }

        .studies-wheel-search-row {
            flex-wrap: wrap;
        }

        .studies-wheel-search-input {
            flex-basis: 100%;
        }

        .studies-wheel-search-feedback {
            min-width: 0;
            max-width: none;
        }

        .filter-grid,
        .kpi-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-title {
            font-size: 24px;
        }

        .filter-actions,
        .datatable-controls,
        .datatable-footer {
            flex-direction: column;
            align-items: stretch;
        }

        .datatable-left,
        .datatable-right {
            width: 100%;
        }

        .datatable-search {
            width: 100%;
        }
    }


    /* Ajuste paso 8: rueda expandible y puntos por área */
    .studies-expand-wheel-button {
        min-height: 34px !important;
        padding-inline: 12px !important;
        white-space: nowrap !important;
    }

    .studies-expand-wheel-button svg {
        width: 15px !important;
        height: 15px !important;
    }

    .page-studies.wheel-expanded-active {
        overflow: hidden !important;
    }

    .page-studies .studies-area-card.is-expanded {
        position: fixed !important;
        inset: 76px 28px 26px calc(var(--inst-sidebar) + 28px) !important;
        z-index: 800 !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        box-shadow: 0 28px 80px rgba(3, 27, 70, .28) !important;
    }

    .page-studies .studies-area-card.is-expanded .studies-area-content {
        flex: 1 1 auto !important;
        grid-template-columns: minmax(520px, 1.25fr) minmax(280px, .55fr) !important;
        min-height: 0 !important;
    }

    .page-studies .studies-area-card.is-expanded #areasChart {
        height: calc(100vh - 330px) !important;
        min-height: 430px !important;
    }

    .page-studies .studies-area-card.is-expanded .studies-area-sidebar {
        max-height: calc(100vh - 330px) !important;
    }

    .page-studies .studies-area-card.is-expanded .studies-area-legend {
        max-height: none !important;
    }

    .page-studies .studies-area-card.is-expanded .studies-expand-wheel-button {
        color: #fff !important;
        border-color: var(--inst-blue-600) !important;
        background: linear-gradient(135deg, #2478fa, #0d5fe8) !important;
    }

    .page-studies .studies-wheel-hint {
        margin-top: 4px;
        color: #8a97aa;
        font-size: 8px;
        font-weight: 700;
    }

    @media (max-width: 980px) {
        .page-studies .studies-area-card.is-expanded {
            inset: 68px 14px 18px 14px !important;
        }

        .page-studies .studies-area-card.is-expanded .studies-area-content {
            grid-template-columns: 1fr !important;
        }
    }

</style>
@endsection

@section('content')
@php
    $orderedPercent = $totalStudies > 0 ? round(($pendingStudies / $totalStudies) * 100, 1) : 0;
    $validatedPercent = $totalStudies > 0 ? round(($validatedStudies / $totalStudies) * 100, 1) : 0;
@endphp

<div class="dashboard-header studies-page-header">
    <div>
        <h1 class="dashboard-title">Tablero de estudios</h1>
        <p class="dashboard-subtitle">Vista operativa de estudios registrados por área, prueba y estatus.</p>
    </div>
</div>

@if (!empty($error))
    <div class="alert-error">{{ $error }}</div>
@endif

<section class="filter-card studies-filter-card">
    <div class="studies-section-heading">
        <span class="studies-heading-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 5h16l-6 7v5l-4 2v-7z"/></svg>
        </span>
        <div>
            <h2>Filtros de estudios</h2>
            <p>Refina la información mostrada en el tablero.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('dashboards.estudios') }}">
        <div class="filter-grid studies-filter-grid">
            <div class="filter-group">
                <label for="year">Año</label>
                <select name="year" id="year">
                    @foreach ($availableYears as $yearOption)
                        <option value="{{ $yearOption }}" @selected((int)$year === (int)$yearOption)>{{ $yearOption }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label for="month">Mes</label>
                <select name="month" id="month">
                    <option value="">Todos</option>
                    @foreach ($months as $monthNumber => $monthName)
                        <option value="{{ $monthNumber }}" @selected((string)$selectedMonth === (string)$monthNumber)>{{ $monthName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label for="area">Área</label>
                <select name="area" id="area">
                    <option value="">Todas</option>
                    @foreach ($availableAreas as $areaOption)
                        <option value="{{ $areaOption }}" @selected($selectedArea === $areaOption)>{{ $areaLabels[$areaOption] ?? $areaOption }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label for="status">Estatus</label>
                <select name="status" id="status">
                    <option value="">Todos</option>
                    @foreach ($availableStatuses as $statusCode => $statusLabel)
                        <option value="{{ $statusCode }}" @selected((string)$selectedStatus === (string)$statusCode)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group studies-search-group">
                <label for="studiesTableSearch">Buscar</label>
                <div class="studies-search-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                    <input
                        type="search"
                        id="studiesTableSearch"
                        placeholder="Buscar estudio, prueba u orden"
                        autocomplete="off"
                        onkeydown="if(event.key === 'Enter'){ event.preventDefault(); }"
                    >
                </div>
            </div>

            <div class="filter-actions studies-filter-actions">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                    <span>Filtrar</span>
                </button>
                <a href="{{ route('dashboards.estudios') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
                    <span>Limpiar</span>
                </a>
            </div>
        </div>
    </form>
</section>

<section class="kpi-grid studies-kpi-grid" aria-label="Indicadores de estudios">
    <article class="kpi-card studies-kpi-card studies-kpi-blue">
        <span class="studies-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 4h10v16H7zM9 2h6v4H9zM10 10h4M10 14h4"/></svg></span>
        <div class="studies-kpi-copy">
            <div class="kpi-label">Total estudios</div>
            <div class="kpi-value">{{ number_format($totalStudies) }}</div>
            <div class="kpi-helper">100% del total</div>
        </div>
    </article>

    <article class="kpi-card studies-kpi-card studies-kpi-purple">
        <span class="studies-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 12 12 4h7a1 1 0 0 1 1 1v7l-8 8-8-8Z"/><circle cx="15.5" cy="8.5" r="1"/></svg></span>
        <div class="studies-kpi-copy">
            <div class="kpi-label">Órdenes únicas</div>
            <div class="kpi-value">{{ number_format($uniqueOrders) }}</div>
            <div class="kpi-helper">100% del total</div>
        </div>
    </article>

    <article class="kpi-card studies-kpi-card studies-kpi-green">
        <span class="studies-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 18 9 13l3 3 7-8M18 8h1v5"/></svg></span>
        <div class="studies-kpi-copy">
            <div class="kpi-label">Promedio por orden</div>
            <div class="kpi-value">{{ number_format($averageStudiesPerOrder, 2) }}</div>
            <div class="kpi-helper">Estudios por orden</div>
        </div>
    </article>

    <article class="kpi-card studies-kpi-card studies-kpi-ordered">
        <span class="studies-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 3h6m-5 0v5l-5 9a2 2 0 0 0 1.7 3h10.6A2 2 0 0 0 19 17l-5-9V3M8 14h8"/></svg></span>
        <div class="studies-kpi-copy">
            <div class="kpi-label">Ordenadas</div>
            <div class="kpi-value">{{ number_format($pendingStudies) }}</div>
            <div class="kpi-helper">{{ number_format($orderedPercent, 1) }}% del total</div>
            <div class="studies-kpi-progress"><span style="width: {{ min(100, $orderedPercent) }}%"></span></div>
        </div>
    </article>

    <article class="kpi-card studies-kpi-card studies-kpi-validated">
        <span class="studies-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg></span>
        <div class="studies-kpi-copy">
            <div class="kpi-label">Validadas</div>
            <div class="kpi-value">{{ number_format($validatedStudies) }}</div>
            <div class="kpi-helper">{{ number_format($validatedPercent, 1) }}% del total</div>
            <div class="studies-kpi-progress"><span style="width: {{ min(100, $validatedPercent) }}%"></span></div>
        </div>
    </article>
</section>

<section class="studies-dashboard-grid studies-dashboard-grid-single">
    <article class="chart-card studies-monthly-card">
        <div class="studies-card-header">
            <div>
                <h2>Estudios y órdenes por mes</h2>
                <p>Comparativo mensual de estudios procesados y órdenes únicas.</p>
            </div>
        </div>
        <div id="studiesChart" aria-label="Comparativo mensual de estudios y órdenes"></div>
    </article>
</section>

<section class="table-card datatable-card studies-table-card">
    <div class="studies-table-heading">
        <div>
            <h2>Últimos registros de estudios</h2>
            <p>Detalle de los estudios más recientes registrados en el sistema.</p>
        </div>
        <select id="studiesPerPage" class="studies-hidden-control" aria-label="Registros por página">
            <option value="5" selected>5</option>
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>

    <div class="datatable-table-wrapper studies-table-wrapper">
        <table id="studiesDataTable" class="datatable-table studies-data-table">
            <colgroup>
                <col class="studies-col-order">
                <col class="studies-col-area">
                <col class="studies-col-study">
                <col class="studies-col-time">
                <col class="studies-col-validated">
                <col class="studies-col-status">
            </colgroup>
            <thead>
                <tr>
                    <th><button type="button" class="datatable-sort" data-column="0">Orden <span>↕</span></button></th>
                    <th><button type="button" class="datatable-sort" data-column="1">Área <span>↕</span></button></th>
                    <th><button type="button" class="datatable-sort" data-column="2">Estudio <span>↕</span></button></th>
                    <th><button type="button" class="datatable-sort" data-column="3">Tiempo de oportunidad <span>↕</span></button></th>
                    <th><button type="button" class="datatable-sort" data-column="4">Validado <span>↕</span></button></th>
                    <th><button type="button" class="datatable-sort" data-column="5">Estatus <span>↕</span></button></th>
                </tr>
            </thead>
            <tbody id="studiesTableBody">
                <tr><td colspan="6"><div class="global-empty-inline">Cargando últimos registros de estudios...</div></td></tr>
            </tbody>
        </table>
    </div>

    <div class="datatable-footer studies-table-footer">
        <div id="studiesTableInfo" class="datatable-info">Preparando registros...</div>
        <div id="studiesPagination" class="datatable-pagination"></div>
    </div>
</section>

@endsection

@section('scripts')
<script>
    const categories = @json($categories);
    const rawSeries = @json($series);
    const wheelDataUrl = @json(route('dashboards.estudios.rueda-data'));
    const paginatedTestsDataUrl = @json(route('dashboards.estudios.paginated-tests-data'));
    const loginUrl = @json(route('login'));
    const selectedMonthIndex = Number(@json($selectedMonth ?: 0)) - 1;

    const studiesSeries = rawSeries.find(item => item.name === 'Estudios') || {
        name: 'Estudios',
        data: []
    };

    const ordersSeries = rawSeries.find(item => item.name === 'Órdenes únicas') || {
        name: 'Órdenes únicas',
        data: []
    };

    const areaColors = [
        '#2563eb',
        '#14b8a6',
        '#8b5cf6',
        '#f97316',
        '#ec4899',
        '#22c55e',
        '#f59e0b',
        '#06b6d4',
        '#64748b',
        '#ef4444'
    ];

    let studiesWheelChart = null;
    let lastStudiesWheelPayload = null;
    let lastWheelOrderEntries = [];
    let wheelSearchMatchKeys = new Set();
    let selectedWheelOrderKey = null;
    let wheelZoomAnimationFrame = null;

    document.addEventListener('DOMContentLoaded', function () {
        const studiesChartData = (studiesSeries.data || []).map((value, index) => ({
            y: Number(value || 0),
            color: index === selectedMonthIndex ? '#3159e8' : '#8eb4f5'
        }));

        Highcharts.chart('studiesChart', {
            chart: {
                zoomType: 'xy',
                spacing: [8, 8, 0, 0]
            },
            title: { text: null },
            subtitle: { text: null },
            xAxis: {
                categories: categories,
                crosshair: true,
                lineColor: '#dce5f0',
                tickLength: 0,
                labels: { style: { fontSize: '9px', color: '#52627e' } }
            },
            yAxis: [
                {
                    min: 0,
                    title: { text: 'Estudios' },
                    gridLineColor: '#edf1f6'
                },
                {
                    min: 0,
                    title: { text: 'Órdenes' },
                    opposite: true,
                    gridLineWidth: 0
                }
            ],
            tooltip: {
                shared: true,
                useHTML: true,
                headerFormat: '<div style="font-weight:800;margin-bottom:6px">{point.key}</div>',
                pointFormat: '<div style="display:flex;gap:10px;justify-content:space-between"><span style="color:{series.color}">● {series.name}</span><strong>{point.y:,.0f}</strong></div>'
            },
            legend: {
                align: 'center',
                verticalAlign: 'top',
                y: -4,
                symbolRadius: 6,
                itemDistance: 22
            },
            plotOptions: {
                column: {
                    borderWidth: 0,
                    borderRadius: 3,
                    maxPointWidth: 24,
                    pointPadding: 0.17,
                    groupPadding: 0.12,
                    dataLabels: { enabled: false }
                },
                spline: {
                    color: '#16a35f',
                    marker: {
                        enabled: true,
                        radius: 3,
                        fillColor: '#ffffff',
                        lineColor: '#16a35f',
                        lineWidth: 2
                    },
                    lineWidth: 2.5
                }
            },
            series: [
                {
                    name: 'Estudios',
                    type: 'column',
                    data: studiesChartData,
                    yAxis: 0,
                    color: '#8eb4f5'
                },
                {
                    name: 'Órdenes',
                    type: 'spline',
                    data: ordersSeries.data,
                    yAxis: 1,
                    color: '#16a35f'
                }
            ]
        });

        initStudiesTable();
    });


    function initStudiesWheelExpand() {
        const card = document.querySelector('.studies-area-card');
        const button = document.getElementById('expandWheelButton');

        if (!card || !button) {
            return;
        }

        const label = button.querySelector('span');

        function setExpanded(isExpanded) {
            card.classList.toggle('is-expanded', isExpanded);
            document.body.classList.toggle('wheel-expanded-active', isExpanded);
            if (label) {
                label.textContent = isExpanded ? 'Contraer' : 'Expandir';
            }

            if (!isExpanded) {
                resetWheelSvgView(true);
            }

            setTimeout(function () {
                if (studiesWheelChart) {
                    studiesWheelChart.reflow();
                }
            }, 80);
        }

        button.addEventListener('click', function () {
            setExpanded(!card.classList.contains('is-expanded'));
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && card.classList.contains('is-expanded')) {
                setExpanded(false);
            }
        });
    }


    function initStudiesWheelSearch() {
        const input = document.getElementById('wheelOrderSearch');
        const searchButton = document.getElementById('wheelOrderSearchButton');
        const clearButton = document.getElementById('wheelOrderClearButton');
        const resetViewButton = document.getElementById('wheelResetViewButton');

        if (!input || !searchButton || !clearButton || !resetViewButton) {
            return;
        }

        const runSearch = () => searchWheelOrder(input.value);

        searchButton.addEventListener('click', runSearch);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                runSearch();
            }
        });

        clearButton.addEventListener('click', function () {
            clearWheelOrderFocus({ clearInput: true, hideDetail: true, resetView: true });
            input.focus();
        });

        resetViewButton.addEventListener('click', function () {
            resetWheelSvgView(true);
        });
    }

    function searchWheelOrder(rawQuery) {
        const query = normalizeWheelOrderKey(rawQuery);

        if (!query) {
            setWheelSearchFeedback('Escribe un número de orden para realizar la búsqueda.', 'warning');
            return;
        }

        const matches = lastWheelOrderEntries.filter(order => {
            return normalizeWheelOrderKey(order.orderNumber).includes(query);
        });

        if (matches.length === 0) {
            wheelSearchMatchKeys = new Set();
            selectedWheelOrderKey = null;
            applyWheelPointState();
            hideWheelSelectedOrderPanel();
            resetWheelSvgView(true);
            setWheelSearchFeedback(`No se encontró una orden que coincida con “${String(rawQuery).trim()}”.`, 'error');
            return;
        }

        wheelSearchMatchKeys = new Set(
            matches.map(order => normalizeWheelOrderKey(order.orderNumber))
        );

        const exactMatch = matches.find(order => normalizeWheelOrderKey(order.orderNumber) === query);
        const singleMatch = exactMatch || (matches.length === 1 ? matches[0] : null);

        if (singleMatch) {
            selectedWheelOrderKey = normalizeWheelOrderKey(singleMatch.orderNumber);
            applyWheelPointState();
            renderWheelSelectedOrder(singleMatch);
            focusWheelOrderByKey(selectedWheelOrderKey, true);
            setWheelSearchFeedback(`Orden ${singleMatch.orderNumber} localizada. Se resaltó el punto y se acercó la vista.`, 'success');
            return;
        }

        selectedWheelOrderKey = null;
        hideWheelSelectedOrderPanel();
        applyWheelPointState();
        resetWheelSvgView(true);
        setWheelSearchFeedback(
            `${Highcharts.numberFormat(matches.length, 0)} órdenes coinciden con la búsqueda. Completa el número para acercarte a una sola orden.`,
            'warning'
        );
    }

    function normalizeWheelOrderKey(value) {
        return String(value ?? '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '');
    }

    function setWheelSearchFeedback(message, type = '') {
        const feedback = document.getElementById('wheelSearchFeedback');

        if (!feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.classList.remove('is-success', 'is-warning', 'is-error');

        if (type) {
            feedback.classList.add(`is-${type}`);
        }
    }

    function clearWheelOrderFocus({ clearInput = false, hideDetail = true, resetView = true } = {}) {
        wheelSearchMatchKeys = new Set();
        selectedWheelOrderKey = null;
        applyWheelPointState();

        if (clearInput) {
            const input = document.getElementById('wheelOrderSearch');
            if (input) {
                input.value = '';
            }
        }

        if (hideDetail) {
            hideWheelSelectedOrderPanel();
        }

        if (resetView) {
            resetWheelSvgView(true);
        }

        setWheelSearchFeedback(
            'Escribe el número de orden. La coincidencia quedará resaltada y el resto de los puntos se atenuará.'
        );
    }

    function applyWheelPointState(svg = null) {
        const stage = svg || document.querySelector('#areasChart .wheel-svg-stage');

        if (!stage) {
            return;
        }

        const hasFilter = wheelSearchMatchKeys.size > 0;

        stage.querySelectorAll('.wheel-svg-point').forEach(point => {
            const key = point.getAttribute('data-order-key') || '';
            const isMatch = hasFilter && wheelSearchMatchKeys.has(key);
            const isSelected = selectedWheelOrderKey !== null && key === selectedWheelOrderKey;

            point.classList.toggle('is-dimmed', hasFilter && !isMatch);
            point.classList.toggle('is-highlighted', isMatch);
            point.classList.toggle('is-selected', isSelected);
        });
    }

    function selectWheelOrder(order, point, semaphorePalette) {
        const key = normalizeWheelOrderKey(order.orderNumber);
        const card = document.querySelector('.studies-area-card');
        const expandButton = document.getElementById('expandWheelButton');
        const needsExpansion = card && !card.classList.contains('is-expanded');

        selectedWheelOrderKey = key;
        wheelSearchMatchKeys = new Set([key]);

        const input = document.getElementById('wheelOrderSearch');
        if (input) {
            input.value = order.orderNumber || '';
        }

        applyWheelPointState(point?.ownerSVGElement || null);
        renderWheelSelectedOrder(order, semaphorePalette);
        setWheelSearchFeedback(`Orden ${order.orderNumber} seleccionada. Se acercó la vista y se mostró el detalle completo.`, 'success');
        hideWheelSvgTooltip();

        if (needsExpansion && expandButton) {
            expandButton.click();
            window.setTimeout(() => focusWheelOrderByKey(key, true), 180);
        } else {
            focusWheelPoint(point, true);
        }
    }

    function focusWheelOrderByKey(orderKey, animate = true) {
        const stage = document.querySelector('#areasChart .wheel-svg-stage');

        if (!stage) {
            return;
        }

        const point = Array.from(stage.querySelectorAll('.wheel-svg-point')).find(item => {
            return (item.getAttribute('data-order-key') || '') === orderKey;
        });

        if (point) {
            focusWheelPoint(point, animate);
        }
    }

    function focusWheelPoint(point, animate = true) {
        if (!point || !point.ownerSVGElement) {
            return;
        }

        const svg = point.ownerSVGElement;
        const baseWidth = Number(svg.getAttribute('data-base-width') || 0);
        const baseHeight = Number(svg.getAttribute('data-base-height') || 0);
        const pointX = Number(point.getAttribute('cx') || 0);
        const pointY = Number(point.getAttribute('cy') || 0);

        if (!baseWidth || !baseHeight || !Number.isFinite(pointX) || !Number.isFinite(pointY)) {
            return;
        }

        const zoomFactor = 4.1;
        const targetWidth = baseWidth / zoomFactor;
        const targetHeight = baseHeight / zoomFactor;
        const targetX = Math.max(0, Math.min(baseWidth - targetWidth, pointX - (targetWidth / 2)));
        const targetY = Math.max(0, Math.min(baseHeight - targetHeight, pointY - (targetHeight / 2)));
        const target = [targetX, targetY, targetWidth, targetHeight];

        if (animate) {
            animateWheelSvgViewBox(svg, target, 420);
        } else {
            svg.setAttribute('viewBox', target.join(' '));
        }
    }

    function resetWheelSvgView(animate = true) {
        const svg = document.querySelector('#areasChart .wheel-svg-stage');

        if (!svg) {
            return;
        }

        const width = Number(svg.getAttribute('data-base-width') || 0);
        const height = Number(svg.getAttribute('data-base-height') || 0);

        if (!width || !height) {
            return;
        }

        const target = [0, 0, width, height];

        if (animate) {
            animateWheelSvgViewBox(svg, target, 360);
        } else {
            svg.setAttribute('viewBox', target.join(' '));
        }
    }

    function animateWheelSvgViewBox(svg, target, duration = 400) {
        if (!svg) {
            return;
        }

        if (wheelZoomAnimationFrame !== null) {
            window.cancelAnimationFrame(wheelZoomAnimationFrame);
            wheelZoomAnimationFrame = null;
        }

        const current = String(svg.getAttribute('viewBox') || '')
            .split(/\s+/)
            .map(Number);
        const source = current.length === 4 && current.every(Number.isFinite)
            ? current
            : target;
        const startedAt = performance.now();

        const frame = now => {
            const progress = Math.min(1, (now - startedAt) / duration);
            const eased = progress < 0.5
                ? 4 * progress * progress * progress
                : 1 - Math.pow(-2 * progress + 2, 3) / 2;
            const values = source.map((value, index) => {
                return value + ((target[index] - value) * eased);
            });

            svg.setAttribute('viewBox', values.join(' '));

            if (progress < 1) {
                wheelZoomAnimationFrame = window.requestAnimationFrame(frame);
            } else {
                wheelZoomAnimationFrame = null;
            }
        };

        wheelZoomAnimationFrame = window.requestAnimationFrame(frame);
    }

    function initStudiesWheel() {
        const dateInput = document.getElementById('wheelDate');
        const loadButton = document.getElementById('loadWheelButton');

        if (!dateInput || !loadButton) {
            return;
        }

        const load = () => loadStudiesWheel(dateInput.value);

        loadButton.addEventListener('click', load);
        dateInput.addEventListener('change', load);

        load();
    }

    async function loadStudiesWheel(date) {
        const status = document.getElementById('wheelStatus');
        const loadButton = document.getElementById('loadWheelButton');

        if (!date) {
            setWheelStatus('Selecciona una fecha válida.', 'error');
            return;
        }

        loadButton.disabled = true;
        setWheelStatus('Consultando órdenes y armando la rueda por horario...', 'loading');

        if (studiesWheelChart) {
            studiesWheelChart.showLoading('Cargando distribución...');
        }

        try {
            const url = new URL(wheelDataUrl, window.location.origin);
            url.searchParams.set('date', date);

            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            const payload = await response.json().catch(() => null);

            if (response.status === 401 || payload?.sessionExpired) {
                window.location.href = payload?.redirect || loginUrl;
                return;
            }

            if (!response.ok || !payload?.success) {
                throw new Error(payload?.message || 'No se pudo cargar la rueda de tiempos.');
            }

            renderStudiesWheel(payload);

            const requestedDate = payload.requestedDate || payload.date || date;
            const dataDate = payload.dataDate || requestedDate;
            const totalVisibleOrders = Array.isArray(payload.orders)
                ? payload.orders.length
                : Number(payload.totalOrders ?? payload.total ?? 0);

            if (payload.dateMismatch && dataDate) {
                setWheelStatus(
                    `${Highcharts.numberFormat(totalVisibleOrders, 0)} órdenes recibidas. Se consultó ${formatWheelDate(requestedDate)}, pero la API devolvió información correspondiente a ${formatWheelDate(dataDate)}.`,
                    'warning'
                );
            } else {
                setWheelStatus(
                    `${Highcharts.numberFormat(totalVisibleOrders, 0)} órdenes visualizadas en la rueda para ${formatWheelDate(dataDate)}.`,
                    'success'
                );
            }
        } catch (error) {
            renderStudiesWheel({ total: 0, areas: [], date });
            setWheelStatus(error.message || 'No se pudo cargar la rueda de tiempos.', 'error');
        } finally {
            loadButton.disabled = false;
        }
    }

    function renderStudiesWheel(payload) {
        lastStudiesWheelPayload = payload;

        const semaphorePalette = {
            green: {
                label: 'Verde',
                range: '≤ 60 min',
                color: '#22c55e',
                soft: '#dcfce7',
                accent: '#166534'
            },
            yellow: {
                label: 'Amarillo',
                range: '61 a 90 min',
                color: '#f59e0b',
                soft: '#fef3c7',
                accent: '#92400e'
            },
            red: {
                label: 'Rojo',
                range: '> 90 min',
                color: '#ef4444',
                soft: '#fee2e2',
                accent: '#991b1b'
            },
            unknown: {
                label: 'Sin dato',
                range: 'No disponible',
                color: '#94a3b8',
                soft: '#f1f5f9',
                accent: '#475569'
            }
        };

        const orderEntries = Array.isArray(payload.orders)
            ? payload.orders.map(item => ({
                orderNumber: String(item.orderNumber || 'Sin número'),
                appointmentTime: String(item.appointmentTime || ''),
                hisTime: String(item.hisTime || ''),
                hourSlot: String(item.hourSlot || ''),
                turnaroundMinutes: item.turnaroundMinutes === null || item.turnaroundMinutes === undefined || item.turnaroundMinutes === ''
                    ? null
                    : Number(item.turnaroundMinutes),
                opTimeStatus: item.opTimeStatus === null || item.opTimeStatus === undefined || item.opTimeStatus === ''
                    ? null
                    : Number(item.opTimeStatus),
                segHours: item.segHours === null || item.segHours === undefined || item.segHours === ''
                    ? null
                    : Number(item.segHours),
                semaphore: resolveWheelSemaphore(item),
                areas: Array.isArray(item.areas) ? item.areas : [],
                areaNames: Array.isArray(item.areaNames) ? item.areaNames : [],
                profileNames: Array.isArray(item.profileNames) ? item.profileNames : [],
                testsCount: Number(item.testsCount || 0),
                tests: Array.isArray(item.tests) ? item.tests : []
            }))
            : [];

        lastWheelOrderEntries = orderEntries;
        wheelSearchMatchKeys = new Set();
        selectedWheelOrderKey = null;

        const searchInput = document.getElementById('wheelOrderSearch');
        if (searchInput) {
            searchInput.value = '';
        }
        hideWheelSelectedOrderPanel();
        setWheelSearchFeedback(
            'Escribe el número de orden. La coincidencia quedará resaltada y el resto de los puntos se atenuará.'
        );

        const slotDefinitions = buildWheelHourSlots(orderEntries);
        const semaphoreSummary = buildWheelSemaphoreSummary(
            orderEntries,
            payload.semaphoreSummary || {}
        );
        const totalOrders = orderEntries.length;
        const totalProfiles = Number(payload.totalProfiles ?? payload.totalTests ?? 0);

        renderWheelLegend({
            slotDefinitions,
            semaphoreSummary,
            semaphorePalette,
            totalOrders,
            totalProfiles
        });

        if (studiesWheelChart) {
            studiesWheelChart.destroy();
        }

        const container = document.getElementById('areasChart');
        let destroyed = false;

        const draw = () => {
            if (destroyed || !container) {
                return;
            }

            drawStudiesWheelSvg({
                container,
                orderEntries,
                slotDefinitions,
                semaphorePalette,
                totalOrders
            });
        };

        studiesWheelChart = {
            showLoading(message = 'Cargando rueda...') {
                if (!container) {
                    return;
                }

                container.innerHTML = `<div class="wheel-svg-loading">${escapeHtml(message)}</div>`;
            },
            reflow() {
                window.requestAnimationFrame(draw);
            },
            destroy() {
                destroyed = true;
                hideWheelSvgTooltip();
                if (container) {
                    container.innerHTML = '';
                }
            }
        };

        window.requestAnimationFrame(draw);
    }

    function resolveWheelSemaphore(item) {
        const status = Number(item?.opTimeStatus);

        if (status === 1) {
            return 'green';
        }

        if (status === 2) {
            return 'yellow';
        }

        if (status === 3) {
            return 'red';
        }

        const minutes = Number(item?.turnaroundMinutes);

        if (Number.isFinite(minutes)) {
            if (minutes <= 60) {
                return 'green';
            }

            if (minutes <= 90) {
                return 'yellow';
            }

            return 'red';
        }

        const normalized = String(item?.semaphore || '').toLowerCase();
        return ['green', 'yellow', 'red'].includes(normalized) ? normalized : 'unknown';
    }

    function buildWheelSemaphoreSummary(orderEntries, backendSummary = {}) {
        const counts = {
            green: 0,
            yellow: 0,
            red: 0,
            unknown: 0
        };

        orderEntries.forEach(order => {
            const key = ['green', 'yellow', 'red'].includes(order.semaphore)
                ? order.semaphore
                : 'unknown';
            counts[key]++;
        });

        const defaults = {
            green: { label: 'Verde', range: '≤ 60 min' },
            yellow: { label: 'Amarillo', range: '61 a 90 min' },
            red: { label: 'Rojo', range: '> 90 min' },
            unknown: { label: 'Sin dato', range: 'No disponible' }
        };

        return Object.fromEntries(
            Object.keys(defaults).map(key => [
                key,
                {
                    label: backendSummary?.[key]?.label || defaults[key].label,
                    range: backendSummary?.[key]?.range || defaults[key].range,
                    count: counts[key]
                }
            ])
        );
    }

    function renderWheelLegend({ slotDefinitions, semaphoreSummary, semaphorePalette, totalOrders, totalProfiles }) {
        const areasLegend = document.getElementById('areasLegend');

        if (!areasLegend) {
            return;
        }

        const semaphoreHtml = Object.entries(semaphorePalette)
            .filter(([key]) => key !== 'unknown' || Number(semaphoreSummary?.unknown?.count || 0) > 0)
            .map(([key, config]) => {
                const count = Number(semaphoreSummary?.[key]?.count || 0);
                const range = escapeHtml(semaphoreSummary?.[key]?.range || config.range);
                const label = escapeHtml(semaphoreSummary?.[key]?.label || config.label);

                return `
                    <div class="studies-semaphore-card is-${key}">
                        <span class="studies-semaphore-dot" aria-hidden="true"></span>
                        <div class="studies-semaphore-copy">
                            <strong>${label}</strong>
                            <small>${range}</small>
                        </div>
                        <div class="studies-semaphore-value">${Highcharts.numberFormat(count, 0)}</div>
                    </div>
                `;
            })
            .join('');

        const hourlyHtml = slotDefinitions.map(slot => `
            <div class="studies-hourly-row">
                <strong>${escapeHtml(slot.label)}</strong>
                <span>${Highcharts.numberFormat(slot.count || 0, 0)} orden(es)</span>
            </div>
        `).join('');

        areasLegend.innerHTML = `
            <div class="studies-wheel-legend">
                <section class="studies-legend-section">
                    <h3>Semáforo de oportunidad</h3>
                    <p>El color de cada punto depende del tiempo de tolerancia de la orden.</p>
                    <div class="studies-semaphore-list">${semaphoreHtml}</div>
                </section>
                <section class="studies-legend-section">
                    <h3>Bloques horarios</h3>
                    <p>Cada círculo concéntrico representa una hora de cita; el primero inicia en 6:00–7:00 a.m. y los siguientes avanzan hacia afuera.</p>
                    <div class="studies-hourly-list">${hourlyHtml}</div>
                </section>
                <section class="studies-legend-section">
                    <h3>Resumen del día</h3>
                    <p>Órdenes totales: <strong>${Highcharts.numberFormat(totalOrders, 0)}</strong><br>Perfiles detectados: <strong>${Highcharts.numberFormat(totalProfiles, 0)}</strong></p>
                </section>
            </div>
        `;
    }

    function drawStudiesWheelSvg({ container, orderEntries, slotDefinitions, semaphorePalette, totalOrders }) {
        const width = Math.max(420, Math.round(container.clientWidth || 760));
        const height = Math.max(420, Math.round(container.clientHeight || 620));
        const centerX = width / 2;
        const centerY = height / 2;
        const outerRadius = Math.max(155, Math.min(width, height) * 0.405);
        // El primer bloque horario comienza prácticamente desde el centro.
        // Solo reservamos el espacio mínimo para el contador central, de modo
        // que los puntos ocupen toda la superficie útil de la rueda.
        const innerRadius = Math.max(11, outerRadius * 0.035);
        const radialSpace = Math.max(90, outerRadius - innerRadius - 3);
        const svgNamespace = 'http://www.w3.org/2000/svg';

        container.innerHTML = '';

        const svg = document.createElementNS(svgNamespace, 'svg');
        svg.setAttribute('class', 'wheel-svg-stage');
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.setAttribute('data-base-width', String(width));
        svg.setAttribute('data-base-height', String(height));
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Rueda de órdenes por círculos horarios concéntricos y semáforo');
        container.appendChild(svg);

        const background = document.createElementNS(svgNamespace, 'rect');
        background.setAttribute('x', '0');
        background.setAttribute('y', '0');
        background.setAttribute('width', String(width));
        background.setAttribute('height', String(height));
        background.setAttribute('fill', '#f8fafc');
        svg.appendChild(background);

        const ordersBySlot = new Map(slotDefinitions.map(slot => [slot.key, []]));

        orderEntries.forEach(order => {
            const normalizedSlot = normalizeWheelHourSlotKey(order.hourSlot || order.appointmentTime);
            const slotKey = ordersBySlot.has(normalizedSlot)
                ? normalizedSlot
                : (ordersBySlot.has('OTROS') ? 'OTROS' : slotDefinitions[slotDefinitions.length - 1]?.key);

            if (slotKey && ordersBySlot.has(slotKey)) {
                ordersBySlot.get(slotKey).push(order);
            }
        });

        /*
         * Cada horario ocupa una banda circular completa. Las bandas avanzan
         * de adentro hacia afuera y su grosor aumenta ligeramente cuando el
         * bloque contiene más órdenes. Así los horarios con mayor volumen
         * disponen de más espacio para colocar sus puntos sin convertir la
         * gráfica en radios o vectores.
         */
        const counts = slotDefinitions.map(slot => (ordersBySlot.get(slot.key) || []).length);
        const maximumCount = Math.max(1, ...counts);
        const weights = counts.map(count => 1 + (Math.sqrt(count / maximumCount) * 0.55));
        const totalWeight = Math.max(1, weights.reduce((sum, weight) => sum + weight, 0));
        const layouts = [];
        let radialCursor = innerRadius + 1;

        slotDefinitions.forEach((slot, slotIndex) => {
            const availableOrders = ordersBySlot.get(slot.key) || [];
            const isLast = slotIndex === slotDefinitions.length - 1;
            const rawThickness = radialSpace * (weights[slotIndex] / totalWeight);
            const remainingSpace = (innerRadius + 1 + radialSpace) - radialCursor;
            const bandThickness = isLast
                ? remainingSpace
                : Math.max(16, Math.min(rawThickness, remainingSpace));
            const bandInnerRadius = radialCursor;
            const bandOuterRadius = Math.min(outerRadius - 3, bandInnerRadius + bandThickness);
            const bandCenterRadius = (bandInnerRadius + bandOuterRadius) / 2;

            layouts.push({
                slot,
                slotIndex,
                orders: availableOrders,
                count: availableOrders.length,
                innerRadius: bandInnerRadius,
                outerRadius: bandOuterRadius,
                centerRadius: bandCenterRadius,
                thickness: Math.max(10, bandOuterRadius - bandInnerRadius)
            });

            radialCursor = bandOuterRadius;
        });

        const gridGroup = document.createElementNS(svgNamespace, 'g');
        gridGroup.setAttribute('aria-hidden', 'true');
        svg.appendChild(gridGroup);

        layouts.forEach((layout, layoutIndex) => {
            const band = document.createElementNS(svgNamespace, 'circle');
            band.setAttribute('cx', String(centerX));
            band.setAttribute('cy', String(centerY));
            band.setAttribute('r', String(layout.centerRadius));
            band.setAttribute('fill', 'none');
            band.setAttribute('stroke', layoutIndex % 2 === 0 ? '#f1f5f9' : '#edf3fa');
            band.setAttribute('stroke-width', String(Math.max(8, layout.thickness - 2)));
            band.setAttribute('opacity', '0.86');
            gridGroup.appendChild(band);

            const centerLine = document.createElementNS(svgNamespace, 'circle');
            centerLine.setAttribute('cx', String(centerX));
            centerLine.setAttribute('cy', String(centerY));
            centerLine.setAttribute('r', String(layout.centerRadius));
            centerLine.setAttribute('fill', 'none');
            centerLine.setAttribute('stroke', '#cbd5e1');
            centerLine.setAttribute('stroke-width', '0.9');
            centerLine.setAttribute('stroke-dasharray', '3 5');
            gridGroup.appendChild(centerLine);

            const boundary = document.createElementNS(svgNamespace, 'circle');
            boundary.setAttribute('cx', String(centerX));
            boundary.setAttribute('cy', String(centerY));
            boundary.setAttribute('r', String(layout.outerRadius));
            boundary.setAttribute('fill', 'none');
            boundary.setAttribute('stroke', layoutIndex === layouts.length - 1 ? '#b9c7d8' : '#d7e1ed');
            boundary.setAttribute('stroke-width', layoutIndex === layouts.length - 1 ? '1.35' : '0.75');
            gridGroup.appendChild(boundary);
        });

        const pointsGroup = document.createElementNS(svgNamespace, 'g');
        svg.appendChild(pointsGroup);

        layouts.forEach(layout => {
            const slotOrders = layout.orders;

            if (slotOrders.length === 0) {
                return;
            }

            const basePointRadius = Math.max(2.8, Math.min(4.2, 3.25 - (slotOrders.length / Math.max(1, maximumCount)) * 0.15));
            const pointDiameter = basePointRadius * 2;
            const laneSpacing = pointDiameter + 2.4;
            const usableThickness = Math.max(laneSpacing, layout.thickness - 5);
            const maximumLanes = Math.max(1, Math.floor(usableThickness / laneSpacing));
            const centerCapacity = Math.max(1, Math.floor((Math.PI * 2 * layout.centerRadius) / (pointDiameter + 2.5)));
            const requiredLanes = Math.max(1, Math.ceil(slotOrders.length / centerCapacity));
            const laneCount = Math.min(maximumLanes, requiredLanes);
            const laneRadii = [];

            const minimumLaneRadius = layout.slotIndex === 0
                ? Math.max(14, layout.innerRadius + basePointRadius + 0.5)
                : layout.innerRadius + basePointRadius + 0.5;
            const maximumLaneRadius = Math.max(
                minimumLaneRadius,
                layout.outerRadius - basePointRadius - 0.5
            );

            if (laneCount === 1) {
                laneRadii.push(Math.min(maximumLaneRadius, Math.max(minimumLaneRadius, layout.centerRadius)));
            } else {
                const laneSpan = Math.min(
                    Math.max(0, maximumLaneRadius - minimumLaneRadius),
                    laneSpacing * (laneCount - 1)
                );
                const centeredLaneStart = layout.centerRadius - (laneSpan / 2);
                const laneStart = Math.min(
                    maximumLaneRadius - laneSpan,
                    Math.max(minimumLaneRadius, centeredLaneStart)
                );
                const laneStep = laneCount > 1 ? laneSpan / (laneCount - 1) : 0;

                for (let laneIndex = 0; laneIndex < laneCount; laneIndex++) {
                    laneRadii.push(laneStart + (laneIndex * laneStep));
                }
            }

            const ordersPerLane = Array.from({ length: laneCount }, () => []);
            slotOrders.forEach((order, orderIndex) => {
                ordersPerLane[orderIndex % laneCount].push(order);
            });

            ordersPerLane.forEach((laneOrders, laneIndex) => {
                const radius = laneRadii[laneIndex] || layout.centerRadius;
                // Ya no se reserva una abertura angular para etiquetas porque
                // estas se muestran en la leyenda lateral. Los puntos ocupan
                // los 360° completos de cada carril y desaparece la cuña vacía.
                const drawableAngle = Math.PI * 2;
                const startAngle = -Math.PI / 2;

                laneOrders.forEach((order, orderIndex) => {
                    const pointAngle = laneOrders.length === 1
                        ? Math.PI / 2
                        : startAngle + (drawableAngle * ((orderIndex + 0.5) / laneOrders.length));
                    const stagger = laneIndex % 2 === 0 ? 0 : Math.min(0.018, 3 / Math.max(100, radius));
                    const angle = pointAngle + stagger;
                    const x = centerX + (Math.cos(angle) * radius);
                    const y = centerY + (Math.sin(angle) * radius);
                    const semaphore = semaphorePalette[order.semaphore] || semaphorePalette.unknown;
                    const pointRadius = Math.max(2.8, Math.min(4.5, basePointRadius + (Number(order.testsCount || 0) * 0.025)));

                    const point = document.createElementNS(svgNamespace, 'circle');
                    point.setAttribute('cx', String(x));
                    point.setAttribute('cy', String(y));
                    point.setAttribute('r', String(pointRadius));
                    point.setAttribute('fill', semaphore.color);
                    point.setAttribute('stroke', '#ffffff');
                    point.setAttribute('stroke-width', '1');
                    point.setAttribute('opacity', '0.95');
                    point.setAttribute('class', 'wheel-svg-point');
                    point.setAttribute('data-order-key', normalizeWheelOrderKey(order.orderNumber));
                    point.setAttribute('data-order-number', String(order.orderNumber || ''));
                    point.setAttribute('tabindex', '0');
                    point.setAttribute('aria-label', `Orden ${order.orderNumber}, ${layout.slot.label}, ${semaphore.label}`);

                    const tooltipPayload = {
                        ...order,
                        hourSlotLabel: layout.slot.label
                    };

                    point.addEventListener('mouseenter', event => {
                        cancelWheelSvgTooltipHide();
                        showWheelSvgTooltip(event, tooltipPayload, semaphorePalette);
                    });
                    point.addEventListener('mousemove', moveWheelSvgTooltip);
                    point.addEventListener('mouseleave', scheduleWheelSvgTooltipHide);
                    point.addEventListener('focus', event => {
                        cancelWheelSvgTooltipHide();
                        showWheelSvgTooltip(event, tooltipPayload, semaphorePalette);
                    });
                    point.addEventListener('blur', scheduleWheelSvgTooltipHide);
                    point.addEventListener('click', event => {
                        event.preventDefault();
                        event.stopPropagation();
                        selectWheelOrder(tooltipPayload, point, semaphorePalette);
                    });
                    point.addEventListener('keydown', event => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            selectWheelOrder(tooltipPayload, point, semaphorePalette);
                        }
                    });
                    pointsGroup.appendChild(point);
                });
            });
        });

        // Las etiquetas de horario se ocultan; los círculos conservan su orden de adentro hacia afuera.

        // Indicador central mínimo: únicamente deja visible el total y permite
        // que la primera banda de puntos llegue prácticamente hasta el centro.
        const centerGroup = document.createElementNS(svgNamespace, 'g');
        centerGroup.setAttribute('aria-hidden', 'true');
        svg.appendChild(centerGroup);

        const compactCenterRadius = Math.max(9, Math.min(12, innerRadius - 1));
        const centerCircle = document.createElementNS(svgNamespace, 'circle');
        centerCircle.setAttribute('cx', String(centerX));
        centerCircle.setAttribute('cy', String(centerY));
        centerCircle.setAttribute('r', String(compactCenterRadius));
        centerCircle.setAttribute('fill', 'rgba(255,255,255,.90)');
        centerCircle.setAttribute('stroke', '#bfdbfe');
        centerCircle.setAttribute('stroke-width', '1');
        centerCircle.setAttribute('filter', 'drop-shadow(0 2px 6px rgba(15,23,42,.08))');
        centerGroup.appendChild(centerCircle);

        const totalValue = document.createElementNS(svgNamespace, 'text');
        totalValue.setAttribute('x', String(centerX));
        totalValue.setAttribute('y', String(centerY + 4));
        totalValue.setAttribute('text-anchor', 'middle');
        totalValue.setAttribute('fill', '#1d4ed8');
        totalValue.setAttribute('font-size', totalOrders >= 10000 ? '7.5' : '9');
        totalValue.setAttribute('font-weight', '950');
        totalValue.textContent = Highcharts.numberFormat(totalOrders, 0);
        centerGroup.appendChild(totalValue);

        const centerTitle = document.createElementNS(svgNamespace, 'title');
        centerTitle.textContent = `${Highcharts.numberFormat(totalOrders, 0)} órdenes totales`;
        centerGroup.appendChild(centerTitle);

        applyWheelPointState(svg);

        if (selectedWheelOrderKey && document.querySelector('.studies-area-card')?.classList.contains('is-expanded')) {
            window.requestAnimationFrame(() => focusWheelOrderByKey(selectedWheelOrderKey, false));
        }
    }

    function renderWheelSelectedOrder(order, semaphorePalette = null) {
        const panel = document.getElementById('wheelSelectedOrderPanel');

        if (!panel) {
            return;
        }

        const palette = semaphorePalette || {
            green: { label: 'Verde', range: '≤ 60 min', color: '#22c55e' },
            yellow: { label: 'Amarillo', range: '61 a 90 min', color: '#f59e0b' },
            red: { label: 'Rojo', range: '> 90 min', color: '#ef4444' },
            unknown: { label: 'Sin dato', range: 'No disponible', color: '#94a3b8' }
        };
        const semaphore = palette[order.semaphore] || palette.unknown;
        const areas = Array.isArray(order.areas) ? order.areas : [];
        const profileNames = Array.isArray(order.profileNames) ? order.profileNames : [];
        const testsCount = Number(order.testsCount || 0);

        const areasHtml = areas.length > 0
            ? areas.map(area => {
                const profiles = Array.isArray(area.profiles) ? area.profiles : [];
                const profilesHtml = profiles.length > 0
                    ? profiles.map(profile => {
                        const profileTests = Array.isArray(profile.tests) ? profile.tests : [];
                        const testsHtml = profileTests.length > 0
                            ? `
                                <div class="order-tooltip-profile-tests">
                                    ${profileTests.map(test => {
                                        const result = String(test.result ?? '').trim();
                                        const unit = String(test.unit ?? '').trim();

                                        return `
                                            <div class="order-tooltip-study">
                                                <span class="order-tooltip-study-name">${escapeHtml(test.testName || 'Estudio sin nombre')}</span>
                                                <span class="order-tooltip-study-result">${escapeHtml(result || '—')}</span>
                                                <span class="order-tooltip-study-unit">${escapeHtml(unit || '—')}</span>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>
                            `
                            : '';

                        return `
                            <div class="order-tooltip-profile">
                                <div class="order-tooltip-profile-name">${escapeHtml(profile.profileName || 'Estudio sin nombre')}</div>
                                ${testsHtml}
                            </div>
                        `;
                    }).join('')
                    : '<div class="order-tooltip-study-empty">Sin estudios registrados en esta área.</div>';

                return `
                    <div class="order-tooltip-area-block">
                        <div class="order-tooltip-area-name">${escapeHtml(area.areaName || 'SIN ÁREA')}</div>
                        <div class="order-tooltip-area-studies">${profilesHtml}</div>
                    </div>
                `;
            }).join('')
            : '<div class="order-tooltip-empty">La orden no contiene áreas registradas.</div>';

        panel.innerHTML = `
            <div class="wheel-selected-order-head">
                <strong>Detalle de la orden ${escapeHtml(order.orderNumber || 'Sin número')}</strong>
                <div class="wheel-selected-order-actions">
                    <button type="button" class="wheel-selected-order-action" id="wheelSelectedResetButton">Vista completa</button>
                    <button type="button" class="wheel-selected-order-action" id="wheelSelectedCloseButton" aria-label="Cerrar detalle">Cerrar</button>
                </div>
            </div>
            <div class="wheel-selected-order-body">
                <div class="order-tooltip-meta">
                    <span>Hora cita</span>
                    <strong>${escapeHtml(formatAppointmentTime(order.appointmentTime))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Hora HIS</span>
                    <strong>${escapeHtml(formatHisTime(order.hisTime))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Bloque</span>
                    <strong>${escapeHtml(order.hourSlotLabel || order.hourSlot || 'Otros horarios')}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Tiempo</span>
                    <strong>${escapeHtml(formatTurnaroundMinutes(order.turnaroundMinutes))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Semáforo</span>
                    <strong><span class="area-tooltip-dot" style="display:inline-block;background:${semaphore.color};margin-right:6px"></span>${escapeHtml(semaphore.label)} · ${escapeHtml(semaphore.range)}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Áreas</span>
                    <strong>${escapeHtml((order.areaNames || []).join(', ') || 'Sin áreas')}</strong>
                </div>
                <div class="order-tooltip-tests-title is-name-only">
                    <span>Áreas y estudios</span>
                    <span>${Highcharts.numberFormat(profileNames.length || testsCount, 0)} elemento(s)</span>
                </div>
                <div class="order-tooltip-tests">${areasHtml}</div>
            </div>
        `;
        panel.hidden = false;

        panel.querySelector('#wheelSelectedResetButton')?.addEventListener('click', function () {
            resetWheelSvgView(true);
        });

        panel.querySelector('#wheelSelectedCloseButton')?.addEventListener('click', function () {
            clearWheelOrderFocus({ clearInput: true, hideDetail: true, resetView: true });
        });
    }

    function hideWheelSelectedOrderPanel() {
        const panel = document.getElementById('wheelSelectedOrderPanel');

        if (!panel) {
            return;
        }

        panel.hidden = true;
        panel.innerHTML = '';
    }

    let wheelSvgTooltipHideTimer = null;

    function getWheelSvgTooltip() {
        let tooltip = document.getElementById('wheelSvgTooltip');

        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'wheelSvgTooltip';
            tooltip.className = 'wheel-svg-tooltip';
            tooltip.addEventListener('mouseenter', cancelWheelSvgTooltipHide);
            tooltip.addEventListener('mouseleave', scheduleWheelSvgTooltipHide);
            tooltip.addEventListener('focusin', cancelWheelSvgTooltipHide);
            tooltip.addEventListener('focusout', function (event) {
                if (!tooltip.contains(event.relatedTarget)) {
                    scheduleWheelSvgTooltipHide();
                }
            });
            document.body.appendChild(tooltip);
        }

        return tooltip;
    }

    function showWheelSvgTooltip(event, order, semaphorePalette) {
        cancelWheelSvgTooltipHide();

        const tooltip = getWheelSvgTooltip();
        const semaphore = semaphorePalette[order.semaphore] || semaphorePalette.unknown;
        const areas = Array.isArray(order.areas) ? order.areas : [];
        const profileNames = Array.isArray(order.profileNames) ? order.profileNames : [];
        const testsCount = Number(order.testsCount || 0);
        let areaIndex = 0;

        const areasHtml = areas.length > 0
            ? areas.map(area => {
                const currentAreaIndex = areaIndex++;
                const areaPanelId = `wheelAreaStudies${currentAreaIndex}`;
                const areaName = escapeHtml(area.areaName || 'SIN ÁREA');
                const profiles = Array.isArray(area.profiles) ? area.profiles : [];
                const profilesHtml = profiles.length > 0
                    ? profiles.map(profile => {
                        const profileTests = Array.isArray(profile.tests) ? profile.tests : [];
                        const testsHtml = profileTests.length > 0
                            ? `
                                <div class="order-tooltip-profile-tests">
                                    ${profileTests.map(test => {
                                        const result = String(test.result ?? '').trim();
                                        const unit = String(test.unit ?? '').trim();

                                        return `
                                            <div class="order-tooltip-study">
                                                <span class="order-tooltip-study-name">${escapeHtml(test.testName || 'Estudio sin nombre')}</span>
                                                <span class="order-tooltip-study-result">${escapeHtml(result || '—')}</span>
                                                <span class="order-tooltip-study-unit">${escapeHtml(unit || '—')}</span>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>
                            `
                            : '';

                        return `
                            <div class="order-tooltip-profile">
                                <div class="order-tooltip-profile-name">${escapeHtml(profile.profileName || 'Estudio sin nombre')}</div>
                                ${testsHtml}
                            </div>
                        `;
                    }).join('')
                    : '<div class="order-tooltip-study-empty">Sin estudios registrados en esta área.</div>';

                return `
                    <div class="order-tooltip-area-block">
                        <button
                            type="button"
                            class="order-tooltip-area-toggle"
                            aria-expanded="false"
                            aria-controls="${areaPanelId}"
                            title="Mostrar estudios del área"
                        >
                            <span class="area-expand-icon" aria-hidden="true">+</span>
                            <span class="order-tooltip-area-name">${areaName}</span>
                        </button>
                        <div class="order-tooltip-area-studies" id="${areaPanelId}" hidden>${profilesHtml}</div>
                    </div>
                `;
            }).join('')
            : '<div class="order-tooltip-empty">La orden no contiene áreas registradas.</div>';

        tooltip.innerHTML = `
            <div class="order-tooltip">
                <div class="order-tooltip-title">
                    <span class="area-tooltip-dot" style="background:${semaphore.color}"></span>
                    <span>Orden <strong>${escapeHtml(order.orderNumber || 'Sin número')}</strong></span>
                </div>
                <div class="order-tooltip-meta">
                    <span>Hora cita</span>
                    <strong>${escapeHtml(formatAppointmentTime(order.appointmentTime))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Hora HIS</span>
                    <strong>${escapeHtml(formatHisTime(order.hisTime))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Bloque</span>
                    <strong>${escapeHtml(order.hourSlotLabel || 'Otros horarios')}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Tiempo</span>
                    <strong>${escapeHtml(formatTurnaroundMinutes(order.turnaroundMinutes))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Semáforo</span>
                    <strong>${escapeHtml(semaphore.label)} · ${escapeHtml(semaphore.range)}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Áreas</span>
                    <strong>${escapeHtml((order.areaNames || []).join(', ') || 'Sin áreas')}</strong>
                </div>
                <div class="order-tooltip-tests-title is-name-only">
                    <span>Áreas y estudios</span>
                    <span>${Highcharts.numberFormat(profileNames.length || testsCount, 0)} elemento(s)</span>
                </div>
                <div class="order-tooltip-tests">${areasHtml}</div>
            </div>
        `;

        tooltip.querySelectorAll('.order-tooltip-area-toggle').forEach(button => {
            button.addEventListener('click', function () {
                const panelId = this.getAttribute('aria-controls');
                const panel = panelId ? document.getElementById(panelId) : null;
                const isExpanded = this.getAttribute('aria-expanded') === 'true';

                this.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                const icon = this.querySelector('.area-expand-icon');

                if (icon) {
                    icon.textContent = isExpanded ? '+' : '−';
                }

                this.title = isExpanded
                    ? 'Mostrar estudios del área'
                    : 'Ocultar estudios del área';

                if (panel) {
                    panel.hidden = isExpanded;
                }

                cancelWheelSvgTooltipHide();
            });
        });

        tooltip.style.display = 'block';
        moveWheelSvgTooltip(event);
    }

    function moveWheelSvgTooltip(event) {
        const tooltip = document.getElementById('wheelSvgTooltip');

        if (!tooltip || tooltip.style.display === 'none') {
            return;
        }

        const clientX = Number(event.clientX || 0);
        const clientY = Number(event.clientY || 0);
        const margin = 14;
        const tooltipWidth = tooltip.offsetWidth || 440;
        const tooltipHeight = tooltip.offsetHeight || 300;
        let left = clientX + 14;
        let top = clientY + 14;

        if (left + tooltipWidth + margin > window.innerWidth) {
            left = clientX - tooltipWidth - 14;
        }

        if (top + tooltipHeight + margin > window.innerHeight) {
            top = window.innerHeight - tooltipHeight - margin;
        }

        tooltip.style.left = `${Math.max(margin, left)}px`;
        tooltip.style.top = `${Math.max(margin, top)}px`;
    }

    function cancelWheelSvgTooltipHide() {
        if (wheelSvgTooltipHideTimer !== null) {
            window.clearTimeout(wheelSvgTooltipHideTimer);
            wheelSvgTooltipHideTimer = null;
        }
    }

    function scheduleWheelSvgTooltipHide() {
        cancelWheelSvgTooltipHide();
        wheelSvgTooltipHideTimer = window.setTimeout(hideWheelSvgTooltip, 320);
    }

    function hideWheelSvgTooltip() {
        cancelWheelSvgTooltipHide();
        const tooltip = document.getElementById('wheelSvgTooltip');

        if (tooltip) {
            tooltip.style.display = 'none';
        }
    }

    function buildWheelHourSlots(orderEntries) {
        const slots = [
            { key: '06:00', label: '6:00 a.m. - 7:00 a.m.', shortLabel: '6–7 a.m.' },
            { key: '07:00', label: '7:00 a.m. - 8:00 a.m.', shortLabel: '7–8 a.m.' },
            { key: '08:00', label: '8:00 a.m. - 9:00 a.m.', shortLabel: '8–9 a.m.' },
            { key: '09:00', label: '9:00 a.m. - 10:00 a.m.', shortLabel: '9–10 a.m.' },
            { key: '10:00', label: '10:00 a.m. - 11:00 a.m.', shortLabel: '10–11 a.m.' },
            { key: '11:00', label: '11:00 a.m. - 12:00 p.m.', shortLabel: '11–12 p.m.' },
            { key: '12:00', label: '12:00 p.m. - 1:00 p.m.', shortLabel: '12–1 p.m.' },
            { key: '13:00', label: '1:00 p.m. - 2:00 p.m.', shortLabel: '1–2 p.m.' }
        ].map(slot => ({
            ...slot,
            count: countOrdersForSlot(orderEntries, slot.key)
        }));

        const hasOverflowOrders = orderEntries.some(order => normalizeWheelHourSlotKey(order.hourSlot || order.appointmentTime, slots) === 'OTROS');
        const overflowCount = orderEntries.filter(
            order => normalizeWheelHourSlotKey(order.hourSlot || order.appointmentTime, slots) === 'OTROS'
        ).length;

        if (hasOverflowOrders || overflowCount > 0) {
            slots.push({
                key: 'OTROS',
                label: 'Otros horarios',
                shortLabel: 'Otros',
                count: overflowCount
            });
        }

        return slots;
    }

    function countOrdersForSlot(orderEntries, slotKey) {
        return orderEntries.filter(order => normalizeWheelHourSlotKey(order.hourSlot || order.appointmentTime) === slotKey).length;
    }

    function normalizeWheelHourSlotKey(rawValue) {
        const value = String(rawValue || '').trim();

        if (value === '' || value === 'SIN_HORA' || value === 'OTROS') {
            return 'OTROS';
        }

        const hourMatch = value.match(/(\d{1,2}):\d{2}/);
        const hour = hourMatch ? Number(hourMatch[1]) : Number.NaN;

        if (!Number.isFinite(hour)) {
            return 'OTROS';
        }

        if (hour >= 6 && hour <= 13) {
            return `${String(hour).padStart(2, '0')}:00`;
        }

        return 'OTROS';
    }

    function formatAppointmentTime(value) {
        const raw = String(value || '').trim();

        if (raw === '') {
            return 'Sin horario';
        }

        const isoCandidate = raw.replace(' ', 'T');
        const date = new Date(isoCandidate);

        if (Number.isNaN(date.getTime())) {
            return raw;
        }

        return date.toLocaleString('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatHisTime(value) {
        const raw = String(value || '').trim();

        if (raw === '') {
            return 'Sin dato';
        }

        const timeOnlyMatch = raw.match(/^([01]?\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/);

        if (timeOnlyMatch) {
            const hour = Number(timeOnlyMatch[1]);
            const minute = timeOnlyMatch[2];
            const suffix = hour >= 12 ? 'p.m.' : 'a.m.';
            const displayHour = hour % 12 || 12;

            return `${displayHour}:${minute} ${suffix}`;
        }

        const isoCandidate = raw.replace(' ', 'T');
        const date = new Date(isoCandidate);

        if (!Number.isNaN(date.getTime())) {
            return date.toLocaleTimeString('es-MX', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        return raw;
    }

    function formatTurnaroundMinutes(value) {
        if (!Number.isFinite(Number(value))) {
            return 'Sin dato';
        }

        return `${Highcharts.numberFormat(Number(value), 0)} min`;
    }

    function setWheelStatus(message, type = 'loading') {
        const status = document.getElementById('wheelStatus');

        if (!status) {
            return;
        }

        status.classList.remove('is-success', 'is-warning', 'is-error');

        if (type === 'success') {
            status.classList.add('is-success');
        } else if (type === 'warning') {
            status.classList.add('is-warning');
        } else if (type === 'error') {
            status.classList.add('is-error');
        }

        const spinner = type === 'loading'
            ? '<span class="wheel-spinner" aria-hidden="true"></span>'
            : '';

        status.innerHTML = `${spinner}<span>${escapeHtml(message)}</span>`;
    }

    function formatWheelDate(date) {
        if (!date) {
            return 'la fecha seleccionada';
        }

        const [year, month, day] = date.split('-');
        return `${day}/${month}/${year}`;
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function fillAreaCenter(total, label, chart, customLabel, metricLabel = 'Órdenes') {
        const safeTotal = Number(total) || 0;
        const rawLabel = String(label || 'Total');
        const safeMetricLabel = String(metricLabel || 'Órdenes');
        const shortLabel = rawLabel.length > 16
            ? `${rawLabel.slice(0, 15)}…`
            : rawLabel;

        const centerX = chart.pane?.[0]?.center?.[0] + chart.plotLeft;
        const centerY = chart.pane?.[0]?.center?.[1] + chart.plotTop;

        if (!Number.isFinite(centerX) || !Number.isFinite(centerY)) {
            return customLabel;
        }

        /*
         * Se usa SVG puro en lugar de renderer.label(..., useHTML: true).
         * Así Highcharts no genera el contenedor HTML rectangular que se
         * alcanzaba a ver detrás del círculo central.
         */
        if (!customLabel || !customLabel.group || customLabel.group.destroyed) {
            const group = chart.renderer
                .g('studies-wheel-center')
                .attr({ zIndex: 10 })
                .css({ pointerEvents: 'none' })
                .add();

            const circle = chart.renderer
                .circle(0, 0, 52)
                .attr({
                    fill: '#ffffff',
                    stroke: '#dce6f2',
                    'stroke-width': 1
                })
                .add(group);

            const halo = chart.renderer
                .circle(0, -27, 8)
                .attr({
                    fill: 'rgba(37, 99, 235, 0.10)',
                    stroke: 'none'
                })
                .add(group);

            const dot = chart.renderer
                .circle(0, -27, 4)
                .attr({
                    fill: '#2563eb',
                    stroke: 'none'
                })
                .add(group);

            const title = chart.renderer
                .text('', 0, -9)
                .attr({
                    align: 'center',
                    'text-anchor': 'middle'
                })
                .css({
                    color: '#0f172a',
                    fontSize: '9px',
                    fontWeight: '900',
                    textTransform: 'uppercase'
                })
                .add(group);

            const metric = chart.renderer
                .text('', 0, 5)
                .attr({
                    align: 'center',
                    'text-anchor': 'middle'
                })
                .css({
                    color: '#64748b',
                    fontSize: '7px',
                    fontWeight: '700',
                    textTransform: 'uppercase'
                })
                .add(group);

            const value = chart.renderer
                .text('', 0, 30)
                .attr({
                    align: 'center',
                    'text-anchor': 'middle'
                })
                .css({
                    color: '#2563eb',
                    fontSize: '19px',
                    fontWeight: '900'
                })
                .add(group);

            customLabel = {
                group,
                circle,
                halo,
                dot,
                title,
                metric,
                value
            };
        }

        customLabel.title.attr({
            text: shortLabel.toUpperCase()
        });

        customLabel.metric.attr({
            text: safeMetricLabel.toUpperCase()
        });

        customLabel.value.attr({
            text: Highcharts.numberFormat(safeTotal, 0)
        });

        customLabel.group.attr({
            translateX: centerX,
            translateY: centerY
        });

        return customLabel;
    }

    function initStudiesTable() {
        const table = document.getElementById('studiesDataTable');
        const tableBody = document.getElementById('studiesTableBody');
        const searchInput = document.getElementById('studiesTableSearch');
        const perPageSelect = document.getElementById('studiesPerPage');
        const info = document.getElementById('studiesTableInfo');
        const pagination = document.getElementById('studiesPagination');
        const sortButtons = Array.from(document.querySelectorAll('.datatable-sort'));

        if (!table || !tableBody || !searchInput || !perPageSelect || !info || !pagination) {
            return;
        }

        const apiBlockSize = 50;
        const records = [];
        const requestedCursors = new Set();

        let nextCursor = '0';
        let hasMore = true;
        let loading = false;
        let loadError = '';
        let currentPage = 1;
        let perPage = parseInt(perPageSelect.value, 10) || 10;
        let sortColumn = null;
        let sortDirection = 'asc';

        function recordColumnValue(record, columnIndex) {
            const values = [
                record.order ?? '',
                record.area ?? '',
                record.testName ?? '',
                record.opTime ?? 999999,
                record.validatedAt ?? '',
                record.statusCode ?? ''
            ];

            return values[columnIndex] ?? '';
        }

        function getFilteredRecords() {
            const query = searchInput.value.trim().toLowerCase();

            let filtered = records.filter(record => {
                if (!query) {
                    return true;
                }

                const searchable = [
                    record.order,
                    record.area,
                    record.testName,
                    record.opTime,
                    record.validatedReadable,
                    record.statusCode,
                    record.status
                ].join(' ').toLowerCase();

                return searchable.includes(query);
            });

            if (sortColumn !== null) {
                filtered = [...filtered].sort((a, b) => {
                    const aValue = recordColumnValue(a, sortColumn);
                    const bValue = recordColumnValue(b, sortColumn);
                    const aNumber = Number(aValue);
                    const bNumber = Number(bValue);

                    let comparison;

                    if (aValue !== '' && bValue !== '' && Number.isFinite(aNumber) && Number.isFinite(bNumber)) {
                        comparison = aNumber - bNumber;
                    } else {
                        comparison = String(aValue).localeCompare(String(bValue), 'es', {
                            numeric: true,
                            sensitivity: 'base'
                        });
                    }

                    return sortDirection === 'asc' ? comparison : comparison * -1;
                });
            }

            return filtered;
        }

        function opportunityBadge(record) {
            const opTime = Number.isFinite(Number(record.opTime))
                ? Number(record.opTime)
                : null;

            if (opTime === null) {
                return '<span class="op-time-empty">Sin tiempo</span>';
            }

            let cssClass = 'op-time-ok';
            let icon = '✓';
            let title = 'En tiempo: 60 minutos o menos';

            if (opTime > 90) {
                cssClass = 'op-time-danger';
                icon = '⚠';
                title = 'Fuera de tiempo: superior a 90 minutos';
            } else if (opTime > 60) {
                cssClass = 'op-time-warning';
                icon = '!';
                title = 'Atención: entre 61 y 90 minutos';
            }

            return `
                <span class="op-time-badge ${cssClass}" title="${escapeHtml(title)}">
                    <span class="op-time-icon">${icon}</span>
                    <span>${Highcharts.numberFormat(opTime, 0)} min</span>
                </span>
            `;
        }

        function statusBadge(record) {
            const allowedClasses = [
                'badge-ordered',
                'badge-repeat',
                'badge-result',
                'badge-validated',
                'badge-unknown'
            ];
            const cssClass = allowedClasses.includes(record.statusClass)
                ? record.statusClass
                : 'badge-unknown';

            return `
                <span class="badge ${cssClass}">
                    ${escapeHtml(record.status ?? 'Desconocido')}
                </span>
            `;
        }

        function renderRows(pageRecords, message = '') {
            if (message) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="global-empty-inline">${escapeHtml(message)}</div>
                        </td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = pageRecords.map(record => `
                <tr data-study-row>
                    <td>${escapeHtml(record.order ?? '-')}</td>
                    <td>${record.area ? `<span class="studies-area-chip">${escapeHtml(record.area)}</span>` : '<span class="empty-text">Sin área</span>'}</td>
                    <td class="datatable-study">${record.testName ? escapeHtml(record.testName) : '<span class="empty-text">Sin nombre</span>'}</td>
                    <td>${opportunityBadge(record)}</td>
                    <td>${escapeHtml(record.validatedReadable ?? '-')}</td>
                    <td>${statusBadge(record)}</td>
                </tr>
            `).join('');
        }

        function renderPagination(totalPages) {
            pagination.innerHTML = '';

            const previousButton = document.createElement('button');
            previousButton.type = 'button';
            previousButton.className = 'datatable-page-btn';
            previousButton.textContent = '‹';
            previousButton.title = 'Página anterior';
            previousButton.disabled = loading || currentPage === 1;
            previousButton.addEventListener('click', function () {
                if (currentPage > 1) {
                    currentPage--;
                    render();
                }
            });
            pagination.appendChild(previousButton);

            const maxButtons = 7;
            let startPage = Math.max(1, currentPage - 3);
            let endPage = Math.min(totalPages, startPage + maxButtons - 1);

            if (endPage - startPage < maxButtons - 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }

            for (let page = startPage; page <= endPage; page++) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'datatable-page-btn';
                button.textContent = page;

                if (page === currentPage) {
                    button.classList.add('active');
                }

                button.disabled = loading;
                button.addEventListener('click', function () {
                    currentPage = page;
                    render();
                });
                pagination.appendChild(button);
            }

            const nextButton = document.createElement('button');
            nextButton.type = 'button';
            nextButton.className = 'datatable-page-btn';
            nextButton.textContent = loading ? '…' : '›';
            nextButton.title = currentPage < totalPages
                ? 'Página siguiente'
                : (hasMore ? 'Cargar el siguiente bloque de estudios' : 'No hay más registros');
            nextButton.disabled = loading || (!hasMore && currentPage >= totalPages);
            nextButton.addEventListener('click', async function () {
                if (currentPage < totalPages) {
                    currentPage++;
                    render();
                    return;
                }

                if (hasMore) {
                    const previousTotal = getFilteredRecords().length;
                    await loadNextBlock();
                    const newTotalPages = Math.max(1, Math.ceil(getFilteredRecords().length / perPage));

                    if (newTotalPages > currentPage && getFilteredRecords().length > previousTotal) {
                        currentPage++;
                    }

                    render();
                }
            });
            pagination.appendChild(nextButton);
        }

        function render() {
            const filtered = getFilteredRecords();
            const totalRows = filtered.length;
            const totalPages = Math.max(1, Math.ceil(totalRows / perPage));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const start = totalRows === 0 ? 0 : (currentPage - 1) * perPage;
            const end = Math.min(start + perPage, totalRows);

            if (loading && records.length === 0) {
                renderRows([], 'Cargando últimos registros de estudios...');
            } else if (loadError && records.length === 0) {
                renderRows([], loadError);
            } else if (totalRows === 0) {
                renderRows([], searchInput.value.trim()
                    ? 'No se encontraron resultados dentro de los registros cargados.'
                    : 'No hay registros de estudios disponibles.');
            } else {
                renderRows(filtered.slice(start, end));
            }

            if (loading && records.length > 0) {
                info.textContent = `Cargando más registros... ${records.length} registros almacenados.`;
            } else if (totalRows === 0) {
                info.textContent = records.length > 0
                    ? `0 coincidencias en ${records.length} registros cargados`
                    : 'Mostrando 0 a 0 de 0 registros';
            } else {
                const suffix = hasMore
                    ? ` · ${records.length} cargados · hay más disponibles`
                    : ` · ${records.length} registros cargados`;
                info.textContent = `Mostrando ${start + 1} a ${end} de ${totalRows}${suffix}`;
            }

            renderPagination(totalPages);
        }

        async function loadNextBlock() {
            if (loading || !hasMore || !nextCursor) {
                return;
            }

            const cursorToRequest = nextCursor;

            if (requestedCursors.has(cursorToRequest)) {
                hasMore = false;
                render();
                return;
            }

            requestedCursors.add(cursorToRequest);
            loading = true;
            loadError = '';
            render();

            try {
                const url = new URL(paginatedTestsDataUrl, window.location.origin);
                url.searchParams.set('cursor', cursorToRequest);
                url.searchParams.set('pageSize', String(apiBlockSize));

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const payload = await response.json().catch(() => ({}));

                if (response.status === 401 && payload.sessionExpired) {
                    window.location.href = payload.redirect || loginUrl;
                    return;
                }

                if (!response.ok || payload.success !== true) {
                    throw new Error(payload.message || 'No se pudieron cargar los estudios.');
                }

                const incoming = Array.isArray(payload.data) ? payload.data : [];
                records.push(...incoming);

                nextCursor = payload.nextCursor || null;
                hasMore = Boolean(payload.hasMore && nextCursor);

                if (incoming.length === 0) {
                    hasMore = false;
                }
            } catch (error) {
                requestedCursors.delete(cursorToRequest);
                loadError = error?.message || 'No se pudieron cargar los últimos registros de estudios.';
            } finally {
                loading = false;
                render();
            }
        }

        searchInput.addEventListener('input', function () {
            currentPage = 1;
            render();
        });

        perPageSelect.addEventListener('change', function () {
            perPage = parseInt(this.value, 10) || 10;
            currentPage = 1;
            render();
        });

        sortButtons.forEach(button => {
            button.addEventListener('click', function () {
                const column = parseInt(this.dataset.column, 10);

                if (sortColumn === column) {
                    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    sortColumn = column;
                    sortDirection = 'asc';
                }

                currentPage = 1;
                render();
            });
        });

        loadNextBlock();
    }
</script>
@endsection