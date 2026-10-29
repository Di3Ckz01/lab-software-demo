@extends('layouts.app')

@section('title', 'Usuarios | Hospital Dashboard')
@section('body-class', 'page-users users-redesign')

@section('styles')
<style>
    .page-users .main-container {
        padding: 22px 26px 28px;
        background: #f5f8fc;
    }

    .users-page {
        max-width: 1680px;
        margin: 0 auto;
        color: #0b254d;
    }

    .users-hero {
        position: relative;
        overflow: hidden;
        min-height: 138px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 24px 28px;
        margin-bottom: 18px;
        border: 1px solid #dce8f7;
        border-radius: 18px;
        background:
            radial-gradient(circle at 96% 18%, rgba(44, 135, 255, .13) 0 76px, transparent 77px),
            linear-gradient(110deg, #ffffff 0%, #f9fcff 56%, #e7fbfb 100%);
        box-shadow: 0 7px 24px rgba(24, 55, 95, .05);
    }

    .users-hero-copy {
        position: relative;
        z-index: 1;
        max-width: 820px;
    }

    .users-kicker {
        width: fit-content;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 11px;
        margin-bottom: 11px;
        border: 1px solid #bcd6ff;
        border-radius: 999px;
        background: #f4f8ff;
        color: #1465df;
        font-size: 11px;
        font-weight: 850;
    }

    .users-kicker svg,
    .create-user-button svg,
    .filter-icon svg,
    .metric-icon svg,
    .edit-button svg {
        width: 17px;
        height: 17px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .users-hero h1 {
        margin: 0;
        color: #0a2248;
        font-size: 29px;
        line-height: 1.08;
        font-weight: 900;
        letter-spacing: -.5px;
    }

    .users-hero p {
        margin: 8px 0 0;
        color: #63758e;
        font-size: 13px;
        line-height: 1.5;
    }

    .create-user-button {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 44px;
        padding: 0 18px;
        border: 1px solid #1569e8;
        border-radius: 11px;
        background: linear-gradient(180deg, #2580ff, #0b67ec);
        color: #fff;
        text-decoration: none;
        font-size: 12px;
        font-weight: 850;
        box-shadow: 0 10px 22px rgba(19, 105, 232, .20);
        transition: .16s ease;
        white-space: nowrap;
    }

    .create-user-button:hover {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 13px 26px rgba(19, 105, 232, .25);
    }

    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 14px;
    }

    .metric-card {
        min-height: 104px;
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 17px 20px;
        border: 1px solid #e0e8f2;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 6px 20px rgba(24, 55, 95, .045);
    }

    .metric-icon {
        width: 52px;
        height: 52px;
        flex: 0 0 52px;
        display: grid;
        place-items: center;
        border-radius: 14px;
    }

    .metric-icon svg { width: 25px; height: 25px; }
    .metric-icon.blue { color: #2374ef; background: #eef5ff; }
    .metric-icon.green { color: #17a65c; background: #eaf9f0; }
    .metric-icon.orange { color: #ee8b18; background: #fff2e3; }
    .metric-icon.purple { color: #7754e8; background: #f0edff; }

    .metric-copy span {
        display: block;
        margin-bottom: 6px;
        color: #5d6f87;
        font-size: 12px;
        font-weight: 750;
    }

    .metric-copy strong {
        display: block;
        color: #09224c;
        font-size: 27px;
        line-height: 1;
        font-weight: 900;
    }

    .users-card {
        padding: 16px;
        border: 1px solid #dfe7f1;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 7px 24px rgba(24, 55, 95, .045);
    }

    .users-toolbar {
        display: grid;
        grid-template-columns: minmax(280px, 1.2fr) minmax(180px, .8fr) minmax(220px, .8fr) auto;
        gap: 12px;
        align-items: center;
        margin-bottom: 15px;
    }

    .filter-control {
        position: relative;
    }

    .filter-icon {
        position: absolute;
        left: 13px;
        top: 50%;
        z-index: 2;
        transform: translateY(-50%);
        display: grid;
        place-items: center;
        color: #58708e;
        pointer-events: none;
    }

    .filter-control input,
    .filter-control select,
    .page-size-select {
        width: 100%;
        height: 42px;
        padding: 0 13px;
        border: 1px solid #d9e3ef;
        border-radius: 10px;
        background: #fff;
        color: #17365d;
        font-family: inherit;
        font-size: 12px;
        font-weight: 650;
        outline: none;
        transition: .16s ease;
    }

    .filter-control input { padding-left: 41px; }
    .filter-control input:focus,
    .filter-control select:focus,
    .page-size-select:focus {
        border-color: #6aa8ff;
        box-shadow: 0 0 0 3px rgba(44, 128, 255, .10);
    }

    .visible-counter {
        justify-self: end;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 0 14px;
        border: 1px solid #d4e4fb;
        border-radius: 999px;
        background: #f3f8ff;
        color: #3074d8;
        font-size: 11px;
        font-weight: 850;
        white-space: nowrap;
    }

    .users-table-wrap {
        overflow-x: auto;
        border: 1px solid #dfe7f1;
        border-radius: 12px;
    }

    .users-table {
        width: 100%;
        min-width: 1180px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .users-table thead th {
        height: 39px;
        padding: 0 14px;
        border-bottom: 1px solid #dfe7f1;
        background: #f9fbfe;
        color: #526985;
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .05em;
        text-transform: uppercase;
        text-align: left;
        white-space: nowrap;
    }

    .users-table tbody td {
        padding: 11px 14px;
        border-bottom: 1px solid #e8eef5;
        color: #40536d;
        font-size: 11px;
        vertical-align: middle;
    }

    .users-table tbody tr:last-child td { border-bottom: 0; }
    .users-table tbody tr:hover td { background: #fbfdff; }

    .user-cell {
        min-width: 245px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        display: grid;
        place-items: center;
        border: 1px solid #cfe0f8;
        border-radius: 50%;
        background: linear-gradient(145deg, #f3f8ff, #e8f2ff);
        color: #1670eb;
        font-size: 13px;
        font-weight: 900;
    }

    .user-name {
        color: #0c2348;
        font-size: 12px;
        font-weight: 850;
        line-height: 1.25;
    }

    .user-email,
    .user-level {
        margin-top: 2px;
        color: #76869b;
        font-size: 10px;
        line-height: 1.2;
    }

    .access-count {
        width: 32px;
        height: 32px;
        display: grid;
        place-items: center;
        border: 1px solid #d9e3ef;
        border-radius: 9px;
        background: #fbfcfe;
        color: #0e2b55;
        font-size: 12px;
        font-weight: 850;
    }

    .access-list {
        min-width: 360px;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .access-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        min-height: 23px;
        padding: 0 9px;
        border-radius: 7px;
        font-size: 9px;
        line-height: 1;
        font-weight: 800;
        white-space: nowrap;
    }

    .access-chip.blue { color: #1268e8; background: #edf5ff; }
    .access-chip.green { color: #168854; background: #eaf9ef; }
    .access-chip.cyan { color: #1484a5; background: #e8f9fc; }
    .access-chip.purple { color: #6347da; background: #f0edff; }
    .access-chip.orange { color: #ce740b; background: #fff2e2; }
    .access-chip.pink { color: #d33678; background: #fdebf4; }
    .access-chip.red { color: #c84d58; background: #ffedef; }
    .access-chip.gray { color: #5d6f87; background: #f1f4f8; }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        min-height: 25px;
        padding: 0 10px;
        border-radius: 999px;
        font-size: 9px;
        font-weight: 850;
        white-space: nowrap;
    }

    .status-pill.active { color: #147a45; background: #e6f8ed; }
    .status-pill.inactive { color: #c44444; background: #ffebeb; }

    .edit-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 34px;
        padding: 0 13px;
        border: 1px solid #bcd7fb;
        border-radius: 999px;
        background: #fff;
        color: #1268e8;
        text-decoration: none;
        font-size: 10px;
        font-weight: 850;
        transition: .15s ease;
    }

    .edit-button:hover { background: #f2f7ff; color: #0c5fd6; transform: translateY(-1px); }
    .edit-button.disabled { opacity: .45; pointer-events: none; }

    .users-footer {
        min-height: 52px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 10px 3px 0;
        color: #61738b;
        font-size: 10px;
    }

    .pagination-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .page-btn {
        width: 31px;
        height: 31px;
        display: grid;
        place-items: center;
        border: 1px solid #d9e3ef;
        border-radius: 8px;
        background: #fff;
        color: #3a526f;
        font-family: inherit;
        font-size: 10px;
        font-weight: 800;
        cursor: pointer;
    }

    .page-btn:hover { background: #f4f8fe; }
    .page-btn.active { color: #fff; border-color: #176eea; background: #176eea; }
    .page-btn:disabled { opacity: .4; cursor: default; }

    .page-size-select {
        width: 130px;
        height: 34px;
        margin-left: 9px;
    }

    .empty-state {
        padding: 42px 18px;
        text-align: center;
        color: #71839a;
    }

    .empty-state strong {
        display: block;
        margin-bottom: 6px;
        color: #15365f;
        font-size: 14px;
    }

    .hidden-by-filter,
    .hidden-by-page { display: none !important; }

    @media (max-width: 1180px) {
        .metrics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .users-toolbar { grid-template-columns: 1fr 1fr; }
        .visible-counter { justify-self: start; }
    }

    @media (max-width: 720px) {
        .page-users .main-container { padding: 15px; }
        .users-hero { align-items: flex-start; flex-direction: column; padding: 20px; }
        .users-hero h1 { font-size: 25px; }
        .metrics-grid { grid-template-columns: 1fr; }
        .users-toolbar { grid-template-columns: 1fr; }
        .visible-counter { justify-self: stretch; }
        .users-footer { align-items: flex-start; flex-direction: column; }
    }
</style>
@endsection

@section('content')
@php
    $totalUsers = count($users);
    $activeUsers = collect($users)->where('status', 'active')->count();
    $inactiveUsers = collect($users)->where('status', 'inactive')->count();
    $adminUsers = collect($users)->filter(fn ($user) => in_array('users', $user['dashboardAccess'] ?? [], true))->count();
@endphp

<div class="users-page">
    <section class="users-hero">
        <div class="users-hero-copy">
            <div class="users-kicker">
                <svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/><path d="M9.5 12.5 11 14l3.5-4"/></svg>
                Administración
            </div>
            <h1>Usuarios y permisos</h1>
            <p>Consulta usuarios, valida su estatus, edita sus tableros y administra qué cuentas permanecen activas.</p>
        </div>

        <a href="{{ route('users.create') }}" class="create-user-button">
            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M19 8v6M16 11h6"/></svg>
            Crear usuario
        </a>
    </section>

    <section class="metrics-grid" aria-label="Resumen de usuarios">
        <article class="metric-card">
            <span class="metric-icon blue"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M17 11h4M19 9v4"/></svg></span>
            <div class="metric-copy"><span>Usuarios totales</span><strong>{{ number_format($totalUsers) }}</strong></div>
        </article>
        <article class="metric-card">
            <span class="metric-icon green"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="m8 12 3 3 5-6"/></svg></span>
            <div class="metric-copy"><span>Activos</span><strong>{{ number_format($activeUsers) }}</strong></div>
        </article>
        <article class="metric-card">
            <span class="metric-icon orange"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.5 8.5v7M14.5 8.5v7"/></svg></span>
            <div class="metric-copy"><span>Inactivos</span><strong>{{ number_format($inactiveUsers) }}</strong></div>
        </article>
        <article class="metric-card">
            <span class="metric-icon purple"><svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/><path d="m12 8 1.1 2.2 2.4.4-1.8 1.7.4 2.4-2.1-1.1-2.1 1.1.4-2.4-1.8-1.7 2.4-.4L12 8Z"/></svg></span>
            <div class="metric-copy"><span>Administradores</span><strong>{{ number_format($adminUsers) }}</strong></div>
        </article>
    </section>

    <section class="users-card">
        <div class="users-toolbar">
            <div class="filter-control">
                <span class="filter-icon"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></span>
                <input type="text" id="userSearch" placeholder="Buscar por nombre, correo o permiso">
            </div>

            <div class="filter-control">
                <select id="statusFilter">
                    <option value="all">Todos los estatus</option>
                    <option value="active">Solo activos</option>
                    <option value="inactive">Solo inactivos</option>
                </select>
            </div>

            <div class="filter-control">
                <select id="accessFilter">
                    <option value="all">Todos los accesos</option>
                    @foreach ($dashboardOptions as $key => $dashboard)
                        <option value="{{ $key }}">{{ $dashboard['label'] ?? $key }}</option>
                    @endforeach
                </select>
            </div>

            <span class="visible-counter" id="visibleUsersCounter">{{ $totalUsers }} registros</span>
        </div>

        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Accesos</th>
                        <th>Tableros permitidos</th>
                        <th>Estatus</th>
                        <th>Creado por</th>
                        <th>Fecha creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    @forelse ($users as $user)
                        @php
                            $dashboardText = collect($user['dashboardLabels'] ?? [])->implode(' ');
                            $initials = collect(explode(' ', trim($user['name'] ?? 'U')))
                                ->filter()->take(2)
                                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                ->implode('');
                        @endphp
                        <tr
                            data-user-row="1"
                            data-status="{{ $user['status'] }}"
                            data-access="{{ implode(',', $user['dashboardAccess'] ?? []) }}"
                            data-search="{{ strtolower(($user['name'] ?? '') . ' ' . ($user['email'] ?? '') . ' ' . $dashboardText) }}"
                        >
                            <td>
                                <div class="user-cell">
                                    <span class="user-avatar">{{ $initials ?: 'U' }}</span>
                                    <span>
                                        <div class="user-name">{{ $user['name'] }}</div>
                                        <div class="user-email">{{ $user['email'] }}</div>
                                        <div class="user-level">Nivel compat. {{ $user['permissionLevel'] }}</div>
                                    </span>
                                </div>
                            </td>
                            <td><span class="access-count">{{ count($user['dashboardAccess'] ?? []) }}</span></td>
                            <td>
                                <div class="access-list">
                                    @forelse ($user['dashboardLabels'] as $key => $label)
                                        @php
                                            $chipClass = match ($key) {
                                                'pacientes-mes' => 'blue',
                                                'estudios' => 'green',
                                                'consulta-externa' => 'blue',
                                                'opportunity-status' => 'purple',
                                                'productividad' => 'purple',
                                                'venopunciones' => 'cyan',
                                                'sample-collection' => 'pink',
                                                'tv-dashboard' => 'orange',
                                                'sample-collection-tv' => 'orange',
                                                'users' => 'orange',
                                                default => 'gray',
                                            };
                                        @endphp
                                        <span class="access-chip {{ $chipClass }}">{{ $dashboardOptions[$key]['icon'] ?? '•' }} {{ $label }}</span>
                                    @empty
                                        <span class="access-chip gray">Sin accesos</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <span class="status-pill {{ $user['status'] === 'active' ? 'active' : 'inactive' }}">
                                    ● {{ $user['status'] === 'active' ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>{{ $user['createdBy'] ?? 'Sistema' }}</td>
                            <td>{{ $user['createdAt'] ?? '-' }}</td>
                            <td>
                                @if (!empty($user['id']))
                                    <a href="{{ route('users.edit', $user['id']) }}" class="edit-button">
                                        <svg viewBox="0 0 24 24"><path d="m4 20 4.2-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z"/><path d="m13.5 7.5 3 3"/></svg>
                                        Editar
                                    </a>
                                @else
                                    <span class="edit-button disabled">Sin ID</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row="1">
                            <td colspan="7"><div class="empty-state"><strong>No hay usuarios registrados</strong>Crea el primer usuario para comenzar a administrar accesos.</div></td>
                        </tr>
                    @endforelse

                    <tr id="noFilterResults" class="hidden-by-filter">
                        <td colspan="7"><div class="empty-state"><strong>No hay resultados</strong>Prueba con otro nombre, correo, estatus o permiso.</div></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="users-footer">
            <span id="paginationSummary">Mostrando 0 registros</span>
            <div class="pagination-wrap">
                <button type="button" class="page-btn" id="prevPage" aria-label="Página anterior">‹</button>
                <span id="pageButtons" class="pagination-wrap"></span>
                <button type="button" class="page-btn" id="nextPage" aria-label="Página siguiente">›</button>
                <select id="pageSize" class="page-size-select" aria-label="Registros por página">
                    <option value="6">6 por página</option>
                    <option value="10" selected>10 por página</option>
                    <option value="20">20 por página</option>
                    <option value="50">50 por página</option>
                </select>
            </div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('userSearch');
        const statusFilter = document.getElementById('statusFilter');
        const accessFilter = document.getElementById('accessFilter');
        const allRows = Array.from(document.querySelectorAll('[data-user-row="1"]'));
        const noResults = document.getElementById('noFilterResults');
        const counter = document.getElementById('visibleUsersCounter');
        const pageButtons = document.getElementById('pageButtons');
        const prevPage = document.getElementById('prevPage');
        const nextPage = document.getElementById('nextPage');
        const pageSizeSelect = document.getElementById('pageSize');
        const paginationSummary = document.getElementById('paginationSummary');

        let currentPage = 1;

        function normalize(value) {
            return String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
        }

        function matchingRows() {
            const term = normalize(searchInput ? searchInput.value : '');
            const status = statusFilter ? statusFilter.value : 'all';
            const access = accessFilter ? accessFilter.value : 'all';

            return allRows.filter(function (row) {
                const rowText = normalize(row.dataset.search || '');
                const rowStatus = row.dataset.status || '';
                const rowAccess = (row.dataset.access || '').split(',').filter(Boolean);
                return (!term || rowText.includes(term))
                    && (status === 'all' || rowStatus === status)
                    && (access === 'all' || rowAccess.includes(access));
            });
        }

        function renderPageButtons(totalPages) {
            pageButtons.innerHTML = '';
            if (totalPages <= 1) return;

            const maxButtons = 5;
            let start = Math.max(1, currentPage - 2);
            let end = Math.min(totalPages, start + maxButtons - 1);
            start = Math.max(1, end - maxButtons + 1);

            for (let page = start; page <= end; page += 1) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'page-btn' + (page === currentPage ? ' active' : '');
                button.textContent = page;
                button.addEventListener('click', function () {
                    currentPage = page;
                    applyFiltersAndPagination(false);
                });
                pageButtons.appendChild(button);
            }
        }

        function applyFiltersAndPagination(resetPage = true) {
            if (resetPage) currentPage = 1;
            const rows = matchingRows();
            const pageSize = Number(pageSizeSelect ? pageSizeSelect.value : 10) || 10;
            const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
            currentPage = Math.min(currentPage, totalPages);

            allRows.forEach(function (row) {
                row.classList.add('hidden-by-filter');
                row.classList.add('hidden-by-page');
            });

            const firstIndex = (currentPage - 1) * pageSize;
            const lastIndex = Math.min(firstIndex + pageSize, rows.length);

            rows.forEach(function (row, index) {
                row.classList.remove('hidden-by-filter');
                row.classList.toggle('hidden-by-page', index < firstIndex || index >= lastIndex);
            });

            if (noResults) noResults.classList.toggle('hidden-by-filter', rows.length !== 0 || allRows.length === 0);
            if (counter) counter.textContent = rows.length + ' registros';

            if (paginationSummary) {
                paginationSummary.textContent = rows.length
                    ? `Mostrando ${firstIndex + 1} a ${lastIndex} de ${rows.length} registros`
                    : 'Mostrando 0 registros';
            }

            if (prevPage) prevPage.disabled = currentPage <= 1 || rows.length === 0;
            if (nextPage) nextPage.disabled = currentPage >= totalPages || rows.length === 0;
            renderPageButtons(rows.length ? totalPages : 0);
        }

        [searchInput, statusFilter, accessFilter].forEach(function (element) {
            if (!element) return;
            element.addEventListener('input', () => applyFiltersAndPagination(true));
            element.addEventListener('change', () => applyFiltersAndPagination(true));
        });

        if (pageSizeSelect) pageSizeSelect.addEventListener('change', () => applyFiltersAndPagination(true));
        if (prevPage) prevPage.addEventListener('click', function () { if (currentPage > 1) { currentPage -= 1; applyFiltersAndPagination(false); } });
        if (nextPage) nextPage.addEventListener('click', function () { currentPage += 1; applyFiltersAndPagination(false); });

        applyFiltersAndPagination(true);
    });
</script>
@endsection
