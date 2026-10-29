@extends('layouts.app')

@section('title', 'Productividad | Hospital Dashboard')
@section('body-class', 'page-productivity')

@section('head')
    <script src="https://code.highcharts.com/highcharts.js"></script>
@endsection

@section('styles')
<style>
    .productivity-header {
        margin-bottom: 24px;
    }

    .productivity-title {
        font-size: 30px;
        font-weight: 850;
        margin-bottom: 6px;
        color: #ffffff;
        text-shadow: 0 2px 10px rgba(15, 23, 42, 0.28);
    }

    .productivity-subtitle {
        font-size: 15px;
        color: rgba(255, 255, 255, 0.84);
    }

    .notice-card {
        background: #fff7ed;
        color: #9a3412;
        border: 1px solid #fed7aa;
        border-radius: 18px;
        padding: 16px 18px;
        margin-bottom: 20px;
        font-size: 14px;
        font-weight: 750;
        box-shadow: 0 10px 26px rgba(154, 52, 18, 0.08);
    }

    .source-card,
    .filter-card,
    .kpi-card,
    .chart-card,
    .table-card,
    .tabs-card,
    .insight-card {
        background: rgba(255, 255, 255, 0.96);
        border-radius: 20px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        border: 1px solid rgba(226, 232, 240, 0.95);
    }

    .source-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 18px;
        margin-bottom: 18px;
        color: #475569;
        font-size: 13px;
        font-weight: 750;
        flex-wrap: wrap;
    }

    .source-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 900;
        border: 1px solid transparent;
    }

    .source-real {
        background: #dcfce7;
        color: #166534;
        border-color: #bbf7d0;
    }

    .source-mock {
        background: #fef3c7;
        color: #92400e;
        border-color: #fde68a;
    }

    .insight-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 22px;
    }

    .insight-card {
        padding: 18px;
        position: relative;
        overflow: hidden;
    }

    .insight-card::after,
    .kpi-card::after {
        content: '';
        position: absolute;
        right: -42px;
        top: -42px;
        width: 96px;
        height: 96px;
        border-radius: 999px;
        background: rgba(37, 99, 235, 0.07);
    }

    .insight-label {
        position: relative;
        color: #64748b;
        font-size: 12px;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 8px;
    }

    .insight-value {
        position: relative;
        color: #0f172a;
        font-size: 20px;
        font-weight: 950;
        line-height: 1.15;
    }

    .insight-helper {
        position: relative;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 750;
        margin-top: 6px;
    }

    .filter-card {
        margin-bottom: 24px;
        padding: 24px;
    }

    .filter-title-row {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .filter-title {
        font-size: 18px;
        font-weight: 900;
        margin-bottom: 4px;
        color: #111827;
    }

    .filter-help {
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr) auto;
        gap: 16px;
        align-items: end;
    }

    .filter-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 18px;
        margin-bottom: 24px;
    }

    .kpi-card {
        position: relative;
        overflow: hidden;
        padding: 20px;
    }

    .kpi-topline {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 9px;
    }

    .kpi-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 850;
    }

    .kpi-icon {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 16px;
        border: 1px solid #dbeafe;
        flex: 0 0 auto;
    }

    .kpi-value {
        position: relative;
        font-size: 31px;
        font-weight: 950;
        color: #0f172a;
        line-height: 1;
    }

    .kpi-helper {
        position: relative;
        margin-top: 8px;
        font-size: 12px;
        color: #94a3b8;
        font-weight: 750;
    }

    .mini-progress {
        position: relative;
        width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
        margin-top: 12px;
    }

    .mini-progress > span {
        display: block;
        height: 100%;
        border-radius: 999px;
        width: var(--value, 0%);
    }

    .progress-good { background: linear-gradient(90deg, #22c55e, #16a34a); }
    .progress-warn { background: linear-gradient(90deg, #f59e0b, #f97316); }
    .progress-bad { background: linear-gradient(90deg, #ef4444, #dc2626); }
    .progress-blue { background: linear-gradient(90deg, #38bdf8, #2563eb); }

    .tabs-card {
        margin-bottom: 24px;
        padding: 16px;
    }

    .tabs-nav {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .tab-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border: 1px solid #dbeafe;
        background: #f8fafc;
        color: #475569;
        border-radius: 999px;
        padding: 10px 15px;
        font-weight: 900;
        line-height: 1;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.18s ease;
    }

    .tab-btn svg {
        width: 16px;
        height: 16px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .productivity-service-divider {
        width: 1px;
        min-height: 34px;
        margin: 0 2px;
        background: #dbe4f0;
        align-self: center;
    }

    .productivity-service-tab {
        background: #ffffff;
        border-color: #cfe0f7;
        color: #174b91;
    }

    .productivity-service-context {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 8px;
        padding: 7px 11px;
        border-radius: 999px;
        background: #eef6ff;
        border: 1px solid #cfe2ff;
        color: #1456b8;
        font-size: 11px;
        font-weight: 900;
    }

    .productivity-service-context::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: #2563eb;
        box-shadow: 0 0 0 4px rgba(37,99,235,.12);
    }

    .tab-btn:hover {
        background: #eff6ff;
        color: #1d4ed8;
        transform: translateY(-1px);
    }

    .tab-btn.active {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
    }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    .chart-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }

    .chart-card,
    .table-card {
        padding: 24px;
    }

    .chart-card {
        min-height: 430px;
    }

    #shiftChart,
    #userChart,
    #areaChart {
        width: 100%;
        height: 390px;
    }

    .table-card {
        margin-bottom: 24px;
        overflow: hidden;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .table-title {
        font-size: 18px;
        font-weight: 900;
        color: #111827;
    }

    .table-subtitle {
        color: #64748b;
        font-size: 13px;
        margin-top: 3px;
        font-weight: 700;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .table-responsive table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-responsive th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 900;
        padding: 13px 14px;
        border-bottom: 1px solid #e2e8f0;
    }

    .table-responsive td {
        padding: 14px;
        border-bottom: 1px solid #eef2f7;
        color: #334155;
        font-weight: 700;
        vertical-align: middle;
    }

    .table-responsive tbody tr:hover td {
        background: #f8fafc;
    }

    .number { text-align: right; }

    .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        min-height: 24px;
        border-radius: 999px;
        padding: 4px 9px;
        font-size: 12px;
        font-weight: 900;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    @keyframes productivityGlowGreen {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 rgba(34, 197, 94, 0);
            filter: brightness(1);
        }
        50% {
            transform: scale(1.035);
            box-shadow: 0 0 16px rgba(34, 197, 94, 0.32);
            filter: brightness(1.06);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 rgba(34, 197, 94, 0);
            filter: brightness(1);
        }
    }

    @keyframes productivityGlowYellow {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 rgba(245, 158, 11, 0);
            filter: brightness(1);
        }
        50% {
            transform: scale(1.035);
            box-shadow: 0 0 16px rgba(245, 158, 11, 0.34);
            filter: brightness(1.06);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 rgba(245, 158, 11, 0);
            filter: brightness(1);
        }
    }

    @keyframes productivityGlowRed {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 rgba(239, 68, 68, 0);
            filter: brightness(1);
        }
        50% {
            transform: scale(1.035);
            box-shadow: 0 0 18px rgba(239, 68, 68, 0.36);
            filter: brightness(1.06);
        }
        100% {
            transform: scale(1);
            box-shadow: 0 0 0 rgba(239, 68, 68, 0);
            filter: brightness(1);
        }
    }

    .badge-range { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .badge-out { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
    .badge-warning { background: #fef9c3; color: #854d0e; border-color: #fde68a; }

    .status-badge {
        transform-origin: center;
        will-change: transform, box-shadow, filter;
    }

    .badge-range.status-badge {
        animation: productivityGlowGreen 2.2s ease-in-out infinite;
    }

    .badge-warning.status-badge {
        animation: productivityGlowYellow 1.8s ease-in-out infinite;
    }

    .badge-out.status-badge {
        animation: productivityGlowRed 1.45s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {
        .status-badge {
            animation: none !important;
        }
    }

    .muted-small {
        display: block;
        color: #94a3b8;
        font-size: 12px;
        margin-top: 4px;
        font-weight: 700;
    }

    .ratio-bar {
        min-width: 170px;
    }

    .ratio-track {
        display: flex;
        width: 100%;
        height: 10px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
        margin-top: 8px;
    }

    .ratio-in { background: #22c55e; width: var(--in, 0%); }
    .ratio-out { background: #ef4444; width: var(--out, 0%); }

    .empty-state {
        text-align: center;
        padding: 52px 18px;
        color: #64748b;
        font-weight: 800;
    }

    .empty-icon {
        width: 56px;
        height: 56px;
        margin: 0 auto 12px;
        border-radius: 18px;
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        border: 1px solid #dbeafe;
    }


    .duplicate-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .duplicate-summary-card {
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .duplicate-summary-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .duplicate-summary-value {
        margin-top: 7px;
        color: #0f172a;
        font-size: 29px;
        line-height: 1;
        font-weight: 950;
    }

    .duplicate-loader,
    .duplicate-message {
        border-radius: 16px;
        padding: 18px;
        text-align: center;
        font-weight: 800;
        color: #475569;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        margin-bottom: 18px;
    }

    .duplicate-message.is-error {
        color: #991b1b;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .duplicate-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .duplicate-search {
        width: min(360px, 100%);
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 10px 13px;
        font: inherit;
    }

    .duplicate-test-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        max-width: 620px;
    }

    .duplicate-test-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 9px;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-size: 11px;
        font-weight: 850;
    }

    .duplicate-pagination {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 16px;
        flex-wrap: wrap;
    }

    .duplicate-pagination button {
        border: 1px solid #dbeafe;
        background: #ffffff;
        color: #1d4ed8;
        border-radius: 10px;
        padding: 8px 12px;
        font-weight: 850;
        cursor: pointer;
    }

    .duplicate-pagination button:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    @media (max-width: 1500px) {
        .filter-grid { grid-template-columns: repeat(3, 1fr); }
        .kpi-grid { grid-template-columns: repeat(3, 1fr); }
        .insight-grid { grid-template-columns: repeat(2, 1fr); }
        .duplicate-summary-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 1000px) {
        .chart-grid { grid-template-columns: 1fr; }
        .filter-grid,
        .kpi-grid { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 640px) {
        .filter-grid,
        .kpi-grid,
        .insight-grid { grid-template-columns: 1fr; }
        .duplicate-summary-grid { grid-template-columns: 1fr; }
    }


    /* Ajuste paso 8: filtro semanal y búsqueda en productividad por usuario */
    .productivity-week-helper {
        margin-top: 5px;
        color: #7a879a;
        font-size: 8px;
        font-weight: 750;
    }

    .productivity-user-tools {
        display: flex;
        align-items: center;
        gap: 9px;
        flex-wrap: wrap;
    }

    .productivity-user-search {
        width: 260px !important;
        min-height: 34px !important;
        padding-left: 32px !important;
        font-size: 9px !important;
    }

    .productivity-user-search-wrap {
        position: relative;
    }

    .productivity-user-search-wrap svg {
        position: absolute;
        left: 11px;
        top: 50%;
        width: 14px;
        height: 14px;
        transform: translateY(-50%);
        fill: none;
        stroke: #6e7f98;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
        pointer-events: none;
    }

    .productivity-user-empty-row td {
        padding: 18px !important;
        text-align: center;
        color: #7a879a !important;
        font-weight: 750;
    }

    @media (max-width: 760px) {
        .productivity-user-tools {
            width: 100%;
            align-items: stretch;
            flex-direction: column;
        }

        .productivity-user-search {
            width: 100% !important;
        }
    }


    /* =========================================================
       AJUSTE FINAL · SELECTOR DIARIO
       ========================================================= */

    .page-productivity .productivity-filter-grid {
        align-items: end !important;
    }

    .page-productivity .productivity-day-group {
        position: relative;
        align-self: end !important;
        min-width: 0;
    }

    .page-productivity .productivity-day-control-wrap {
        position: relative;
        width: 100%;
    }

    .page-productivity .productivity-day-control {
        width: 100%;
        min-height: 38px;
        padding: 8px 11px;
        border: 1px solid #d8e1ed;
        border-radius: 9px;
        background: #ffffff;
        color: var(--inst-text);
        font: inherit;
        font-size: 11px;
        font-weight: 750;
        line-height: 1;
        outline: none;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, .015);
    }

    .page-productivity .productivity-day-control:hover,
    .page-productivity .productivity-day-control:focus {
        border-color: #75a8ff;
        box-shadow: 0 0 0 3px rgba(20,104,243,.10);
    }

    .page-productivity .productivity-day-helper {
        display: block;
        margin-top: 5px;
        padding-left: 1px;
        color: #7a879a;
        font-size: 8px;
        line-height: 1.2;
        font-weight: 750;
        text-transform: capitalize;
        white-space: nowrap;
    }

    .page-productivity .productivity-week-group {
        position: relative;
        align-self: end !important;
        min-width: 0;
    }

    .page-productivity .productivity-week-picker {
        position: relative;
        width: 100%;
    }

    .page-productivity .productivity-week-control {
        width: 100%;
        min-height: 38px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 11px;
        border: 1px solid #d8e1ed;
        border-radius: 9px;
        background: #ffffff;
        color: var(--inst-text);
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        text-align: left;
        cursor: pointer;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, .015);
    }

    .page-productivity .productivity-week-control:hover,
    .page-productivity .productivity-week-control.is-open {
        border-color: #75a8ff;
        box-shadow: 0 0 0 3px rgba(20,104,243,.10);
    }

    .page-productivity .productivity-week-control svg {
        width: 15px;
        height: 15px;
        flex: 0 0 15px;
        fill: none;
        stroke: #526984;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .page-productivity .productivity-week-helper {
        position: static !important;
        display: block !important;
        margin-top: 5px !important;
        padding-left: 1px !important;
        color: #7a879a !important;
        font-size: 8px !important;
        line-height: 1.2 !important;
        font-weight: 750 !important;
        white-space: nowrap;
    }

    .page-productivity .productivity-week-popover {
        position: absolute;
        left: 0;
        top: calc(100% + 7px);
        width: 318px;
        padding: 12px;
        border: 1px solid #d6e1ef;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 18px 44px rgba(15, 35, 70, .16);
        z-index: 250;
        display: none;
    }

    .page-productivity .productivity-week-popover.is-open {
        display: block;
    }

    .page-productivity .productivity-week-calendar-head {
        display: grid;
        grid-template-columns: 32px 1fr 32px;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
    }

    .page-productivity .productivity-week-calendar-head strong {
        text-align: center;
        color: #102142;
        font-size: 12px;
        font-weight: 900;
        text-transform: capitalize;
    }

    .page-productivity .productivity-week-calendar-head button,
    .page-productivity .productivity-week-calendar-footer button {
        border: 1px solid #dbe5f1;
        background: #ffffff;
        color: #31415e;
        border-radius: 9px;
        cursor: pointer;
        font-weight: 850;
    }

    .page-productivity .productivity-week-calendar-head button {
        width: 32px;
        height: 32px;
        font-size: 18px;
        line-height: 1;
    }

    .page-productivity .productivity-week-calendar-head button:hover,
    .page-productivity .productivity-week-calendar-footer button:hover {
        border-color: #9fc2ff;
        background: #edf4ff;
        color: var(--inst-blue-600);
    }

    .page-productivity .productivity-week-days,
    .page-productivity .productivity-week-row {
        display: grid;
        grid-template-columns: 34px repeat(7, 1fr);
        gap: 4px;
        align-items: center;
    }

    .page-productivity .productivity-week-days {
        margin-bottom: 5px;
        color: #65738d;
        font-size: 9px;
        font-weight: 900;
        text-align: center;
    }

    .page-productivity .productivity-week-row {
        min-height: 31px;
        margin-bottom: 4px;
        border-radius: 10px;
    }

    .page-productivity .productivity-week-row:hover {
        background: #f5f8ff;
    }

    .page-productivity .productivity-week-number {
        color: #7a879a;
        font-size: 9px;
        font-weight: 900;
        text-align: center;
    }

    .page-productivity .productivity-week-day {
        width: 100%;
        height: 28px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: #102142;
        font-size: 10px;
        font-weight: 800;
        cursor: pointer;
    }

    .page-productivity .productivity-week-day.is-muted {
        color: #b1bccb;
    }

    .page-productivity .productivity-week-day:hover {
        background: #eaf2ff;
        color: var(--inst-blue-600);
    }

    .page-productivity .productivity-week-row.is-selected {
        background: #eaf2ff;
        box-shadow: inset 0 0 0 1px #bdd3ff;
    }

    .page-productivity .productivity-week-row.is-selected .productivity-week-number {
        color: var(--inst-blue-600);
    }

    .page-productivity .productivity-week-row.is-selected .productivity-week-day {
        color: #0d5fe8;
        background: #dbeafe;
    }

    .page-productivity .productivity-week-row.is-selected .productivity-week-day:first-of-type,
    .page-productivity .productivity-week-row.is-selected .productivity-week-day:last-of-type {
        background: #2478fa;
        color: #ffffff;
    }

    .page-productivity .productivity-week-day.is-today {
        box-shadow: inset 0 0 0 2px #1468f3;
    }

    .page-productivity .productivity-week-calendar-footer {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        padding-top: 8px;
        border-top: 1px solid #edf1f6;
        margin-top: 8px;
    }

    .page-productivity .productivity-week-calendar-footer button {
        min-height: 30px;
        padding: 0 10px;
        color: #1468f3;
        font-size: 9px;
    }

    .page-productivity .productivity-filter-actions {
        align-self: end !important;
        padding-top: 0 !important;
    }

    @media (max-width: 760px) {
        .page-productivity .productivity-week-popover {
            width: min(318px, calc(100vw - 42px));
        }
    }

    /* =========================================================
       ALINEACIÓN FINAL · FILTROS Y BOTONES
       La ayuda descriptiva debajo de Fecha hacía que el resto de
       los controles se alineara contra la parte inferior del grupo.
       Ahora todos comienzan desde la misma línea superior.
       ========================================================= */
    .page-productivity .productivity-filter-grid {
        align-items: start !important;
    }

    .page-productivity .productivity-filter-grid > .form-group,
    .page-productivity .productivity-day-group {
        align-self: start !important;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .page-productivity .productivity-filter-grid > .form-group > label {
        display: block;
        min-height: 14px;
        line-height: 14px;
        margin: 0 0 6px !important;
    }

    .page-productivity .productivity-filter-grid select,
    .page-productivity .productivity-day-control,
    .page-productivity .productivity-filter-actions .btn {
        height: 40px !important;
        min-height: 40px !important;
    }

    .page-productivity .productivity-filter-actions {
        align-self: start !important;
        align-items: flex-start !important;
        padding-top: 20px !important;
    }

    @media (max-width: 640px) {
        .page-productivity .productivity-filter-actions {
            padding-top: 0 !important;
            justify-content: stretch !important;
        }

        .page-productivity .productivity-filter-actions .btn {
            flex: 1 1 0;
        }
    }

</style>
@endsection

@section('content')
@php
    try {
        $productivitySelectedDate = \Carbon\Carbon::parse($filters['date_from'] ?? now())->startOfDay();
    } catch (\Throwable $e) {
        $productivitySelectedDate = now()->startOfDay();
    }

    $productivityDateValue = $productivitySelectedDate->toDateString();
    $productivityDateLabel = $productivitySelectedDate->locale('es')->translatedFormat('l, d \d\e F \d\e Y');

    $activeServiceScope = $activeServiceScope ?? 'all';
    $activeServiceLabel = $activeServiceLabel ?? 'Tablero general';

    $productivityScopeQuery = array_filter([
        'date_from' => $filters['date_from'] ?? $productivityDateValue,
        'date_to' => $filters['date_to'] ?? $productivityDateValue,
        'shift' => $filters['shift'] ?? null,
        'user_key' => $filters['user_key'] ?? null,
        'area' => $filters['area'] ?? null,
    ], static fn ($value) => $value !== null && $value !== '');

    $generalProductivityUrl = route('dashboards.productividad', array_merge($productivityScopeQuery, [
        'service_scope' => 'all',
    ]));

    $outpatientUrl = route('dashboards.productividad', array_merge($productivityScopeQuery, [
        'service_scope' => 'consulta_externa',
    ]));

    $hospitalizationUrl = route('dashboards.productividad', array_merge($productivityScopeQuery, [
        'service_scope' => 'hospitalizacion',
    ]));

    $clearProductivityUrl = route('dashboards.productividad', $activeServiceScope !== 'all'
        ? ['service_scope' => $activeServiceScope]
        : []);
@endphp
    <div class="productivity-header productivity-page-header">
        <div>
            <h1 class="productivity-title">Tablero de productividad</h1>
            <p class="productivity-subtitle">Monitorea el desempeño del laboratorio en tiempo real.</p>
            <span class="productivity-service-context">Vista: {{ $activeServiceLabel }}</span>
        </div>
    </div>

    @if ($errorMessage)
        <div class="notice-card productivity-notice">
            {{ $errorMessage }}
        </div>
    @endif

    <section class="filter-card productivity-filter-card">
        <div class="productivity-section-heading">
            <span class="productivity-heading-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 5h16l-6 7v5l-4 2v-7z"/></svg>
            </span>
            <div>
                <h2>Filtros</h2>
                <p>Usa los filtros para ajustar la información mostrada en el tablero.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('dashboards.productividad') }}">
            <input type="hidden" name="service_scope" value="{{ $activeServiceScope }}">
            <div class="filter-grid productivity-filter-grid">
                <div class="form-group productivity-day-group">
                    <label for="date_from">Fecha</label>
                    <div class="productivity-day-control-wrap">
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            class="productivity-day-control"
                            value="{{ $productivityDateValue }}"
                            required
                        >
                        <input type="hidden" id="date_to" name="date_to" value="{{ $productivityDateValue }}">
                    </div>
                    <div class="productivity-day-helper" id="productivityDayLabel">{{ ucfirst($productivityDateLabel) }}</div>
                </div>

                <div class="form-group">
                    <label for="shift">Turno</label>
                    <select id="shift" name="shift">
                        <option value="">Todos</option>
                        @foreach ($shiftCatalog as $shiftKey => $shiftLabel)
                            <option value="{{ $shiftKey }}" {{ ($filters['shift'] ?? '') === $shiftKey ? 'selected' : '' }}>
                                {{ $shiftLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="user_key">Usuario</label>
                    <select id="user_key" name="user_key">
                        <option value="">Todos</option>
                        @foreach ($availableUsers as $user)
                            <option value="{{ $user['key'] }}" {{ ($filters['user_key'] ?? '') === $user['key'] ? 'selected' : '' }}>
                                {{ $user['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="area">Área</label>
                    <select id="area" name="area">
                        <option value="">Todas</option>
                        @foreach ($availableAreas as $area)
                            <option value="{{ $area }}" {{ ($filters['area'] ?? '') === $area ? 'selected' : '' }}>
                                {{ $area }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions productivity-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                        <span>Aplicar</span>
                    </button>
                    <a href="{{ $clearProductivityUrl }}" class="btn btn-outline">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
                        <span>Limpiar</span>
                    </a>
                </div>
            </div>
        </form>
    </section>

    <section class="kpi-grid productivity-kpi-grid" aria-label="Indicadores de productividad">
        <article class="kpi-card productivity-kpi-card productivity-kpi-blue">
            <span class="productivity-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 4h10v16H7zM9 2h6v4H9zM10 10h4M10 14h4"/></svg></span>
            <div class="productivity-kpi-copy">
                <div class="kpi-label">Órdenes validadas</div>
                <div class="kpi-value">{{ number_format($summary['ordersTotal']) }}</div>
                <div class="kpi-helper">Órdenes únicas</div>
            </div>
        </article>

        <article class="kpi-card productivity-kpi-card productivity-kpi-purple">
            <span class="productivity-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 3h6m-5 0v5l-5 9a2 2 0 0 0 1.7 3h10.6A2 2 0 0 0 19 17l-5-9V3M8 14h8"/></svg></span>
            <div class="productivity-kpi-copy">
                <div class="kpi-label">Exámenes validados</div>
                <div class="kpi-value">{{ number_format($summary['examsTotal']) }}</div>
                <div class="kpi-helper">Registros procesados</div>
            </div>
        </article>

        <article class="kpi-card productivity-kpi-card productivity-kpi-green">
            <span class="productivity-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg></span>
            <div class="productivity-kpi-copy">
                <div class="kpi-label">Exámenes en rango</div>
                <div class="kpi-value">{{ number_format($summary['examsInRange']) }}</div>
                <div class="kpi-helper">{{ $summary['examsInRangePercent'] }}% dentro de ≤ {{ $opportunityLimitMinutes }} min</div>
                <div class="mini-progress"><span class="progress-good" style="--value: {{ $summary['examsInRangePercent'] }}%;"></span></div>
            </div>
        </article>

        <article class="kpi-card productivity-kpi-card productivity-kpi-red">
            <span class="productivity-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 3 20h18L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg></span>
            <div class="productivity-kpi-copy">
                <div class="kpi-label">Exámenes fuera de rango</div>
                <div class="kpi-value">{{ number_format($summary['examsOutRange']) }}</div>
                <div class="kpi-helper">
                    {{ $summary['examsOutRangePercent'] }}% arriba de {{ $opportunityLimitMinutes }} min
                    · {{ number_format($summary['examsWarning'] ?? 0) }} entre 61–90
                    · {{ number_format($summary['examsCritical'] ?? 0) }} mayores de 90
                </div>
                <div class="mini-progress"><span class="progress-bad" style="--value: {{ $summary['examsOutRangePercent'] }}%;"></span></div>
            </div>
        </article>

        <article class="kpi-card productivity-kpi-card productivity-kpi-orange">
            <span class="productivity-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3h10v18H7zM10 7h4M10 11h4M10 15h2"/><circle cx="17" cy="17" r="3"/></svg></span>
            <div class="productivity-kpi-copy">
                <div class="kpi-label">Órdenes fuera de rango</div>
                <div class="kpi-value">{{ number_format($summary['ordersOutRange']) }}</div>
                <div class="kpi-helper">{{ $summary['ordersOutRangePercent'] }}% según peor tiempo</div>
                <div class="mini-progress"><span class="progress-warn" style="--value: {{ $summary['ordersOutRangePercent'] }}%;"></span></div>
            </div>
        </article>

        <article class="kpi-card productivity-kpi-card productivity-kpi-cyan">
            <span class="productivity-kpi-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
            <div class="productivity-kpi-copy">
                <div class="kpi-label">Tiempo promedio</div>
                <div class="kpi-value">{{ number_format($summary['averageOpTime']) }}</div>
                <div class="kpi-helper">Minutos promedio</div>
                <div class="mini-progress"><span class="progress-blue" style="--value: {{ min($summary['averageOpTime'], 100) }}%;"></span></div>
            </div>
        </article>
    </section>

    @if ($summary['examsTotal'] === 0)
        <div class="tabs-card productivity-tabs-card">
            <div class="tabs-nav productivity-tabs-nav">
                <a
                    href="{{ $generalProductivityUrl }}"
                    class="tab-btn productivity-service-tab {{ $activeServiceScope === 'all' ? 'active' : '' }}"
                >
                    <span>General</span>
                </a>
                <a
                    href="{{ $hospitalizationUrl }}"
                    class="tab-btn productivity-service-tab {{ $activeServiceScope === 'hospitalizacion' ? 'active' : '' }}"
                >
                    <span>Hospitalización</span>
                </a>
                <a
                    href="{{ $outpatientUrl }}"
                    class="tab-btn productivity-service-tab {{ $activeServiceScope === 'consulta_externa' ? 'active' : '' }}"
                >
                    <span>Consulta externa</span>
                </a>
            </div>
        </div>

        <div class="table-card">
            <div class="empty-state">
                <div class="empty-icon">🔎</div>
                No hay datos para el día, servicio y filtros seleccionados.<br>
                <span class="muted-small">Prueba seleccionando otra fecha, turno, usuario, área o servicio.</span>
            </div>
        </div>
    @else
        <div class="tabs-card productivity-tabs-card">
            <div class="tabs-nav productivity-tabs-nav">
                <a
                    href="{{ $generalProductivityUrl }}"
                    class="tab-btn productivity-service-tab {{ $activeServiceScope === 'all' ? 'active' : '' }}"
                    aria-current="{{ $activeServiceScope === 'all' ? 'page' : 'false' }}"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/></svg>
                    <span>General</span>
                </a>

                <a
                    href="{{ $hospitalizationUrl }}"
                    class="tab-btn productivity-service-tab {{ $activeServiceScope === 'hospitalizacion' ? 'active' : '' }}"
                    aria-current="{{ $activeServiceScope === 'hospitalizacion' ? 'page' : 'false' }}"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V6h16v15M8 6V3h8v3M4 13h16M8 10h.01M12 10h.01M16 10h.01M8 17h.01M12 17h.01M16 17h.01"/></svg>
                    <span>Hospitalización</span>
                </a>

                <a
                    href="{{ $outpatientUrl }}"
                    class="tab-btn productivity-service-tab {{ $activeServiceScope === 'consulta_externa' ? 'active' : '' }}"
                    aria-current="{{ $activeServiceScope === 'consulta_externa' ? 'page' : 'false' }}"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18M3 12h18"/><circle cx="12" cy="12" r="9"/></svg>
                    <span>Consulta externa</span>
                </a>

                <span class="productivity-service-divider" aria-hidden="true"></span>

                <button type="button" class="tab-btn active" data-tab-target="resumen">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/></svg>
                    <span>Resumen gráfico</span>
                </button>
                <button type="button" class="tab-btn" data-tab-target="usuarios">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span>Productividad por usuario</span>
                </button>
                <button type="button" class="tab-btn" data-tab-target="areas">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V8h6v12M14 20V4h6v16M2 20h20"/></svg>
                    <span>Productividad por área</span>
                </button>
            </div>
        </div>

        <section id="tab-resumen" class="tab-panel active">
            <div class="chart-grid productivity-chart-grid">
                <article class="chart-card productivity-chart-card">
                    <div class="productivity-card-header">
                        <div>
                            <h2>Productividad por turno</h2>
                            <p>Órdenes y exámenes validados, separados por tiempo de oportunidad</p>
                        </div>
                        <button type="button" class="productivity-more-button" aria-label="Más opciones"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg></button>
                    </div>
                    <div id="shiftChart"></div>
                </article>

                <article class="chart-card productivity-chart-card">
                    <div class="productivity-card-header">
                        <div>
                            <h2>Exámenes por usuario</h2>
                            <p>Top usuarios por volumen validado</p>
                        </div>
                        <button type="button" class="productivity-more-button" aria-label="Más opciones"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg></button>
                    </div>
                    <div id="userChart"></div>
                </article>
            </div>
        </section>

        <section id="tab-usuarios" class="tab-panel">
            <div class="table-card productivity-data-card productivity-user-card">
                <div class="table-header">
                    <div>
                        <div class="table-title">Productividad por usuario</div>
                        <div class="table-subtitle">Llave formada con ID de usuario + nombre.</div>
                    </div>
                    <div class="productivity-user-tools">
                        <div class="productivity-user-search-wrap">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                            <input type="search" id="productivityUserSearch" class="productivity-user-search" placeholder="Buscar usuario o ID..." autocomplete="off">
                        </div>
                        <span class="badge badge-range" id="productivityUserCount">{{ count($userSummary) }} usuario(s)</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="productivity-data-table productivity-user-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th class="number">Órdenes</th>
                                <th class="number">Exámenes</th>
                                <th class="number">En rango</th>
                                <th class="number">Fuera de rango</th>
                                <th>Cumplimiento</th>
                                <th class="number">Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($userSummary as $user)
                                <tr>
                                    <td>
                                        <strong>{{ $user['userName'] }}</strong>
                                        <span class="muted-small">{{ $user['userKey'] }}</span>
                                    </td>
                                    <td class="number">{{ number_format($user['ordersTotal']) }}</td>
                                    <td class="number">{{ number_format($user['examsTotal']) }}</td>
                                    <td class="number"><span class="badge badge-range status-badge">{{ number_format($user['examsInRange']) }}</span></td>
                                    <td class="number"><span class="badge status-badge {{ $user['examsOutRange'] > 0 ? 'badge-out' : 'badge-range' }}">{{ number_format($user['examsOutRange']) }}</span></td>
                                    <td>
                                        <div class="ratio-bar">
                                            <strong>{{ $user['examsInRangePercent'] }}%</strong>
                                            <span class="muted-small">{{ $user['examsInRange'] }} en rango · {{ $user['examsOutRange'] }} fuera</span>
                                            <div class="ratio-track" style="--in: {{ $user['examsInRangePercent'] }}%; --out: {{ $user['examsOutRangePercent'] }}%;">
                                                <span class="ratio-in"></span>
                                                <span class="ratio-out"></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="number">{{ number_format($user['averageOpTime']) }} min</td>
                                </tr>
                            @endforeach
                            <tr id="productivityUserEmptyRow" class="productivity-user-empty-row" style="display:none;">
                                <td colspan="7">No se encontraron usuarios con la búsqueda actual.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="tab-areas" class="tab-panel">
            <div class="chart-card productivity-area-chart-card" style="margin-bottom:14px;">
                <div id="areaChart"></div>
            </div>

            <div class="table-card productivity-data-card productivity-area-card">
                <div class="table-header">
                    <div>
                        <div class="table-title">Productividad por área</div>
                        <div class="table-subtitle">Órdenes y exámenes por área de laboratorio.</div>
                    </div>
                    <span class="badge badge-range">{{ count($areaSummary) }} área(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="productivity-data-table productivity-area-table">
                        <thead>
                            <tr>
                                <th>Área</th>
                                <th class="number">Órdenes</th>
                                <th class="number">Exámenes</th>
                                <th class="number">En rango</th>
                                <th class="number">Fuera de rango</th>
                                <th>Cumplimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($areaSummary as $area)
                                <tr>
                                    <td><strong>{{ $area['area'] }}</strong></td>
                                    <td class="number">{{ number_format($area['ordersTotal']) }}</td>
                                    <td class="number">{{ number_format($area['examsTotal']) }}</td>
                                    <td class="number"><span class="badge badge-range status-badge">{{ number_format($area['examsInRange']) }}</span></td>
                                    <td class="number"><span class="badge status-badge {{ $area['examsOutRange'] > 0 ? 'badge-out' : 'badge-range' }}">{{ number_format($area['examsOutRange']) }}</span></td>
                                    <td>
                                        <div class="ratio-bar">
                                            <strong>{{ $area['examsInRangePercent'] }}%</strong>
                                            <span class="muted-small">{{ $area['examsInRange'] }} en rango · {{ $area['examsOutRange'] }} fuera</span>
                                            <div class="ratio-track" style="--in: {{ $area['examsInRangePercent'] }}%; --out: {{ $area['examsOutRangePercent'] }}%;">
                                                <span class="ratio-in"></span>
                                                <span class="ratio-out"></span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif
@endsection

@section('scripts')
<script>
    const shiftSummary = @json($shiftSummary);
    const userSummary = @json($userSummary);
    const areaSummary = @json($areaSummary);

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.tab-btn[data-tab-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                const target = button.dataset.tabTarget;

                document.querySelectorAll('.tab-btn[data-tab-target]').forEach(function (item) {
                    item.classList.remove('active');
                });

                document.querySelectorAll('.tab-panel').forEach(function (panel) {
                    panel.classList.remove('active');
                });

                button.classList.add('active');
                document.getElementById('tab-' + target).classList.add('active');

                setTimeout(function () {
                    Highcharts.charts.forEach(function (chart) {
                        if (chart) {
                            chart.reflow();
                        }
                    });
                }, 80);
            });
        });

        if (document.getElementById('shiftChart')) {
            renderShiftChart();
        }
        if (document.getElementById('userChart')) {
            renderUserChart();
        }
        if (document.getElementById('areaChart')) {
            renderAreaChart();
        }


        initProductivityDayFilter();
        initProductivityUserSearch();
    });

    const chartColors = {
        orders: '#38bdf8',
        exams: '#6366f1',
        inRange: '#22c55e',
        warning: '#f59e0b',
        critical: '#ef4444',
        outRange: '#ef4444',
    };


    function initProductivityDayFilter() {
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');
        const label = document.getElementById('productivityDayLabel');

        if (!dateFrom || !dateTo) {
            return;
        }

        const dayNames = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        const monthNames = [
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
        ];

        function updateSelectedDay() {
            const selected = String(dateFrom.value || '').trim();
            dateTo.value = selected;

            if (!label || selected === '') {
                return;
            }

            const parts = selected.split('-').map(Number);
            if (parts.length !== 3 || parts.some(Number.isNaN)) {
                label.textContent = selected;
                return;
            }

            const date = new Date(parts[0], parts[1] - 1, parts[2]);
            label.textContent = `${dayNames[date.getDay()]}, ${String(parts[2]).padStart(2, '0')} de ${monthNames[parts[1] - 1]} de ${parts[0]}`;
        }

        dateFrom.addEventListener('input', updateSelectedDay);
        dateFrom.addEventListener('change', updateSelectedDay);
        dateFrom.closest('form')?.addEventListener('submit', updateSelectedDay);
        updateSelectedDay();
    }

    function initProductivityUserSearch() {
        const search = document.getElementById('productivityUserSearch');
        const table = document.querySelector('.productivity-user-table');
        const count = document.getElementById('productivityUserCount');
        const emptyRow = document.getElementById('productivityUserEmptyRow');

        if (!search || !table) {
            return;
        }

        const rows = Array.from(table.querySelectorAll('tbody tr')).filter(row => row.id !== 'productivityUserEmptyRow');

        function applySearch() {
            const term = search.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach(function (row) {
                const matches = term === '' || row.innerText.toLowerCase().includes(term);
                row.style.display = matches ? '' : 'none';
                if (matches) {
                    visible++;
                }
            });

            if (count) {
                count.textContent = `${visible} usuario(s)`;
            }

            if (emptyRow) {
                emptyRow.style.display = visible === 0 ? '' : 'none';
            }
        }

        search.addEventListener('input', applySearch);
        applySearch();
    }

    function renderShiftChart() {
        Highcharts.chart('shiftChart', {
            chart: { type: 'column', height: 285, backgroundColor: 'transparent', spacing: [8, 8, 0, 0] },
            title: { text: null },
            subtitle: { text: null },
            xAxis: { categories: shiftSummary.map(item => item.label), crosshair: true, labels: { style: { color: '#425572', fontSize: '9px' } } },
            yAxis: { min: 0, title: { text: 'Cantidad' }, allowDecimals: false },
            tooltip: { shared: true, borderRadius: 12 },
            plotOptions: {
                column: {
                    borderRadius: 3,
                    maxPointWidth: 22,
                    pointPadding: 0.10,
                    groupPadding: 0.16,
                }
            },
            legend: { itemStyle: { fontWeight: '700' } },
            credits: { enabled: false },
            series: [
                { name: 'Órdenes', color: chartColors.orders, data: shiftSummary.map(item => item.ordersTotal) },
                { name: 'Exámenes', color: chartColors.exams, data: shiftSummary.map(item => item.examsTotal) },
                { name: 'En tiempo (≤60 min)', color: chartColors.inRange, data: shiftSummary.map(item => item.examsInRange) },
                { name: 'Fuera 61–90 min', color: chartColors.warning, data: shiftSummary.map(item => item.examsWarning || 0) },
                { name: 'Fuera >90 min', color: chartColors.critical, data: shiftSummary.map(item => item.examsCritical || 0) },
            ],
        });
    }

    function renderUserChart() {
        const topUsers = userSummary.slice(0, 6);

        Highcharts.chart('userChart', {
            chart: { type: 'bar', height: 285, backgroundColor: 'transparent', spacing: [8, 8, 0, 0], marginLeft: 155 },
            title: { text: null },
            subtitle: { text: null },
            xAxis: {
                categories: topUsers.map(item => item.userName),
                title: { text: null },
                lineWidth: 0,
                tickWidth: 0,
                labels: { style: { color: '#425572', fontSize: '9px' } }
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                title: { text: 'Exámenes' },
                gridLineColor: '#edf1f6'
            },
            tooltip: { shared: true, borderRadius: 10 },
            plotOptions: {
                series: { stacking: 'normal' },
                bar: { borderRadius: 3, pointPadding: 0.12, groupPadding: 0.16, maxPointWidth: 22 },
            },
            legend: { itemStyle: { fontWeight: '700', fontSize: '9px' } },
            credits: { enabled: false },
            series: [
                { name: 'En tiempo (≤60 min)', color: chartColors.inRange, data: topUsers.map(item => item.examsInRange) },
                { name: '61–90 min', color: chartColors.warning, data: topUsers.map(item => item.examsWarning || 0) },
                { name: '>90 min', color: chartColors.critical, data: topUsers.map(item => item.examsCritical || 0) },
            ],
        });
    }

    function renderAreaChart() {
        const minimumAreaChartWidth = Math.max(900, areaSummary.length * 78);

        Highcharts.chart('areaChart', {
            chart: {
                type: 'column',
                height: 320,
                backgroundColor: 'transparent',
                spacing: [8, 10, 0, 0],
                scrollablePlotArea: {
                    minWidth: minimumAreaChartWidth,
                    scrollPositionX: 0
                }
            },
            title: { text: null },
            subtitle: { text: null },
            xAxis: {
                categories: areaSummary.map(item => item.area),
                crosshair: true,
                tickLength: 0,
                labels: {
                    rotation: 0,
                    style: { color: '#425572', fontSize: '9px' }
                }
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                title: { text: 'Exámenes' },
                gridLineColor: '#edf1f6'
            },
            tooltip: { shared: true, borderRadius: 12 },
            plotOptions: {
                series: { stacking: 'normal' },
                column: {
                    borderRadius: 4,
                    borderWidth: 0,
                    maxPointWidth: 48,
                    pointPadding: 0.08,
                    groupPadding: 0.14
                },
            },
            legend: { itemStyle: { fontWeight: '700', fontSize: '9px' } },
            credits: { enabled: false },
            series: [
                { name: 'En tiempo (≤60 min)', color: chartColors.inRange, data: areaSummary.map(item => item.examsInRange) },
                { name: '61–90 min', color: chartColors.warning, data: areaSummary.map(item => item.examsWarning || 0) },
                { name: '>90 min', color: chartColors.critical, data: areaSummary.map(item => item.examsCritical || 0) },
            ],
        });
    }
</script>
@endsection
