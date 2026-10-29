@extends('layouts.app')

@section('title', 'Inicio | Hospital Dashboard')
@section('body-class', 'page-home-dashboard')

@section('content')
@php
    $authUser = session('auth_user', []);
    $userName = trim((string) ($authUser['name'] ?? 'Usuario'));
    $firstName = preg_split('/\s+/', $userName)[0] ?? 'Usuario';
    $visibleDashboards = \App\Support\DashboardAccess::dashboardItems($authUser);
    $screenDashboards = \App\Support\DashboardAccess::screenItems($authUser);
    $adminDashboards = \App\Support\DashboardAccess::visibleItems($authUser, true);

    $moduleLinks = [];
    foreach ($visibleDashboards as $key => $item) {
        $moduleLinks[] = [
            'key' => $key,
            'label' => $item['short_label'] ?? $item['label'] ?? $key,
            'route' => $item['route'],
        ];
    }

    $screenCount = count($screenDashboards);
    $activeModules = count($moduleLinks);
    $assignedAccess = count($authUser['dashboardAccess'] ?? []);
    $availableRoutes = max(1, count($visibleDashboards));

    $summaryRows = [];
    foreach ($visibleDashboards as $key => $item) {
        $summaryRows[] = [
            'label' => $item['label'] ?? $key,
            'key' => $key,
            'route' => $item['route'],
            'available' => true,
        ];
    }

    $quickActions = array_slice($moduleLinks, 0, 3);
@endphp

<div class="home-dashboard">
    <header class="home-page-heading">
        <h1>Dashboard principal</h1>
        <p>Resumen general y vista central de operación del laboratorio.</p>
    </header>

    <div class="home-layout">
        <div class="home-main-column">
            <section class="home-welcome-card">
                <div>
                    <h2>Bienvenido, <span>{{ $firstName }}</span></h2>
                    <p>Consulta el estado operativo, tus accesos y la actividad de la sesión desde un solo lugar.</p>
                </div>
                <div class="home-welcome-graphic" aria-hidden="true">
                    <svg viewBox="0 0 520 130" preserveAspectRatio="none">
                        <path d="M0 102 C70 98 95 55 155 66 C220 78 235 24 305 45 C368 64 408 22 520 38"/>
                        <path d="M0 118 C95 101 115 87 175 90 C245 94 290 58 345 68 C405 80 452 46 520 53"/>
                        <g><circle cx="90" cy="83" r="4"/><circle cx="155" cy="66" r="5"/><circle cx="234" cy="53" r="4"/><circle cx="305" cy="45" r="5"/><circle cx="408" cy="43" r="4"/><circle cx="475" cy="38" r="3"/></g>
                    </svg>
                </div>
            </section>

            <section class="home-kpi-grid" aria-label="Resumen de acceso">
                <article class="home-kpi-card home-kpi-blue">
                    <span class="home-kpi-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
                    <div><span>Módulos activos</span><strong>{{ $activeModules }}</strong><small>Disponibles para tu usuario</small></div>
                </article>
                <article class="home-kpi-card home-kpi-purple">
                    <span class="home-kpi-icon"><svg viewBox="0 0 24 24"><path d="M7 4h10v16H7zM9 2h6v4H9zM10 10h4M10 14h4"/></svg></span>
                    <div><span>Accesos asignados</span><strong>{{ $assignedAccess }}</strong><small>Permisos cargados en sesión</small></div>
                </article>
                <article class="home-kpi-card home-kpi-orange">
                    <span class="home-kpi-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M8 21h8M12 19v2"/></svg></span>
                    <div><span>Pantallas</span><strong>{{ $screenCount }}</strong><small>Vistas operativas disponibles</small></div>
                </article>
                <article class="home-kpi-card home-kpi-red">
                    <span class="home-kpi-icon"><svg viewBox="0 0 24 24"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5M12 17h.01"/></svg></span>
                    <div><span>Alertas de sesión</span><strong>0</strong><small>Sin incidencias activas</small></div>
                </article>
            </section>

            <section class="home-card home-operational-card">
                <div class="home-section-title">
                    <h2>Resumen operativo del día</h2>
                    <span>Accesos habilitados</span>
                </div>

                <div class="home-operational-list">
                    @forelse ($summaryRows as $row)
                        <a href="{{ route($row['route']) }}" class="home-operational-row">
                            <span class="home-row-icon">
                                @switch($row['key'])
                                    @case('estudios')
                                        <svg viewBox="0 0 24 24"><path d="M9 3h6m-5 0v5l-5 9a2 2 0 0 0 1.7 3h10.6A2 2 0 0 0 19 17l-5-9V3M8 14h8"/></svg>
                                        @break
                                    @case('consulta-externa')
                                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/></svg>
                                        @break
                                    @case('productividad')
                                        <svg viewBox="0 0 24 24"><path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/></svg>
                                        @break
                                    @case('venopunciones')
                                        <svg viewBox="0 0 24 24"><path d="M12 3s6 6.2 6 11a6 6 0 1 1-12 0c0-4.8 6-11 6-11Z"/><path d="M9.5 15.5a3 3 0 0 0 5 1"/></svg>
                                        @break
                                    @case('opportunity-status')
                                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg>
                                        @break
                                    @default
                                        <svg viewBox="0 0 24 24"><path d="M12 3s6 6.2 6 11a6 6 0 1 1-12 0c0-4.8 6-11 6-11Z"/></svg>
                                @endswitch
                            </span>
                            <span class="home-row-label">{{ $row['label'] }}</span>
                            <span class="home-row-progress"><i style="width: 100%"></i></span>
                            <span class="home-row-status">Disponible</span>
                            <svg class="home-row-chevron" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    @empty
                        <div class="home-empty-state">No hay módulos operativos asignados a este usuario.</div>
                    @endforelse
                </div>
            </section>

            <section class="home-card home-agenda-card">
                <div class="home-section-title"><h2>Agenda y pendientes</h2><span>Inicio de sesión</span></div>
                <div class="home-agenda-list">
                    <div class="home-agenda-row"><span class="agenda-icon"><svg viewBox="0 0 24 24"><path d="M7 3v3m10-3v3M4 9h16M5 5h14v15H5z"/></svg></span><span>Revisar indicadores del día</span><strong>Disponible</strong></div>
                    <div class="home-agenda-row"><span class="agenda-icon"><svg viewBox="0 0 24 24"><path d="M12 3s6 6.2 6 11a6 6 0 1 1-12 0c0-4.8 6-11 6-11Z"/></svg></span><span>Consultar pendientes de toma de muestra</span><strong>Disponible</strong></div>
                    <div class="home-agenda-row"><span class="agenda-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg></span><span>Validar pantallas operativas</span><strong>{{ $screenCount > 0 ? 'Disponible' : 'Sin acceso' }}</strong></div>
                    <div class="home-agenda-row"><span class="agenda-icon"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg></span><span>Confirmar permisos del usuario</span><strong>{{ $assignedAccess }} accesos</strong></div>
                </div>
            </section>
        </div>

        <aside class="home-side-column">
            <section class="home-card home-status-card">
                <div class="home-section-title"><h2>Estado general</h2></div>
                <div class="home-status-list">
                    <div class="home-status-row"><span class="status-icon"><svg viewBox="0 0 24 24"><path d="M7 18h10a4 4 0 0 0 .5-8A6 6 0 0 0 6.3 8.4 4.5 4.5 0 0 0 7 18Z"/></svg></span><div><strong>Aplicación</strong><small>Sesión web activa</small></div><em>En línea</em></div>
                    <div class="home-status-row"><span class="status-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg></span><div><strong>Última actualización</strong><small>{{ now()->format('d/m/Y H:i') }}</small></div><em>Actualizado</em></div>
                    <div class="home-status-row"><span class="status-icon"><svg viewBox="0 0 24 24"><path d="M7 3v3m10-3v3M4 9h16M5 5h14v15H5z"/></svg></span><div><strong>Accesos asignados</strong><small>Permisos vigentes</small></div><b>{{ $assignedAccess }}</b></div>
                    <div class="home-status-row"><span class="status-icon"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="6" rx="1"/><rect x="4" y="14" width="16" height="6" rx="1"/><path d="M8 7h.01M8 17h.01"/></svg></span><div><strong>Módulos</strong><small>Disponibles para navegar</small></div><em>Operativos</em></div>
                    <div class="home-status-row"><span class="status-icon"><svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/></svg></span><div><strong>Sesión</strong><small>Autenticación válida</small></div><em>Protegida</em></div>
                </div>
            </section>

            <section class="home-card home-activity-card">
                <div class="home-section-title"><h2>Actividad reciente</h2></div>
                <div class="home-activity-list">
                    <div class="home-activity-row"><span class="activity-dot success"><svg viewBox="0 0 24 24"><path d="m6 12 4 4 8-8"/></svg></span><div><strong>Sesión iniciada</strong><small>Acceso correcto al sistema</small></div><time>Ahora</time></div>
                    <div class="home-activity-row"><span class="activity-dot blue"><svg viewBox="0 0 24 24"><path d="M7 4h10v16H7zM9 2h6v4H9z"/></svg></span><div><strong>Permisos cargados</strong><small>{{ $assignedAccess }} accesos asignados</small></div><time>Ahora</time></div>
                    <div class="home-activity-row"><span class="activity-dot orange"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg></span><div><strong>Pantallas disponibles</strong><small>{{ $screenCount }} vistas operativas</small></div><time>Ahora</time></div>
                    <div class="home-activity-row"><span class="activity-dot blue"><svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5M5.5 10v10h13V10"/></svg></span><div><strong>Inicio preparado</strong><small>Dashboard principal cargado</small></div><time>Ahora</time></div>
                </div>
            </section>

            <section class="home-card home-actions-card">
                <div class="home-section-title"><h2>Acciones del día</h2></div>
                <div class="home-actions-grid">
                    @foreach ($quickActions as $action)
                        <a href="{{ route($action['route']) }}">
                            <span><svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM8 9h8M8 13h8"/></svg></span>
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                    @if ($screenCount > 0)
                        <button type="button" id="homeOpenScreens"><span><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg></span>Ver pantallas</button>
                    @endif
                </div>
            </section>
        </aside>
    </div>

    <section class="home-card home-notice-card">
        <span class="home-notice-icon"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span>
        <div><h2>Avisos importantes</h2><strong>Dashboard principal disponible</strong><p>Utiliza el menú lateral para ingresar al módulo que requieras. Los indicadores de esta vista resumen los accesos disponibles en tu sesión.</p></div>
        <a href="{{ $moduleLinks ? route($moduleLinks[0]['route']) : '#' }}" class="home-notice-button">Abrir primer módulo</a>
    </section>
</div>

<style>
    .page-home-dashboard .main-container{padding:24px 28px 28px;background:#f4f7fb}.home-dashboard{max-width:1680px;margin:0 auto;color:#0a2248}.home-page-heading{margin-bottom:18px}.home-page-heading h1{margin:0;font-size:31px;line-height:1.1;font-weight:850;letter-spacing:-.7px}.home-page-heading p{margin:6px 0 0;color:#60708a;font-size:13px}.home-layout{display:grid;grid-template-columns:minmax(0,2.1fr) minmax(330px,.95fr);gap:20px}.home-main-column,.home-side-column{display:flex;flex-direction:column;gap:14px;min-width:0}.home-card,.home-welcome-card,.home-kpi-card{background:#fff;border:1px solid #dde6f2;border-radius:15px;box-shadow:0 5px 17px rgba(24,55,95,.045)}.home-welcome-card{min-height:130px;padding:25px 28px;display:flex;position:relative;overflow:hidden;align-items:center}.home-welcome-card>div:first-child{position:relative;z-index:2;max-width:50%}.home-welcome-card h2{margin:0;font-size:25px;font-weight:850}.home-welcome-card h2 span{color:#1877f2}.home-welcome-card p{margin:9px 0 0;color:#51637d;line-height:1.55;max-width:470px}.home-welcome-graphic{position:absolute;right:0;top:0;width:58%;height:100%;background:linear-gradient(90deg,rgba(255,255,255,0),rgba(229,239,255,.72))}.home-welcome-graphic svg{width:100%;height:100%}.home-welcome-graphic path{fill:none;stroke:#b7d1fa;stroke-width:1.5}.home-welcome-graphic circle{fill:#80aff5}.home-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.home-kpi-card{padding:17px 18px;display:flex;align-items:center;gap:14px;min-height:108px}.home-kpi-icon{width:54px;height:54px;border-radius:13px;display:grid;place-items:center;flex:0 0 auto}.home-kpi-icon svg{width:27px;height:27px;fill:none;stroke:currentColor;stroke-width:1.8}.home-kpi-card div{min-width:0;display:flex;flex-direction:column}.home-kpi-card span:not(.home-kpi-icon){font-size:12px;font-weight:750;color:#263d61}.home-kpi-card strong{font-size:26px;line-height:1;margin:5px 0 7px}.home-kpi-card small{font-size:10px;color:#677993;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.home-kpi-blue .home-kpi-icon{color:#176ff0;background:#e9f2ff}.home-kpi-purple .home-kpi-icon{color:#7048ed;background:#f0ebff}.home-kpi-orange .home-kpi-icon{color:#f18a00;background:#fff1da}.home-kpi-red .home-kpi-icon{color:#ee3041;background:#ffebed}.home-card{padding:17px}.home-section-title{display:flex;justify-content:space-between;align-items:center;margin-bottom:9px}.home-section-title h2{margin:0;font-size:16px;font-weight:850}.home-section-title span{font-size:10px;color:#8593a8}.home-operational-list{display:flex;flex-direction:column}.home-operational-row{display:grid;grid-template-columns:34px minmax(130px,190px) 1fr 82px 18px;align-items:center;gap:10px;min-height:42px;border-top:1px solid #edf1f6;text-decoration:none;color:inherit}.home-operational-row:first-child{border-top:0}.home-row-icon{width:28px;height:28px;border-radius:50%;background:#edf4ff;color:#1670ef;display:grid;place-items:center}.home-row-icon svg,.agenda-icon svg,.status-icon svg,.activity-dot svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:1.8}.home-row-label{font-size:12px;font-weight:700}.home-row-progress{height:6px;border-radius:20px;background:#e8edf4;overflow:hidden}.home-row-progress i{display:block;height:100%;border-radius:inherit;background:#2478f5}.home-row-status{font-size:11px;color:#1670ef;font-weight:750;text-align:right}.home-row-chevron{width:16px;height:16px;fill:none;stroke:#274b7c;stroke-width:1.8}.home-agenda-list{display:flex;flex-direction:column}.home-agenda-row{display:grid;grid-template-columns:28px 1fr auto;align-items:center;min-height:37px;border-top:1px solid #edf1f6;font-size:12px}.home-agenda-row:first-child{border-top:0}.agenda-icon{color:#1670ef;display:grid;place-items:center}.home-agenda-row strong{font-size:10px;color:#1670ef}.home-status-list,.home-activity-list{display:flex;flex-direction:column}.home-status-row{display:grid;grid-template-columns:34px 1fr auto;align-items:center;gap:8px;padding:11px 0;border-top:1px solid #edf1f6}.home-status-row:first-child{border-top:0}.status-icon{color:#1670ef}.home-status-row div{display:flex;flex-direction:column}.home-status-row strong{font-size:11px}.home-status-row small{font-size:9px;color:#677993;margin-top:2px}.home-status-row em{font-style:normal;color:#16864e;background:#e8f8ef;border-radius:20px;padding:5px 9px;font-size:9px;font-weight:750}.home-status-row b{color:#ef8a00;background:#fff1dc;border-radius:20px;padding:5px 9px;font-size:10px}.home-activity-row{display:grid;grid-template-columns:36px 1fr auto;align-items:center;gap:8px;padding:10px 0;border-top:1px solid #edf1f6}.home-activity-row:first-child{border-top:0}.activity-dot{width:29px;height:29px;border-radius:50%;display:grid;place-items:center}.activity-dot.success{color:#19a860;background:#e8f8ef}.activity-dot.blue{color:#1670ef;background:#eaf2ff}.activity-dot.orange{color:#ec8b00;background:#fff0d9}.home-activity-row div{display:flex;flex-direction:column}.home-activity-row strong{font-size:10px}.home-activity-row small{font-size:9px;color:#687993;margin-top:3px}.home-activity-row time{font-size:9px;color:#566983}.home-actions-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.home-actions-grid a,.home-actions-grid button{min-height:58px;border:1px solid #dce6f3;border-radius:10px;background:#fff;color:#17345d;text-decoration:none;font:inherit;font-size:10px;font-weight:700;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;cursor:pointer}.home-actions-grid span{color:#1670ef}.home-actions-grid svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.8}.home-notice-card{margin-top:14px;display:grid;grid-template-columns:56px 1fr auto;align-items:center;gap:14px;padding:14px 18px}.home-notice-icon{width:44px;height:44px;border-radius:11px;background:#fff0d8;color:#ed8b00;display:grid;place-items:center}.home-notice-icon svg{width:23px;height:23px;fill:none;stroke:currentColor;stroke-width:1.8}.home-notice-card h2{margin:0 0 4px;font-size:15px}.home-notice-card strong{font-size:11px}.home-notice-card p{margin:3px 0 0;color:#62738d;font-size:10px}.home-notice-button{border:1px solid #2277ef;border-radius:9px;padding:10px 18px;color:#1669db;text-decoration:none;font-size:10px;font-weight:800}.home-empty-state{padding:30px;text-align:center;color:#78879b}.home-operational-row:hover,.home-actions-grid a:hover,.home-actions-grid button:hover{background:#f6f9ff}.home-notice-button:hover{background:#edf5ff}
    @media(max-width:1250px){.home-layout{grid-template-columns:1fr}.home-side-column{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));align-items:start}.home-actions-card{grid-column:1/-1}.home-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:720px){.page-home-dashboard .main-container{padding:16px}.home-kpi-grid{grid-template-columns:1fr}.home-side-column{display:flex}.home-welcome-card>div:first-child{max-width:100%}.home-welcome-graphic{opacity:.35;width:100%}.home-operational-row{grid-template-columns:34px 1fr auto 18px}.home-row-progress{display:none}.home-notice-card{grid-template-columns:48px 1fr}.home-notice-button{grid-column:1/-1;text-align:center}}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const button = document.getElementById('homeOpenScreens');
        const trigger = document.getElementById('screensMenuTrigger');
        if (button && trigger) button.addEventListener('click', () => trigger.click());
    });
</script>
@endsection
