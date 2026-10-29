@extends('layouts.app')

@section('title', 'Tiempo de oportunidad | Hospital Dashboard')
@section('body-class', 'page-opportunity')

@section('styles')
<style>
    .opp-page-header { margin: 0 0 18px !important; }
    .opp-page-header .dashboard-title { margin-bottom: 5px !important; }
    .opp-page-header .dashboard-subtitle { margin: 0 !important; }

    .opp-filter-card { margin: 0 0 14px !important; padding: 16px 18px !important; }
    .opp-section-heading { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .opp-heading-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 9px; color: var(--inst-blue-600); background: #edf4ff; }
    .opp-heading-icon svg,
    .opp-filter-actions svg,
    .opp-card-icon svg,
    .opp-panel-menu svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .opp-section-heading h2 { margin: 0 !important; color: var(--inst-text) !important; font-size: 13px !important; font-weight: 900 !important; }
    .opp-section-heading p { margin: 3px 0 0 !important; color: var(--inst-muted) !important; font-size: 9px !important; }

    .opp-filter-grid {
        display: grid !important;
        grid-template-columns: minmax(180px,.9fr) minmax(120px,.65fr) minmax(190px,1fr) minmax(150px,.78fr) auto !important;
        gap: 12px !important;
        align-items: end !important;
    }
    .opp-filter-grid .filter-group { min-width: 0; margin: 0 !important; }
    .opp-filter-grid input,
    .opp-filter-grid select { width: 100% !important; }
    .opp-filter-actions { display: flex !important; gap: 8px !important; justify-content: flex-end !important; }
    .opp-filter-actions .btn { min-width: 88px; white-space: nowrap; }

    .opp-summary-grid {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 12px !important;
        margin: 0 0 12px !important;
    }
    .opp-summary-grid.secondary { margin-bottom: 14px !important; }
    .opp-summary-card { position: relative; min-height: 90px !important; padding: 14px 14px 13px 60px !important; overflow: hidden; }
    .opp-summary-card::before { display: none !important; }
    .opp-card-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 14px; }
    .opp-blue .opp-card-icon { color: #1468f3; background: #e8f1ff; }
    .opp-green .opp-card-icon { color: #16a35f; background: #e9f9f0; }
    .opp-yellow .opp-card-icon { color: #d89500; background: #fff7db; }
    .opp-red .opp-card-icon { color: #ef4444; background: #fff0f0; }
    .opp-purple .opp-card-icon { color: #6d5ce8; background: #f0edff; }
    .opp-cyan .opp-card-icon { color: #0ea5e9; background: #edf5ff; }
    .opp-orange .opp-card-icon { color: #f97316; background: #fff2e9; }
    .opp-summary-label { color: #354663 !important; font-size: 9px !important; font-weight: 850 !important; margin-bottom: 5px !important; }
    .opp-summary-value { color: #0d1d3b !important; font-size: 22px !important; line-height: 1 !important; font-weight: 950 !important; letter-spacing: -.02em; }
    .opp-summary-helper { color: #78869a !important; font-size: 8.5px !important; margin-top: 7px !important; }
    .opp-mini-progress { height: 5px; margin-top: 8px; overflow: hidden; background: #edf1f6; border-radius: 999px; }
    .opp-mini-progress span { display: block; height: 100%; border-radius: inherit; }
    .opp-green .opp-mini-progress span { background: #16a35f; }
    .opp-yellow .opp-mini-progress span { background: #fbbf24; }
    .opp-red .opp-mini-progress span { background: #ef4444; }

    .opp-content-grid { display: grid; grid-template-columns: minmax(0, 1.02fr) minmax(420px, .78fr); gap: 14px; align-items: stretch; }
    .opp-panel-card { padding: 16px 18px !important; margin: 0 !important; min-width: 0; }
    .opp-panel-header { min-height: 40px; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 12px; }
    .opp-panel-title { margin: 0 !important; color: var(--inst-text) !important; font-size: 13px !important; font-weight: 950 !important; }
    .opp-panel-subtitle { margin: 3px 0 0 !important; color: var(--inst-muted) !important; font-size: 9px !important; }
    .opp-panel-menu { width: 34px; height: 34px; border: 1px solid #dfe7f1; border-radius: 10px; background: #fff; color: #53637e; display: inline-flex; align-items: center; justify-content: center; }

    .opp-table-tools { display: flex; align-items: center; justify-content: flex-end; gap: 8px; margin-bottom: 10px; }
    .opp-table-tools select { width: 74px !important; min-height: 34px !important; }
    .opp-table-tools input { width: min(320px, 100%) !important; min-height: 34px !important; }
    .opp-table-wrap { overflow: auto !important; border: 1px solid #e5ebf3; border-radius: 11px; max-height: 430px; }
    .opp-matrix-table { width: 100%; min-width: 980px; table-layout: fixed; border-collapse: collapse; }
    .opp-matrix-table th { position: sticky; top: 0; z-index: 1; padding: 10px 10px !important; background: #f8fafd !important; color: #66758d !important; font-size: 8px !important; }
    .opp-matrix-table td { height: 45px; padding: 8px 10px !important; font-size: 9px !important; vertical-align: middle; }
    .opp-col-order { width: 190px; }
    .opp-col-time { width: 100px; }
    .opp-col-status { width: 130px; }
    .order-main { display: flex; align-items: center; gap: 8px; }
    .order-number { color: #102142; font-size: 10px; font-weight: 950; }
    .muted-small { display: block; margin-top: 3px; color: #8491a4; font-size: 8px; }
    .type-badge,
    .op-badge,
    .status-badge,
    .empty-badge { display: inline-flex; align-items: center; justify-content: center; gap: 5px; border-radius: 999px !important; font-weight: 900 !important; line-height: 1; white-space: nowrap; border: 1px solid transparent; }
    .type-badge { min-width: 25px; height: 22px; padding: 0 8px; font-size: 8px; }
    .type-r { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .type-s { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
    .op-badge { min-width: 76px; padding: 6px 8px !important; font-size: 8px !important; }
    .op-ok { background: #e8f9ef !important; color: #137844 !important; border-color: #bceacb !important; }
    .op-warning { background: #fff7d9 !important; color: #9a6500 !important; border-color: #f6dc83 !important; }
    .op-danger { background: #fff0f0 !important; color: #ba3540 !important; border-color: #f5c2c7 !important; }
    .op-empty { background: #f1f5f9 !important; color: #64748b !important; border-color: #e2e8f0 !important; }
    .status-badge { min-width: 86px; padding: 6px 8px !important; font-size: 8px !important; }
    .empty-badge { width: 25px; height: 24px; color: #94a3b8; background: #f8fafc; border-color: #e2e8f0; font-size: 10px; }
    .status-ordered { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }
    .status-repeat { background: #f3e8ff; color: #7e22ce; border-color: #e9d5ff; }
    .status-result { background: #ccfbf1; color: #0f766e; border-color: #99f6e4; }
    .status-printed { background: #e2e8f0; color: #334155; border-color: #cbd5e1; }
    .status-validated { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .status-partial-validation,
    .status-partial-result { background: #fef9c3; color: #854d0e; border-color: #fde68a; }
    .status-partial-print { background: #ffedd5; color: #9a3412; border-color: #fed7aa; }
    .status-process { background: #e0e7ff; color: #3730a3; border-color: #c7d2fe; }
    .status-review { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
    .status-empty,
    .status-unknown { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }

    .opp-pagination-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 10px; color: #7a879a; font-size: 8.5px; }
    .opp-pager-buttons { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
    .opp-pager-btn { width: 27px; height: 27px; border-radius: 999px; border: 1px solid #dbe3ef; background: #fff; color: #334155; font-size: 9px; font-weight: 900; cursor: pointer; }
    .opp-pager-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; }
    .opp-pager-btn:disabled { opacity: .45; cursor: not-allowed; }

    .opp-legend-grid { display: flex; gap: 7px; flex-wrap: wrap; margin-top: 12px; padding-top: 12px; border-top: 1px solid #edf2f7; }
    .opp-legend-label { font-size: 8.5px; font-weight: 900; color: #425572; }

    .opp-semaphore-content { display: grid; grid-template-columns: minmax(180px, .85fr) minmax(190px, 1fr); gap: 16px; align-items: center; min-height: 270px; }
    .opp-donut-wrap { display: grid; place-items: center; }
    .opp-donut { width: 210px; height: 210px; display: grid; place-items: center; border-radius: 50%; background: conic-gradient(#22c55e 0 var(--ok), #fbbf24 var(--ok) var(--risk), #ef4444 var(--risk) 100%); box-shadow: inset 0 0 0 1px rgba(15,23,42,.04); }
    .opp-donut-center { width: 126px; height: 126px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; border-radius: 50%; background: #fff; box-shadow: 0 10px 24px rgba(15,23,42,.08); }
    .opp-donut-value { color: #0d1d3b; font-size: 23px; line-height: 1; font-weight: 950; }
    .opp-donut-label { margin-top: 5px; color: #65738d; font-size: 9px; font-weight: 800; }
    .opp-donut.is-animated { --ok: 0%; --risk: 0%; position: relative; transform: rotate(-90deg); transition: filter .25s ease; }
    .opp-donut.is-animated .opp-donut-center { transform: rotate(90deg); }
    .opp-donut.is-animated:hover { filter: drop-shadow(0 12px 24px rgba(15,23,42,.12)); }
    .opp-donut-percent { position: absolute; min-width: 42px; padding: 4px 7px; border-radius: 999px; background: #fff; border: 1px solid #dfe7f1; box-shadow: 0 8px 18px rgba(15,23,42,.08); color: #102142; font-size: 8px; font-weight: 950; text-align: center; transform: rotate(90deg); pointer-events: none; }
    .opp-donut-percent.ok { top: 18px; right: 12px; color: #137844; border-color: #bceacb; }
    .opp-donut-percent.risk { left: -8px; bottom: 46px; color: #9a6500; border-color: #f6dc83; }
    .opp-donut-percent.out { right: -8px; bottom: 46px; color: #ba3540; border-color: #f5c2c7; }
    .opp-semaphore-value { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; color: #102142; font-weight: 950; }
    .opp-semaphore-value small { color: #7a879a; font-size: 8px; font-weight: 850; }

    .opp-semaphore-list { display: flex; flex-direction: column; gap: 9px; }
    .opp-semaphore-item { display: grid; grid-template-columns: 9px minmax(0,1fr) auto; gap: 8px; align-items: center; min-height: 34px; border-bottom: 1px solid #edf1f6; }
    .opp-semaphore-dot { width: 8px; height: 8px; border-radius: 50%; }
    .opp-semaphore-copy strong { display: block; color: #334967; font-size: 9px; }
    .opp-semaphore-copy span { display: block; color: #8491a4; font-size: 8px; margin-top: 2px; }
    .opp-semaphore-item > strong { color: #263957; font-size: 9px; }
    .opp-alert-list { margin-top: 18px; padding-top: 14px; border-top: 1px solid #edf1f6; }
    .opp-alert-title { margin: 0 0 8px; color: #34435d; font-size: 9px; font-weight: 950; }
    .opp-alert-row { display: grid; grid-template-columns: 22px minmax(0,1fr) auto; gap: 8px; align-items: center; min-height: 31px; }
    .opp-alert-rank { width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #edf4ff; color: #1468f3; font-size: 8px; font-weight: 950; }
    .opp-alert-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #425572; font-size: 9px; font-weight: 750; }
    .opp-alert-count { min-width: 32px; padding: 4px 8px; border-radius: 8px; background: #fff0f0; color: #ef4444; text-align: center; font-size: 9px; font-weight: 950; border: 1px solid #f5c2c7; }

    @media (max-width: 1420px) { .opp-filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; } .opp-filter-actions { grid-column: 1 / -1; justify-content: flex-end !important; } .opp-content-grid { grid-template-columns: 1fr; } }
    @media (max-width: 1050px) { .opp-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; } }
    @media (max-width: 760px) { .opp-filter-grid, .opp-summary-grid { grid-template-columns: 1fr !important; } .opp-filter-actions { grid-column: auto; justify-content: stretch !important; } .opp-filter-actions .btn { flex: 1; } .opp-semaphore-content { grid-template-columns: 1fr; } }


    /* =========================================================
       AJUSTE · ANIMACIÓN HOVER PARA SEMÁFORO DE OPORTUNIDAD
       Ilumina el segmento activo y atenúa el resto de la rueda.
       ========================================================= */
    .page-opportunity .opp-donut.is-animated {
        position: relative !important;
        isolation: isolate;
        overflow: visible !important;
        background: none !important;
    }

    .page-opportunity .opp-donut.is-animated::before,
    .page-opportunity .opp-donut.is-animated::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: 50%;
        pointer-events: none;
        transition: opacity .22s ease, filter .22s ease, transform .22s ease;
    }

    .page-opportunity .opp-donut.is-animated::before {
        z-index: 0;
        background:
            conic-gradient(
                #22c55e 0 var(--ok),
                #fbbf24 var(--ok) var(--risk),
                #ef4444 var(--risk) 100%
            );
        box-shadow:
            inset 0 0 0 1px rgba(15, 23, 42, .04),
            0 12px 28px rgba(15, 23, 42, .06);
    }

    .page-opportunity .opp-donut.is-animated::after {
        z-index: 1;
        opacity: 0;
        transform: scale(1.015);
        filter: blur(.1px);
    }

    .page-opportunity .opp-donut.is-animated.hover-ok::before,
    .page-opportunity .opp-donut.is-animated.hover-risk::before,
    .page-opportunity .opp-donut.is-animated.hover-out::before {
        opacity: .28;
        filter: saturate(.65) brightness(1.08);
    }

    .page-opportunity .opp-donut.is-animated.hover-ok::after {
        opacity: 1;
        background:
            conic-gradient(
                #22c55e 0 var(--ok),
                rgba(34, 197, 94, 0) var(--ok) 100%
            );
        filter: drop-shadow(0 0 14px rgba(34, 197, 94, .42));
        animation: oppSegmentPulse 1.25s ease-in-out infinite alternate;
    }

    .page-opportunity .opp-donut.is-animated.hover-risk::after {
        opacity: 1;
        background:
            conic-gradient(
                rgba(251, 191, 36, 0) 0 var(--ok),
                #fbbf24 var(--ok) var(--risk),
                rgba(251, 191, 36, 0) var(--risk) 100%
            );
        filter: drop-shadow(0 0 14px rgba(251, 191, 36, .46));
        animation: oppSegmentPulse 1.25s ease-in-out infinite alternate;
    }

    .page-opportunity .opp-donut.is-animated.hover-out::after {
        opacity: 1;
        background:
            conic-gradient(
                rgba(239, 68, 68, 0) 0 var(--risk),
                #ef4444 var(--risk) 100%
            );
        filter: drop-shadow(0 0 14px rgba(239, 68, 68, .42));
        animation: oppSegmentPulse 1.25s ease-in-out infinite alternate;
    }

    .page-opportunity .opp-donut.is-animated .opp-donut-center,
    .page-opportunity .opp-donut.is-animated .opp-donut-percent {
        position: relative;
        z-index: 3;
    }

    .page-opportunity .opp-donut.is-animated .opp-donut-percent {
        transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
    }

    .page-opportunity .opp-donut.is-animated.hover-ok .opp-donut-percent.ok,
    .page-opportunity .opp-donut.is-animated.hover-risk .opp-donut-percent.risk,
    .page-opportunity .opp-donut.is-animated.hover-out .opp-donut-percent.out {
        box-shadow: 0 10px 24px rgba(15, 23, 42, .16);
        transform: rotate(90deg) scale(1.06);
    }

    .page-opportunity .opp-semaphore-item {
        cursor: default;
        border-radius: 10px;
        padding: 0 6px;
        margin-left: -6px;
        transition: background .18s ease, transform .18s ease;
    }

    .page-opportunity .opp-semaphore-item:hover {
        background: #f8fbff;
        transform: translateX(2px);
    }

    .page-opportunity .opp-donut.is-animated.is-empty::before {
        opacity: 1 !important;
        background: #eef3f8 !important;
        filter: none !important;
        box-shadow:
            inset 0 0 0 1px rgba(15, 23, 42, .04),
            0 12px 28px rgba(15, 23, 42, .05) !important;
    }

    .page-opportunity .opp-donut.is-animated.is-empty::after {
        display: none !important;
    }

    .page-opportunity .opp-donut.is-animated.is-empty .opp-donut-percent {
        opacity: 0 !important;
        pointer-events: none !important;
    }


    @keyframes oppSegmentPulse {
        from { transform: scale(1.01); }
        to { transform: scale(1.055); }
    }


/* =========================================================
   AJUSTE V6 · SEMÁFORO LIMPIO SIN PORCENTAJES FLOTANTES
   Los porcentajes se muestran en la leyenda derecha.
   La rueda conserva animación y efecto hover.
   ========================================================= */

.page-opportunity .opp-donut-wrap {
    padding: 6px 0 !important;
}

.page-opportunity .opp-donut.is-animated {
    width: 210px !important;
    height: 210px !important;
    overflow: visible !important;
    cursor: default;
}

.page-opportunity .opp-donut.is-animated::before {
    box-shadow:
        inset 0 0 0 1px rgba(15, 23, 42, .04),
        0 12px 28px rgba(15, 23, 42, .06) !important;
}

/* Ocultamos las etiquetas flotantes porque se enciman y hacen que la rueda se vea sucia. */
.page-opportunity .opp-donut.is-animated .opp-donut-percent {
    display: none !important;
}

.page-opportunity .opp-donut-center {
    width: 120px !important;
    height: 120px !important;
    z-index: 4 !important;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .08) !important;
}

.page-opportunity .opp-donut-value {
    font-size: 24px !important;
}

.page-opportunity .opp-donut-label {
    font-size: 8.5px !important;
    line-height: 1.2 !important;
}

/* Al pasar el mouse se ilumina el segmento activo y se apaga el resto. */
.page-opportunity .opp-donut.is-animated.hover-ok::before,
.page-opportunity .opp-donut.is-animated.hover-risk::before,
.page-opportunity .opp-donut.is-animated.hover-out::before {
    opacity: .34 !important;
    filter: saturate(.65) brightness(1.06) !important;
}

.page-opportunity .opp-donut.is-animated.hover-ok::after,
.page-opportunity .opp-donut.is-animated.hover-risk::after,
.page-opportunity .opp-donut.is-animated.hover-out::after {
    opacity: 1 !important;
    transform: scale(1.02) !important;
}

/* La leyenda conserva los porcentajes, más limpia y parecida al mockup. */
.page-opportunity .opp-semaphore-item {
    min-height: 37px !important;
    padding: 5px 7px !important;
    border-radius: 10px !important;
}

.page-opportunity .opp-semaphore-item:hover {
    background: #f8fbff !important;
}

.page-opportunity .opp-semaphore-value {
    min-width: 48px !important;
    text-align: right !important;
}

.page-opportunity .opp-semaphore-value small {
    display: block !important;
    margin-top: 2px !important;
    font-size: 8px !important;
}


/* =========================================================
   AJUSTE V8 · PORCENTAJES SOBRE LA RUEDA SIN ENSUCIAR EL HOVER
   ========================================================= */

.page-opportunity .opp-donut-frame {
    position: relative !important;
    width: 238px !important;
    height: 238px !important;
    display: grid !important;
    place-items: center !important;
    overflow: visible !important;
}

.page-opportunity .opp-donut-frame.is-empty .opp-donut-ring-label {
    display: none !important;
}

.page-opportunity .opp-donut.is-animated {
    position: relative !important;
    z-index: 2 !important;
}

/* Los porcentajes se colocan fuera del elemento rotado para que no se inviertan ni se amontonen. */
.page-opportunity .opp-donut-ring-label {
    position: absolute !important;
    z-index: 8 !important;
    min-width: 46px !important;
    height: 24px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 8px !important;
    border-radius: 999px !important;
    border: 1px solid #dfe7f1 !important;
    background: rgba(255,255,255,.96) !important;
    box-shadow: 0 9px 20px rgba(15,23,42,.11) !important;
    font-size: 8.5px !important;
    font-weight: 950 !important;
    line-height: 1 !important;
    cursor: default !important;
    user-select: none !important;
    transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease, filter .18s ease !important;
}

.page-opportunity .opp-donut-ring-label.ok {
    top: 31px !important;
    right: 30px !important;
    color: #137844 !important;
    border-color: #bceacb !important;
}

.page-opportunity .opp-donut-ring-label.risk {
    right: 28px !important;
    bottom: 54px !important;
    color: #9a6500 !important;
    border-color: #f6dc83 !important;
}

.page-opportunity .opp-donut-ring-label.out {
    left: 26px !important;
    bottom: 54px !important;
    color: #ba3540 !important;
    border-color: #f5c2c7 !important;
}

.page-opportunity .opp-donut-frame:has(.opp-donut.hover-ok) .opp-donut-ring-label:not(.ok),
.page-opportunity .opp-donut-frame:has(.opp-donut.hover-risk) .opp-donut-ring-label:not(.risk),
.page-opportunity .opp-donut-frame:has(.opp-donut.hover-out) .opp-donut-ring-label:not(.out) {
    opacity: .42 !important;
    filter: saturate(.7) !important;
}

.page-opportunity .opp-donut-frame:has(.opp-donut.hover-ok) .opp-donut-ring-label.ok,
.page-opportunity .opp-donut-frame:has(.opp-donut.hover-risk) .opp-donut-ring-label.risk,
.page-opportunity .opp-donut-frame:has(.opp-donut.hover-out) .opp-donut-ring-label.out,
.page-opportunity .opp-donut-ring-label:hover {
    transform: translateY(-1px) scale(1.04) !important;
    box-shadow: 0 12px 24px rgba(15,23,42,.15) !important;
}

@media (max-width: 760px) {
    .page-opportunity .opp-donut-frame {
        width: 226px !important;
        height: 226px !important;
    }

    .page-opportunity .opp-donut-ring-label.ok {
        top: 25px !important;
        right: 24px !important;
    }

    .page-opportunity .opp-donut-ring-label.risk {
        right: 21px !important;
        bottom: 49px !important;
    }

    .page-opportunity .opp-donut-ring-label.out {
        left: 19px !important;
        bottom: 49px !important;
    }
}

</style>
@endsection

@section('content')
@php
    $totalShown = (int) ($summary['totalShown'] ?? 0);
    $ok = (int) ($summary['withinRange'] ?? 0);
    $risk = (int) ($summary['warningRange'] ?? 0);
    $out = (int) ($summary['outOfRange'] ?? 0);
    $timedTotalRaw = $ok + $risk + $out;
    $hasSemaphoreData = $timedTotalRaw > 0;
    $timedTotal = max(1, $timedTotalRaw);

    $okPct = $hasSemaphoreData ? round(($ok / $timedTotal) * 100, 1) : 0;
    $warningPct = $hasSemaphoreData ? round(($risk / $timedTotal) * 100, 1) : 0;
    $outPct = $hasSemaphoreData ? round(($out / $timedTotal) * 100, 1) : 0;
    $riskPct = $hasSemaphoreData ? round((($ok + $risk) / $timedTotal) * 100, 1) : 0;
@endphp

<div class="dashboard-header opp-page-header">
    <div>
        <h1 class="dashboard-title">Tablero de tiempo de oportunidad</h1>
        <p class="dashboard-subtitle">Monitoreo de cumplimiento, estados operativos y seguimiento por área.</p>
    </div>
</div>

@if (!empty($errorMessage))
    <div class="alert-error">{{ $errorMessage }}</div>
@endif

<section class="filter-card opp-filter-card">
    <div class="opp-section-heading">
        <span class="opp-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16l-6 7v5l-4 2v-7z"/></svg></span>
        <div>
            <h2>Filtros del tablero</h2>
            <p>Selecciona un día para revisar únicamente la operación correspondiente a esa fecha.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('dashboards.opportunity-status') }}">
        <div class="opp-filter-grid">
            <div class="filter-group">
                <label for="date">Fecha</label>
                <input type="date" id="date" name="date" value="{{ $filters['date'] ?? now()->toDateString() }}" required>
            </div>
            <div class="filter-group">
                <label for="type">Tipo</label>
                <select id="type" name="type">
                    <option value="">Todos</option>
                    <option value="R" @selected(($filters['type'] ?? '') === 'R')>Rutina</option>
                    <option value="S" @selected(($filters['type'] ?? '') === 'S')>Urgencia</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="service">Servicio</label>
                <select id="service" name="service">
                    <option value="">Todos</option>
                    @foreach ($services as $service)
                        <option value="{{ $service }}" @selected(($filters['service'] ?? '') === $service)>{{ $service }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label for="area">Área</label>
                <select id="area" name="area">
                    <option value="">Todas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area }}" @selected(($filters['area'] ?? '') === $area)>{{ $area }}</option>
                    @endforeach
                </select>
            </div>
            <div class="opp-filter-actions">
                <button type="submit" class="btn btn-primary"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><span>Aplicar</span></button>
                <a href="{{ route('dashboards.opportunity-status') }}" class="btn btn-secondary"><svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg><span>Limpiar</span></a>
            </div>
        </div>
    </form>
</section>

<section class="opp-summary-grid" aria-label="Indicadores principales">
    <article class="summary-card opp-summary-card opp-blue"><span class="opp-card-icon"><svg viewBox="0 0 24 24"><path d="M7 4h10v16H7zM9 2h6v4H9z"/></svg></span><div class="opp-summary-label">Fechas recibidas</div><div class="opp-summary-value">{{ number_format($summary['totalReceived'] ?? 0) }}</div><div class="opp-summary-helper">Órdenes</div></article>
    <article class="summary-card opp-summary-card opp-blue"><span class="opp-card-icon"><svg viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></span><div class="opp-summary-label">Fechas mostradas</div><div class="opp-summary-value">{{ number_format($summary['totalShown'] ?? 0) }}</div><div class="opp-summary-helper">Órdenes filtradas</div></article>
    <article class="summary-card opp-summary-card opp-green"><span class="opp-card-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg></span><div class="opp-summary-label">Cumplimiento</div><div class="opp-summary-value">{{ number_format($summary['compliance'] ?? 0, 1) }}%</div><div class="opp-summary-helper">Meta: ≥ 90%</div><div class="opp-mini-progress"><span style="width:{{ min(100, $summary['compliance'] ?? 0) }}%"></span></div></article>
    <article class="summary-card opp-summary-card opp-red"><span class="opp-card-icon"><svg viewBox="0 0 24 24"><path d="M12 3 3 20h18L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg></span><div class="opp-summary-label">Fuera de rango</div><div class="opp-summary-value">{{ number_format($summary['outOfRange'] ?? 0) }}</div><div class="opp-summary-helper">{{ $totalShown > 0 ? number_format((($summary['outOfRange'] ?? 0) / $totalShown) * 100, 1) : '0.0' }}% del total</div><div class="opp-mini-progress"><span style="width:{{ $totalShown > 0 ? min(100, (($summary['outOfRange'] ?? 0) / $totalShown) * 100) : 0 }}%"></span></div></article>
</section>

<section class="opp-summary-grid secondary" aria-label="Indicadores secundarios">
    <article class="summary-card opp-summary-card opp-cyan"><span class="opp-card-icon"><svg viewBox="0 0 24 24"><path d="M20 12a8 8 0 1 1-2.34-5.66M20 4v6h-6"/></svg></span><div class="opp-summary-label">Órdenes en proceso</div><div class="opp-summary-value">{{ number_format($summary['inProcess'] ?? 0) }}</div><div class="opp-summary-helper">Estatus operativo</div></article>
    <article class="summary-card opp-summary-card opp-yellow"><span class="opp-card-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><div class="opp-summary-label">En riesgo</div><div class="opp-summary-value">{{ number_format($summary['warningRange'] ?? 0) }}</div><div class="opp-summary-helper">61 a 90 min</div></article>
    <article class="summary-card opp-summary-card opp-purple"><span class="opp-card-icon"><svg viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5h20v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/></svg></span><div class="opp-summary-label">Resultados impresos</div><div class="opp-summary-value">{{ number_format($summary['printed'] ?? 0) }}</div><div class="opp-summary-helper">Órdenes</div></article>
    <article class="summary-card opp-summary-card opp-cyan"><span class="opp-card-icon"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg></span><div class="opp-summary-label">Revisión</div><div class="opp-summary-value">{{ number_format($summary['review'] ?? 0) }}</div><div class="opp-summary-helper">Órdenes</div></article>
</section>

<section class="opp-content-grid">
    <article class="panel-card opp-panel-card">
        <div class="opp-panel-header">
            <div>
                <h2 class="opp-panel-title">Estados de la orden por área</h2>
                <p class="opp-panel-subtitle">BIO, COA, END, HEM, INM, MIC, TOX y REM</p>
            </div>
            <button type="button" class="opp-panel-menu" aria-label="Opciones"><svg viewBox="0 0 24 24"><path d="M12 5h.01M12 12h.01M12 19h.01"/></svg></button>
        </div>

        <div class="opp-table-tools">
            <select id="rowsPerPage" aria-label="Registros por página"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select>
            <input type="search" id="tableSearch" placeholder="Buscar orden, tipo, área o estatus...">
        </div>

        <div class="opp-table-wrap">
            <table class="opp-matrix-table" id="opportunityTable">
                <colgroup><col class="opp-col-order"><col class="opp-col-time"><col class="opp-col-status">@foreach ($areas as $area)<col>@endforeach</colgroup>
                <thead><tr><th>Orden / prioridad</th><th>Tiempo</th><th>Estatus orden</th>@foreach ($areas as $area)<th>{{ $area }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td><div class="order-main"><span class="order-number">{{ $row['order'] }}</span><span class="type-badge {{ $row['type'] === 'S' ? 'type-s' : 'type-r' }}" title="{{ $row['typeLabel'] }}">{{ $row['type'] }}</span></div><span class="muted-small">{{ $row['createdAt'] }} · {{ $row['service'] }}</span></td>
                            <td data-sort-value="{{ $row['opTime'] ?? 999999 }}"><span class="op-badge {{ $row['opTimeClass'] }}">@if (($row['opTimeClass'] ?? '') === 'op-ok') ✓ @elseif (($row['opTimeClass'] ?? '') === 'op-warning') ! @elseif (($row['opTimeClass'] ?? '') === 'op-danger') ⚠ @endif {{ $row['opTimeLabel'] }}</span></td>
                            <td><span class="status-badge {{ $row['statusClass'] }}">{{ $row['statusLabel'] }}</span></td>
                            @foreach ($areas as $area)
                                @php $areaStatusCode = $row['areaStatuses'][$area] ?? null; $areaStatus = $statusCatalog[$areaStatusCode] ?? null; @endphp
                                <td>@if ($areaStatusCode !== null && $areaStatus)<span class="status-badge {{ $areaStatus['class'] }}">{{ $areaStatus['label'] }}</span>@elseif ($areaStatusCode !== null)<span class="status-badge status-unknown">Estado {{ $areaStatusCode }}</span>@else<span class="empty-badge" title="Sin estatus para esta área">—</span>@endif</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ 3 + count($areas) }}"><div class="global-empty-inline">No hay información para mostrar con los filtros actuales.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="opp-pagination-bar"><div id="paginationInfo">Mostrando 0 registros</div><div class="opp-pager-buttons" id="pagerButtons"></div></div>
        <div class="opp-legend-grid"><span class="opp-legend-label">Leyenda:</span>@foreach ($statusCatalog as $code => $status)<span class="status-badge {{ $status['class'] }}">{{ $status['label'] }}</span>@endforeach</div>
    </article>

    <article class="panel-card opp-panel-card">
        <div class="opp-panel-header">
            <div><h2 class="opp-panel-title">Semáforo de oportunidad</h2><p class="opp-panel-subtitle">Resumen de cumplimiento general</p></div>
            <button type="button" class="opp-panel-menu" aria-label="Opciones"><svg viewBox="0 0 24 24"><path d="M12 5h.01M12 12h.01M12 19h.01"/></svg></button>
        </div>

        <div class="opp-semaphore-content">
            <div class="opp-donut-wrap">
                <div class="opp-donut-frame {{ $hasSemaphoreData ? '' : 'is-empty' }}">
                    <div class="opp-donut is-animated {{ $hasSemaphoreData ? '' : 'is-empty' }}" data-ok="{{ $okPct }}" data-risk="{{ $riskPct }}" data-total="{{ $timedTotalRaw }}" style="--ok: 0%; --risk: 0%;">
                        <div class="opp-donut-center">
                            <div class="opp-donut-value">{{ number_format($summary['totalShown'] ?? 0) }}</div>
                            <div class="opp-donut-label">Órdenes<br>totales</div>
                        </div>
                    </div>

                    <button type="button" class="opp-donut-ring-label ok" data-segment="ok" title="Dentro de rango">{{ number_format($okPct, 1) }}%</button>
                    <button type="button" class="opp-donut-ring-label risk" data-segment="risk" title="En riesgo">{{ number_format($warningPct, 1) }}%</button>
                    <button type="button" class="opp-donut-ring-label out" data-segment="out" title="Fuera de rango">{{ number_format($outPct, 1) }}%</button>
                </div>
            </div>
            <div>
                <div class="opp-semaphore-list">
                    <div class="opp-semaphore-item"><span class="opp-semaphore-dot" style="background:#22c55e"></span><span class="opp-semaphore-copy"><strong>Dentro de rango</strong><span>≤ 60 min</span></span><strong class="opp-semaphore-value"><span>{{ number_format($summary['withinRange'] ?? 0) }}</span><small>{{ number_format($okPct, 1) }}%</small></strong></div>
                    <div class="opp-semaphore-item"><span class="opp-semaphore-dot" style="background:#fbbf24"></span><span class="opp-semaphore-copy"><strong>En riesgo</strong><span>61 a 90 min</span></span><strong class="opp-semaphore-value"><span>{{ number_format($summary['warningRange'] ?? 0) }}</span><small>{{ number_format($warningPct, 1) }}%</small></strong></div>
                    <div class="opp-semaphore-item"><span class="opp-semaphore-dot" style="background:#ef4444"></span><span class="opp-semaphore-copy"><strong>Fuera de rango</strong><span>&gt; 90 min</span></span><strong class="opp-semaphore-value"><span>{{ number_format($summary['outOfRange'] ?? 0) }}</span><small>{{ number_format($outPct, 1) }}%</small></strong></div>
                </div>
                <div class="opp-alert-list"><p class="opp-alert-title">Áreas con más alertas</p>@forelse ($alertsByArea as $area => $count)<div class="opp-alert-row"><span class="opp-alert-rank">{{ $loop->iteration }}</span><span class="opp-alert-name">{{ $area }}</span><span class="opp-alert-count">{{ $count }}</span></div>@empty<div class="global-empty-inline">Sin alertas fuera de rango.</div>@endforelse</div>
            </div>
        </div>
    </article>
</section>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = document.getElementById('opportunityTable');
        const searchInput = document.getElementById('tableSearch');
        const rowsPerPageSelect = document.getElementById('rowsPerPage');
        const paginationInfo = document.getElementById('paginationInfo');
        const pagerButtons = document.getElementById('pagerButtons');
        if (!table || !searchInput || !rowsPerPageSelect || !paginationInfo || !pagerButtons) return;
        const allRows = Array.from(table.querySelectorAll('tbody tr'));
        let currentPage = 1;
        function rowMatches(row) { const term = searchInput.value.trim().toLowerCase(); return !term || row.innerText.toLowerCase().includes(term); }
        function getFilteredRows() { return allRows.filter(rowMatches); }
        function renderTable() {
            const rowsPerPage = parseInt(rowsPerPageSelect.value, 10) || 10;
            const filteredRows = getFilteredRows();
            const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
            if (currentPage > totalPages) currentPage = totalPages;
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            allRows.forEach(row => row.style.display = 'none');
            filteredRows.slice(start, end).forEach(row => row.style.display = '');
            const visibleStart = filteredRows.length === 0 ? 0 : start + 1;
            const visibleEnd = Math.min(end, filteredRows.length);
            paginationInfo.textContent = `Mostrando ${visibleStart} a ${visibleEnd} de ${filteredRows.length} registros`;
            renderPager(totalPages);
        }
        function renderPager(totalPages) {
            pagerButtons.innerHTML = '';
            const previous = document.createElement('button'); previous.type = 'button'; previous.className = 'opp-pager-btn'; previous.textContent = '‹'; previous.disabled = currentPage === 1; previous.addEventListener('click', function () { currentPage = Math.max(1, currentPage - 1); renderTable(); }); pagerButtons.appendChild(previous);
            const maxButtons = 7; let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2)); let endPage = Math.min(totalPages, startPage + maxButtons - 1); if (endPage - startPage < maxButtons - 1) startPage = Math.max(1, endPage - maxButtons + 1);
            for (let page = startPage; page <= endPage; page++) { const button = document.createElement('button'); button.type = 'button'; button.className = 'opp-pager-btn' + (page === currentPage ? ' active' : ''); button.textContent = page; button.addEventListener('click', function () { currentPage = page; renderTable(); }); pagerButtons.appendChild(button); }
            const next = document.createElement('button'); next.type = 'button'; next.className = 'opp-pager-btn'; next.textContent = '›'; next.disabled = currentPage === totalPages; next.addEventListener('click', function () { currentPage = Math.min(totalPages, currentPage + 1); renderTable(); }); pagerButtons.appendChild(next);
        }
        searchInput.addEventListener('input', function () { currentPage = 1; renderTable(); });
        rowsPerPageSelect.addEventListener('change', function () { currentPage = 1; renderTable(); });
        renderTable();
    });


    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.opp-donut.is-animated').forEach(function (donut) {
            const targetOk = parseFloat(donut.dataset.ok || '0');
            const targetRisk = parseFloat(donut.dataset.risk || '0');
            const totalSegments = parseFloat(donut.dataset.total || '0');

            if (!totalSegments) {
                donut.style.setProperty('--ok', '0%');
                donut.style.setProperty('--risk', '0%');
                return;
            }

            const duration = 950;
            const start = performance.now();

            function animate(now) {
                const progress = Math.min(1, (now - start) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                donut.style.setProperty('--ok', (targetOk * eased).toFixed(2) + '%');
                donut.style.setProperty('--risk', (targetRisk * eased).toFixed(2) + '%');

                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            }

            requestAnimationFrame(animate);
        });
    });



    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.opp-donut.is-animated').forEach(function (donut) {
            const okLimit = parseFloat(donut.dataset.ok || '0');
            const riskLimit = parseFloat(donut.dataset.risk || '0');
            const totalSegments = parseFloat(donut.dataset.total || '0');
            const activeClasses = ['hover-ok', 'hover-risk', 'hover-out'];

            if (!totalSegments) {
                donut.style.setProperty('--ok', '0%');
                donut.style.setProperty('--risk', '0%');
                return;
            }

            function setActive(segment) {
                donut.classList.remove(...activeClasses);

                if (segment) {
                    donut.classList.add(`hover-${segment}`);
                }
            }

            function segmentFromPointer(event) {
                const rect = donut.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                const deltaX = event.clientX - centerX;
                const deltaY = event.clientY - centerY;
                const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
                const innerRadius = Math.min(rect.width, rect.height) * 0.31;

                if (distance < innerRadius) {
                    return null;
                }

                let angle = Math.atan2(deltaY, deltaX) * 180 / Math.PI;

                /*
                 * La rueda visual está rotada -90deg por CSS para que el primer
                 * segmento inicie desde el lado izquierdo, igual que el mockup.
                 *
                 * Highcharts/CSS calcula el conic-gradient desde arriba, pero aquí
                 * necesitamos detectar el segmento desde la posición visual real:
                 * izquierda = 0%, arriba = 25%, derecha = 50%, abajo = 75%.
                 */
                angle = (angle + 90 + 360) % 360;
                const visualPercent = ((angle - 270 + 360) % 360) / 360 * 100;

                if (visualPercent <= okLimit) {
                    return 'ok';
                }

                if (visualPercent <= riskLimit) {
                    return 'risk';
                }

                return 'out';
            }

            donut.addEventListener('mousemove', function (event) {
                setActive(segmentFromPointer(event));
            });

            donut.addEventListener('mouseleave', function () {
                setActive(null);
            });

            const percentTriggers = [
                ['.opp-donut-percent.ok, .opp-donut-ring-label.ok', 'ok'],
                ['.opp-donut-percent.risk, .opp-donut-ring-label.risk', 'risk'],
                ['.opp-donut-percent.out, .opp-donut-ring-label.out', 'out'],
            ];

            const donutFrame = donut.closest('.opp-donut-frame') || donut;

            percentTriggers.forEach(function ([selector, segment]) {
                donutFrame.querySelectorAll(selector).forEach(function (trigger) {
                    trigger.addEventListener('mouseenter', function () {
                        setActive(segment);
                    });

                    trigger.addEventListener('mouseleave', function () {
                        setActive(null);
                    });
                });
            });

            document.querySelectorAll('.opp-semaphore-item').forEach(function (item, index) {
                const segment = ['ok', 'risk', 'out'][index];

                if (!segment) {
                    return;
                }

                item.addEventListener('mouseenter', function () {
                    setActive(segment);
                });

                item.addEventListener('mouseleave', function () {
                    setActive(null);
                });
            });
        });
    });

</script>
@endsection
