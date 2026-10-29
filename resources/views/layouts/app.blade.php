<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#06265f">
    <title>@yield('title', 'Hospital Dashboard')</title>

    @yield('head')
    @yield('styles')

    <link rel="stylesheet" href="{{ asset('css/institute-theme.css') }}">

    <style>
        .screens-menu-trigger {
            font-family: inherit;
            cursor: pointer;
        }

        .screens-menu-trigger .screens-chevron {
            width: 14px !important;
            height: 14px !important;
            margin-top: -4px;
            transition: transform .18s ease;
        }

        .screens-menu-trigger[aria-expanded="true"] .screens-chevron {
            transform: rotate(180deg);
        }

        .screens-popover {
            position: fixed;
            z-index: 2500;
            width: 250px;
            padding: 10px;
            border: 1px solid #dbe5f2;
            border-radius: 18px;
            background: rgba(255, 255, 255, .98);
            box-shadow: 0 20px 45px rgba(15, 35, 70, .18);
            opacity: 0;
            visibility: hidden;
            transform: translateX(-8px) scale(.98);
            transform-origin: left center;
            transition: opacity .16s ease, transform .16s ease, visibility .16s ease;
        }

        .screens-popover.is-open {
            opacity: 1;
            visibility: visible;
            transform: translateX(0) scale(1);
        }

        .screens-popover::before {
            content: "";
            position: absolute;
            left: -7px;
            top: var(--screens-arrow-top, 34px);
            width: 12px;
            height: 12px;
            border-left: 1px solid #dbe5f2;
            border-bottom: 1px solid #dbe5f2;
            background: #fff;
            transform: rotate(45deg);
        }

        .screens-popover-title {
            padding: 5px 8px 9px;
            color: #71819a;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .screens-popover-option {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 11px;
            min-height: 62px;
            padding: 10px;
            border: 1px solid transparent;
            border-radius: 14px;
            color: #223b60;
            text-decoration: none;
            transition: .16s ease;
        }

        .screens-popover-option + .screens-popover-option {
            margin-top: 5px;
        }

        .screens-popover-option:hover,
        .screens-popover-option.active {
            color: #075ee5;
            border-color: #cfe0fb;
            background: #f2f7ff;
            transform: translateX(2px);
        }

        .screens-option-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: #eaf2ff;
            color: #1468f3;
        }

        .screens-option-icon.sample {
            color: #0f9f70;
            background: #e8f8f2;
        }

        .screens-option-icon svg {
            width: 21px;
            height: 21px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .screens-option-copy {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .screens-option-copy strong {
            color: inherit;
            font-size: 12px;
            line-height: 1.2;
        }

        .screens-option-copy small {
            color: #75849b;
            font-size: 10px;
            line-height: 1.25;
        }

        .topbar-user-menu-wrap {
            position: relative;
        }

        .topbar-user-pill {
            border: 0;
            font-family: inherit;
            cursor: pointer;
        }

        .topbar-user-pill:focus-visible {
            outline: 3px solid rgba(96, 165, 250, .45);
            outline-offset: 2px;
        }

        .topbar-user-pill[aria-expanded="true"] .topbar-chevron {
            transform: rotate(180deg);
        }

        .topbar-user-menu {
            position: absolute;
            z-index: 2600;
            top: calc(100% + 10px);
            right: 0;
            width: 245px;
            padding: 10px;
            border: 1px solid #dbe5f2;
            border-radius: 16px;
            background: rgba(255, 255, 255, .99);
            box-shadow: 0 20px 45px rgba(15, 35, 70, .2);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-7px) scale(.98);
            transform-origin: top right;
            transition: opacity .16s ease, transform .16s ease, visibility .16s ease;
        }

        .topbar-user-menu.is-open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .topbar-user-menu::before {
            content: "";
            position: absolute;
            top: -7px;
            right: 24px;
            width: 12px;
            height: 12px;
            border-top: 1px solid #dbe5f2;
            border-left: 1px solid #dbe5f2;
            background: #fff;
            transform: rotate(45deg);
        }

        .user-menu-header {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-radius: 12px;
            background: #f5f8fd;
        }

        .user-menu-avatar {
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #0b5edb;
            background: #e6f0ff;
            font-size: 14px;
            font-weight: 900;
        }

        .user-menu-copy {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .user-menu-copy strong {
            overflow: hidden;
            color: #18345b;
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-menu-copy small {
            margin-top: 2px;
            color: #73839a;
            font-size: 10px;
        }

        .user-menu-divider {
            height: 1px;
            margin: 9px 2px;
            background: #e4ebf4;
        }

        .user-menu-logout-form {
            margin: 0;
        }

        .user-menu-logout {
            width: 100%;
            min-height: 42px;
            padding: 9px 10px;
            border: 1px solid transparent;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #c52c3d;
            background: transparent;
            font-family: inherit;
            font-size: 11px;
            font-weight: 850;
            cursor: pointer;
            transition: .16s ease;
        }

        .user-menu-logout:hover {
            border-color: #ffd3d9;
            background: #fff1f3;
        }

        .user-menu-logout svg {
            width: 19px;
            height: 19px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @media (max-width: 980px) {
            .screens-popover {
                width: min(250px, calc(100vw - 132px));
            }
        }
    </style>
</head>
<body class="@yield('body-class')">
@php
    $authUser = session('auth_user');
    $dashboardNavItems = $authUser ? \App\Support\DashboardAccess::dashboardItems($authUser) : [];
    $screenNavItems = $authUser ? \App\Support\DashboardAccess::screenItems($authUser) : [];
    $adminNavItems = $authUser ? \App\Support\DashboardAccess::visibleItems($authUser, true) : [];
    $rawPageTitle = $__env->yieldContent('title', 'Hospital Dashboard');
    $pageTitle = trim(\Illuminate\Support\Str::before($rawPageTitle, '|'));
    $hasScreensMenu = !empty($screenNavItems);
    $screensMenuActive = request()->routeIs('dashboards.tv*')
        || request()->routeIs('dashboards.sample-collection-tv*');
@endphp

<div class="app-shell" id="appShell">
    @if ($authUser)
        <aside class="sidebar sidebar-rail" id="sidebar" aria-label="Navegación principal">
            <div class="sidebar-header">
                <a href="{{ route('dashboards.home') }}" class="brand-box brand-box-rail" aria-label="Inicio">
                    <span aria-label="Laboratorio Demo" style="display:inline-block;padding:7px 12px;border-radius:9px;background:#e8f1ff;color:#174b92;font-weight:800;font-size:15px;letter-spacing:.07em;white-space:nowrap;">LAB DEMO</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('dashboards.home') }}"
                   class="nav-link {{ request()->routeIs('dashboards.home') ? 'active' : '' }}"
                   title="Inicio">
                    <span class="nav-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5M5.5 10v10h13V10M9 20v-6h6v6"/></svg>
                    </span>
                    <span class="nav-label">Inicio</span>
                </a>

                @forelse ($dashboardNavItems as $dashboardKey => $dashboard)
                    <a href="{{ route($dashboard['route']) }}"
                       data-dashboard-key="{{ $dashboardKey }}"
                       class="nav-link {{ request()->routeIs($dashboard['route']) ? 'active' : '' }}"
                       title="{{ $dashboard['label'] ?? $dashboardKey }}">
                        <span class="nav-icon" aria-hidden="true">
                            @switch($dashboardKey)
                                @case('pacientes-mes')
                                    <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M20 8v6M17 11h6"/></svg>
                                    @break
                                @case('estudios')
                                    <svg viewBox="0 0 24 24"><path d="M9 3h6m-5 0v5l-5 9a2 2 0 0 0 1.7 3h10.6A2 2 0 0 0 19 17l-5-9V3M8 14h8"/></svg>
                                    @break
                                @case('consulta-externa')
                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r="1"/><path d="M12 4v2M12 18v2M4 12h2M18 12h2"/></svg>
                                    @break
                                @case('opportunity-status')
                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg>
                                    @break
                                @case('productividad')
                                    <svg viewBox="0 0 24 24"><path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/></svg>
                                    @break
                                @case('venopunciones')
                                    <svg viewBox="0 0 24 24"><path d="M12 3s6 6.2 6 11a6 6 0 1 1-12 0c0-4.8 6-11 6-11Z"/><path d="M9.5 15.5a3 3 0 0 0 5 1"/></svg>
                                    @break
                                @case('sample-collection')
                                    <svg viewBox="0 0 24 24"><path d="M12 3s6 6.2 6 11a6 6 0 1 1-12 0c0-4.8 6-11 6-11Z"/><path d="M9 15h6M12 12v6"/></svg>
                                    @break
                                @case('sample-collection-analytics')
                                    <svg viewBox="0 0 24 24"><path d="M4 20V12h4v8M10 20V5h4v15M16 20v-9h4v9M2 20h20"/></svg>
                                    @break
                                @case('supplier-studies')
                                    <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM7 9h4M7 13h4M14 9h3M14 13h3"/><path d="M8 5V3h8v2"/></svg>
                                    @break
                                @default
                                    <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4zM8 9h8M8 13h8"/></svg>
                            @endswitch
                        </span>

                        <span class="nav-label">{{ $dashboard['short_label'] ?? $dashboard['label'] ?? $dashboardKey }}</span>
                    </a>




                @empty
                    <div class="nav-empty-state">
                        <strong>Sin tableros</strong>
                    </div>
                @endforelse

                @if ($hasScreensMenu)
                    <button type="button"
                            class="nav-link screens-menu-trigger {{ $screensMenuActive ? 'active' : '' }}"
                            id="screensMenuTrigger"
                            aria-expanded="false"
                            aria-controls="screensPopover"
                            title="Seleccionar pantalla">
                        <span class="nav-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                        </span>
                        <span class="nav-label">Pantallas</span>
                        <svg class="screens-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                    </button>
                @endif

                @if (!empty($adminNavItems))
                    <div class="nav-section-title">Admin</div>

                    @foreach ($adminNavItems as $dashboardKey => $dashboard)
                        <a href="{{ route($dashboard['route']) }}"
                           data-dashboard-key="{{ $dashboardKey }}"
                           class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                           title="{{ $dashboard['label'] ?? $dashboardKey }}">
                            <span class="nav-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M16 20v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 11l2 2 3-3"/></svg>
                            </span>
                            <span class="nav-label">{{ $dashboard['short_label'] ?? $dashboard['label'] ?? $dashboardKey }}</span>
                        </a>
                    @endforeach
                @endif
            </nav>

        </aside>
    @endif

    <div class="main-area">
        <header class="topbar">
            <div class="topbar-content">
                <div class="topbar-left">
                    @if ($authUser)
                        <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Abrir menú">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                        </button>
                    @endif

                    <div class="breadcrumb-line">
                        <span class="breadcrumb-home" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5M5.5 10v10h13V10M9 20v-6h6v6"/></svg>
                        </span>
                        <span>Laboratorio Clínico</span>
                        <span class="breadcrumb-dot">•</span>
                        <strong>{{ $pageTitle }}</strong>
                    </div>
                </div>

                <div class="topbar-actions">
                    @if ($authUser)
                        @include('partials.context-help-button')

                        <span class="topbar-clock-pill" id="topbarClock">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg>
                            <span>Cargando fecha...</span>
                        </span>

                        <div class="topbar-user-menu-wrap">
                            <button type="button"
                                    class="topbar-user-pill"
                                    id="topbarUserMenuTrigger"
                                    aria-expanded="false"
                                    aria-controls="topbarUserMenu">
                                <span class="topbar-avatar">{{ mb_strtoupper(mb_substr($authUser['name'] ?? 'U', 0, 1)) }}</span>
                                <span class="topbar-user-copy">
                                    <strong>{{ $authUser['name'] ?? 'Usuario' }}</strong>
                                    <small>{{ count($authUser['dashboardAccess'] ?? []) }} accesos</small>
                                </span>
                                <svg class="topbar-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg>
                            </button>

                            <div class="topbar-user-menu" id="topbarUserMenu" aria-hidden="true">
                                <div class="user-menu-header">
                                    <span class="user-menu-avatar">{{ mb_strtoupper(mb_substr($authUser['name'] ?? 'U', 0, 1)) }}</span>
                                    <span class="user-menu-copy">
                                        <strong>{{ $authUser['name'] ?? 'Usuario' }}</strong>
                                        <small>{{ count($authUser['dashboardAccess'] ?? []) }} accesos asignados</small>
                                    </span>
                                </div>
                                <div class="user-menu-divider"></div>
                                <form method="POST"
                                      action="{{ route('logout') }}"
                                      class="user-menu-logout-form"
                                      data-no-loading="true">
                                    @csrf
                                    <button type="submit" class="user-menu-logout">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/></svg>
                                        <span>Cerrar sesión</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </header>

        <main class="main-container">
            @if (session('success'))
                <div class="alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert-error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@if ($hasScreensMenu)
    <div class="screens-popover" id="screensPopover" role="menu" aria-hidden="true">
        <div class="screens-popover-title">Selecciona una pantalla</div>

        @foreach ($screenNavItems as $screenKey => $screen)
            <a href="{{ route($screen['route']) }}"
               class="screens-popover-option {{ request()->routeIs($screen['route']) ? 'active' : '' }}"
               role="menuitem">
                <span class="screens-option-icon {{ $screenKey === 'sample-collection-tv' ? 'sample' : '' }}" aria-hidden="true">
                    @if ($screenKey === 'sample-collection-tv')
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4M12 7s3.5 3.5 3.5 6a3.5 3.5 0 1 1-7 0C8.5 10.5 12 7 12 7Z"/></svg>
                    @else
                        <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4M7 9h3v4H7zM14 8h3v5h-3z"/></svg>
                    @endif
                </span>
                <span class="screens-option-copy">
                    <strong>{{ $screen['label'] ?? $screenKey }}</strong>
                    <small>{{ $screen['description'] ?? 'Pantalla operativa disponible.' }}</small>
                </span>
            </a>
        @endforeach
    </div>
@endif

<div class="loading-overlay" id="loadingOverlay" aria-hidden="true" aria-live="polite">
    <div class="loading-card" role="status" aria-atomic="true">
        <span class="loading-spinner" aria-hidden="true"></span>
        <span class="loading-copy">
            <strong id="loadingTitle">Cargando tablero</strong>
            <small id="loadingMessage">Estamos preparando la información. Esto puede tomar unos segundos.</small>
        </span>
    </div>
</div>

@include('partials.context-help-drawer')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const topbarClock = document.getElementById('topbarClock');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const loadingTitle = document.getElementById('loadingTitle');
        const loadingMessage = document.getElementById('loadingMessage');
        const screensMenuTrigger = document.getElementById('screensMenuTrigger');
        const screensPopover = document.getElementById('screensPopover');
        const topbarUserMenuTrigger = document.getElementById('topbarUserMenuTrigger');
        const topbarUserMenu = document.getElementById('topbarUserMenu');

        const normalizeLoadingName = function (value) {
            return String(value || '')
                .replace(/\s+/g, ' ')
                .replace(/^(abrir|ver|ir a|consultar)\s+/i, '')
                .trim();
        };

        const showLoading = function (title, message) {
            if (!loadingOverlay) return;

            if (loadingTitle) {
                loadingTitle.textContent = title || 'Cargando tablero';
            }

            if (loadingMessage) {
                loadingMessage.textContent = message || 'Estamos preparando la información. Esto puede tomar unos segundos.';
            }

            loadingOverlay.classList.add('is-visible');
            loadingOverlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('is-loading-navigation');
        };

        const hideLoading = function () {
            if (!loadingOverlay) return;

            loadingOverlay.classList.remove('is-visible');
            loadingOverlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('is-loading-navigation');
        };

        // Indicador de carga compartido para consultas AJAX.
        window.VCareLoading = {
            show: function (title, message) {
                showLoading(title, message);
            },
            hide: hideLoading,
        };

        const getLinkLoadingName = function (link) {
            const explicitName = link.dataset.loadingName;
            if (explicitName) return normalizeLoadingName(explicitName);

            const label = link.querySelector('.nav-label, strong, .screens-option-copy strong');
            if (label && label.textContent) return normalizeLoadingName(label.textContent);

            return normalizeLoadingName(link.getAttribute('title') || link.textContent || 'tablero');
        };

        const closeScreensMenu = function () {
            if (!screensMenuTrigger || !screensPopover) return;

            screensMenuTrigger.setAttribute('aria-expanded', 'false');
            screensPopover.setAttribute('aria-hidden', 'true');
            screensPopover.classList.remove('is-open');
        };

        const closeUserMenu = function () {
            if (!topbarUserMenuTrigger || !topbarUserMenu) return;

            topbarUserMenuTrigger.setAttribute('aria-expanded', 'false');
            topbarUserMenu.setAttribute('aria-hidden', 'true');
            topbarUserMenu.classList.remove('is-open');
        };

        const positionScreensMenu = function () {
            if (!screensMenuTrigger || !screensPopover) return;

            const triggerRect = screensMenuTrigger.getBoundingClientRect();
            const spacing = 12;
            const viewportPadding = 12;

            screensPopover.style.left = `${Math.min(triggerRect.right + spacing, window.innerWidth - screensPopover.offsetWidth - viewportPadding)}px`;

            const preferredTop = triggerRect.top + (triggerRect.height / 2) - (screensPopover.offsetHeight / 2);
            const top = Math.max(viewportPadding, Math.min(preferredTop, window.innerHeight - screensPopover.offsetHeight - viewportPadding));
            screensPopover.style.top = `${top}px`;

            const arrowTop = Math.max(18, Math.min(triggerRect.top + (triggerRect.height / 2) - top - 6, screensPopover.offsetHeight - 28));
            screensPopover.style.setProperty('--screens-arrow-top', `${arrowTop}px`);
        };

        if (topbarUserMenuTrigger && topbarUserMenu) {
            topbarUserMenuTrigger.addEventListener('click', function (event) {
                event.stopPropagation();
                const willOpen = !topbarUserMenu.classList.contains('is-open');

                closeScreensMenu();
                if (!willOpen) {
                    closeUserMenu();
                    return;
                }

                topbarUserMenu.classList.add('is-open');
                topbarUserMenu.setAttribute('aria-hidden', 'false');
                topbarUserMenuTrigger.setAttribute('aria-expanded', 'true');
            });

            topbarUserMenu.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        }

        if (screensMenuTrigger && screensPopover) {
            screensMenuTrigger.addEventListener('click', function (event) {
                event.stopPropagation();
                const willOpen = !screensPopover.classList.contains('is-open');

                if (!willOpen) {
                    closeScreensMenu();
                    return;
                }

                screensPopover.classList.add('is-open');
                screensPopover.setAttribute('aria-hidden', 'false');
                screensMenuTrigger.setAttribute('aria-expanded', 'true');
                positionScreensMenu();
            });

            screensPopover.addEventListener('click', function (event) {
                event.stopPropagation();
            });

            screensPopover.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    closeScreensMenu();
                    if (sidebar && window.innerWidth <= 980) {
                        sidebar.classList.remove('mobile-open');
                    }
                });
            });

            window.addEventListener('resize', function () {
                if (screensPopover.classList.contains('is-open')) {
                    positionScreensMenu();
                }
            });

            window.addEventListener('scroll', function () {
                if (screensPopover.classList.contains('is-open')) {
                    positionScreensMenu();
                }
            }, true);
        }

        if (topbarClock) {
            const textNode = topbarClock.querySelector('span');
            const updateClock = function () {
                const now = new Date();
                const formatter = new Intl.DateTimeFormat('es-MX', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });

                textNode.textContent = formatter.format(now);
            };

            updateClock();
            setInterval(updateClock, 60000);
        }

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (form.dataset.noLoading === 'true') return;

                const dashboardName = normalizeLoadingName(form.dataset.loadingName || pageTitle || 'tablero');
                showLoading(
                    `Actualizando ${dashboardName}`,
                    'Estamos consultando y procesando la información solicitada.'
                );

                const submitButton = form.querySelector('button[type="submit"]');

                if (submitButton) {
                    submitButton.disabled = true;
                    if (!submitButton.dataset.originalText) {
                        submitButton.dataset.originalText = submitButton.innerHTML;
                    }
                    submitButton.innerHTML = 'Procesando...';
                }
            });
        });

        document.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (event.defaultPrevented || link.dataset.noLoading === 'true') return;
                if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                if (link.target === '_blank' || link.hasAttribute('download')) return;

                const href = link.getAttribute('href');
                if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:')) return;

                let targetUrl;
                try {
                    targetUrl = new URL(link.href, window.location.href);
                } catch (error) {
                    return;
                }

                if (targetUrl.origin !== window.location.origin) return;
                if (targetUrl.href === window.location.href) return;

                const destinationName = getLinkLoadingName(link) || 'tablero';
                showLoading(
                    `Cargando ${destinationName}`,
                    'Estamos preparando el tablero y sus datos. Esto puede tomar unos segundos.'
                );
            });
        });

        window.addEventListener('pageshow', hideLoading);
        window.addEventListener('pagehide', function () {
            // Se mantiene visible mientras el navegador cambia de página.
        });

        if (mobileMenuBtn && sidebar) {
            mobileMenuBtn.addEventListener('click', function () {
                sidebar.classList.toggle('mobile-open');
            });

            sidebar.querySelectorAll('.nav-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (link.id === 'screensMenuTrigger') return;

                    closeScreensMenu();
                    if (window.innerWidth <= 980) {
                        sidebar.classList.remove('mobile-open');
                    }
                });
            });
        }

        document.addEventListener('click', function (event) {
            closeScreensMenu();
            closeUserMenu();

            if (!sidebar || !mobileMenuBtn) return;

            if (window.innerWidth <= 980 && !sidebar.contains(event.target) && !mobileMenuBtn.contains(event.target)) {
                sidebar.classList.remove('mobile-open');
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeScreensMenu();
                closeUserMenu();
            }
        });
    });
</script>

<script>
    if (window.Highcharts) {
        Highcharts.setOptions({
            colors: ['#2478fa', '#6d5ce8', '#16b364', '#f97316', '#ef4444', '#0ea5e9', '#8b5cf6'],
            chart: {
                style: {
                    fontFamily: 'Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, Segoe UI, Arial, sans-serif'
                },
                backgroundColor: 'transparent',
                animation: { duration: 350 }
            },
            title: {
                style: {
                    color: '#102142',
                    fontSize: '14px',
                    fontWeight: '800'
                }
            },
            subtitle: {
                style: {
                    color: '#65738d',
                    fontSize: '10px'
                }
            },
            xAxis: {
                lineColor: '#dfe6ef',
                tickColor: '#dfe6ef',
                labels: {
                    style: {
                        color: '#53637e',
                        fontSize: '10px'
                    }
                },
                title: {
                    style: {
                        color: '#65738d',
                        fontSize: '10px'
                    }
                }
            },
            yAxis: {
                gridLineColor: '#edf1f6',
                labels: {
                    style: {
                        color: '#53637e',
                        fontSize: '10px'
                    }
                },
                title: {
                    style: {
                        color: '#65738d',
                        fontSize: '10px'
                    }
                }
            },
            legend: {
                itemStyle: {
                    color: '#425572',
                    fontSize: '10px',
                    fontWeight: '600'
                }
            },
            credits: { enabled: false },
            tooltip: {
                backgroundColor: '#ffffff',
                borderColor: '#dce5f0',
                borderRadius: 10,
                shadow: {
                    color: 'rgba(15,23,42,.16)',
                    offsetX: 0,
                    offsetY: 8,
                    opacity: .18,
                    width: 12
                },
                style: {
                    color: '#102142',
                    fontSize: '10px'
                }
            }
        });
    }
</script>

@yield('scripts')
    @include('partials.demo-notice')
</body>
</html>