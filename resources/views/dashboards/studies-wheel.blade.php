@extends('layouts.app')

@section('title', 'Consulta externa | Hospital Dashboard')
@section('body-class', 'page-studies page-studies-wheel')

@section('head')
    <script src="https://code.highcharts.com/highcharts.js"></script>
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


    .page-studies .studies-dashboard-grid.studies-dashboard-grid-single {
        grid-template-columns: minmax(0, 1fr) !important;
    }


    .page-studies-wheel .dashboard-header {
        margin-bottom: 14px;
    }

    .page-studies-wheel .studies-area-card.is-expanded {
        position: relative !important;
        inset: auto !important;
        z-index: auto !important;
        width: auto !important;
        height: auto !important;
        max-height: none !important;
        margin: 0 !important;
        border-radius: 18px !important;
        overflow: visible !important;
    }

    .page-studies-wheel .studies-wheel-search-panel {
        display: grid !important;
    }

    .page-studies-wheel .studies-area-content,
    .page-studies-wheel .studies-area-card.is-expanded .studies-area-content {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) minmax(300px, 360px) !important;
        gap: 18px !important;
        min-height: 0 !important;
    }

    .page-studies-wheel #areasChart,
    .page-studies-wheel .studies-area-card.is-expanded #areasChart {
        height: clamp(680px, calc(100vh - 300px), 920px) !important;
        min-height: 680px !important;
        border-radius: 18px;
        overflow: hidden;
    }

    .page-studies-wheel .studies-area-sidebar,
    .page-studies-wheel .studies-area-card.is-expanded .studies-area-sidebar {
        min-height: 680px;
        max-height: clamp(680px, calc(100vh - 300px), 920px) !important;
    }

    .page-studies-wheel .studies-expand-wheel-button {
        display: none !important;
    }

    .studies-pending-section {
        border-color: #c7d2fe !important;
        background: linear-gradient(135deg, #eef2ff 0%, #f8faff 100%) !important;
    }

    .studies-pending-total {
        margin: 8px 0 3px;
        color: #1d4ed8;
        font-size: 42px;
        font-weight: 950;
        line-height: 1;
    }

    @media (max-width: 1180px) {
        .page-studies-wheel .studies-area-content,
        .page-studies-wheel .studies-area-card.is-expanded .studies-area-content {
            grid-template-columns: 1fr !important;
        }

        .page-studies-wheel .studies-area-sidebar,
        .page-studies-wheel .studies-area-card.is-expanded .studies-area-sidebar {
            min-height: 0;
            max-height: none !important;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-header">
    <h1 class="dashboard-title">Consulta externa</h1>
    <p class="dashboard-subtitle">Monitorea las citas por bloque horario y su semáforo de oportunidad.</p>
</div>

<article class="chart-card studies-area-card is-expanded">
    <div class="studies-card-header studies-area-header">
        <div>
            <h2>Semáforo de tiempos por horario</h2>
            <p>Cada círculo representa un bloque con citas. Los horarios sin citas no generan líneas vacías.</p>
        </div>
        <div class="studies-wheel-actions">
            <div class="studies-date-field">
                <label for="wheelDate">Fecha de distribución</label>
                <input type="date" id="wheelDate" value="{{ $wheelDate }}">
            </div>
            <button type="button" class="btn btn-primary" id="loadWheelButton">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
                <span>Actualizar rueda</span>
            </button>
        </div>
    </div>

    <div id="wheelStatus" class="wheel-status studies-wheel-status">
        <span class="wheel-spinner" aria-hidden="true"></span>
        <span>Preparando semáforo por horario...</span>
    </div>

    <div class="studies-wheel-search-panel" id="wheelSearchPanel">
        <div class="studies-wheel-search-field">
            <label for="wheelOrderSearch">Buscar orden dentro de la rueda</label>
            <div class="studies-wheel-search-row">
                <input type="search" id="wheelOrderSearch" class="studies-wheel-search-input" placeholder="Ej. 202607220001" autocomplete="off">
                <button type="button" class="btn btn-primary" id="wheelOrderSearchButton">Buscar</button>
                <button type="button" class="btn btn-secondary" id="wheelOrderClearButton">Limpiar</button>
                <button type="button" class="btn btn-secondary" id="wheelResetViewButton">Vista completa</button>
            </div>
        </div>
        <div id="wheelSearchFeedback" class="studies-wheel-search-feedback" role="status" aria-live="polite">
            Escribe el número de orden. La coincidencia quedará resaltada y el resto se atenuará.
        </div>
    </div>

    <div class="studies-area-content">
        <div>
            <div id="areasChart" aria-label="Rueda de órdenes por horario"></div>
            <div class="studies-wheel-hint">Los bloques con citas avanzan de adentro hacia afuera. Haz clic en un punto para acercarte y consultar el detalle de la orden.</div>
        </div>
        <div class="studies-area-sidebar">
            <section id="wheelSelectedOrderPanel" class="wheel-selected-order-panel" hidden></section>
            <div id="areasLegend" class="studies-area-legend">
                <div class="studies-area-empty">Cargando semáforo y bloques horarios...</div>
            </div>
        </div>
    </div>
</article>
@endsection

@section('scripts')
<script>
    const wheelDataUrl = @json(route('dashboards.rueda.data'));
    const loginUrl = @json(route('login'));

    let studiesWheelChart = null;
    let lastStudiesWheelPayload = null;
    let lastWheelOrderEntries = [];
    let wheelSearchMatchKeys = new Set();
    let selectedWheelOrderKey = null;
    let wheelZoomAnimationFrame = null;

    document.addEventListener('DOMContentLoaded', function () {
        initStudiesWheel();
        initStudiesWheelSearch();
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
                attentionTimeStatus: item.attentionTimeStatus === null || item.attentionTimeStatus === undefined || item.attentionTimeStatus === ''
                    ? null
                    : Number(item.attentionTimeStatus),
                segHours: item.segHours === null || item.segHours === undefined || item.segHours === ''
                    ? null
                    : Number(item.segHours),
                doorID: item.doorID === null || item.doorID === undefined || item.doorID === ''
                    ? null
                    : Number(item.doorID),
                phlebotomist: String(item.phlebotomist || ''),
                phlebotomistUsername: String(item.phlebotomistUsername || ''),
                phlebotomyStartAt: String(item.phlebotomyStartAt || ''),
                phlebotomyEndAt: String(item.phlebotomyEndAt || ''),
                phlebotomyTime: item.phlebotomyTime === null || item.phlebotomyTime === undefined || item.phlebotomyTime === ''
                    ? null
                    : Number(item.phlebotomyTime),
                phlebotomyTimeStatus: item.phlebotomyTimeStatus === null || item.phlebotomyTimeStatus === undefined || item.phlebotomyTimeStatus === ''
                    ? null
                    : Number(item.phlebotomyTimeStatus),
                tubesCount: item.tubesCount === null || item.tubesCount === undefined || item.tubesCount === ''
                    ? null
                    : Number(item.tubesCount),
                isPendingAttention: Boolean(item.isPendingAttention),
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
        // Los horarios sin citas no crean una banda ni una línea vacía.
        const activeSlotDefinitions = slotDefinitions.filter(slot => Number(slot.count || 0) > 0);
        const semaphoreSummary = buildWheelSemaphoreSummary(
            orderEntries,
            payload.semaphoreSummary || {}
        );
        const totalOrders = orderEntries.length;
        const totalProfiles = Number(payload.totalProfiles ?? payload.totalTests ?? 0);

        renderWheelLegend({
            slotDefinitions: activeSlotDefinitions,
            semaphoreSummary,
            semaphorePalette,
            totalOrders,
            totalProfiles,
            pendingAttentionCount: Number(payload.pendingAttentionCount || 0)
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

            if (activeSlotDefinitions.length === 0) {
                container.innerHTML = '<div class="wheel-svg-loading">No hay citas con horario para dibujar en la fecha seleccionada.</div>';
                return;
            }

            drawStudiesWheelSvg({
                container,
                orderEntries,
                slotDefinitions: activeSlotDefinitions,
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
        const status = Number(item?.attentionTimeStatus ?? item?.opTimeStatus);

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

    function renderWheelLegend({ slotDefinitions, semaphoreSummary, semaphorePalette, totalOrders, totalProfiles, pendingAttentionCount = 0 }) {
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
                <section class="studies-legend-section studies-pending-section">
                    <h3>Citas pendientes de atención</h3>
                    <div class="studies-pending-total">${Highcharts.numberFormat(pendingAttentionCount, 0)}</div>
                    <p>Total de citas que aún no registran atención.</p>
                </section>
                <section class="studies-legend-section">
                    <h3>Bloques horarios</h3>
                    <p>Los bloques con citas avanzan de adentro hacia afuera. Los círculos se colocan de forma consecutiva, sin bandas vacías entre sus puntos.</p>
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
        const outerRadius = Math.max(170, Math.min(width, height) * 0.455);
        const firstLaneRadius = Math.max(110, outerRadius * 0.34);
        const maximumLaneRadius = Math.max(firstLaneRadius + 12, outerRadius - 8);
        const svgNamespace = 'http://www.w3.org/2000/svg';

        container.innerHTML = '';

        const svg = document.createElementNS(svgNamespace, 'svg');
        svg.setAttribute('class', 'wheel-svg-stage');
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.setAttribute('data-base-width', String(width));
        svg.setAttribute('data-base-height', String(height));
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Rueda de órdenes por bloques horarios y semáforo');
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
                : (ordersBySlot.has('OTROS') ? 'OTROS' : null);

            if (slotKey && ordersBySlot.has(slotKey)) {
                ordersBySlot.get(slotKey).push(order);
            }
        });

        const activeSlots = slotDefinitions
            .map(slot => ({
                slot,
                orders: ordersBySlot.get(slot.key) || []
            }))
            .filter(item => item.orders.length > 0);

        /*
         * Cada bloque horario usa exactamente un anillo. El radio de los
         * anillos se distribuye en todo el espacio disponible y el tamaño de
         * los puntos se adapta a la cantidad de órdenes del horario. Así, un
         * horario nunca se parte en dos o más anillos.
         */
        const ringCount = activeSlots.length;
        const radialSpan = Math.max(0, maximumLaneRadius - firstLaneRadius);
        const minimumPointRadius = 1.55;
        const maximumPointRadius = 5.2;
        const minimumPointGap = 1.65;

        const ringLayouts = activeSlots.map(({ slot, orders }, slotIndex) => {
            let radius;

            if (ringCount <= 1) {
                const desiredSpacing = (maximumPointRadius * 2) + minimumPointGap;
                const requiredRadius = (Math.max(1, orders.length) * desiredSpacing) / (Math.PI * 2);
                radius = Math.max(firstLaneRadius, Math.min(maximumLaneRadius, requiredRadius));
            } else {
                radius = firstLaneRadius + ((radialSpan * slotIndex) / (ringCount - 1));
            }

            const availableArcPerPoint = (Math.PI * 2 * Math.max(1, radius)) / Math.max(1, orders.length);
            const fittedPointRadius = Math.max(
                minimumPointRadius,
                Math.min(maximumPointRadius, (availableArcPerPoint - minimumPointGap) / 2)
            );

            return {
                slot,
                orders,
                radius,
                pointRadius: fittedPointRadius
            };
        });

        const gridGroup = document.createElementNS(svgNamespace, 'g');
        gridGroup.setAttribute('aria-hidden', 'true');
        svg.appendChild(gridGroup);

        ringLayouts.forEach((layout, ringIndex) => {
            const guide = document.createElementNS(svgNamespace, 'circle');
            guide.setAttribute('cx', String(centerX));
            guide.setAttribute('cy', String(centerY));
            guide.setAttribute('r', String(layout.radius));
            guide.setAttribute('fill', 'none');
            guide.setAttribute('stroke', ringIndex === ringLayouts.length - 1 ? '#b9c7d8' : '#d5e0ec');
            guide.setAttribute('stroke-width', ringIndex === ringLayouts.length - 1 ? '1.15' : '0.8');
            guide.setAttribute('stroke-dasharray', '3 5');
            guide.setAttribute('opacity', '0.9');
            gridGroup.appendChild(guide);
        });

        const pointsGroup = document.createElementNS(svgNamespace, 'g');
        svg.appendChild(pointsGroup);

        ringLayouts.forEach((layout, slotIndex) => {
            const laneOrders = layout.orders;
            const radius = layout.radius;
            const angularStep = laneOrders.length > 0
                ? (Math.PI * 2) / laneOrders.length
                : 0;
            const startAngle = (-Math.PI / 2) + ((slotIndex % 2) * (angularStep / 2));

            laneOrders.forEach((order, orderIndex) => {
                const angle = startAngle + (orderIndex * angularStep);
                const x = centerX + (Math.cos(angle) * radius);
                const y = centerY + (Math.sin(angle) * radius);
                const semaphore = semaphorePalette[order.semaphore] || semaphorePalette.unknown;
                const pointRadius = layout.pointRadius;

                const point = document.createElementNS(svgNamespace, 'circle');
                point.setAttribute('cx', String(x));
                point.setAttribute('cy', String(y));
                point.setAttribute('r', String(pointRadius));
                point.setAttribute('fill', semaphore.color);
                point.setAttribute('stroke', '#ffffff');
                point.setAttribute('stroke-width', pointRadius <= 2 ? '0.55' : '0.9');
                point.setAttribute('opacity', '0.97');
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

        const centerGroup = document.createElementNS(svgNamespace, 'g');
        centerGroup.setAttribute('aria-hidden', 'true');
        svg.appendChild(centerGroup);

        const largestInnerPoint = ringLayouts.length > 0 ? ringLayouts[0].pointRadius : maximumPointRadius;
        const compactCenterRadius = Math.max(68, firstLaneRadius - largestInnerPoint - 8);
        const centerCircle = document.createElementNS(svgNamespace, 'circle');
        centerCircle.setAttribute('cx', String(centerX));
        centerCircle.setAttribute('cy', String(centerY));
        centerCircle.setAttribute('r', String(compactCenterRadius));
        centerCircle.setAttribute('fill', 'rgba(255,255,255,.94)');
        centerCircle.setAttribute('stroke', '#bfdbfe');
        centerCircle.setAttribute('stroke-width', '1');
        centerCircle.setAttribute('filter', 'drop-shadow(0 2px 6px rgba(15,23,42,.08))');
        centerGroup.appendChild(centerCircle);

        const totalValue = document.createElementNS(svgNamespace, 'text');
        totalValue.setAttribute('x', String(centerX));
        totalValue.setAttribute('y', String(centerY + 6));
        totalValue.setAttribute('text-anchor', 'middle');
        totalValue.setAttribute('fill', '#1d4ed8');
        totalValue.setAttribute('font-size', totalOrders >= 10000 ? '13' : '17');
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
                    <span>Tiempo de atención</span>
                    <strong>${escapeHtml(formatTurnaroundMinutes(order.turnaroundMinutes))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Semáforo</span>
                    <strong><span class="area-tooltip-dot" style="display:inline-block;background:${semaphore.color};margin-right:6px"></span>${escapeHtml(semaphore.label)} · ${escapeHtml(semaphore.range)}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Puerta</span>
                    <strong>${escapeHtml(formatDoor(order.doorID))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Flebotomista</span>
                    <strong>${escapeHtml(order.phlebotomist || 'Sin asignar')}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Inicio de toma</span>
                    <strong>${escapeHtml(formatClinicalDateTime(order.phlebotomyStartAt))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Fin de toma</span>
                    <strong>${escapeHtml(formatClinicalDateTime(order.phlebotomyEndAt))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Tiempo de toma</span>
                    <strong>${escapeHtml(formatTurnaroundMinutes(order.phlebotomyTime))}</strong>
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
                    <span>Tiempo de atención</span>
                    <strong>${escapeHtml(formatTurnaroundMinutes(order.turnaroundMinutes))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Semáforo</span>
                    <strong>${escapeHtml(semaphore.label)} · ${escapeHtml(semaphore.range)}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Puerta</span>
                    <strong>${escapeHtml(formatDoor(order.doorID))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Flebotomista</span>
                    <strong>${escapeHtml(order.phlebotomist || 'Sin asignar')}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Inicio de toma</span>
                    <strong>${escapeHtml(formatClinicalDateTime(order.phlebotomyStartAt))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Fin de toma</span>
                    <strong>${escapeHtml(formatClinicalDateTime(order.phlebotomyEndAt))}</strong>
                </div>
                <div class="order-tooltip-meta">
                    <span>Tiempo de toma</span>
                    <strong>${escapeHtml(formatTurnaroundMinutes(order.phlebotomyTime))}</strong>
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

    function formatClinicalDateTime(value) {
        const raw = String(value || '').trim();

        if (raw === '' || raw.startsWith('0001-01-01')) {
            return 'Sin registro';
        }

        const date = new Date(raw.replace(' ', 'T'));

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

    function formatDoor(value) {
        const door = Number(value);
        return Number.isFinite(door) && door > 0
            ? `Puerta ${Highcharts.numberFormat(door, 0)}`
            : 'Sin puerta';
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


</script>
@endsection
