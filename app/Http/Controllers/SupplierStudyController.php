<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierStudyController extends Controller
{
    public function index(Request $request, GoApiService $goApiService): View|RedirectResponse
    {
        $errors = [];

        $suppliersResponse = $goApiService->getSuppliers();

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        $suppliers = [];
        if (is_array($suppliersResponse)) {
            $suppliers = $this->normalizeSuppliers($suppliersResponse);
        } else {
            $errors[] = $this->apiErrorMessage(
                $goApiService,
                'No se pudo obtener el catálogo de proveedores.'
            );
        }

        // Al abrir el tablero consultamos los periodos que realmente existen
        // en el backend. De esta forma los selects de Año/Mes no ofrecen
        // combinaciones que todavía no tienen información mensual.
        $periodsResponse = $goApiService->getClinicalStudyAvailablePeriods();

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        $availablePeriods = [];
        if (is_array($periodsResponse)) {
            $availablePeriods = $this->normalizeAvailablePeriods($periodsResponse);
        } else {
            $errors[] = $this->apiErrorMessage(
                $goApiService,
                'No se pudieron obtener los periodos disponibles del tablero.'
            );
        }

        [$defaultYear, $defaultMonth] = $this->latestAvailablePeriod($availablePeriods);

        return view('dashboards.supplier-studies', [
            'suppliers' => $suppliers,
            'availablePeriods' => $availablePeriods,
            'defaultMonth' => $defaultMonth,
            'defaultYear' => $defaultYear,
            'errorMessage' => $errors !== [] ? implode(' ', array_unique($errors)) : null,
        ]);
    }

    /**
     * Totales mensuales más el listado de estudios subrogados del periodo.
     * APIs: GET /clinical-studies/{year}/{monthNumber}/totals y GET /clinicalStudy/{start}-{end}.
     */
    public function data(Request $request, GoApiService $goApiService): JsonResponse
    {
        [$month, $year, $validationError] = $this->validateMonthYear(
            $request->query('month'), $request->query('year'), $request->query('start'), $request->query('end')
        );
        if ($validationError !== null) {
            return response()->json(['ok' => false, 'message' => $validationError], 422);
        }

        $supplierID = trim((string) $request->query('supplierID', ''));
        if (mb_strlen($supplierID) > 100) {
            return response()->json(['ok' => false, 'message' => 'El identificador del proveedor no es válido.'], 422);
        }

        $totalsResponse = $goApiService->getClinicalStudyValidationTotals($year, $month, $supplierID !== '' ? $supplierID : null);
        if ($sessionResponse = $this->sessionExpiredJson()) return $sessionResponse;
        if (!is_array($totalsResponse)) {
            return response()->json(['ok' => false, 'message' => $this->apiErrorMessage($goApiService, 'No se pudieron obtener los totales mensuales de estudios.')], 502);
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Ymd');
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Ymd');
        $studiesResponse = $goApiService->getClinicalStudies($start, $end);
        if ($sessionResponse = $this->sessionExpiredJson()) return $sessionResponse;
        if (!is_array($studiesResponse)) {
            return response()->json(['ok' => false, 'message' => $this->apiErrorMessage($goApiService, 'No se pudo obtener el detalle de estudios del periodo.')], 502);
        }

        $totals = $this->normalizeMonthlyClinicalStudyTotals($totalsResponse['monthTotals'] ?? []);
        $suppliers = $this->normalizeMonthlyClinicalStudiesBySupplier($totalsResponse['suppliers'] ?? []);
        $rows = $this->normalizeClinicalStudies($studiesResponse);
        $job = isset($totalsResponse['job']) && is_array($totalsResponse['job']) ? $totalsResponse['job'] : null;
        $lastUpdatedAt = $totalsResponse['lastUpdatedAt'] ?? null;
        $alert = isset($totalsResponse['alert']) && is_string($totalsResponse['alert']) ? $totalsResponse['alert'] : null;

        return response()->json([
            'ok' => true,
            'data' => [
                'month' => $month, 'year' => $year, 'periodLabel' => $this->periodLabel($month, $year),
                'lastUpdatedAt' => $lastUpdatedAt, 'job' => $job, 'alert' => $alert,
                'summary' => $totals, 'totals' => $totals, 'bySupplier' => $suppliers, 'rows' => $rows,
            ],
            'updated_at' => $this->formatLastUpdatedAt($lastUpdatedAt),
        ]);
    }

    /** Consulta los pendientes históricos solo cuando se abre el modal. */
    public function historicalPending(Request $request, GoApiService $goApiService): JsonResponse
    {
        $yearRaw = $request->query('year');
        $monthRaw = $request->query('month');
        $supplierID = trim((string) $request->query('supplierID', ''));
        if (filter_var($yearRaw, FILTER_VALIDATE_INT) === false || (int) $yearRaw < 2000 || (int) $yearRaw > 2100) {
            return response()->json(['ok' => false, 'message' => 'El año debe ser válido.'], 422);
        }
        if (filter_var($monthRaw, FILTER_VALIDATE_INT) === false || (int) $monthRaw < 1 || (int) $monthRaw > 12) {
            return response()->json(['ok' => false, 'message' => 'El mes debe estar entre 1 y 12.'], 422);
        }
        if (mb_strlen($supplierID) > 100) {
            return response()->json(['ok' => false, 'message' => 'El identificador del proveedor no es válido.'], 422);
        }

        $response = $goApiService->getClinicalStudyValidationPending((int) $yearRaw, (int) $monthRaw, $supplierID !== '' ? $supplierID : null);
        if ($sessionResponse = $this->sessionExpiredJson()) return $sessionResponse;
        if (!is_array($response)) {
            return response()->json(['ok' => false, 'message' => $this->apiErrorMessage($goApiService, 'No se pudo obtener el detalle de pendientes de meses anteriores.')], 502);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'year' => (int) ($response['year'] ?? $yearRaw),
                'monthNumber' => (int) ($response['monthNumber'] ?? $monthRaw),
                'supplierID' => $response['supplierID'] ?? ($supplierID !== '' ? $supplierID : null),
                'lastPeriods_stillPending' => max(0, (int) ($response['lastPeriods_stillPending'] ?? 0)),
                'byPeriod' => $this->normalizeHistoricalPendingPeriods($response['byPeriod'] ?? []),
            ],
        ]);
    }

    /**
     * Catálogo de proveedores.
     * API: GET /supplier
     */
    public function suppliers(GoApiService $goApiService): JsonResponse
    {
        $response = $goApiService->getSuppliers();

        if ($sessionResponse = $this->sessionExpiredJson()) {
            return $sessionResponse;
        }

        if (!is_array($response)) {
            return response()->json([
                'ok' => false,
                'message' => $this->apiErrorMessage($goApiService, 'No se pudo consultar proveedores.'),
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->normalizeSuppliers($response),
        ]);
    }

    /**
     * Catálogo de estudios clínicos, paginado 50 en 50.
     * API: GET /clinical-studies/{page}?clinicalStudyNumber=...
     */
    public function catalog(Request $request, GoApiService $goApiService): JsonResponse
    {
        $pageRaw = $request->query('page', 0);

        if (filter_var($pageRaw, FILTER_VALIDATE_INT) === false || (int) $pageRaw < 0) {
            return response()->json([
                'ok' => false,
                'message' => 'La página del catálogo debe ser un entero mayor o igual a 0.',
            ], 422);
        }

        $page = (int) $pageRaw;
        $clinicalStudyNumber = trim((string) $request->query('clinicalStudyNumber', ''));

        if (mb_strlen($clinicalStudyNumber) > 100) {
            return response()->json([
                'ok' => false,
                'message' => 'El código de examen es demasiado largo.',
            ], 422);
        }

        $response = $goApiService->getClinicalStudyCatalog(
            $page,
            $clinicalStudyNumber !== '' ? $clinicalStudyNumber : null
        );

        if ($sessionResponse = $this->sessionExpiredJson()) {
            return $sessionResponse;
        }

        if (!is_array($response)) {
            return response()->json([
                'ok' => false,
                'message' => $this->apiErrorMessage(
                    $goApiService,
                    'No se pudo obtener el catálogo de estudios clínicos.'
                ),
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->normalizeCatalogPage($response, $page),
        ]);
    }

    /**
     * Construye el mapa actual estudio -> proveedor consultando la lista de
     * estudios de cada proveedor. Esto permite pintar correctamente check y
     * candado sin inferir la asignación actual desde el reporte histórico.
     *
     * API utilizada por proveedor: GET /suppliers/{supplierID}/studies
     */
    public function assignments(Request $request, GoApiService $goApiService): JsonResponse
    {
        $requestedSupplierID = trim((string) $request->query('supplierID', ''));

        $suppliersResponse = $goApiService->getSuppliers();

        if ($sessionResponse = $this->sessionExpiredJson()) {
            return $sessionResponse;
        }

        if (!is_array($suppliersResponse)) {
            return response()->json([
                'ok' => false,
                'message' => $this->apiErrorMessage(
                    $goApiService,
                    'No se pudo consultar proveedores para obtener sus estudios.'
                ),
            ], 502);
        }

        $suppliers = $this->normalizeSuppliers($suppliersResponse);

        // Modo administración: cuando se manda supplierID consultamos
        // directamente GET /suppliers/{supplierID}/studies y devolvemos la
        // lista completa asignada a ese proveedor. El backend responde 200
        // con studies: [] tanto si no existe como si no tiene estudios.
        if ($requestedSupplierID !== '') {
            $supplier = collect($suppliers)->first(
                static fn (array $item): bool => (string) $item['id'] === $requestedSupplierID
            );

            $response = $goApiService->getSupplierStudies($requestedSupplierID);

            if ($sessionResponse = $this->sessionExpiredJson()) {
                return $sessionResponse;
            }

            if (!is_array($response)) {
                return response()->json([
                    'ok' => false,
                    'message' => $this->apiErrorMessage(
                        $goApiService,
                        'No se pudieron consultar los estudios asignados al proveedor seleccionado.'
                    ),
                ], 502);
            }

            $studies = isset($response['studies']) && is_array($response['studies'])
                ? $response['studies']
                : [];

            $normalized = [];

            foreach ($studies as $study) {
                if (!is_array($study) || !is_numeric($study['clinicalStudyID'] ?? null)) {
                    continue;
                }

                $studyId = (int) $study['clinicalStudyID'];
                $normalized[] = [
                    'clinicalStudyID' => $studyId,
                    'clinicalStudyNumber' => trim((string) ($study['clinicalStudyNumber'] ?? '')),
                    'clinicalStudyName' => trim((string) ($study['clinicalStudyName'] ?? '')),
                    'supplierID' => $requestedSupplierID,
                    'supplierName' => is_array($supplier)
                        ? (string) $supplier['name']
                        : $requestedSupplierID,
                    'supplierStatus' => is_array($supplier)
                        ? (string) $supplier['status']
                        : 'active',
                ];
            }

            usort(
                $normalized,
                static fn (array $a, array $b): int => strnatcasecmp(
                    $a['clinicalStudyName'],
                    $b['clinicalStudyName']
                )
            );

            return response()->json([
                'ok' => true,
                'supplierID' => $requestedSupplierID,
                'data' => $normalized,
            ]);
        }

        // Modo global: se conserva para saber quién tiene cada estudio y
        // pintar correctamente check/candado cuando se busca en el catálogo.
        $assignments = [];

        foreach ($suppliers as $supplier) {
            $response = $goApiService->getSupplierStudies($supplier['id']);

            if ($sessionResponse = $this->sessionExpiredJson()) {
                return $sessionResponse;
            }

            if (!is_array($response)) {
                return response()->json([
                    'ok' => false,
                    'message' => $this->apiErrorMessage(
                        $goApiService,
                        'No se pudieron consultar los estudios asignados a ' . $supplier['name'] . '.'
                    ),
                ], 502);
            }

            $studies = isset($response['studies']) && is_array($response['studies'])
                ? $response['studies']
                : [];

            foreach ($studies as $study) {
                if (!is_array($study) || !is_numeric($study['clinicalStudyID'] ?? null)) {
                    continue;
                }

                $studyId = (int) $study['clinicalStudyID'];
                $assignments[$studyId] = [
                    'clinicalStudyID' => $studyId,
                    'clinicalStudyNumber' => trim((string) ($study['clinicalStudyNumber'] ?? '')),
                    'clinicalStudyName' => trim((string) ($study['clinicalStudyName'] ?? '')),
                    'supplierID' => $supplier['id'],
                    'supplierName' => $supplier['name'],
                    'supplierStatus' => $supplier['status'],
                ];
            }
        }

        ksort($assignments, SORT_NUMERIC);

        return response()->json([
            'ok' => true,
            'data' => array_values($assignments),
        ]);
    }

    public function storeSupplier(Request $request, GoApiService $goApiService): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            return response()->json([
                'ok' => false,
                'message' => 'El nombre del proveedor es obligatorio.',
            ], 422);
        }

        $response = $goApiService->createSupplier(['name' => $name]);

        if ($sessionResponse = $this->sessionExpiredJson()) {
            return $sessionResponse;
        }

        if (!is_array($response) || ($response['created'] ?? false) !== true) {
            return response()->json([
                'ok' => false,
                'message' => is_array($response)
                    ? (string) ($response['message'] ?? 'No se pudo crear el proveedor.')
                    : $this->apiErrorMessage($goApiService, 'No se pudo crear el proveedor.'),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => (string) ($response['message'] ?? 'Proveedor creado correctamente.'),
            'provider' => $response['provider'] ?? null,
        ], 201);
    }

    public function updateSupplier(Request $request, GoApiService $goApiService): JsonResponse
    {
        $id = trim((string) $request->input('id', ''));
        $name = trim((string) $request->input('name', ''));
        $status = trim((string) $request->input('status', 'active'));

        if ($id === '' || $name === '') {
            return response()->json([
                'ok' => false,
                'message' => 'El id y el nombre del proveedor son obligatorios.',
            ], 422);
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            return response()->json([
                'ok' => false,
                'message' => 'El estatus debe ser active o inactive.',
            ], 422);
        }

        $response = $goApiService->updateSupplier([
            'id' => $id,
            'name' => $name,
            'status' => $status,
        ]);

        if ($sessionResponse = $this->sessionExpiredJson()) {
            return $sessionResponse;
        }

        if (!is_array($response) || ($response['updated'] ?? false) !== true) {
            return response()->json([
                'ok' => false,
                'message' => is_array($response)
                    ? (string) ($response['message'] ?? 'No se pudo actualizar el proveedor.')
                    : $this->apiErrorMessage($goApiService, 'No se pudo actualizar el proveedor.'),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => (string) ($response['message'] ?? 'Proveedor actualizado correctamente.'),
            'provider' => $response['provider'] ?? null,
        ]);
    }

    public function assignStudy(Request $request, GoApiService $goApiService): JsonResponse
    {
        $clinicalStudyID = $request->input('clinicalStudyID');
        $supplierID = trim((string) $request->input('supplierID', ''));
        $action = strtolower(trim((string) $request->input('action', 'assign')));

        if (!is_numeric($clinicalStudyID) || $supplierID === '') {
            return response()->json([
                'ok' => false,
                'message' => 'El estudio y el proveedor son obligatorios.',
            ], 422);
        }

        $clinicalStudyID = (int) $clinicalStudyID;

        // El frontend usa el mismo endpoint interno para marcar/desmarcar el
        // checkbox. Al desmarcar, Laravel traduce la acción al DELETE real de
        // backend sin mostrar un botón adicional en la interfaz.
        if ($action === 'unassign') {
            $response = $goApiService->unassignStudyFromSupplier($supplierID, $clinicalStudyID);

            if ($sessionResponse = $this->sessionExpiredJson()) {
                return $sessionResponse;
            }

            if (!is_array($response) || ($response['unassigned'] ?? false) !== true) {
                return response()->json([
                    'ok' => false,
                    'message' => is_array($response)
                        ? (string) ($response['message'] ?? 'No se pudo desasignar el estudio del proveedor.')
                        : $this->apiErrorMessage($goApiService, 'No se pudo desasignar el estudio del proveedor.'),
                ], 422);
            }

            return response()->json([
                'ok' => true,
                'action' => 'unassign',
                'message' => (string) ($response['message'] ?? 'Estudio desasignado del proveedor correctamente.'),
            ]);
        }

        $response = $goApiService->assignStudyToSupplier([
            'clinicalStudyID' => $clinicalStudyID,
            'supplierID' => $supplierID,
        ]);

        if ($sessionResponse = $this->sessionExpiredJson()) {
            return $sessionResponse;
        }

        if (!is_array($response) || ($response['assigned'] ?? false) !== true) {
            return response()->json([
                'ok' => false,
                'message' => is_array($response)
                    ? (string) ($response['message'] ?? 'No se pudo asignar el proveedor al estudio.')
                    : $this->apiErrorMessage($goApiService, 'No se pudo asignar el proveedor al estudio.'),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'action' => 'assign',
            'message' => (string) ($response['message'] ?? 'Proveedor asignado al estudio correctamente.'),
            'assignment' => $response['assignment'] ?? null,
        ]);
    }

    private function normalizeAvailablePeriods(array $response): array
    {
        $rows = isset($response['years']) && is_array($response['years'])
            ? $response['years']
            : [];

        $periods = [];

        foreach ($rows as $row) {
            if (!is_array($row) || !is_numeric($row['year'] ?? null)) {
                continue;
            }

            $year = (int) $row['year'];
            if ($year < 2000 || $year > 2100) {
                continue;
            }

            $months = [];
            foreach (($row['months'] ?? []) as $month) {
                if (!is_numeric($month)) {
                    continue;
                }

                $month = (int) $month;
                if ($month >= 1 && $month <= 12) {
                    $months[$month] = $month;
                }
            }

            if ($months === []) {
                continue;
            }

            ksort($months, SORT_NUMERIC);
            $periods[$year] = [
                'year' => $year,
                'months' => array_values($months),
            ];
        }

        ksort($periods, SORT_NUMERIC);

        return array_values($periods);
    }

    private function latestAvailablePeriod(array $periods): array
    {
        if ($periods === []) {
            return [null, null];
        }

        $latest = end($periods);
        if (!is_array($latest)) {
            return [null, null];
        }

        $year = is_numeric($latest['year'] ?? null) ? (int) $latest['year'] : null;
        $months = isset($latest['months']) && is_array($latest['months'])
            ? $latest['months']
            : [];
        $month = $months !== [] ? max(array_map('intval', $months)) : null;

        return [$year, $month];
    }

    private function validateMonthYear(mixed $month, mixed $year, mixed $start = null, mixed $end = null): array
    {
        $monthValue = trim((string) $month);
        $yearValue = trim((string) $year);

        // Compatibilidad temporal con clientes que todavía manden start/end.
        if ($monthValue === '' && $yearValue === '') {
            $startValue = trim((string) $start);
            if ($this->validDate($startValue)) {
                $date = Carbon::createFromFormat('!Y-m-d', $startValue);
                $monthValue = (string) $date->month;
                $yearValue = (string) $date->year;
            }
        }

        if (!preg_match('/^\d{1,2}$/', $monthValue) || !preg_match('/^\d{4}$/', $yearValue)) {
            return [null, null, 'Selecciona un mes y un año válidos.'];
        }

        $monthInt = (int) $monthValue;
        $yearInt = (int) $yearValue;

        if ($monthInt < 1 || $monthInt > 12) {
            return [$monthInt, $yearInt, 'El mes debe estar entre 1 y 12.'];
        }

        if ($yearInt < 2000 || $yearInt > 2100) {
            return [$monthInt, $yearInt, 'El año seleccionado no es válido.'];
        }

        return [$monthInt, $yearInt, null];
    }

    private function validDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $date)->format('Y-m-d') === $date;
        } catch (\Throwable) {
            return false;
        }
    }

    private function periodLabel(int $month, int $year): string
    {
        return ucfirst(Carbon::create($year, $month, 1)->locale('es')->translatedFormat('F Y'));
    }

    private function formatLastUpdatedAt(mixed $lastUpdatedAt): string
    {
        if (!is_string($lastUpdatedAt) || trim($lastUpdatedAt) === '') {
            return now()->format('d/m/Y H:i:s');
        }

        try {
            return Carbon::parse($lastUpdatedAt)->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s');
        } catch (\Throwable) {
            return now()->format('d/m/Y H:i:s');
        }
    }

    private function normalizeSuppliers(array $response): array
    {
        $rows = isset($response['data']) && is_array($response['data'])
            ? $response['data']
            : $response;

        $suppliers = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = trim((string) ($row['id'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($id === '' || $name === '') {
                continue;
            }

            $status = strtolower(trim((string) ($row['status'] ?? 'active')));
            $status = in_array($status, ['active', 'inactive'], true) ? $status : 'active';

            $suppliers[] = [
                'id' => $id,
                'name' => $name,
                'status' => $status,
                'createdAt' => $row['createdAt'] ?? null,
            ];
        }

        usort($suppliers, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

        return $suppliers;
    }

    private function normalizeCatalogPage(array $response, int $requestedPage): array
    {
        $rows = isset($response['data']) && is_array($response['data'])
            ? $response['data']
            : [];

        $studies = [];

        foreach ($rows as $row) {
            if (!is_array($row) || !is_numeric($row['clinicalStudyID'] ?? null)) {
                continue;
            }

            $studyId = (int) $row['clinicalStudyID'];
            $studyNumber = trim((string) ($row['clinicalStudyNumber'] ?? ''));
            $studyAbbrev = trim((string) ($row['clinicalStudyAbbrev'] ?? ''));
            $studyName = trim((string) ($row['clinicalStudyName'] ?? ''));

            $studies[] = [
                'clinicalStudyID' => $studyId,
                'clinicalStudyNumber' => $studyNumber,
                'clinicalStudyAbbrev' => $studyAbbrev,
                'clinicalStudyName' => $studyName !== '' ? $studyName : 'Estudio ' . $studyId,
            ];
        }

        return [
            'page' => is_numeric($response['page'] ?? null) ? (int) $response['page'] : $requestedPage,
            'pageSize' => is_numeric($response['pageSize'] ?? null) ? (int) $response['pageSize'] : 50,
            'hasMore' => (bool) ($response['hasMore'] ?? false),
            'data' => $studies,
        ];
    }

    private function normalizeMonthlyClinicalStudyTotals(mixed $value): array
    {
        $source = is_array($value) ? $value : [];
        $fallback = [
            'properlyValidated' => 0,
            'unProperlyValidated' => 0,
            'currentPeriod_stillPending' => 0,
            'allStudies' => 0,
            'lastPeriods_stillPending' => 0,
        ];

        $properlyValidated = max(0, (int) ($source['properlyValidated'] ?? $fallback['properlyValidated']));
        $unProperlyValidated = max(0, (int) ($source['unProperlyValidated'] ?? $fallback['unProperlyValidated']));
        $currentPending = max(0, (int) ($source['currentPeriod_stillPending'] ?? $fallback['currentPeriod_stillPending']));
        $allStudies = is_numeric($source['allStudies'] ?? null)
            ? max(0, (int) $source['allStudies'])
            : max(0, (int) $fallback['allStudies']);

        return [
            'properlyValidated' => $properlyValidated,
            'unProperlyValidated' => $unProperlyValidated,
            'currentPeriod_stillPending' => $currentPending,
            'allStudies' => $allStudies,
            'lastPeriods_stillPending' => max(0, (int) ($source['lastPeriods_stillPending'] ?? $fallback['lastPeriods_stillPending'])),
        ];
    }

    private function normalizeMonthlyClinicalStudiesBySupplier(mixed $value): array
    {
        $rows = is_array($value) ? $value : [];
        $suppliers = [];
        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['supplierID'])) continue;
            $totals = is_array($row['supplierTotals'] ?? null) ? $row['supplierTotals'] : [];
            $properlyValidated = max(0, (int) ($totals['properlyValidated'] ?? 0));
            $unProperlyValidated = max(0, (int) ($totals['unProperlyValidated'] ?? 0));
            $currentPending = max(0, (int) ($totals['currentPeriod_stillPending'] ?? 0));
            $suppliers[] = [
                'supplierID' => trim((string) $row['supplierID']),
                'supplierName' => trim((string) ($row['supplierName'] ?? '')),
                'properlyValidated' => $properlyValidated,
                'unProperlyValidated' => $unProperlyValidated,
                'currentPeriod_stillPending' => $currentPending,
                'allStudies' => max(0, (int) ($totals['allStudies'] ?? ($properlyValidated + $unProperlyValidated + $currentPending))),
                'lastPeriods_stillPending' => max(0, (int) ($totals['lastPeriods_stillPending'] ?? 0)),
            ];
        }
        usort($suppliers, static fn (array $a, array $b): int => strnatcasecmp($a['supplierName'], $b['supplierName']));
        return $suppliers;
    }

    private function normalizeHistoricalPendingPeriods(mixed $value): array
    {
        if (!is_array($value)) return [];
        $periods = [];
        foreach ($value as $period) {
            if (!is_array($period)) continue;
            $orders = [];
            foreach (($period['orders'] ?? []) as $item) {
                if (!is_array($item)) continue;
                $orders[] = [
                    'orderNumber' => isset($item['orderNumber']) ? trim((string) $item['orderNumber']) : '',
                    'orderCreatedDate' => isset($item['orderCreatedDate']) ? trim((string) $item['orderCreatedDate']) : '',
                    'clinicalStudyID' => is_numeric($item['clinicalStudyID'] ?? null) ? (int) $item['clinicalStudyID'] : null,
                    'clinicalStudyName' => trim((string) ($item['clinicalStudyName'] ?? '')),
                    'validatedAt' => $item['validatedAt'] ?? null,
                    'supplierID' => isset($item['supplierID']) ? trim((string) $item['supplierID']) : null,
                    'supplierName' => trim((string) ($item['supplierName'] ?? '')),
                ];
            }
            $periods[] = [
                'year' => is_numeric($period['year'] ?? null) ? (int) $period['year'] : null,
                'month' => is_numeric($period['month'] ?? null) ? (int) $period['month'] : null,
                'count' => max(0, (int) ($period['count'] ?? count($orders))),
                'orders' => $orders,
            ];
        }
        return $periods;
    }

    private function normalizeClinicalStudies(array $response): array
    {
        $rows = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;
        $grouped = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !is_numeric($row['clinicalStudyID'] ?? null)) continue;
            $studyId = (int) $row['clinicalStudyID'];
            $supplierId = trim((string) ($row['supplierID'] ?? ''));
            $key = $studyId . ':' . $supplierId;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'clinicalStudyID' => $studyId,
                    'clinicalStudyNumber' => trim((string) ($row['clinicalStudyNumber'] ?? '')),
                    'clinicalStudyAbbrev' => trim((string) ($row['clinicalStudyAbbrev'] ?? '')),
                    'clinicalStudyName' => trim((string) ($row['clinicalStudyName'] ?? '')) ?: 'Estudio ' . $studyId,
                    'supplierID' => $supplierId !== '' ? $supplierId : null,
                    'supplierName' => trim((string) ($row['supplierName'] ?? '')),
                    'quantity' => 0,
                    'clinicalStudyStatus' => is_numeric($row['clinicalStudyStatus'] ?? null) ? (int) $row['clinicalStudyStatus'] : null,
                ];
            }
            $grouped[$key]['quantity'] += max(0, (int) ($row['clinicalStudyQuantity'] ?? 0));
        }
        $result = array_values($grouped);
        usort($result, static fn (array $a, array $b): int => strnatcasecmp($a['clinicalStudyName'], $b['clinicalStudyName']));
        return $result;
    }

    private function sessionExpiredJson(): ?JsonResponse
    {
        if (!session('api_session_expired')) {
            return null;
        }

        return response()->json([
            'ok' => false,
            'sessionExpired' => true,
            'message' => session('api_session_message', 'Tu sesión expiró. Inicia sesión nuevamente.'),
            'redirect' => route('login'),
        ], 401);
    }
}
