(function () {
    'use strict';

    const root = document.getElementById('supplierStudyDashboard');
    if (!root) return;

    const dataUrl = root.dataset.dataUrl;
    const pendingUrl = root.dataset.pendingUrl;
    const suppliersUrl = root.dataset.suppliersUrl;
    const catalogUrl = root.dataset.catalogUrl;
    const assignmentsUrl = root.dataset.assignmentsUrl;
    const createSupplierUrl = root.dataset.createSupplierUrl;
    const editSupplierUrl = root.dataset.editSupplierUrl;
    const assignUrl = root.dataset.assignUrl;
    const loginUrl = root.dataset.loginUrl;
    const csrfToken = root.dataset.csrf;

    const form = document.getElementById('supplierFiltersForm');
    const filtersTitle = document.getElementById('supplierFiltersTitle');
    const monthInput = document.getElementById('supplierMonth');
    const yearInput = document.getElementById('supplierYear');
    const supplierSelect = document.getElementById('supplierSelect');
    const supplierSelectShell = supplierSelect.closest('.supplier-select-shell');
    const viewAllRadio = document.getElementById('supplierViewAll');
    const viewSpecificRadio = document.getElementById('supplierViewSpecific');
    const consultButton = document.getElementById('supplierConsultButton');
    const clearButton = document.getElementById('supplierClearButton');
    const alertBox = document.getElementById('supplierAlert');
    const jobNotice = document.getElementById('supplierJobNotice');
    const tableBody = document.getElementById('supplierStudyTableBody');
    const tableDescription = document.getElementById('supplierTableDescription');
    const resultCounter = document.getElementById('supplierResultCounter');
    const updatedAt = document.getElementById('supplierUpdatedAt');
    const tableMeta = document.getElementById('supplierTableMeta');
    const reportTabs = document.getElementById('supplierReportTabs');
    const studyPagination = document.getElementById('supplierStudyPagination');
    const studyPrevPage = document.getElementById('supplierStudyPrevPage');
    const studyNextPage = document.getElementById('supplierStudyNextPage');
    const studyPageLabel = document.getElementById('supplierStudyPageLabel');

    const metricTotal = document.getElementById('supplierMetricTotal');
    const metricTotalLabel = document.getElementById('supplierMetricTotalLabel');
    const metricTotalNote = document.getElementById('supplierMetricTotalNote');
    const metricAssigned = document.getElementById('supplierMetricAssigned');
    const metricAssignedLabel = document.getElementById('supplierMetricAssignedLabel');
    const metricAssignedNote = document.getElementById('supplierMetricAssignedNote');
    const metricOccupied = document.getElementById('supplierMetricOccupied');
    const metricOccupiedLabel = document.getElementById('supplierMetricOccupiedLabel');
    const metricOccupiedNote = document.getElementById('supplierMetricOccupiedNote');
    const metricValidatedLater = document.getElementById('supplierMetricValidatedLater');
    const metricValidatedLaterLabel = document.getElementById('supplierMetricValidatedLaterLabel');
    const metricValidatedLaterNote = document.getElementById('supplierMetricValidatedLaterNote');
    const metricHistoricalPending = document.getElementById('supplierMetricHistoricalPending');
    const metricHistoricalPendingNote = document.getElementById('supplierMetricHistoricalPendingNote');
    const historicalPendingButton = document.getElementById('supplierHistoricalPendingButton');
    const historicalPendingModal = document.getElementById('supplierHistoricalPendingModal');
    const historicalPendingDescription = document.getElementById('supplierHistoricalPendingDescription');
    const historicalPendingContent = document.getElementById('supplierHistoricalPendingContent');
    const historicalPendingCount = document.getElementById('supplierHistoricalPendingCount');
    const historicalPendingMore = document.getElementById('supplierHistoricalPendingMore');

    const exportButton = document.getElementById('supplierExportButton');

    const manageSuppliersButton = document.getElementById('manageSuppliersButton');
    const managementModal = document.getElementById('supplierManagementModal');
    const managementTabs = Array.from(document.querySelectorAll('[data-management-tab]'));
    const managementPanels = Array.from(document.querySelectorAll('[data-management-panel]'));

    const providerForm = document.getElementById('supplierProviderForm');
    const providerIdInput = document.getElementById('supplierProviderId');
    const providerNameInput = document.getElementById('supplierProviderName');
    const providerStatusSelect = document.getElementById('supplierProviderStatus');
    const providerSaveButton = document.getElementById('supplierProviderSaveButton');
    const providerCancelEdit = document.getElementById('supplierProviderCancelEdit');
    const providerTableBody = document.getElementById('supplierProviderTableBody');

    const catalogSupplierSelect = document.getElementById('supplierCatalogSupplierSelect');
    const catalogSearchForm = document.getElementById('supplierCatalogSearchForm');
    const catalogSearchInput = document.getElementById('supplierCatalogSearchInput');
    const catalogClearSearch = document.getElementById('supplierCatalogClearSearch');
    const catalogTableBody = document.getElementById('supplierCatalogTableBody');
    const catalogStatus = document.getElementById('supplierCatalogStatus');
    const catalogPageLabel = document.getElementById('supplierCatalogPageLabel');
    const catalogPrevButton = document.getElementById('supplierCatalogPrevButton');
    const catalogNextButton = document.getElementById('supplierCatalogNextButton');

    const assignModal = document.getElementById('supplierAssignModal');
    const assignModalTitle = document.getElementById('supplierAssignModalTitle');
    const assignModalText = document.getElementById('supplierAssignModalText');
    const assignSummary = document.getElementById('supplierAssignSummary');
    const confirmAssignButton = document.getElementById('supplierConfirmAssignButton');
    const toast = document.getElementById('supplierToast');

    const state = {
        suppliers: Array.isArray(window.supplierStudyInitialSuppliers)
            ? window.supplierStudyInitialSuppliers
            : [],
        availablePeriods: Array.isArray(window.supplierStudyAvailablePeriods)
            ? window.supplierStudyAvailablePeriods
            : [],
        reportRows: [],
        bySupplier: [],
        monthlyDataLoaded: false,
        reportSupplierTab: 'all',
        reportPage: 1,
        reportPageSize: 20,
        assignmentMap: new Map(),
        assignmentsLoaded: false,
        assignmentsLoading: false,
        catalog: {
            page: 0,
            pageSize: 50,
            hasMore: false,
            rows: [],
            search: '',
            mode: 'assigned',
            loading: false,
        },
        catalogCodeMap: new Map(),
        catalogPageCache: new Map(),
        knownStudies: new Map(),
        pendingAssignment: null,
        loading: false,
        summary: {
            totalStudies: null,
            properlyValidated: null,
            unProperlyValidated: null,
            currentPeriod_stillPending: null,
            lastPeriods_stillPending: null,
        },
        job: null,
        lastUpdatedAt: null,
        toastTimer: null,
        historicalPending: {
            year: null,
            monthNumber: null,
            supplierID: null,
            records: [],
            visibleCount: 50,
            pageSize: 50,
            openPeriod: null,
            hasMore: false,
            loading: false,
            error: '',
        },
    };

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeXml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;');
    }

    function number(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('es-MX').format(number(value));
    }

    function formatDate(value) {
        if (!value) return '—';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return String(value);
        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(date);
    }

    function formatDateOnly(value) {
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return formatDate(value);
        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit', month: '2-digit', year: 'numeric',
        }).format(new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])));
    }

    function formatDateTime(value) {
        if (!value) return '—';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return String(value);
        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: false,
        }).format(date);
    }

    function monthNames() {
        return ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    }

    function normalizedAvailablePeriods() {
        return state.availablePeriods
            .filter(period => period && Number.isFinite(Number(period.year)) && Array.isArray(period.months))
            .map(period => ({
                year: Number(period.year),
                months: Array.from(new Set(period.months.map(Number).filter(month => month >= 1 && month <= 12))).sort((a, b) => a - b),
            }))
            .filter(period => period.months.length > 0)
            .sort((a, b) => a.year - b.year);
    }

    function monthsForYear(year) {
        const match = normalizedAvailablePeriods().find(period => period.year === Number(year));
        return match ? match.months : [];
    }

    function latestAvailablePeriod() {
        const periods = normalizedAvailablePeriods();
        if (!periods.length) return null;
        const latest = periods[periods.length - 1];
        return {
            year: latest.year,
            month: latest.months[latest.months.length - 1],
        };
    }

    function renderMonthOptions(preferredMonth) {
        const year = Number(yearInput.value);
        const months = monthsForYear(year);
        const preferred = Number(preferredMonth);
        const selectedMonth = months.includes(preferred)
            ? preferred
            : (months.length ? months[months.length - 1] : null);

        if (!months.length) {
            monthInput.innerHTML = '<option value="">Sin meses disponibles</option>';
            monthInput.disabled = true;
            consultButton.disabled = true;
            updateFiltersTitle();
            return;
        }

        monthInput.innerHTML = months.map(month =>
            '<option value="' + month + '">' + escapeHtml(monthNames()[month - 1] || String(month)) + '</option>'
        ).join('');
        monthInput.value = String(selectedMonth);
        monthInput.disabled = false;
        if (!state.loading) consultButton.disabled = false;
        updateFiltersTitle();
    }

    function resetToLatestAvailablePeriod() {
        const latest = latestAvailablePeriod();
        if (!latest) {
            yearInput.disabled = true;
            monthInput.disabled = true;
            consultButton.disabled = true;
            return false;
        }

        yearInput.value = String(latest.year);
        renderMonthOptions(latest.month);
        return true;
    }

    function currentPeriod() {
        const month = number(monthInput && monthInput.value ? monthInput.value : 0);
        const year = number(yearInput && yearInput.value ? yearInput.value : 0);
        return { month, year };
    }

    function periodLabel() {
        const period = currentPeriod();
        return monthNames()[period.month - 1] + ' ' + period.year;
    }

    function periodFileSuffix() {
        const period = currentPeriod();
        return period.year + '_' + String(period.month).padStart(2, '0');
    }

    function updateFiltersTitle() {
        if (!filtersTitle) return;
        const period = currentPeriod();
        const monthName = monthNames()[period.month - 1];
        filtersTitle.textContent = monthName
            ? 'Filtros de consulta mes ' + monthName
            : 'Filtros de consulta';
    }

    function firstNumber(source, keys) {
        if (!source || typeof source !== 'object') return null;
        for (const key of keys) {
            const value = source[key];
            if (Number.isFinite(Number(value))) return Math.max(0, Number(value));
        }
        return null;
    }

    function summaryFromRows(rows) {
        return (Array.isArray(rows) ? rows : []).reduce((summary, row) => {
            const properlyValidated = number(row.properlyValidated != null ? row.properlyValidated : row.validatedInMonthCount);
            const unProperlyValidated = number(row.unProperlyValidated != null ? row.unProperlyValidated : row.validatedLaterCount);
            const currentPending = number(row.currentPeriod_stillPending != null ? row.currentPeriod_stillPending : (row.pendingValidationCount != null ? row.pendingValidationCount : row.pendingCount));
            const rowTotal = row.total != null ? number(row.total) : (row.allStudies != null ? number(row.allStudies) : number(row.createdCount || row.quantity));

            summary.totalStudies += rowTotal || properlyValidated + unProperlyValidated + currentPending;
            summary.properlyValidated += properlyValidated;
            summary.unProperlyValidated += unProperlyValidated;
            summary.currentPeriod_stillPending += currentPending;
            summary.lastPeriods_stillPending += number(row.lastPeriods_stillPending);
            return summary;
        }, {
            totalStudies: 0,
            properlyValidated: 0,
            unProperlyValidated: 0,
            currentPeriod_stillPending: 0,
            lastPeriods_stillPending: 0,
        });
    }

    function normalizeSummary(source, fallbackRows) {
        const fallback = summaryFromRows(fallbackRows);
        const raw = source && typeof source === 'object' ? source : {};

        const totalStudies = firstNumber(raw, ['allStudies', 'totalStudies', 'total', 'monthlyTotal']);
        const properlyValidated = firstNumber(raw, ['properlyValidated', 'performedStudies', 'validatedInMonthCount']);
        const unProperlyValidated = firstNumber(raw, ['unProperlyValidated', 'validatedLaterStudies', 'validatedLaterCount']);
        const currentPending = firstNumber(raw, ['currentPeriod_stillPending', 'availableStudies', 'pendingStudies', 'pendingCount']);
        const historicalPending = firstNumber(raw, ['lastPeriods_stillPending']);

        const normalizedTotal = totalStudies == null ? fallback.totalStudies : totalStudies;
        const normalizedProperlyValidated = properlyValidated == null ? fallback.properlyValidated : properlyValidated;
        const normalizedUnProperlyValidated = unProperlyValidated == null ? fallback.unProperlyValidated : unProperlyValidated;
        const normalizedCurrentPending = currentPending == null ? fallback.currentPeriod_stillPending : currentPending;
        const normalizedHistoricalPending = historicalPending == null ? fallback.lastPeriods_stillPending : historicalPending;

        return {
            totalStudies: normalizedTotal,
            properlyValidated: normalizedProperlyValidated,
            unProperlyValidated: normalizedUnProperlyValidated,
            currentPeriod_stillPending: normalizedCurrentPending,
            lastPeriods_stillPending: normalizedHistoricalPending,
            // Alias internos para otras partes del tablero que aún los consultan.
            performedStudies: normalizedProperlyValidated,
            validatedLaterStudies: normalizedUnProperlyValidated,
            availableStudies: normalizedCurrentPending,
        };
    }

    function getSupplier(id) {
        const supplier = state.suppliers.find(item => String(item.id) === String(id));
        if (supplier) return supplier;
        const snapshot = state.bySupplier.find(item => String(item.supplierID) === String(id));
        return snapshot ? { id: String(snapshot.supplierID), name: snapshot.supplierName || String(snapshot.supplierID), status: 'active' } : null;
    }

    function selectedSupplier() {
        return supplierSelect.value ? getSupplier(supplierSelect.value) : null;
    }

    /** Devuelve el proveedor que limita el detalle histórico, si hay uno seleccionado. */
    function historicalPendingSupplierID() {
        if (!isAllMode()) {
            const supplier = selectedSupplier();
            if (supplier) return String(supplier.id);
        }

        return state.reportSupplierTab !== 'all' ? String(state.reportSupplierTab) : null;
    }

    function selectedCatalogSupplier() {
        return catalogSupplierSelect.value ? getSupplier(catalogSupplierSelect.value) : null;
    }

    function isAllMode() {
        return Boolean(viewAllRadio && viewAllRadio.checked);
    }

    function setAlert(message) {
        alertBox.textContent = message || '';
        alertBox.hidden = !message;
    }

    function renderJobNotice(data) {
        if (!jobNotice) return;

        const job = data && data.job && typeof data.job === 'object' ? data.job : null;
        const backendAlert = data && typeof data.alert === 'string' ? data.alert.trim() : '';
        const status = job && job.status ? String(job.status).toLowerCase() : '';

        jobNotice.className = 'supplier-job-notice';
        jobNotice.hidden = true;
        jobNotice.textContent = '';

        if (!job) {
            jobNotice.textContent = 'Este mes todavía no ha sido procesado por el backend.';
            jobNotice.classList.add('is-info');
            jobNotice.hidden = false;
            return;
        }

        if (status === 'failed') {
            jobNotice.textContent = backendAlert || 'El proceso mensual falló. Revisa el job antes de utilizar estos datos.';
            jobNotice.classList.add('is-error');
            jobNotice.hidden = false;
            return;
        }

        if (status === 'scheduled') {
            jobNotice.textContent = 'El proceso mensual está programado y todavía no comienza.';
            jobNotice.classList.add('is-info');
            jobNotice.hidden = false;
            return;
        }

        if (status === 'running') {
            const attempts = number(job.attempts);
            const maxAttempts = number(job.maxAttempts);
            jobNotice.textContent = 'El proceso mensual está en ejecución' +
                (maxAttempts ? ' · intento ' + attempts + ' de ' + maxAttempts : '') + '.';
            jobNotice.classList.add('is-info');
            jobNotice.hidden = false;
        }
    }

    function showToast(message, isError) {
        clearTimeout(state.toastTimer);
        toast.textContent = message || '';
        toast.classList.toggle('is-error', Boolean(isError));
        toast.classList.add('is-visible');
        state.toastTimer = setTimeout(() => {
            toast.classList.remove('is-visible');
        }, 3600);
    }

    function setLoading(loading) {
        state.loading = Boolean(loading);
        consultButton.disabled = state.loading || !latestAvailablePeriod();
        consultButton.querySelector('span').textContent = state.loading ? 'Consultando...' : 'Consultar';
    }

    function currentOwner(clinicalStudyID) {
        return state.assignmentMap.get(String(clinicalStudyID)) || null;
    }

    function studyCode(row) {
        const id = String(row && row.clinicalStudyID != null ? row.clinicalStudyID : '');
        const cached = state.catalogCodeMap.get(id) || {};
        const known = state.knownStudies.get(id) || {};
        const assignment = state.assignmentMap.get(id) || {};

        // clinicalStudyNumber es el código visible del examen.
        // clinicalStudyID se conserva únicamente para operaciones internas
        // de asignación/desasignación y nunca se pinta como código al usuario.
        const code = String(
            (row && row.clinicalStudyNumber) ||
            cached.clinicalStudyNumber ||
            known.clinicalStudyNumber ||
            assignment.clinicalStudyNumber ||
            ''
        ).trim();

        return code || '—';
    }

    const knownStudiesStorageKey = 'vcare_supplier_known_studies_v1';

    function normalizeKnownStudy(study) {
        if (!study || study.clinicalStudyID == null) return null;

        const id = String(study.clinicalStudyID);
        const cached = state.catalogCodeMap.get(id) || {};
        return {
            clinicalStudyID: number(study.clinicalStudyID),
            clinicalStudyNumber: String(study.clinicalStudyNumber || cached.clinicalStudyNumber || '').trim(),
            clinicalStudyAbbrev: String(study.clinicalStudyAbbrev || cached.clinicalStudyAbbrev || '').trim(),
            clinicalStudyName: String(study.clinicalStudyName || cached.clinicalStudyName || ('Estudio ' + id)).trim(),
        };
    }

    function persistKnownStudies() {
        try {
            const rows = Array.from(state.knownStudies.values());
            localStorage.setItem(knownStudiesStorageKey, JSON.stringify(rows));
        } catch (_) {
            // La persistencia local es auxiliar; la asignación real siempre vive en backend.
        }
    }

    function rememberStudy(study, persist = true) {
        const normalized = normalizeKnownStudy(study);
        if (!normalized) return;

        const id = String(normalized.clinicalStudyID);
        const previous = state.knownStudies.get(id) || {};
        state.knownStudies.set(id, {
            ...previous,
            ...normalized,
            clinicalStudyNumber: normalized.clinicalStudyNumber || previous.clinicalStudyNumber || '',
            clinicalStudyAbbrev: normalized.clinicalStudyAbbrev || previous.clinicalStudyAbbrev || '',
            clinicalStudyName: normalized.clinicalStudyName || previous.clinicalStudyName || ('Estudio ' + id),
        });

        if (persist) persistKnownStudies();
    }

    function rememberStudies(rows, persist = true) {
        (Array.isArray(rows) ? rows : []).forEach(row => rememberStudy(row, false));
        if (persist) persistKnownStudies();
    }

    function restoreKnownStudies() {
        try {
            const raw = localStorage.getItem(knownStudiesStorageKey);
            const rows = raw ? JSON.parse(raw) : [];
            rememberStudies(Array.isArray(rows) ? rows : [], false);
        } catch (_) {
            // Si el navegador no permite localStorage simplemente seguimos con los datos del API.
        }
    }

    function knownCatalogRows() {
        return Array.from(state.knownStudies.values()).sort((a, b) => {
            const codeA = String(a.clinicalStudyNumber || '');
            const codeB = String(b.clinicalStudyNumber || '');
            if (codeA && codeB) return codeA.localeCompare(codeB, 'es', { numeric: true });
            return String(a.clinicalStudyName || '').localeCompare(String(b.clinicalStudyName || ''), 'es');
        });
    }

    function applyViewStyles() {
        document.querySelectorAll('.supplier-view-card').forEach(card => {
            const input = card.querySelector('input[type="radio"]');
            card.classList.toggle('is-active', Boolean(input && input.checked));
        });
    }

    function syncExclusiveFilters(source) {
        if (source === 'supplier' && supplierSelect.value) {
            viewSpecificRadio.checked = true;
        }

        if (source === 'view' && isAllMode()) {
            supplierSelect.value = '';
        }

        if (isAllMode()) {
            supplierSelect.disabled = true;
            supplierSelectShell.classList.add('is-disabled');
        } else {
            supplierSelect.disabled = false;
            supplierSelectShell.classList.remove('is-disabled');
        }

        applyViewStyles();
        updateTableContext();
        renderStudies();
    }

    function updateTableContext() {
        if (state.reportSupplierTab !== 'all') {
            const supplier = getSupplier(state.reportSupplierTab);
            tableDescription.textContent = supplier
                ? 'Estudios cuyo proveedor registrado en el corte mensual es ' + supplier.name + '.'
                : 'Estudios del proveedor seleccionado para el mes consultado.';
            return;
        }

        if (isAllMode()) {
            tableDescription.textContent = 'Detalle del mes seleccionado, con el proveedor registrado al momento de generar el corte mensual.';
            return;
        }

        const supplier = selectedSupplier();
        if (supplier) {
            tableDescription.textContent = 'Proveedor: ' + supplier.name + '. El resumen usa los datos guardados en el corte mensual.';
        } else {
            tableDescription.textContent = 'Selecciona un proveedor para consultar sus estudios y administrar asignaciones del mes seleccionado.';
        }
    }

    function filteredReportRows() {
        const rows = Array.isArray(state.reportRows) ? state.reportRows : [];
        if (state.reportSupplierTab === 'all') return rows;

        return rows.filter(row => {
            if (row.supplierID != null && row.supplierID !== '') {
                return String(row.supplierID) === String(state.reportSupplierTab);
            }
            const owner = currentOwner(row.clinicalStudyID);
            return owner && String(owner.supplierID) === String(state.reportSupplierTab);
        });
    }

    function visibleReportRows() {
        const rows = filteredReportRows();
        const start = (state.reportPage - 1) * state.reportPageSize;
        return rows.slice(start, start + state.reportPageSize);
    }

    function renderReportSupplierTabs() {
        if (!reportTabs) return;
        const monthlySuppliers = state.bySupplier.length
            ? state.bySupplier.map(item => ({ id: String(item.supplierID), name: item.supplierName || String(item.supplierID) }))
            : activeSupplierOptions();
        if (state.reportSupplierTab !== 'all' && !monthlySuppliers.some(item => String(item.id) === String(state.reportSupplierTab))) {
            state.reportSupplierTab = 'all';
        }

        const tabs = [{ id: 'all', label: 'Todos' }].concat(
            monthlySuppliers.map(item => ({ id: String(item.id), label: item.name }))
        );
        reportTabs.innerHTML = tabs.map(item => {
            const selected = String(state.reportSupplierTab) === String(item.id);
            return '<button type="button" class="supplier-report-tab' + (selected ? ' is-active' : '') + '" role="tab" aria-selected="' + (selected ? 'true' : 'false') + '" data-report-provider="' + escapeHtml(item.id) + '">' + escapeHtml(item.label) + '</button>';
        }).join('');
    }

    function assignmentText(row) {
        if (!state.assignmentsLoaded) return 'Asignación actual no disponible';
        const owner = currentOwner(row.clinicalStudyID);
        return owner ? 'Asignado a ' + owner.supplierName : 'Sin proveedor asignado';
    }

    function renderAssignmentCell(row) {
        if (!state.assignmentsLoaded) {
            return '<span class="supplier-assignment-free">Cargando asignación…</span>';
        }

        const owner = currentOwner(row.clinicalStudyID);
        const supplier = selectedSupplier();

        if (isAllMode() || !supplier) {
            if (!owner) {
                return '<span class="supplier-assignment-free" title="Sin proveedor asignado">Sin asignar</span>';
            }

            return '<div class="supplier-catalog-assignment-state">' +
                '<span class="supplier-readonly-lock" title="Asignado a ' + escapeHtml(owner.supplierName) + '">🔒</span>' +
                '<small title="' + escapeHtml(owner.supplierName) + '">' + escapeHtml(owner.supplierName) + '</small>' +
                '</div>';
        }

        if (!owner) {
            return '<label class="supplier-check-wrap" title="Asignar a ' + escapeHtml(supplier.name) + '">' +
                '<input class="supplier-study-check" type="checkbox" data-report-assign="' + escapeHtml(row.clinicalStudyID) + '">' +
                '</label>';
        }

        if (String(owner.supplierID) === String(supplier.id)) {
            return '<label class="supplier-check-wrap" title="Asignado a ' + escapeHtml(supplier.name) + '">' +
                '<input class="supplier-study-check" type="checkbox" checked disabled>' +
                '</label>';
        }

        return '<div class="supplier-catalog-assignment-state">' +
            '<button type="button" class="supplier-lock-button" data-report-reassign="' + escapeHtml(row.clinicalStudyID) + '" title="Asignado a ' +
            escapeHtml(owner.supplierName + '. Clic para reasignar a ' + supplier.name) + '">🔒</button>' +
            '<small title="' + escapeHtml(owner.supplierName) + '">' + escapeHtml(owner.supplierName) + '</small>' +
            '</div>';
    }

    function updateSummaryCards(rows) {
        const detailRows = Array.isArray(state.reportRows) ? state.reportRows : [];
        const selected = !isAllMode() ? selectedSupplier() : null;
        let summary = normalizeSummary(state.summary, detailRows);
        if (selected) {
            const supplierSummary = state.bySupplier.find(item => String(item.supplierID) === String(selected.id));
            const supplierRows = detailRows.filter(row => String(row.supplierID || '') === String(selected.id));
            summary = normalizeSummary(supplierSummary || {}, supplierRows);
        }

        const selectedMonth = monthNames()[currentPeriod().month - 1] || 'mes';
        metricTotalLabel.textContent = 'Total de estudios / ' + selectedMonth;
        metricTotal.textContent = formatNumber(summary.totalStudies);
        metricTotalNote.textContent = 'Total de estudios registrados en ' + selectedMonth.toLowerCase() + ' ' + currentPeriod().year + '.';

        metricAssignedLabel.textContent = 'Validados a tiempo';
        metricAssigned.textContent = formatNumber(summary.properlyValidated);
        metricAssignedNote.textContent = 'Creados y validados dentro del mismo mes.';

        metricValidatedLaterLabel.textContent = 'Validados tarde';
        metricValidatedLater.textContent = formatNumber(summary.unProperlyValidated);
        metricValidatedLaterNote.textContent = 'Creados este mes y validados después.';

        metricOccupiedLabel.textContent = 'Pendientes del mes';
        metricOccupied.textContent = formatNumber(summary.currentPeriod_stillPending);
        metricOccupiedNote.textContent = 'Creados este mes y todavía sin validar.';
        metricHistoricalPending.textContent = formatNumber(summary.lastPeriods_stillPending);
        metricHistoricalPendingNote.textContent = 'Pendientes acumulados de meses y años anteriores.';
    }

    function renderTableLoading() {
        tableBody.innerHTML = '<tr class="supplier-empty-row supplier-table-loading-row">' +
            '<td class="supplier-table-loading-cell" colspan="4" role="status" aria-live="polite">' +
                '<span class="supplier-table-loading">' +
                    '<span class="supplier-table-loading-spinner" aria-hidden="true"></span>' +
                    '<span>Consultando los estudios del periodo seleccionado…</span>' +
                '</span>' +
            '</td>' +
        '</tr>';
        if (resultCounter) resultCounter.textContent = 'Cargando…';
        if (tableMeta) tableMeta.textContent = 'Consultando información del periodo';
        if (studyPagination) studyPagination.hidden = true;
    }

    function renderStudies() {
        if (state.loading) {
            renderTableLoading();
            return;
        }

        const allRows = filteredReportRows();
        const pageCount = Math.max(1, Math.ceil(allRows.length / state.reportPageSize));
        state.reportPage = Math.min(Math.max(1, state.reportPage), pageCount);
        const rows = visibleReportRows();
        const firstRow = allRows.length ? (state.reportPage - 1) * state.reportPageSize + 1 : 0;
        const lastRow = Math.min(state.reportPage * state.reportPageSize, allRows.length);

        if (resultCounter) {
            resultCounter.textContent = formatNumber(allRows.length) + (allRows.length === 1 ? ' estudio' : ' estudios');
        }

        updateSummaryCards(allRows);
        if (tableMeta) {
            tableMeta.textContent = allRows.length
                ? 'Mostrando ' + formatNumber(firstRow) + '–' + formatNumber(lastRow) + ' de ' + formatNumber(allRows.length) + ' estudios'
                : 'Mostrando 0 de 0 estudios';
        }
        if (studyPagination) {
            studyPagination.hidden = allRows.length <= state.reportPageSize;
            studyPrevPage.disabled = state.reportPage <= 1;
            studyNextPage.disabled = state.reportPage >= pageCount;
            studyPageLabel.textContent = 'Página ' + state.reportPage + ' de ' + pageCount;
        }

        if (!allRows.length) {
            tableBody.innerHTML = '<tr class="supplier-empty-row"><td colspan="4">No hay estudios para los filtros seleccionados.</td></tr>';
            return;
        }

        tableBody.innerHTML = rows.map(row => {
            return '<tr>' +
                '<td><span class="supplier-study-code">' + escapeHtml(studyCode(row)) + '</span></td>' +
                '<td><span class="supplier-study-name"><strong>' + escapeHtml(row.clinicalStudyName) + '</strong></span></td>' +
                '<td><span class="supplier-quantity">' + formatNumber(row.quantity) + '</span></td>' +
                '<td class="supplier-assignment-cell">' + renderAssignmentCell(row) + '</td>' +
                '</tr>';
        }).join('');
    }

    async function fetchJson(url, options) {
        const response = await fetch(url, options || {});
        const payload = await response.json().catch(() => ({}));

        if (payload.sessionExpired) {
            window.location.href = payload.redirect || loginUrl;
            throw new Error('Sesión expirada.');
        }

        if (!response.ok || payload.ok === false) {
            throw new Error(payload.message || ('Error HTTP ' + response.status));
        }

        return payload;
    }

    function buildGetOptions() {
        return {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            cache: 'no-store',
        };
    }

    async function loadAssignments(force) {
        if (state.assignmentsLoaded && !force) return;
        if (state.assignmentsLoading) return;

        state.assignmentsLoading = true;

        try {
            const payload = await fetchJson(assignmentsUrl, buildGetOptions());
            const rows = Array.isArray(payload.data) ? payload.data : [];
            const nextMap = new Map();

            rows.forEach(item => {
                if (item && item.clinicalStudyID != null && item.supplierID) {
                    const id = String(item.clinicalStudyID);
                    nextMap.set(id, item);

                    // Conserva también el número clínico devuelto por
                    // GET /suppliers/{supplierID}/studies para usarlo en pantalla.
                    if (item.clinicalStudyNumber) {
                        const previous = state.catalogCodeMap.get(id) || {};
                        state.catalogCodeMap.set(id, {
                            ...previous,
                            ...item,
                            clinicalStudyNumber: String(item.clinicalStudyNumber).trim(),
                        });
                    }

                    rememberStudy(item, false);
                }
            });

            persistKnownStudies();
            state.assignmentMap = nextMap;
            state.assignmentsLoaded = true;
            renderStudies();
            renderCatalog();
        } finally {
            state.assignmentsLoading = false;
        }
    }

    async function loadData() {
        if (state.loading) return;

        const period = currentPeriod();

        if (!period.month || !period.year || !monthsForYear(period.year).includes(period.month)) {
            setAlert('Selecciona uno de los periodos disponibles reportados por el backend.');
            return;
        }

        if (!isAllMode() && !supplierSelect.value) {
            setAlert('Selecciona un proveedor o activa la vista de Todos los proveedores.');
            return;
        }

        setAlert('');
        state.reportPage = 1;
        state.monthlyDataLoaded = false;
        if (window.VCareLoading && typeof window.VCareLoading.show === 'function') {
            window.VCareLoading.show(
                'Actualizando Auditoría exámenes por proveedores',
                'Estamos consultando el periodo seleccionado y preparando el detalle. Esto puede tomar unos segundos.'
            );
        }
        setLoading(true);
        renderTableLoading();

        try {
            const url = new URL(dataUrl, window.location.origin);
            url.searchParams.set('month', String(period.month));
            url.searchParams.set('year', String(period.year));
            if (!isAllMode() && supplierSelect.value) {
                url.searchParams.set('supplierID', supplierSelect.value);
            }

            const assignmentsPromise = loadAssignments(false).catch(error => {
                state.assignmentsLoaded = false;
                showToast('No se pudo cargar la asignación actual de los exámenes: ' + error.message, true);
            });

            const payload = await fetchJson(url.toString(), buildGetOptions());
            await assignmentsPromise;

            state.monthlyDataLoaded = true;
            state.reportRows = Array.isArray(payload.data && payload.data.rows)
                ? payload.data.rows
                : [];
            state.bySupplier = Array.isArray(payload.data && payload.data.bySupplier)
                ? payload.data.bySupplier
                : [];
            renderReportSupplierTabs();
            rememberStudies(state.reportRows);
            state.summary = normalizeSummary(
                (payload.data && (payload.data.totals || payload.data.summary)) || payload.totals || payload.summary || null,
                state.reportRows
            );
            state.job = payload.data && payload.data.job ? payload.data.job : null;
            state.lastUpdatedAt = payload.data && payload.data.lastUpdatedAt ? payload.data.lastUpdatedAt : null;

            renderJobNotice(payload.data || {});
            updatedAt.textContent = payload.updated_at
                ? 'Actualizado: ' + payload.updated_at
                : 'Consulta completada';

            renderStudies();
            updateTableContext();

        } catch (error) {
            state.monthlyDataLoaded = false;
            state.reportRows = [];
            state.bySupplier = [];
            renderReportSupplierTabs();
            state.summary = normalizeSummary(null, []);
            state.job = null;
            state.lastUpdatedAt = null;
            if (jobNotice) jobNotice.hidden = true;
            renderStudies();
            setAlert(error.message || 'No se pudo cargar la información.');
        } finally {
            setLoading(false);
            renderStudies();
            if (window.VCareLoading && typeof window.VCareLoading.hide === 'function') {
                window.VCareLoading.hide();
            }
        }
    }

    function cacheCatalogRows(rows) {
        (Array.isArray(rows) ? rows : []).forEach(row => {
            if (row && row.clinicalStudyID != null) {
                state.catalogCodeMap.set(String(row.clinicalStudyID), row);
                rememberStudy(row, false);
            }
        });
        persistKnownStudies();
    }

    async function fetchCatalogPage(page, search) {
        const normalizedSearch = String(search || '').trim();
        const cacheKey = normalizedSearch === '' ? String(page) : null;

        if (cacheKey !== null && state.catalogPageCache.has(cacheKey)) {
            return state.catalogPageCache.get(cacheKey);
        }

        const url = new URL(catalogUrl, window.location.origin);
        url.searchParams.set('page', String(page));
        if (normalizedSearch !== '') {
            url.searchParams.set('clinicalStudyNumber', normalizedSearch);
        }

        const payload = await fetchJson(url.toString(), buildGetOptions());
        const data = payload.data || {};
        const normalized = {
            page: number(data.page),
            pageSize: number(data.pageSize) || 50,
            hasMore: Boolean(data.hasMore),
            rows: Array.isArray(data.data) ? data.data : [],
        };

        cacheCatalogRows(normalized.rows);
        if (cacheKey !== null) state.catalogPageCache.set(cacheKey, normalized);
        return normalized;
    }

    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    /** Presenta el histórico pendiente por mes; el botón solo pagina el render local. */
    function renderHistoricalPending() {
        const history = state.historicalPending;
        const visibleRecords = history.records.slice(0, history.visibleCount);
        const groups = new Map();
        const totalsByPeriod = new Map();

        history.records.forEach(item => {
            const period = String(item.orderCreatedDate || '').slice(0, 7) || 'sin-fecha';
            totalsByPeriod.set(period, (totalsByPeriod.get(period) || 0) + 1);
        });
        visibleRecords.forEach(item => {
            const period = String(item.orderCreatedDate || '').slice(0, 7) || 'sin-fecha';
            if (!groups.has(period)) groups.set(period, []);
            groups.get(period).push(item);
        });

        if (history.loading) {
            historicalPendingContent.innerHTML = '<div class="supplier-later-loading" role="status"><span class="supplier-table-loading-spinner" aria-hidden="true"></span><span>Consultando los pendientes de meses anteriores…</span></div>';
        } else if (history.error) {
            historicalPendingContent.innerHTML = '<div class="supplier-later-error">' + escapeHtml(history.error) + '</div>';
        } else if (!state.monthlyDataLoaded) {
            historicalPendingContent.innerHTML = '<div class="supplier-later-empty">Consulta primero un mes para cargar el detalle de pendientes históricos.</div>';
        } else if (!history.records.length) {
            historicalPendingContent.innerHTML = '<div class="supplier-later-empty">No hay órdenes pendientes de meses anteriores para este periodo y proveedor.</div>';
        } else {
            historicalPendingContent.innerHTML = Array.from(groups.entries()).map(([period, rows]) => {
                const expanded = history.openPeriod === period;
                const [year, month] = period.split('-');
                const monthLabel = period === 'sin-fecha' ? 'Fecha no disponible' : ((monthNames()[Number(month) - 1] || month) + ' ' + year);
                return '<section class="supplier-later-month' + (expanded ? ' is-expanded' : '') + '">' +
                    '<button type="button" class="supplier-later-month-toggle" data-historical-period="' + escapeHtml(period) + '" aria-expanded="' + (expanded ? 'true' : 'false') + '">' +
                        '<span class="supplier-later-chevron" aria-hidden="true">⌄</span>' +
                        '<span class="supplier-later-month-title"><strong>' + escapeHtml(monthLabel) + '</strong><small>' + formatNumber(totalsByPeriod.get(period)) + (totalsByPeriod.get(period) === 1 ? ' orden-estudio pendiente' : ' órdenes-estudio pendientes') + '</small></span>' +
                        '<span class="supplier-later-period">' + escapeHtml(period) + '</span>' +
                    '</button>' +
                    '<div class="supplier-later-month-body"><div class="supplier-later-study-list">' +
                        rows.map(item => '<article class="supplier-later-study historical-pending-study">' +
                            '<div class="supplier-later-study-name"><strong>' + escapeHtml(item.clinicalStudyName || 'Estudio sin nombre') + '</strong><span>Orden ' + escapeHtml(item.orderNumber || '—') + '</span></div>' +
                            '<div class="supplier-later-study-date"><small>Creada</small><strong>' + escapeHtml(formatDateOnly(item.orderCreatedDate)) + '</strong></div>' +
                            '<div class="supplier-later-study-date"><small>Estado</small><strong>Pendiente de validar</strong></div>' +
                            '<div class="supplier-later-study-supplier"><small>Proveedor</small><strong>' + escapeHtml(item.supplierName || ((getSupplier(item.supplierID) || {}).name) || 'No disponible') + '</strong></div>' +
                        '</article>').join('') +
                    '</div></div>' +
                '</section>';
            }).join('');
        }

        const shown = Math.min(history.visibleCount, history.records.length);
        historicalPendingCount.textContent = history.loading ? 'Cargando pendientes…' : 'Mostrando ' + formatNumber(shown) + ' de ' + formatNumber(history.records.length) + ' órdenes-estudio pendientes';
        history.hasMore = shown < history.records.length;
        historicalPendingMore.hidden = history.loading || Boolean(history.error) || !history.hasMore;
        historicalPendingMore.disabled = history.loading;
        historicalPendingMore.textContent = 'Cargar más';
    }

    async function openHistoricalPending() {
        const history = state.historicalPending;
        const period = currentPeriod();
        history.year = period.year;
        history.monthNumber = period.month;
        history.supplierID = historicalPendingSupplierID();
        history.records = [];
        history.visibleCount = history.pageSize;
        history.openPeriod = null;
        history.error = '';

        const monthLabel = monthNames()[Number(history.monthNumber) - 1] || 'mes seleccionado';
        const supplierName = history.supplierID ? ((getSupplier(history.supplierID) || {}).name || history.supplierID) : null;
        historicalPendingDescription.textContent = 'Órdenes creadas antes de ' + monthLabel + ' ' + history.year +
            (supplierName ? ' para ' + supplierName : '') + ' que continúan sin validarse.';

        openModal(historicalPendingModal);
        history.loading = true;
        historicalPendingButton.disabled = true;
        renderHistoricalPending();

        try {
            const url = new URL(pendingUrl, window.location.origin);
            url.searchParams.set('year', String(history.year));
            url.searchParams.set('month', String(history.monthNumber));
            if (history.supplierID) url.searchParams.set('supplierID', history.supplierID);
            const payload = await fetchJson(url.toString(), buildGetOptions());
            const periods = Array.isArray(payload.data && payload.data.byPeriod) ? payload.data.byPeriod : [];
            history.records = periods.flatMap(periodRow => Array.isArray(periodRow.orders) ? periodRow.orders : [])
                .map(item => ({ ...item, supplierID: item.supplierID || history.supplierID || null }))
                .sort((a, b) => {
                    const dateOrder = String(b.orderCreatedDate || '').localeCompare(String(a.orderCreatedDate || ''));
                    if (dateOrder) return dateOrder;
                    return String(b.orderNumber || '').localeCompare(String(a.orderNumber || ''), undefined, { numeric: true });
                });
        } catch (error) {
            history.error = error.message || 'No se pudo cargar el detalle de pendientes históricos.';
        } finally {
            history.loading = false;
            historicalPendingButton.disabled = false;
            renderHistoricalPending();
        }
    }

    function findStudyById(clinicalStudyID) {
        const id = String(clinicalStudyID);
        return state.reportRows.find(item => String(item.clinicalStudyID) === id)
            || state.catalog.rows.find(item => String(item.clinicalStudyID) === id)
            || state.catalogCodeMap.get(id)
            || null;
    }

    function prepareAssignment(study, targetSupplier) {
        const supplier = targetSupplier || selectedSupplier() || selectedCatalogSupplier();
        if (!supplier) {
            showToast('Selecciona el proveedor al que deseas asignar el examen.', true);
            return;
        }

        if (!state.assignmentsLoaded) {
            showToast('Aún no se pudo comprobar la asignación actual del examen.', true);
            return;
        }

        const owner = currentOwner(study.clinicalStudyID) || (study.supplierID ? {
            supplierID: study.supplierID,
            supplierName: study.supplierName || study.supplierID
        } : null);
        if (owner && String(owner.supplierID) === String(supplier.id)) {
            showToast('Este examen ya está asignado a ' + supplier.name + '.');
            return;
        }

        state.pendingAssignment = {
            study,
            supplier,
            owner,
        };

        const code = studyCode(study);
        const titleText = code + ' · ' + (study.clinicalStudyName || ('Estudio ' + study.clinicalStudyID));

        if (owner) {
            assignModalTitle.textContent = 'Reasignar estudio';
            assignModalText.textContent = 'Este examen ya pertenece a otro proveedor. La nueva asignación reemplazará la actual.';
            confirmAssignButton.textContent = 'Reasignar';
        } else {
            assignModalTitle.textContent = 'Asignar estudio';
            assignModalText.textContent = 'Este examen no tiene proveedor asignado. ¿Deseas asignarlo al proveedor seleccionado?';
            confirmAssignButton.textContent = 'Asignar';
        }

        assignSummary.innerHTML =
            '<strong>' + escapeHtml(titleText) + '</strong><br>' +
            (owner ? 'Proveedor actual: ' + escapeHtml(owner.supplierName) + '<br>' : '') +
            'Nuevo proveedor: <strong>' + escapeHtml(supplier.name) + '</strong>';

        openModal(assignModal);
    }

    async function executeAssignment() {
        const pending = state.pendingAssignment;
        if (!pending) return;

        const actionLabel = pending.owner ? 'Reasignar' : 'Asignar';
        confirmAssignButton.disabled = true;
        confirmAssignButton.textContent = 'Guardando...';

        try {
            const payload = await fetchJson(assignUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    clinicalStudyID: number(pending.study.clinicalStudyID),
                    supplierID: pending.supplier.id,
                }),
            });

            rememberStudy(pending.study);

            state.assignmentMap.set(String(pending.study.clinicalStudyID), {
                clinicalStudyID: number(pending.study.clinicalStudyID),
                clinicalStudyName: pending.study.clinicalStudyName || '',
                supplierID: pending.supplier.id,
                supplierName: pending.supplier.name,
                supplierStatus: pending.supplier.status || 'active',
            });
            state.assignmentsLoaded = true;

            renderStudies();
            if ((state.catalog.mode === 'assigned' || state.catalog.mode === 'master') && catalogSupplierSelect.value) {
                await loadSupplierStudies(catalogSupplierSelect.value);
            } else {
                renderCatalog();
            }
            closeModal(assignModal);
            showToast(payload.message || 'Proveedor asignado al estudio correctamente.');
            state.pendingAssignment = null;
        } catch (error) {
            showToast(error.message || 'No se pudo guardar la asignación.', true);
        } finally {
            confirmAssignButton.disabled = false;
            confirmAssignButton.textContent = actionLabel;
        }
    }

    async function unassignStudy(study, supplier, checkbox) {
        if (!study || !supplier) {
            if (checkbox) checkbox.checked = true;
            showToast('No se pudo identificar el estudio o el proveedor.', true);
            return;
        }

        if (checkbox) checkbox.disabled = true;

        try {
            const payload = await fetchJson(assignUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    action: 'unassign',
                    clinicalStudyID: number(study.clinicalStudyID),
                    supplierID: supplier.id,
                }),
            });

            rememberStudy(study);
            state.assignmentMap.delete(String(study.clinicalStudyID));
            study.supplierID = '';
            study.supplierName = '';

            // Conservamos la fila en pantalla y en el catálogo conocido para que
            // siga apareciendo como ☐ al cerrar y volver a abrir la ventana.
            // volver a marcarse inmediatamente si el usuario cambia de idea.
            renderCatalog();
            renderStudies();
            showToast(payload.message || 'Estudio desasignado del proveedor correctamente.');
        } catch (error) {
            if (checkbox) {
                checkbox.checked = true;
                checkbox.disabled = false;
            }
            showToast(error.message || 'No se pudo quitar la asignación del estudio.', true);
        }
    }

    function activeSupplierOptions() {
        return state.suppliers.filter(item => String(item.status || 'active').toLowerCase() === 'active');
    }

    function renderSupplierOptions() {
        const selectedTopId = supplierSelect.value;
        const selectedCatalogId = catalogSupplierSelect.value;
        const active = activeSupplierOptions();
        const options = ['<option value="">Selecciona un proveedor</option>'];

        active.forEach(item => {
            options.push('<option value="' + escapeHtml(item.id) + '">' + escapeHtml(item.name) + '</option>');
        });

        supplierSelect.innerHTML = options.join('');
        catalogSupplierSelect.innerHTML = options.join('');

        if (selectedTopId && active.some(item => String(item.id) === String(selectedTopId))) {
            supplierSelect.value = selectedTopId;
        }

        if (selectedCatalogId && active.some(item => String(item.id) === String(selectedCatalogId))) {
            catalogSupplierSelect.value = selectedCatalogId;
        } else {
            const preferred = selectedSupplier();
            if (preferred && active.some(item => String(item.id) === String(preferred.id))) {
                catalogSupplierSelect.value = preferred.id;
            } else if (active.length) {
                catalogSupplierSelect.value = active[0].id;
            }
        }

        renderReportSupplierTabs();
    }

    function renderProviderTable() {
        if (!state.suppliers.length) {
            providerTableBody.innerHTML = '<tr><td colspan="4">No hay proveedores registrados.</td></tr>';
            return;
        }

        providerTableBody.innerHTML = state.suppliers.map(item => {
            const status = String(item.status || 'active').toLowerCase();
            return '<tr>' +
                '<td><strong>' + escapeHtml(item.name) + '</strong></td>' +
                '<td><span class="supplier-status-badge ' + escapeHtml(status) + '">' + (status === 'active' ? 'Activo' : 'Inactivo') + '</span></td>' +
                '<td>' + escapeHtml(formatDate(item.createdAt)) + '</td>' +
                '<td><button type="button" class="supplier-provider-edit" data-edit-provider="' + escapeHtml(item.id) + '">Editar</button></td>' +
                '</tr>';
        }).join('');
    }

    function resetProviderForm() {
        providerIdInput.value = '';
        providerNameInput.value = '';
        providerStatusSelect.value = 'active';
        providerStatusSelect.disabled = true;
        providerSaveButton.textContent = 'Crear proveedor';
        providerCancelEdit.hidden = true;
    }

    function editProvider(id) {
        const supplier = getSupplier(id);
        if (!supplier) return;

        providerIdInput.value = supplier.id;
        providerNameInput.value = supplier.name;
        providerStatusSelect.value = supplier.status || 'active';
        providerStatusSelect.disabled = false;
        providerSaveButton.textContent = 'Guardar cambios';
        providerCancelEdit.hidden = false;
        providerNameInput.focus();
    }

    async function reloadSuppliers() {
        const payload = await fetchJson(suppliersUrl, buildGetOptions());
        state.suppliers = Array.isArray(payload.data) ? payload.data : [];
        renderSupplierOptions();
        renderProviderTable();
        renderStudies();
    }

    async function saveProvider(event) {
        event.preventDefault();

        const id = providerIdInput.value.trim();
        const name = providerNameInput.value.trim();
        const isEdit = Boolean(id);

        if (!name) {
            showToast('Escribe el nombre del proveedor.', true);
            return;
        }

        providerSaveButton.disabled = true;
        providerSaveButton.textContent = 'Guardando...';

        try {
            const payload = { name };
            if (isEdit) {
                payload.id = id;
                payload.status = providerStatusSelect.value;
            }

            const response = await fetchJson(isEdit ? editSupplierUrl : createSupplierUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            await reloadSuppliers();
            await loadAssignments(true).catch(() => {});
            resetProviderForm();
            renderCatalog();
            showToast(response.message || (isEdit ? 'Proveedor actualizado.' : 'Proveedor creado.'));
        } catch (error) {
            showToast(error.message || 'No se pudo guardar el proveedor.', true);
        } finally {
            providerSaveButton.disabled = false;
            providerSaveButton.textContent = providerIdInput.value ? 'Guardar cambios' : 'Crear proveedor';
        }
    }

    function activateManagementTab(name) {
        managementTabs.forEach(button => {
            const active = button.dataset.managementTab === name;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        managementPanels.forEach(panel => {
            const active = panel.dataset.managementPanel === name;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });

        if (name === 'studies') {
            initializeCatalogPanel();
        }
    }

    async function loadSupplierStudies(supplierID) {
        const id = String(supplierID || '').trim();
        state.catalog.mode = 'master';
        state.catalog.search = '';
        state.catalog.page = 0;
        state.catalog.hasMore = false;
        state.catalog.loading = true;
        renderCatalog();

        if (!id) {
            state.catalog.rows = knownCatalogRows();
            state.catalog.loading = false;
            renderCatalog();
            return;
        }

        try {
            // Cargamos el mapa global para saber qué proveedor tiene cada estudio.
            // Esto NO elimina estudios: únicamente refleja la relación actual.
            if (!state.assignmentsLoaded) {
                await loadAssignments(false);
            }

            const url = new URL(assignmentsUrl, window.location.origin);
            url.searchParams.set('supplierID', id);
            const payload = await fetchJson(url.toString(), buildGetOptions());
            const assignedRows = Array.isArray(payload.data) ? payload.data : [];
            const supplier = getSupplier(id);

            assignedRows.forEach(item => {
                const studyID = String(item.clinicalStudyID);
                const cached = state.catalogCodeMap.get(studyID) || {};
                const monthly = state.reportRows.find(row => String(row.clinicalStudyID) === studyID) || {};
                const study = {
                    ...item,
                    clinicalStudyNumber: item.clinicalStudyNumber || cached.clinicalStudyNumber || monthly.clinicalStudyNumber || '',
                    clinicalStudyAbbrev: item.clinicalStudyAbbrev || cached.clinicalStudyAbbrev || monthly.clinicalStudyAbbrev || '',
                    supplierID: item.supplierID || id,
                    supplierName: item.supplierName || (supplier ? supplier.name : id),
                    assignmentKnown: true,
                };

                rememberStudy(study, false);
                state.assignmentMap.set(studyID, {
                    clinicalStudyID: number(study.clinicalStudyID),
                    clinicalStudyNumber: study.clinicalStudyNumber || '',
                    clinicalStudyName: study.clinicalStudyName || '',
                    supplierID: id,
                    supplierName: study.supplierName || (supplier ? supplier.name : id),
                    supplierStatus: supplier ? (supplier.status || 'active') : 'active',
                });
            });

            // El listado visual parte del catálogo conocido, no únicamente de
            // GET /suppliers/{supplierID}/studies. Por eso un estudio que se
            // desmarca sigue apareciendo como ☐ y puede volver a asignarse.
            rememberStudies(state.reportRows, false);
            persistKnownStudies();
            state.catalog.rows = knownCatalogRows().map(item => ({
                ...item,
                assignmentKnown: true,
            }));
        } catch (error) {
            // Incluso si falla la lectura de relaciones, conservamos el catálogo
            // conocido para no hacer desaparecer estudios del administrador.
            state.catalog.rows = knownCatalogRows();
            showToast(error.message || 'No se pudieron consultar los estudios del proveedor.', true);
        } finally {
            state.catalog.loading = false;
            renderCatalog();
        }
    }

    async function initializeCatalogPanel() {
        renderSupplierOptions();

        if (!catalogSupplierSelect.value && activeSupplierOptions().length) {
            catalogSupplierSelect.value = activeSupplierOptions()[0].id;
        }

        // La edición de un proveedor parte SIEMPRE del endpoint directo:
        // GET /suppliers/{supplierID}/studies
        await loadSupplierStudies(catalogSupplierSelect.value);
    }

    function renderCatalogAssignment(study) {
        const supplier = selectedCatalogSupplier();

        if (!supplier) {
            return '<span class="supplier-catalog-disabled">Selecciona proveedor</span>';
        }

        const directOwner = study.supplierID ? {
            supplierID: study.supplierID,
            supplierName: study.supplierName || study.supplierID
        } : null;

        if (!state.assignmentsLoaded && !directOwner && !study.assignmentKnown) {
            return '<span class="supplier-catalog-disabled">Cargando…</span>';
        }

        const owner = directOwner || currentOwner(study.clinicalStudyID);

        if (!owner) {
            return '<label class="supplier-check-wrap" title="Marcar para asignar a ' + escapeHtml(supplier.name) + '">' +
                '<input class="supplier-study-check" type="checkbox" data-catalog-toggle="' + escapeHtml(study.clinicalStudyID) + '">' +
                '</label>';
        }

        if (String(owner.supplierID) === String(supplier.id)) {
            return '<div class="supplier-catalog-assignment-state">' +
                '<label class="supplier-check-wrap" title="Desmarcar para quitar la asignación de ' + escapeHtml(supplier.name) + '">' +
                '<input class="supplier-study-check" type="checkbox" checked data-catalog-toggle="' + escapeHtml(study.clinicalStudyID) + '">' +
                '</label>' +
                '<small>Asignado</small>' +
                '</div>';
        }

        return '<div class="supplier-catalog-assignment-state">' +
            '<button type="button" class="supplier-lock-button" data-catalog-reassign="' + escapeHtml(study.clinicalStudyID) + '" title="Asignado a ' +
            escapeHtml(owner.supplierName + '. Clic para reasignar a ' + supplier.name) + '">🔒</button>' +
            '<small title="' + escapeHtml(owner.supplierName) + '">' + escapeHtml(owner.supplierName) + '</small>' +
            '</div>';
    }

    /**
     * Orden visual para "Exámenes por proveedor":
     * 1) asignados al proveedor seleccionado,
     * 2) asignados a otro proveedor,
     * 3) libres/sin asignación.
     * Dentro de cada grupo se ordenan por clinicalStudyNumber.
     */
    function orderedCatalogRows(rows) {
        const supplier = selectedCatalogSupplier();
        const selectedSupplierID = supplier ? String(supplier.id) : '';

        const rank = study => {
            const owner = currentOwner(study.clinicalStudyID);

            if (!owner) return 2;
            if (selectedSupplierID && String(owner.supplierID) === selectedSupplierID) return 0;
            return 1;
        };

        return [...rows].sort((a, b) => {
            const rankDiff = rank(a) - rank(b);
            if (rankDiff !== 0) return rankDiff;

            const codeA = studyCode(a);
            const codeB = studyCode(b);
            const codeCompare = codeA.localeCompare(codeB, 'es', { numeric: true });
            if (codeCompare !== 0) return codeCompare;

            return String(a.clinicalStudyName || '').localeCompare(
                String(b.clinicalStudyName || ''),
                'es'
            );
        });
    }

    function renderCatalog() {
        const rows = orderedCatalogRows(state.catalog.rows);
        const pageHuman = state.catalog.page + 1;
        const assignedMode = state.catalog.mode === 'assigned';
        const masterMode = state.catalog.mode === 'master';
        const supplier = selectedCatalogSupplier();

        catalogPageLabel.textContent = (assignedMode || masterMode) ? 'Sin paginación' : ('Página ' + pageHuman);
        catalogPrevButton.disabled = assignedMode || masterMode || state.catalog.loading || state.catalog.page <= 0 || Boolean(state.catalog.search);
        catalogNextButton.disabled = assignedMode || masterMode || state.catalog.loading || !state.catalog.hasMore || Boolean(state.catalog.search);

        if (state.catalog.loading) {
            catalogStatus.textContent = (assignedMode || masterMode) ? 'Consultando estudios y asignaciones...' : 'Consultando catálogo...';
            catalogTableBody.innerHTML = '<tr><td colspan="4" class="supplier-catalog-empty">Consultando estudios...</td></tr>';
            return;
        }

        if (assignedMode || masterMode) {
            catalogStatus.textContent = 'Mostrando ' + rows.length + (rows.length === 1 ? ' estudio' : ' estudios') +
                (supplier ? (' · ' + supplier.name) : '');
        } else {
            catalogStatus.textContent = state.catalog.search
                ? 'Búsqueda exacta: ' + state.catalog.search + ' · ' + rows.length + (rows.length === 1 ? ' resultado' : ' resultados')
                : 'Mostrando ' + rows.length + (rows.length === 1 ? ' estudio' : ' estudios') + ' · Página ' + pageHuman;
        }

        if (!rows.length) {
            catalogTableBody.innerHTML = '<tr><td colspan="4" class="supplier-catalog-empty">' +
                ((assignedMode || masterMode) ? 'Todavía no hay estudios conocidos para mostrar.' : 'No se encontraron estudios.') +
                '</td></tr>';
            return;
        }

        catalogTableBody.innerHTML = rows.map(study => {
            const cached = state.catalogCodeMap.get(String(study.clinicalStudyID)) || {};
            const code = studyCode(study);
            const abbrev = study.clinicalStudyAbbrev || cached.clinicalStudyAbbrev || '—';

            return '<tr>' +
                '<td><span class="supplier-study-code">' + escapeHtml(code) + '</span></td>' +
                '<td><span class="supplier-catalog-abbrev">' + escapeHtml(abbrev) + '</span></td>' +
                '<td><strong>' + escapeHtml(study.clinicalStudyName) + '</strong></td>' +
                '<td class="supplier-assignment-cell">' + renderCatalogAssignment(study) + '</td>' +
                '</tr>';
        }).join('');
    }

    async function loadCatalog(page, search) {
        if (state.catalog.loading) return;

        const normalizedSearch = String(search || '').trim();
        state.catalog.mode = normalizedSearch ? 'search' : 'catalog';
        state.catalog.loading = true;
        state.catalog.page = Math.max(0, number(page));
        state.catalog.search = normalizedSearch;
        renderCatalog();

        try {
            const result = await fetchCatalogPage(state.catalog.page, normalizedSearch);
            state.catalog.page = result.page;
            state.catalog.pageSize = result.pageSize;
            state.catalog.hasMore = result.hasMore;
            state.catalog.rows = result.rows;
        } catch (error) {
            state.catalog.rows = [];
            state.catalog.hasMore = false;
            showToast(error.message || 'No se pudo consultar el catálogo de estudios.', true);
        } finally {
            state.catalog.loading = false;
            renderCatalog();
        }
    }

    function exportContextLabel() {
        if (isAllMode()) return 'Todos los proveedores';
        const supplier = selectedSupplier();
        return supplier ? supplier.name : 'Sin proveedor seleccionado';
    }

    function exportRows() {
        return filteredReportRows().map(row => ({
            code: studyCode(row),
            name: row.clinicalStudyName,
            quantity: number(row.quantity),
            assignment: assignmentText(row),
        }));
    }

    function exportExcel() {
        // La exportación usa directamente los datos mensuales ya cargados en el tablero.
        // La columna visible usa clinicalStudyNumber; clinicalStudyID queda solo para lógica interna.
        // como respaldo (por ejemplo: "ID 3517"). No se consulta el catálogo al exportar.
        const rows = exportRows();
        if (!rows.length) {
            showToast('No hay datos para exportar.', true);
            return;
        }

        const reportTitle = 'Auditoría exámenes por proveedores';
        const period = periodLabel();
        const context = exportContextLabel();
        const generatedAt = new Intl.DateTimeFormat('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(new Date());

        const headerCells = [
            ['Código del examen', 'Nombre', 'Cantidad', 'Asignación'],
            ...rows.map(row => [row.code, row.name, row.quantity, row.assignment]),
        ];

        const dataRowsXml = headerCells.map((row, rowIndex) => {
            const cells = row.map((cell, cellIndex) => {
                const isHeader = rowIndex === 0;
                const numericCell = !isHeader && cellIndex === 2;
                const style = isHeader ? ' ss:StyleID="Header"' : (numericCell ? ' ss:StyleID="Number"' : '');
                return '<Cell' + style + '><Data ss:Type="' + (numericCell ? 'Number' : 'String') + '">' + escapeXml(cell) + '</Data></Cell>';
            }).join('');
            return '<Row>' + cells + '</Row>';
        }).join('');

        const metaRowsXml =
            '<Row ss:Height="24"><Cell ss:StyleID="Title" ss:MergeAcross="3"><Data ss:Type="String">' + escapeXml(reportTitle) + '</Data></Cell></Row>' +
            '<Row><Cell ss:StyleID="MetaLabel"><Data ss:Type="String">Mes exportado:</Data></Cell><Cell ss:MergeAcross="2"><Data ss:Type="String">' + escapeXml(period) + '</Data></Cell></Row>' +
            '<Row><Cell ss:StyleID="MetaLabel"><Data ss:Type="String">Vista / proveedor:</Data></Cell><Cell ss:MergeAcross="2"><Data ss:Type="String">' + escapeXml(context) + '</Data></Cell></Row>' +
            '<Row><Cell ss:StyleID="MetaLabel"><Data ss:Type="String">Generado:</Data></Cell><Cell ss:MergeAcross="2"><Data ss:Type="String">' + escapeXml(generatedAt) + '</Data></Cell></Row>' +
            '<Row ss:Height="8"></Row>';

        const xml = '<?xml version="1.0"?>' +
            '<?mso-application progid="Excel.Sheet"?>' +
            '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ' +
            'xmlns:o="urn:schemas-microsoft-com:office:office" ' +
            'xmlns:x="urn:schemas-microsoft-com:office:excel" ' +
            'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' +
            '<Styles>' +
                '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11"/></Style>' +
                '<Style ss:ID="Title"><Font ss:FontName="Calibri" ss:Size="16" ss:Bold="1" ss:Color="#0B2E63"/><Alignment ss:Vertical="Center"/></Style>' +
                '<Style ss:ID="MetaLabel"><Font ss:FontName="Calibri" ss:Bold="1" ss:Color="#48627F"/></Style>' +
                '<Style ss:ID="Header"><Font ss:FontName="Calibri" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B5ED7" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/></Style>' +
                '<Style ss:ID="Number"><NumberFormat ss:Format="#,##0"/><Alignment ss:Horizontal="Right"/></Style>' +
            '</Styles>' +
            '<Worksheet ss:Name="' + escapeXml(period.substring(0, 31)) + '"><Table>' +
                '<Column ss:Width="125"/>' +
                '<Column ss:Width="260"/>' +
                '<Column ss:Width="90"/>' +
                '<Column ss:Width="150"/>' +
                metaRowsXml +
                dataRowsXml +
            '</Table>' +
            '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>6</SplitHorizontal><TopRowBottomPane>6</TopRowBottomPane></WorksheetOptions>' +
            '</Worksheet></Workbook>';

        const blob = new Blob(['\ufeff', xml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'auditoria_examenes_proveedores_' + periodFileSuffix() + '.xls';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        loadData();
    });

    yearInput.addEventListener('change', () => {
        renderMonthOptions(monthInput.value);
        updateFiltersTitle();
        setAlert('');
    });

    monthInput.addEventListener('change', () => {
        updateFiltersTitle();
        setAlert('');
    });

    supplierSelect.addEventListener('change', () => syncExclusiveFilters('supplier'));
    viewAllRadio.addEventListener('change', () => syncExclusiveFilters('view'));
    viewSpecificRadio.addEventListener('change', () => syncExclusiveFilters('view'));
    clearButton.addEventListener('click', () => {
        resetToLatestAvailablePeriod();
        supplierSelect.value = '';
        viewAllRadio.checked = true;
        viewSpecificRadio.checked = false;
        state.reportRows = [];
        state.bySupplier = [];
        state.monthlyDataLoaded = false;
        renderReportSupplierTabs();
        state.summary = normalizeSummary(null, []);
        setAlert('');
        syncExclusiveFilters('view');
        if (latestAvailablePeriod()) loadData();
    });

    tableBody.addEventListener('change', event => {
        const checkbox = event.target.closest('[data-report-assign]');
        if (!checkbox) return;
        const study = findStudyById(checkbox.dataset.reportAssign);
        checkbox.checked = false;
        if (study) prepareAssignment(study, selectedSupplier());
    });

    tableBody.addEventListener('click', event => {
        const lockButton = event.target.closest('[data-report-reassign]');
        if (!lockButton) return;
        const study = findStudyById(lockButton.dataset.reportReassign);
        if (study) prepareAssignment(study, selectedSupplier());
    });

    if (reportTabs) {
        reportTabs.addEventListener('click', event => {
            const tab = event.target.closest('[data-report-provider]');
            if (!tab) return;
            state.reportSupplierTab = tab.dataset.reportProvider;
            state.reportPage = 1;
            renderReportSupplierTabs();
            updateTableContext();
            renderStudies();
        });
    }

    studyPrevPage.addEventListener('click', () => {
        if (state.reportPage > 1) {
            state.reportPage -= 1;
            renderStudies();
        }
    });

    studyNextPage.addEventListener('click', () => {
        const pageCount = Math.max(1, Math.ceil(filteredReportRows().length / state.reportPageSize));
        if (state.reportPage < pageCount) {
            state.reportPage += 1;
            renderStudies();
        }
    });

    confirmAssignButton.addEventListener('click', executeAssignment);

    historicalPendingButton.addEventListener('click', openHistoricalPending);
    historicalPendingMore.addEventListener('click', () => {
        if (!state.historicalPending.hasMore) return;
        state.historicalPending.visibleCount += state.historicalPending.pageSize;
        renderHistoricalPending();
    });
    historicalPendingContent.addEventListener('click', event => {
        const toggle = event.target.closest('[data-historical-period]');
        if (!toggle) return;
        const period = toggle.dataset.historicalPeriod;
        state.historicalPending.openPeriod = state.historicalPending.openPeriod === period ? null : period;
        renderHistoricalPending();
    });

    manageSuppliersButton.addEventListener('click', () => {
        resetProviderForm();
        renderProviderTable();
        activateManagementTab('providers');
        openModal(managementModal);
    });

    managementTabs.forEach(button => {
        button.addEventListener('click', () => activateManagementTab(button.dataset.managementTab));
    });

    providerForm.addEventListener('submit', saveProvider);
    providerCancelEdit.addEventListener('click', resetProviderForm);

    providerTableBody.addEventListener('click', event => {
        const button = event.target.closest('[data-edit-provider]');
        if (button) editProvider(button.dataset.editProvider);
    });

    catalogSupplierSelect.addEventListener('change', () => {
        catalogSearchInput.value = '';
        void loadSupplierStudies(catalogSupplierSelect.value);
    });

    catalogSearchForm.addEventListener('submit', async event => {
        event.preventDefault();
        const query = catalogSearchInput.value.trim();
        if (!query) {
            await loadSupplierStudies(catalogSupplierSelect.value);
            return;
        }

        if (!state.assignmentsLoaded) {
            try {
                await loadAssignments(false);
            } catch (error) {
                showToast('No se pudo comprobar la asignación actual: ' + error.message, true);
            }
        }

        const normalized = query.toLowerCase();
        const matches = knownCatalogRows().filter(study => {
            const code = String(study.clinicalStudyNumber || '').toLowerCase();
            const id = String(study.clinicalStudyID || '').toLowerCase();
            return code === normalized || id === normalized || ('id ' + id) === normalized;
        });

        state.catalog.mode = 'master';
        state.catalog.search = query;
        state.catalog.page = 0;
        state.catalog.hasMore = false;
        state.catalog.rows = matches;
        renderCatalog();

        if (!matches.length) {
            showToast('No se encontró ese código entre los estudios conocidos.');
        }
    });

    catalogClearSearch.addEventListener('click', () => {
        catalogSearchInput.value = '';
        void loadSupplierStudies(catalogSupplierSelect.value);
    });

    catalogPrevButton.addEventListener('click', () => {
        if (state.catalog.page > 0) loadCatalog(state.catalog.page - 1, '');
    });

    catalogNextButton.addEventListener('click', () => {
        if (state.catalog.hasMore) loadCatalog(state.catalog.page + 1, '');
    });

    catalogTableBody.addEventListener('change', event => {
        const checkbox = event.target.closest('[data-catalog-toggle]');
        if (!checkbox) return;

        const study = findStudyById(checkbox.dataset.catalogToggle);
        const supplier = selectedCatalogSupplier();

        if (!study || !supplier) {
            checkbox.checked = !checkbox.checked;
            showToast('Selecciona un proveedor antes de modificar la asignación.', true);
            return;
        }

        if (checkbox.checked) {
            // La confirmación de asignación/reasignación ya existe en el modal.
            checkbox.checked = false;
            prepareAssignment(study, supplier);
            return;
        }

        // Desmarcar no muestra ningún botón de eliminar: el propio checkbox
        // ejecuta DELETE /suppliers/{supplierID}/studies/{clinicalStudyID}.
        void unassignStudy(study, supplier, checkbox);
    });

    catalogTableBody.addEventListener('click', event => {
        const lockButton = event.target.closest('[data-catalog-reassign]');
        if (!lockButton) return;
        const study = findStudyById(lockButton.dataset.catalogReassign);
        if (study) prepareAssignment(study, selectedCatalogSupplier());
    });

    document.querySelectorAll('[data-close-modal]').forEach(button => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.closeModal);
            closeModal(modal);
            if (modal === assignModal) state.pendingAssignment = null;
        });
    });

    [assignModal, managementModal, historicalPendingModal].forEach(modal => {
        modal.addEventListener('click', event => {
            if (event.target === modal) {
                closeModal(modal);
                if (modal === assignModal) state.pendingAssignment = null;
            }
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;

        if (assignModal.classList.contains('is-open')) {
            closeModal(assignModal);
            state.pendingAssignment = null;
            return;
        }

        if (managementModal.classList.contains('is-open')) {
            closeModal(managementModal);
            return;
        }

        if (historicalPendingModal.classList.contains('is-open')) {
            closeModal(historicalPendingModal);
            return;
        }

    });

    exportButton.addEventListener('click', () => {
        void exportExcel();
    });

    updateFiltersTitle();

    restoreKnownStudies();
    rememberStudies(state.reportRows, false);

    renderSupplierOptions();
    renderProviderTable();
    resetProviderForm();
    syncExclusiveFilters('view');

    const initialPeriod = currentPeriod();
    if (initialPeriod.year && monthsForYear(initialPeriod.year).length) {
        renderMonthOptions(initialPeriod.month);
        loadData();
    } else if (resetToLatestAvailablePeriod()) {
        loadData();
    } else {
        setAlert('No hay periodos disponibles para consultar.');
        tableBody.innerHTML = '<tr class="supplier-empty-row"><td colspan="4">El backend todavía no reporta meses disponibles.</td></tr>';
    }
})();
