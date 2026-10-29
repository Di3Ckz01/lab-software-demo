@extends('layouts.app')

@section('title', 'Proveedores | V-Care')
@section('body-class', 'page-supplier-studies')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/supplier-studies.css') }}?v={{ filemtime(public_path('css/supplier-studies.css')) }}">
@endsection

@section('content')
<div class="supplier-page" id="supplierStudyDashboard"
     data-data-url="{{ route('dashboards.supplier-studies.data') }}"
     data-pending-url="{{ route('dashboards.supplier-studies.pending') }}"
     data-suppliers-url="{{ route('dashboards.supplier-studies.suppliers') }}"
     data-catalog-url="{{ route('dashboards.supplier-studies.catalog') }}"
     data-assignments-url="{{ route('dashboards.supplier-studies.assignments') }}"
     data-create-supplier-url="{{ route('dashboards.supplier-studies.suppliers.store') }}"
     data-edit-supplier-url="{{ route('dashboards.supplier-studies.suppliers.update') }}"
     data-assign-url="{{ route('dashboards.supplier-studies.assign-study') }}"
     data-login-url="{{ route('login') }}"
     data-csrf="{{ csrf_token() }}">

    @php
        $monthNames = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
    @endphp

    <header class="supplier-page-header dashboard-header">
        <div>
            <div class="supplier-title-row">
                <h1 class="dashboard-title">Auditoría exámenes por proveedores</h1>
            </div>
            <p class="dashboard-subtitle">
                Consulta estudios por mes y administra su asignación a proveedores.
            </p>
        </div>

        <div class="supplier-header-actions">
            <button type="button" class="supplier-btn supplier-btn-secondary" id="manageSuppliersButton">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm9 14v-2a4 4 0 0 0-3-3.87M14.5 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Administrar proveedores</span>
            </button>

            <div class="supplier-export-wrap supplier-export-wrap-header">
                <button type="button" class="supplier-btn supplier-btn-secondary" id="supplierExportButton">
                    <svg class="supplier-excel-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path class="excel-file" d="M6 2.5h8.4L19 7.1V21H6z"/>
                        <path class="excel-fold" d="M14.4 2.5v4.7H19"/>
                        <path class="excel-grid" d="M13.2 10.2h3.4M13.2 13.1h3.4M13.2 16h3.4"/>
                        <path class="excel-x" d="M7.9 10.2l3.4 5.8M11.3 10.2L7.9 16"/>
                    </svg>
                    <span>Exportar Excel</span>
                </button>
            </div>
        </div>
    </header>

    <section class="supplier-filter-card" aria-labelledby="supplierFiltersTitle">
        <div class="supplier-section-heading">
            <span class="supplier-heading-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 5h16l-6 7v5l-4 2v-7z"/></svg>
            </span>
            <div>
                <h2 id="supplierFiltersTitle">Filtros de consulta mes {{ $monthNames[(int) $defaultMonth] ?? 'seleccionado' }}</h2>
                <p>Selecciona el mes y después trabaja con un proveedor específico o con la vista consolidada de todos.</p>
            </div>
        </div>

        <form id="supplierFiltersForm" class="supplier-filter-grid" data-no-loading="true">
            <div class="supplier-filter-group supplier-period-group">
                <label for="supplierMonth">Mes - Año</label>
                <div class="supplier-period-row">
                    <div class="supplier-select-shell supplier-period-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v3M17 2v3M4 8h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/></svg>
                        @php
                            $selectedYearPeriod = collect($availablePeriods ?? [])->firstWhere('year', (int) $defaultYear);
                            $availableMonths = $selectedYearPeriod['months'] ?? [];
                        @endphp
                        <select id="supplierMonth" required @disabled(empty($availablePeriods))>
                            @if (empty($availablePeriods))
                                <option value="">Sin periodos disponibles</option>
                            @else
                                @foreach ($availableMonths as $monthNumber)
                                    <option value="{{ $monthNumber }}" @selected((int) $defaultMonth === (int) $monthNumber)>{{ $monthNames[(int) $monthNumber] ?? $monthNumber }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="supplier-select-shell supplier-period-shell supplier-year-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v3M17 2v3M4 8h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/></svg>
                        <select id="supplierYear" required @disabled(empty($availablePeriods))>
                            @if (empty($availablePeriods))
                                <option value="">Sin años disponibles</option>
                            @else
                                @foreach (array_reverse($availablePeriods) as $period)
                                    <option value="{{ $period['year'] }}" @selected((int) $defaultYear === (int) $period['year'])>{{ $period['year'] }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <small>Solo se muestran los meses y años que el backend reporta como disponibles.</small>
            </div>

            <div class="supplier-filter-group supplier-provider-group">
                <label for="supplierSelect">Proveedor</label>
                <div class="supplier-select-shell">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h16M6 20V7h12v13M9 10h2M13 10h2M9 14h2M13 14h2M9 4h6v3H9z"/></svg>
                    <select id="supplierSelect" disabled>
                        <option value="">Selecciona un proveedor</option>
                        @foreach ($suppliers as $supplier)
                            @if (($supplier['status'] ?? 'active') === 'active')
                                <option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <small>El proveedor seleccionado será el destino de cualquier reasignación.</small>
            </div>

            <div class="supplier-filter-group supplier-view-group">
                <label>Vista <span class="supplier-inline-info">i</span></label>
                <div class="supplier-view-selector">
                    <label class="supplier-view-card is-active" for="supplierViewAll">
                        <input type="radio" name="supplierViewMode" id="supplierViewAll" value="all" checked>
                        <span class="supplier-view-radio" aria-hidden="true"></span>
                        <span class="supplier-view-copy">
                            <strong>Todos los proveedores</strong>
                            <small>Vista consolidada, sin edición de asignaciones.</small>
                        </span>
                    </label>

                    <span class="supplier-view-divider" aria-hidden="true">×</span>

                    <label class="supplier-view-card" for="supplierViewSpecific">
                        <input type="radio" name="supplierViewMode" id="supplierViewSpecific" value="specific">
                        <span class="supplier-view-radio" aria-hidden="true"></span>
                        <span class="supplier-view-copy">
                            <strong>Proveedor específico</strong>
                            <small>Permite asignar estudios al proveedor seleccionado.</small>
                        </span>
                    </label>
                </div>

                <div class="supplier-filter-actions supplier-filter-actions-embedded">
                    <button type="submit" class="supplier-btn supplier-btn-primary" id="supplierConsultButton">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                        <span>Consultar</span>
                    </button>
                    <button type="button" class="supplier-btn supplier-btn-secondary" id="supplierClearButton">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
                        <span>Limpiar</span>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <div class="supplier-alert" id="supplierAlert" role="alert" @if (empty($errorMessage)) hidden @endif>
        {{ $errorMessage ?? '' }}
    </div>

    <div class="supplier-job-notice" id="supplierJobNotice" role="status" hidden></div>

    <section class="supplier-metrics-grid" aria-label="Resumen del periodo">
        <article class="supplier-metric-card">
            <span class="supplier-metric-icon blue" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v6h6M10 13h6M10 17h6"/></svg>
            </span>
            <div class="supplier-metric-copy">
                <small id="supplierMetricTotalLabel">Total de estudios / mes</small>
                <strong id="supplierMetricTotal">0</strong>
                <span id="supplierMetricTotalNote">Total generado en el mes seleccionado.</span>
            </div>
        </article>

        <article class="supplier-metric-card">
            <span class="supplier-metric-icon green" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <div class="supplier-metric-copy">
                <small id="supplierMetricAssignedLabel">Validados a tiempo</small>
                <strong id="supplierMetricAssigned">0</strong>
                <span id="supplierMetricAssignedNote">Estudios validados dentro del mismo mes.</span>
            </div>
        </article>

        <article class="supplier-metric-card">
            <span class="supplier-metric-icon amber" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.8 1.8"/></svg>
            </span>
            <div class="supplier-metric-copy">
                <small id="supplierMetricValidatedLaterLabel">Validados tarde</small>
                <strong id="supplierMetricValidatedLater">0</strong>
                <span id="supplierMetricValidatedLaterNote">Estudios validados en un mes posterior.</span>
            </div>
        </article>

        <article class="supplier-metric-card">
            <span class="supplier-metric-icon amber" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.8 1.8"/></svg>
            </span>
            <div class="supplier-metric-copy">
                <small id="supplierMetricOccupiedLabel">Pendientes del mes</small>
                <strong id="supplierMetricOccupied">0</strong>
                <span id="supplierMetricOccupiedNote">Estudios del mes que aún no se validan.</span>
            </div>
        </article>

        <article class="supplier-metric-card">
            <span class="supplier-metric-icon violet" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 7h16M6 4h12M6 10v9m6-9v9m6-9v9M4 21h16"/><path d="M8 4V2h8v2"/></svg>
            </span>
            <div class="supplier-metric-copy">
                <small id="supplierMetricHistoricalPendingLabel">Pendientes de meses anteriores</small>
                <strong id="supplierMetricHistoricalPending">0</strong>
                <span id="supplierMetricHistoricalPendingNote">Arrastre de meses y años anteriores.</span>
            </div>
            <button type="button" class="supplier-metric-open is-sky" id="supplierHistoricalPendingButton"
                    aria-label="Ver órdenes pendientes de meses anteriores" title="Ver órdenes pendientes de meses anteriores">
                <span aria-hidden="true">+</span>
            </button>
        </article>
    </section>

    <!-- Modal: detalle de órdenes pendientes de meses anteriores -->
    <div class="supplier-modal-backdrop" id="supplierHistoricalPendingModal" aria-hidden="true">
        <section class="supplier-modal supplier-pending-modal" role="dialog" aria-modal="true" aria-labelledby="supplierHistoricalPendingTitle">
            <header class="supplier-management-head">
                <div>
                    <span class="supplier-card-eyebrow">Pendientes históricos</span>
                    <h2 id="supplierHistoricalPendingTitle">Pendientes de meses anteriores</h2>
                    <p id="supplierHistoricalPendingDescription">Órdenes creadas antes del periodo seleccionado que siguen sin validarse.</p>
                </div>
                <button type="button" class="supplier-modal-close" data-close-modal="supplierHistoricalPendingModal" aria-label="Cerrar">×</button>
            </header>
            <div class="supplier-pending-content" id="supplierHistoricalPendingContent" aria-live="polite">
                <div class="supplier-later-loading">Abre el histórico para ver las órdenes pendientes…</div>
            </div>
            <footer class="supplier-later-footer">
                <span id="supplierHistoricalPendingCount">0 órdenes-estudio</span>
                <button type="button" class="supplier-btn supplier-btn-secondary" id="supplierHistoricalPendingMore" hidden>Cargar más</button>
            </footer>
        </section>
    </div>

    <section class="supplier-table-card supplier-results-card">
        <header class="supplier-table-header supplier-results-header">
            <div class="supplier-results-title">
                <span class="supplier-heading-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v6h6M10 13h6M10 17h6"/></svg>
                </span>
                <div>
                    <div class="supplier-card-eyebrow">Resultados de estudios</div>
                    <h2>Resultados de estudios</h2>
                    <p id="supplierTableDescription">Consulta para cargar los estudios del mes seleccionado.</p>
                </div>
            </div>

            <div class="supplier-table-meta-wrap">
                <div class="supplier-result-counter" id="supplierResultCounter">0 estudios</div>
                <div class="supplier-table-meta" id="supplierTableMeta">Mostrando 0 de 0 estudios</div>
            </div>
        </header>

        <nav class="supplier-report-tabs" id="supplierReportTabs" role="tablist" aria-label="Filtrar estudios por proveedor"></nav>

        <div class="supplier-table-wrap">
            <table class="supplier-table">
                <thead>
                    <tr>
                        <th class="supplier-code-column">Código del examen</th>
                        <th>Nombre</th>
                        <th class="supplier-quantity-column">Cantidad</th>
                        <th class="supplier-assignment-column">Asignación</th>
                    </tr>
                </thead>
                <tbody id="supplierStudyTableBody">
                    <tr class="supplier-empty-row">
                        <td colspan="4">Selecciona los filtros y presiona Consultar.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <footer class="supplier-table-footer">
            <nav class="supplier-study-pagination" id="supplierStudyPagination" aria-label="Paginación de estudios" hidden>
                <button type="button" class="supplier-page-button" id="supplierStudyPrevPage">Anterior</button>
                <span id="supplierStudyPageLabel">Página 1 de 1</span>
                <button type="button" class="supplier-page-button" id="supplierStudyNextPage">Siguiente</button>
            </nav>
            <div class="supplier-legend">
                <span><i class="supplier-legend-check">✓</i> Asignado al proveedor seleccionado</span>
                <span><i class="supplier-legend-lock">🔒</i> Asignado a otro proveedor</span>
            </div>
            <span id="supplierUpdatedAt">Sin consulta reciente</span>
        </footer>
    </section>

    <!-- Modal: confirmación de asignación / reasignación -->
    <div class="supplier-modal-backdrop" id="supplierAssignModal" aria-hidden="true">
        <div class="supplier-modal supplier-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="supplierAssignModalTitle">
            <button type="button" class="supplier-modal-close" data-close-modal="supplierAssignModal" aria-label="Cerrar">×</button>
            <div class="supplier-modal-icon warning">↔</div>
            <h2 id="supplierAssignModalTitle">Asignar estudio</h2>
            <p id="supplierAssignModalText">Confirma la asignación del estudio.</p>
            <div class="supplier-confirm-summary" id="supplierAssignSummary"></div>
            <div class="supplier-modal-actions">
                <button type="button" class="supplier-btn supplier-btn-secondary" data-close-modal="supplierAssignModal">Cancelar</button>
                <button type="button" class="supplier-btn supplier-btn-primary" id="supplierConfirmAssignButton">Asignar</button>
            </div>
        </div>
    </div>

    <!-- Modal: administración de proveedores y exámenes -->
    <div class="supplier-modal-backdrop" id="supplierManagementModal" aria-hidden="true">
        <div class="supplier-modal supplier-management-modal" role="dialog" aria-modal="true" aria-labelledby="supplierManagementTitle">
            <div class="supplier-management-head">
                <div>
                    <span class="supplier-card-eyebrow">Administración</span>
                    <h2 id="supplierManagementTitle">Proveedores y exámenes</h2>
                    <p>Administra proveedores y define qué estudios clínicos corresponden a cada uno.</p>
                </div>
                <button type="button" class="supplier-modal-close" data-close-modal="supplierManagementModal" aria-label="Cerrar">×</button>
            </div>

            <div class="supplier-management-tabs" role="tablist" aria-label="Administración de proveedores">
                <button type="button" class="supplier-management-tab is-active" data-management-tab="providers" role="tab" aria-selected="true">
                    Proveedores
                </button>
                <button type="button" class="supplier-management-tab" data-management-tab="studies" role="tab" aria-selected="false">
                    Exámenes por proveedor
                </button>
            </div>

            <section class="supplier-management-panel is-active" data-management-panel="providers">
                <form id="supplierProviderForm" class="supplier-provider-form" data-no-loading="true">
                    <input type="hidden" id="supplierProviderId">
                    <div class="supplier-provider-fields">
                        <div>
                            <label for="supplierProviderName">Nombre</label>
                            <input type="text" id="supplierProviderName" maxlength="150" placeholder="Nombre del proveedor" required>
                        </div>
                        <div>
                            <label for="supplierProviderStatus">Estatus</label>
                            <select id="supplierProviderStatus">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
                        </div>
                        <button type="submit" class="supplier-btn supplier-btn-primary" id="supplierProviderSaveButton">Crear proveedor</button>
                        <button type="button" class="supplier-btn supplier-btn-secondary" id="supplierProviderCancelEdit" hidden>Cancelar edición</button>
                    </div>
                </form>

                <div class="supplier-provider-table-wrap">
                    <table class="supplier-provider-table">
                        <thead>
                            <tr>
                                <th>Proveedor</th>
                                <th>Estatus</th>
                                <th>Fecha de alta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="supplierProviderTableBody"></tbody>
                    </table>
                </div>
            </section>

            <section class="supplier-management-panel" data-management-panel="studies" hidden>
                <div class="supplier-catalog-toolbar">
                    <div class="supplier-catalog-provider">
                        <label for="supplierCatalogSupplierSelect">Proveedor</label>
                        <select id="supplierCatalogSupplierSelect">
                            <option value="">Selecciona un proveedor</option>
                        </select>
                        <small>Solo los proveedores activos pueden recibir nuevas asignaciones.</small>
                    </div>

                    <form id="supplierCatalogSearchForm" class="supplier-catalog-search" data-no-loading="true">
                        <label for="supplierCatalogSearchInput">Buscar por código exacto</label>
                        <div class="supplier-catalog-search-row">
                            <input type="text" id="supplierCatalogSearchInput" maxlength="100" placeholder="Ej. 042030" autocomplete="off">
                            <button type="submit" class="supplier-btn supplier-btn-primary">Buscar</button>
                            <button type="button" class="supplier-btn supplier-btn-secondary" id="supplierCatalogClearSearch">Limpiar</button>
                        </div>
                    </form>
                </div>

                <div class="supplier-catalog-note">
                    <strong>Asignación de exámenes:</strong>
                    marca el checkbox para asignar un estudio al proveedor seleccionado y desmárcalo para quitar esa asignación.
                    Si el estudio pertenece a otro proveedor, se muestra un candado; al reasignarlo, el backend reemplaza la asignación anterior.
                </div>

                <div class="supplier-catalog-table-wrap">
                    <table class="supplier-provider-table supplier-catalog-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Abrev.</th>
                                <th>Nombre</th>
                                <th class="supplier-catalog-assignment-heading">Asignación</th>
                            </tr>
                        </thead>
                        <tbody id="supplierCatalogTableBody">
                            <tr><td colspan="4" class="supplier-catalog-empty">Abre esta pestaña para cargar el catálogo.</td></tr>
                        </tbody>
                    </table>
                </div>

                <footer class="supplier-catalog-footer">
                    <div id="supplierCatalogStatus">Página 1</div>
                    <div class="supplier-catalog-pagination">
                        <button type="button" class="supplier-btn supplier-btn-secondary" id="supplierCatalogPrevButton" disabled>Anterior</button>
                        <span id="supplierCatalogPageLabel">Página 1</span>
                        <button type="button" class="supplier-btn supplier-btn-secondary" id="supplierCatalogNextButton" disabled>Siguiente</button>
                    </div>
                </footer>
            </section>
        </div>
    </div>

    <div class="supplier-toast" id="supplierToast" role="status" aria-live="polite"></div>
</div>
@endsection

@section('scripts')
<script>
    window.supplierStudyInitialSuppliers = @json($suppliers);
    window.supplierStudyAvailablePeriods = @json($availablePeriods ?? []);
</script>
<script src="{{ asset('js/supplier-studies.js') }}?v={{ filemtime(public_path('js/supplier-studies.js')) }}"></script>
@endsection
