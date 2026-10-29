@extends('layouts.app')

@section('title', 'Crear usuario | Hospital Dashboard')
@section('body-class', 'page-users page-user-create')

@section('styles')
<style>
    .page-user-create .main-container {
        padding: 22px 26px 28px;
        background: #f5f8fc;
    }

    .create-page {
        max-width: 1680px;
        margin: 0 auto;
        color: #0b254d;
    }

    .create-hero {
        position: relative;
        overflow: hidden;
        min-height: 138px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 24px 28px;
        margin-bottom: 14px;
        border: 1px solid #dce8f7;
        border-radius: 18px;
        background:
            radial-gradient(circle at 96% 18%, rgba(44, 135, 255, .13) 0 76px, transparent 77px),
            linear-gradient(110deg, #ffffff 0%, #f9fcff 56%, #e7fbfb 100%);
        box-shadow: 0 7px 24px rgba(24, 55, 95, .05);
    }

    .create-hero-copy { position: relative; z-index: 1; max-width: 820px; }

    .create-kicker {
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

    .create-kicker svg,
    .back-button svg,
    .section-icon svg {
        width: 17px;
        height: 17px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .create-hero h1 {
        margin: 0;
        color: #0a2248;
        font-size: 29px;
        line-height: 1.08;
        font-weight: 900;
        letter-spacing: -.5px;
    }

    .create-hero p {
        margin: 8px 0 0;
        color: #63758e;
        font-size: 13px;
        line-height: 1.5;
        max-width: 800px;
    }

    .back-button {
        position: relative;
        z-index: 1;
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 0 15px;
        border: 1px solid #1269e8;
        border-radius: 10px;
        background: linear-gradient(180deg, #2781ff, #0e67e9);
        color: #fff;
        text-decoration: none;
        font-size: 11px;
        font-weight: 850;
        box-shadow: 0 9px 20px rgba(20, 105, 232, .18);
    }

    .back-button:hover { color: #fff; transform: translateY(-1px); }

    .create-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 278px;
        gap: 16px;
        align-items: start;
    }

    .form-panel,
    .preview-panel {
        border: 1px solid #dfe7f1;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 7px 24px rgba(24, 55, 95, .045);
    }

    .form-panel { overflow: hidden; }
    .form-section { padding: 18px 20px; }
    .form-section + .form-section { border-top: 1px solid #e7edf5; }

    .section-heading {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 15px;
    }

    .section-icon {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: grid;
        place-items: center;
        border: 1px solid #cfe0f8;
        border-radius: 11px;
        background: #f4f8ff;
        color: #153f7d;
    }

    .section-icon.permissions { color: #d68a12; background: #fff7e8; border-color: #f7deb5; }

    .section-title {
        margin: 0;
        color: #0c2348;
        font-size: 15px;
        line-height: 1.2;
        font-weight: 900;
    }

    .section-subtitle {
        margin-top: 4px;
        color: #718299;
        font-size: 10px;
        line-height: 1.4;
    }

    .user-fields-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(240px, .8fr);
        column-gap: 40px;
        row-gap: 12px;
        align-items: end;
    }

    .left-fields {
        display: grid;
        gap: 10px;
    }

    .field-row {
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr);
        gap: 12px;
        align-items: center;
    }

    .field-row label,
    .status-field label {
        color: #17365d;
        font-size: 10px;
        font-weight: 850;
    }

    .field-control input,
    .status-field select {
        width: 100%;
        height: 38px;
        padding: 0 12px;
        border: 1px solid #d8e3ef;
        border-radius: 9px;
        background: #fff;
        color: #193a64;
        font-family: inherit;
        font-size: 11px;
        font-weight: 650;
        outline: none;
        transition: .16s ease;
    }

    .field-control input:focus,
    .status-field select:focus {
        border-color: #6aa8ff;
        box-shadow: 0 0 0 3px rgba(44, 128, 255, .10);
    }

    .field-hint {
        margin-top: 5px;
        color: #8a98aa;
        font-size: 9px;
        line-height: 1.3;
    }

    .field-error {
        margin-top: 5px;
        color: #c83241;
        font-size: 9px;
        font-weight: 700;
    }

    .status-field {
        align-self: end;
        max-width: 270px;
    }

    .status-field label { display: block; margin-bottom: 6px; }

    .permission-topline {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 9px;
    }

    .permission-topline .section-heading { margin-bottom: 0; }

    .permission-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 7px;
        flex-wrap: wrap;
    }

    .mini-button {
        min-height: 32px;
        padding: 0 13px;
        border: 1px solid #bcd7fb;
        border-radius: 999px;
        background: #fff;
        color: #1268e8;
        font-family: inherit;
        font-size: 9px;
        font-weight: 850;
        cursor: pointer;
        transition: .15s ease;
    }

    .mini-button:hover { background: #f3f8ff; }

    .info-strip {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 39px;
        padding: 8px 12px;
        margin-bottom: 10px;
        border: 1px solid #b9ccff;
        border-radius: 9px;
        background: #f0f4ff;
        color: #3345a3;
        font-size: 10px;
        line-height: 1.4;
    }

    .info-strip strong { font-weight: 900; }

    .permission-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .permission-card {
        min-height: 67px;
        display: grid;
        grid-template-columns: 18px 36px minmax(0, 1fr);
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        border: 1px solid #dde6f0;
        border-radius: 11px;
        background: #fff;
        cursor: pointer;
        transition: .15s ease;
    }

    .permission-card:hover { border-color: #9bc3fb; background: #fbfdff; }
    .permission-card:has(input:checked) { border-color: #5a9bfa; background: #eef5ff; box-shadow: inset 0 0 0 1px rgba(90,155,250,.18); }
    .permission-card input { width: 15px; height: 15px; accent-color: #176eea; }

    .permission-icon {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        border: 1px solid #d9e3ef;
        border-radius: 10px;
        background: #f8fbff;
        font-size: 15px;
    }

    .permission-name {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #102b55;
        font-size: 11px;
        font-weight: 900;
        line-height: 1.2;
    }

    .permission-description {
        display: block;
        margin-top: 4px;
        color: #687b93;
        font-size: 9px;
        line-height: 1.3;
        font-weight: 600;
    }

    .admin-label {
        display: inline-flex;
        align-items: center;
        min-height: 17px;
        padding: 0 6px;
        border-radius: 999px;
        background: #fff1de;
        color: #c46e08;
        font-size: 8px;
        font-weight: 900;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 13px 20px;
        border-top: 1px solid #e5ebf3;
        background: #fbfcfe;
    }

    .cancel-button,
    .submit-button {
        min-height: 39px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 20px;
        border-radius: 9px;
        font-family: inherit;
        font-size: 10px;
        font-weight: 850;
        text-decoration: none;
        cursor: pointer;
    }

    .cancel-button { border: 1px solid #bcd7fb; background: #fff; color: #1268e8; }
    .submit-button { min-width: 130px; border: 1px solid #1469e8; background: #1268e8; color: #fff; box-shadow: 0 8px 17px rgba(18,104,232,.16); }

    .preview-panel {
        position: sticky;
        top: 88px;
        padding: 17px;
    }

    .preview-avatar {
        width: 54px;
        height: 54px;
        display: grid;
        place-items: center;
        margin-bottom: 12px;
        border: 1px solid #bad6ff;
        border-radius: 15px;
        background: linear-gradient(145deg, #f6faff, #e9f3ff);
        color: #1168e8;
        font-size: 19px;
        font-weight: 900;
    }

    .preview-name {
        color: #0d264d;
        font-size: 14px;
        font-weight: 900;
        line-height: 1.25;
        word-break: break-word;
    }

    .preview-email {
        margin-top: 4px;
        color: #7a899b;
        font-size: 9px;
        word-break: break-word;
    }

    .preview-status {
        width: fit-content;
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 0 9px;
        margin-top: 11px;
        border-radius: 999px;
        background: #e4f8ec;
        color: #147b45;
        font-size: 9px;
        font-weight: 850;
    }

    .preview-status.inactive { background: #ffebeb; color: #bf4242; }

    .preview-divider { height: 1px; margin: 15px 0 12px; background: #e6edf5; }

    .preview-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 15px;
    }

    .preview-stat {
        min-height: 58px;
        padding: 10px;
        border: 1px solid #dfe7f1;
        border-radius: 11px;
        background: #fbfcfe;
    }

    .preview-stat span {
        display: block;
        color: #60738b;
        font-size: 8px;
        font-weight: 850;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .preview-stat strong {
        display: block;
        margin-top: 5px;
        color: #0b244d;
        font-size: 20px;
        line-height: 1;
        font-weight: 900;
    }

    .preview-subheading {
        margin-bottom: 8px;
        color: #263f63;
        font-size: 10px;
        font-weight: 850;
    }

    .selected-access-list { display: flex; flex-direction: column; gap: 6px; max-height: 300px; overflow-y: auto; }

    .selected-access-item {
        min-height: 30px;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 0 9px;
        border: 1px solid #c1d8fa;
        border-radius: 8px;
        background: #f2f7ff;
        color: #1769df;
        font-size: 9px;
        font-weight: 800;
    }

    .selected-access-item.admin { color: #bd700f; border-color: #f2d6aa; background: #fff6e9; }
    .empty-access { color: #7c8b9f; font-size: 9px; line-height: 1.4; }

    .validation-banner {
        margin-bottom: 12px;
        padding: 10px 12px;
        border: 1px solid #ffc8cf;
        border-radius: 10px;
        background: #fff2f4;
        color: #b72d3c;
        font-size: 10px;
        font-weight: 750;
    }

    @media (max-width: 1150px) {
        .create-shell { grid-template-columns: 1fr; }
        .preview-panel { position: static; }
        .user-fields-grid { grid-template-columns: 1fr; }
        .status-field { max-width: none; }
    }

    @media (max-width: 760px) {
        .page-user-create .main-container { padding: 15px; }
        .create-hero { align-items: flex-start; flex-direction: column; padding: 20px; }
        .create-hero h1 { font-size: 25px; }
        .field-row { grid-template-columns: 1fr; gap: 5px; }
        .permission-grid { grid-template-columns: 1fr; }
        .permission-topline { flex-direction: column; }
        .permission-actions { justify-content: flex-start; }
    }
</style>
@endsection

@section('content')
@php
    $selectedDashboards = old('dashboardAccess', $defaultDashboardAccess ?? []);
    if (is_string($selectedDashboards)) $selectedDashboards = [$selectedDashboards];
@endphp

<div class="create-page">
    <section class="create-hero">
        <div class="create-hero-copy">
            <div class="create-kicker">
                <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Nuevo acceso
            </div>
            <h1>Crear usuario</h1>
            <p>Registra una cuenta y selecciona exactamente qué tableros podrá consultar. El menú se ajustará automáticamente según estos permisos.</p>
        </div>

        <a href="{{ route('users.index') }}" class="back-button">
            <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
            Volver a usuarios
        </a>
    </section>

    @if ($errors->any())
        <div class="validation-banner"><strong>Revisa la información:</strong> {{ $errors->first() }}</div>
    @endif

    <div class="create-shell">
        <form class="form-panel" method="POST" action="{{ route('users.store') }}">
            @csrf

            <section class="form-section">
                <div class="section-heading">
                    <span class="section-icon">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg>
                    </span>
                    <div>
                        <h2 class="section-title">Datos del usuario</h2>
                        <div class="section-subtitle">Información básica para iniciar sesión en el dashboard.</div>
                    </div>
                </div>

                <div class="user-fields-grid">
                    <div class="left-fields">
                        <div class="field-row">
                            <label for="name">Nombre completo</label>
                            <div class="field-control">
                                <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Ej. Juan Pérez" autocomplete="name" required>
                                @error('name') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="field-row">
                            <label for="email">Correo electrónico</label>
                            <div class="field-control">
                                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="usuario@hospital.com" autocomplete="email" required>
                                @error('email') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="field-row">
                            <label for="password">Contraseña temporal</label>
                            <div class="field-control">
                                <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" autocomplete="new-password" required>
                                <div class="field-hint">El usuario podrá usar esta contraseña para su primer acceso.</div>
                                @error('password') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="status-field">
                        <label for="status">Estatus</label>
                        <select id="status" name="status" required>
                            <option value="active" @selected(old('status', 'active') === 'active')>Activo</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactivo</option>
                        </select>
                        <div class="field-hint">Los usuarios inactivos no deberían operar el sistema.</div>
                        @error('status') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="permission-topline">
                    <div class="section-heading">
                        <span class="section-icon permissions">
                            <svg viewBox="0 0 24 24"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/><path d="M12 8v8M9 11h6"/></svg>
                        </span>
                        <div>
                            <h2 class="section-title">Permisos por tablero</h2>
                            <div class="section-subtitle">Selecciona las secciones visibles para este usuario.</div>
                        </div>
                    </div>

                    <div class="permission-actions">
                        <button type="button" class="mini-button" id="selectOperationalDashboards">Seleccionar tableros</button>
                        <button type="button" class="mini-button" id="selectAllDashboards">Seleccionar todo</button>
                        <button type="button" class="mini-button" id="clearDashboards">Limpiar</button>
                    </div>
                </div>

                <div class="info-strip">
                    <span>ⓘ</span>
                    <span>La opción <strong>Usuarios</strong> otorga acceso administrativo para crear cuentas y modificar permisos desde esta sección.</span>
                </div>

                <div class="permission-grid">
                    @foreach ($dashboardOptions as $key => $dashboard)
                        <label
                            class="permission-card"
                            data-dashboard-card="1"
                            data-dashboard-key="{{ $key }}"
                            data-dashboard-label="{{ $dashboard['label'] ?? $key }}"
                            data-dashboard-icon="{{ $dashboard['icon'] ?? '•' }}"
                            data-dashboard-admin="{{ ($dashboard['admin'] ?? false) ? '1' : '0' }}"
                        >
                            <input
                                type="checkbox"
                                name="dashboardAccess[]"
                                value="{{ $key }}"
                                data-admin="{{ ($dashboard['admin'] ?? false) ? '1' : '0' }}"
                                @checked(in_array($key, $selectedDashboards, true))
                            >
                            <span class="permission-icon">{{ $dashboard['icon'] ?? '•' }}</span>
                            <span>
                                <span class="permission-name">
                                    {{ $dashboard['label'] ?? $key }}
                                    @if (($dashboard['admin'] ?? false) === true)<span class="admin-label">Admin</span>@endif
                                </span>
                                <span class="permission-description">{{ $dashboard['description'] ?? 'Acceso al módulo seleccionado.' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('dashboardAccess') <div class="field-error">{{ $message }}</div> @enderror
                @error('dashboardAccess.*') <div class="field-error">{{ $message }}</div> @enderror
            </section>

            <div class="form-actions">
                <a href="{{ route('users.index') }}" class="cancel-button">Cancelar</a>
                <button type="submit" class="submit-button">Crear usuario</button>
            </div>
        </form>

        <aside class="preview-panel">
            <div class="preview-avatar" id="previewAvatar">NU</div>
            <div class="preview-name" id="previewName">Nuevo usuario</div>
            <div class="preview-email" id="previewEmail">correo@hospital.com</div>
            <div class="preview-status" id="previewStatus">● Activo</div>

            <div class="preview-divider"></div>

            <div class="preview-stats">
                <div class="preview-stat"><span>Accesos</span><strong id="previewAccessCount">0</strong></div>
                <div class="preview-stat"><span>Admin</span><strong id="previewAdminValue">No</strong></div>
            </div>

            <div class="preview-subheading">Tableros seleccionados</div>
            <div id="selectedAccessList" class="selected-access-list"></div>
        </aside>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = Array.from(document.querySelectorAll('input[name="dashboardAccess[]"]'));
        const cards = Array.from(document.querySelectorAll('[data-dashboard-card="1"]'));
        const selectOperational = document.getElementById('selectOperationalDashboards');
        const selectAll = document.getElementById('selectAllDashboards');
        const clearAll = document.getElementById('clearDashboards');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const statusInput = document.getElementById('status');
        const previewAvatar = document.getElementById('previewAvatar');
        const previewName = document.getElementById('previewName');
        const previewEmail = document.getElementById('previewEmail');
        const previewStatus = document.getElementById('previewStatus');
        const previewAccessCount = document.getElementById('previewAccessCount');
        const previewAdminValue = document.getElementById('previewAdminValue');
        const selectedAccessList = document.getElementById('selectedAccessList');

        function initialsFromName(name) {
            const parts = String(name || '').trim().split(/\s+/).filter(Boolean).slice(0, 2);
            if (!parts.length) return 'NU';
            return parts.map(part => part.charAt(0).toUpperCase()).join('');
        }

        function selectedCards() {
            return cards.filter(function (card) {
                const input = card.querySelector('input[type="checkbox"]');
                return input && input.checked;
            });
        }

        function updatePreview() {
            const name = nameInput && nameInput.value.trim() ? nameInput.value.trim() : 'Nuevo usuario';
            const email = emailInput && emailInput.value.trim() ? emailInput.value.trim() : 'correo@hospital.com';
            const status = statusInput ? statusInput.value : 'active';
            const selected = selectedCards();
            const hasAdmin = selected.some(card => card.dataset.dashboardAdmin === '1');

            previewAvatar.textContent = initialsFromName(name);
            previewName.textContent = name;
            previewEmail.textContent = email;
            previewAccessCount.textContent = selected.length;
            previewAdminValue.textContent = hasAdmin ? 'Sí' : 'No';
            previewStatus.textContent = status === 'active' ? '● Activo' : '● Inactivo';
            previewStatus.classList.toggle('inactive', status !== 'active');

            selectedAccessList.innerHTML = '';
            if (!selected.length) {
                const empty = document.createElement('div');
                empty.className = 'empty-access';
                empty.textContent = 'Selecciona al menos un tablero para permitir el acceso del usuario.';
                selectedAccessList.appendChild(empty);
                return;
            }

            selected.forEach(function (card) {
                const item = document.createElement('div');
                item.className = 'selected-access-item' + (card.dataset.dashboardAdmin === '1' ? ' admin' : '');
                item.textContent = (card.dataset.dashboardIcon || '•') + ' ' + (card.dataset.dashboardLabel || card.dataset.dashboardKey || 'Tablero');
                selectedAccessList.appendChild(item);
            });
        }

        if (selectOperational) {
            selectOperational.addEventListener('click', function () {
                checkboxes.forEach(function (checkbox) { checkbox.checked = checkbox.dataset.admin !== '1'; });
                updatePreview();
            });
        }

        if (selectAll) {
            selectAll.addEventListener('click', function () {
                checkboxes.forEach(function (checkbox) { checkbox.checked = true; });
                updatePreview();
            });
        }

        if (clearAll) {
            clearAll.addEventListener('click', function () {
                checkboxes.forEach(function (checkbox) { checkbox.checked = false; });
                updatePreview();
            });
        }

        checkboxes.forEach(function (checkbox) { checkbox.addEventListener('change', updatePreview); });
        [nameInput, emailInput, statusInput].forEach(function (element) {
            if (!element) return;
            element.addEventListener('input', updatePreview);
            element.addEventListener('change', updatePreview);
        });

        updatePreview();
    });
</script>
@endsection
