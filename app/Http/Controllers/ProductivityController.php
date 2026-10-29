<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ProductivityController extends Controller
{
    /**
     * Umbral para definir si un resultado está dentro de tiempo oportunidad.
     * Se puede personalizar desde .env con PRODUCTIVITY_OPPORTUNITY_LIMIT_MINUTES.
     */
    private int $opportunityLimitMinutes = 60;

    /**
     * Umbral visual para marcar advertencia antes de considerarlo crítico.
     * Se puede personalizar desde .env con PRODUCTIVITY_WARNING_LIMIT_MINUTES.
     */
    private int $warningLimitMinutes = 90;

    /**
     * El tablero trabaja exclusivamente por día. Esto evita descargar
     * respuestas semanales o mensuales innecesarias y protege la memoria de PHP.
     */
    private int $maxRangeDays = 1;

    /**
     * Salvaguarda adicional para no construir estructuras ilimitadas en PHP.
     */
    private int $maxRecords = 250000;

    /**
     * Caché breve de los cálculos finales; nunca se guarda el JSON masivo.
     */
    private int $cacheSeconds = 90;

    /**
     * Estados que el catálogo del LIS considera como validados.
     * 4 = validada, 6 = validada.
     */
    private array $validatedStatuses = [4, 6];

    /**
     * Vistas disponibles del tablero de productividad.
     * Backend expone un endpoint general y dos variantes por tipo de atención:
     * /productivity/{date}, /productivity/{date}/0 y /productivity/{date}/1.
     */
    private array $serviceScopes = [];

    private array $shiftCatalog = [
        'morning' => 'Matutino',
        'evening' => 'Vespertino',
        'night' => 'Nocturno',
        'unknown' => 'Sin turno',
    ];

    public function __construct()
    {
        $this->opportunityLimitMinutes = (int) config('services.go_api.productivity_opportunity_limit_minutes', 60);
        $this->warningLimitMinutes = (int) config('services.go_api.productivity_warning_limit_minutes', 90);
        $this->maxRangeDays = 1;
        $this->maxRecords = max(1000, (int) config('services.go_api.productivity_max_records', 250000));
        $this->cacheSeconds = max(0, (int) config('services.go_api.productivity_cache_seconds', 90));

        $configuredShifts = config('services.go_api.productivity_shifts', []);

        if (is_array($configuredShifts) && !empty($configuredShifts)) {
            $this->shiftCatalog = array_merge($this->shiftCatalog, $configuredShifts);
        }

        $scopeSuffixes = config('services.go_api.productivity_scope_suffixes', []);

        if (!is_array($scopeSuffixes)) {
            $scopeSuffixes = [];
        }

        $this->serviceScopes = [
            'all' => [
                'label' => 'Tablero general',
                'endpoint_suffix' => null,
            ],
            'consulta_externa' => [
                'label' => 'Consulta externa',
                'endpoint_suffix' => isset($scopeSuffixes['consulta_externa'])
                    ? (int) $scopeSuffixes['consulta_externa']
                    : 0,
            ],
            'hospitalizacion' => [
                'label' => 'Hospitalización',
                'endpoint_suffix' => isset($scopeSuffixes['hospitalizacion'])
                    ? (int) $scopeSuffixes['hospitalizacion']
                    : 1,
            ],
        ];
    }

    public function index(Request $request, GoApiService $goApiService)
    {
        $defaultFrom = now()->toDateString();
        $defaultTo = $defaultFrom;

        [$dateFrom, $dateTo, $rangeWasAdjusted] = $this->normalizeProductivityDateRange(
            $request->get('date_from', $defaultFrom),
            $request->get('date_to', $defaultTo)
        );

        $serviceScope = (string) $request->get('service_scope', 'all');

        if (!array_key_exists($serviceScope, $this->serviceScopes)) {
            $serviceScope = 'all';
        }

        $endpointSuffix = $this->serviceScopes[$serviceScope]['endpoint_suffix'];

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'shift' => $request->get('shift'),
            'user_key' => $request->get('user_key'),
            'area' => $request->get('area'),
            'service_scope' => $serviceScope,
            'service_ids' => [],
        ];

        $periods = $this->monthPeriods($dateFrom, $dateTo);
        $apiFilters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'periods' => $periods,
            'service_scope' => $serviceScope,
            'endpoint_suffix' => $endpointSuffix,
        ];

        if (function_exists('set_time_limit')) {
            @set_time_limit(max(180, (int) config('services.go_api.productivity_timeout', 180) + 30));
        }

        $cacheKey = 'productivity-dashboard:' . (session('vcare_demo_session') === true ? 'demo:' : 'real:') . 'v12-separated-endpoints:' . sha1((string) json_encode([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'shift' => $filters['shift'],
            'user_key' => $filters['user_key'],
            'area' => $filters['area'],
            'service_scope' => $serviceScope,
            'endpoint_suffix' => $endpointSuffix,
            'opportunity_limit' => $this->opportunityLimitMinutes,
            'max_records' => $this->maxRecords,
        ]));

        $dashboardData = null;

        if ($this->cacheSeconds > 0) {
            try {
                $dashboardData = Cache::get($cacheKey);
            } catch (\Throwable $exception) {
                // El tablero debe seguir funcionando aunque el almacén de caché no esté disponible.
                $dashboardData = null;
            }
        }

        $usingMock = false;
        $messages = [];

        if (!is_array($dashboardData)) {
            // Productividad trabaja por día con tres endpoints separados:
            // General:         GET /productivity/{YYYYMMDD}
            // Consulta externa GET /productivity/{YYYYMMDD}/0
            // Hospitalización: GET /productivity/{YYYYMMDD}/1
            $apiResponse = $goApiService->getProductivityByDate(
                $dateFrom,
                $endpointSuffix
            );

            if ($redirect = $this->redirectIfApiSessionExpired()) {
                return $redirect;
            }

            if (!is_array($apiResponse)) {
                $usingMock = true;
                $messages[] = $this->apiErrorMessage(
                    $goApiService,
                    'No fue posible consultar la productividad del día y servicio seleccionados.'
                );
                $records = $this->mockProductivityRecords();
            } else {
                $records = $this->extractRecordList($apiResponse);
            }

            $dashboardData = $this->buildProductivityDashboardData($records, $filters);

            // Liberar cuanto antes la respuesta original y su estructura JSON.
            unset($records, $apiResponse);
            gc_collect_cycles();

            if (!$usingMock && $this->cacheSeconds > 0) {
                try {
                    Cache::put($cacheKey, $dashboardData, now()->addSeconds($this->cacheSeconds));
                } catch (\Throwable $exception) {
                    // La caché es una optimización, no un requisito para mostrar el tablero.
                }
            }
        }

        if ($rangeWasAdjusted) {
            $messages[] = 'Productividad funciona por día. Se tomó únicamente la fecha '
                . Carbon::parse($dateFrom)->format('d/m/Y') . '.';
        }

        if (($dashboardData['truncated'] ?? false) === true) {
            $messages[] = 'La respuesta superó el límite de seguridad de '
                . number_format($this->maxRecords)
                . ' registros. Se muestran resultados parciales para evitar que PHP se quede sin memoria.';
        }

        $errorMessage = !empty($messages)
            ? implode(' ', array_values(array_unique(array_filter($messages))))
            : null;

        return view('dashboards.productividad', [
            'filters' => $filters,
            'apiFilters' => $apiFilters,
            'serviceScopes' => $this->serviceScopes,
            'activeServiceScope' => $serviceScope,
            'activeServiceLabel' => $this->serviceScopes[$serviceScope]['label'],
            'activeServiceEndpointSuffix' => $endpointSuffix,
            'queryPeriods' => $periods,
            'shiftCatalog' => $this->shiftCatalog,
            'availableUsers' => $dashboardData['availableUsers'],
            'availableAreas' => $dashboardData['availableAreas'],
            'allRowsCount' => $dashboardData['allRowsCount'],
            'filteredRowsCount' => $dashboardData['filteredRowsCount'],
            'recordRange' => $dashboardData['recordRange'],
            'filteredRange' => $dashboardData['filteredRange'],
            'summary' => $dashboardData['summary'],
            'shiftSummary' => $dashboardData['shiftSummary'],
            'userSummary' => $dashboardData['userSummary'],
            'areaSummary' => $dashboardData['areaSummary'],
            'usingMock' => $usingMock,
            'errorMessage' => $errorMessage,
            'opportunityLimitMinutes' => $this->opportunityLimitMinutes,
            'warningLimitMinutes' => $this->warningLimitMinutes,
        ]);
    }

    /**
     * Vista independiente para consultar venopunciones duplicadas.
     * La información se carga por AJAX para no retrasar la apertura del tablero.
     */
    public function venopunctures(Request $request)
    {
        [$dateFrom, $dateTo] = $this->normalizeDateRange(
            $request->get('date_from', now()->startOfMonth()->toDateString()),
            $request->get('date_to', now()->toDateString())
        );

        $periods = $this->monthPeriods($dateFrom, $dateTo);

        return view('dashboards.venopunciones', [
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'periods' => $periods,
            'periodLabels' => array_map(
                fn (string $period) => $this->formatPeriodLabel($period),
                $periods
            ),
        ]);
    }

    /**
     * Carga bajo demanda la sección de venopunciones duplicadas para no
     * retrasar la apertura del tablero principal.
     */
    public function duplicatedVenopuncturesData(Request $request, GoApiService $goApiService)
    {
        [$dateFrom, $dateTo] = $this->normalizeDateRange(
            $request->get('date_from', now()->startOfMonth()->toDateString()),
            $request->get('date_to', now()->toDateString())
        );

        $periods = $this->monthPeriods($dateFrom, $dateTo);

        if (function_exists('set_time_limit')) {
            @set_time_limit(max(180, (count($periods) * 180) + 30));
        }

        $payloads = [];
        $failedPeriods = [];

        foreach ($periods as $period) {
            $response = $goApiService->getDuplicatedVenopuncturesByMonth($period);

            if (session('api_session_expired')) {
                return response()->json([
                    'success' => false,
                    'sessionExpired' => true,
                    'message' => session('api_session_message', 'Tu sesión expiró.'),
                    'redirect' => route('login'),
                ], 401);
            }

            if (!is_array($response)) {
                $failedPeriods[] = $period;
                continue;
            }

            foreach ($this->extractRecordList($response) as $patient) {
                if (is_array($patient)) {
                    $payloads[] = [
                        'period' => $period,
                        'patient' => $patient,
                    ];
                }
            }
        }

        if (empty($payloads) && !empty($failedPeriods)) {
            return response()->json([
                'success' => false,
                'message' => $this->apiErrorMessage(
                    $goApiService,
                    'No fue posible consultar las venopunciones duplicadas.'
                ),
                'periods' => $periods,
            ], 502);
        }

        $result = $this->buildDuplicatedVenopunctures($payloads);

        return response()->json([
            'success' => true,
            'periods' => $periods,
            'periodLabels' => array_map(fn (string $period) => $this->formatPeriodLabel($period), $periods),
            'warning' => !empty($failedPeriods)
                ? 'No respondieron los periodos: ' . implode(', ', $failedPeriods) . '.'
                : null,
            'summary' => $result['summary'],
            'rows' => $result['rows'],
        ]);
    }

    /**
     * Normaliza la consulta para que siempre corresponda a un solo día.
     * Aunque se reciban dos fechas distintas por URL, se conserva date_from
     * y date_to se iguala a la misma fecha.
     */
    private function normalizeProductivityDateRange($dateFrom, $dateTo): array
    {
        try {
            $selectedDate = Carbon::parse($dateFrom ?: $dateTo ?: now())->startOfDay();
        } catch (\Throwable $exception) {
            $selectedDate = now()->startOfDay();
        }

        $selected = $selectedDate->toDateString();

        try {
            $requestedTo = Carbon::parse($dateTo ?: $selected)->toDateString();
        } catch (\Throwable $exception) {
            $requestedTo = $selected;
        }

        return [$selected, $selected, $requestedTo !== $selected];
    }

    /**
     * Recorre la respuesta una sola vez y construye únicamente los agregados
     * que necesita la vista. No conserva una copia normalizada por examen.
     */
    private function buildProductivityDashboardData(array $records, array $filters): array
    {
        $availableUsers = [];
        $availableAreas = [];
        $allRowsCount = 0;
        $filteredRowsCount = 0;
        $truncated = false;
        $recordRange = ['first' => null, 'last' => null];
        $filteredRange = ['first' => null, 'last' => null];
        $observedServiceIds = [];

        $summary = [
            'orders' => [],
            'examsTotal' => 0,
            'examsInRange' => 0,
            'examsWarning' => 0,
            'examsCritical' => 0,
            'examsOutRange' => 0,
            'opTimeSum' => 0,
            'opTimeCount' => 0,
        ];

        $shiftSummary = [];
        foreach ($this->shiftCatalog as $shiftKey => $shiftLabel) {
            $shiftSummary[$shiftKey] = [
                'shift' => $shiftKey,
                'label' => $shiftLabel,
                'orders' => [],
                'ordersTotal' => 0,
                'examsTotal' => 0,
                'examsInRange' => 0,
                'examsWarning' => 0,
                'examsCritical' => 0,
                'examsOutRange' => 0,
            ];
        }

        $userSummary = [];
        $areaSummary = [];

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $tests = $record['tests'] ?? $record['exams'] ?? $record['studies'] ?? null;

            if (is_array($tests) && !empty($tests)) {
                foreach ($tests as $test) {
                    if (!is_array($test)) {
                        continue;
                    }

                    $row = $this->normalizeRecord($test, $record);

                    if ($row !== null) {
                        if ($row['serviceId'] !== null) {
                            $observedServiceIds[(int) $row['serviceId']] = true;
                        }

                        $this->accumulateProductivityRow(
                            $row,
                            $filters,
                            $availableUsers,
                            $availableAreas,
                            $allRowsCount,
                            $filteredRowsCount,
                            $recordRange,
                            $filteredRange,
                            $summary,
                            $shiftSummary,
                            $userSummary,
                            $areaSummary
                        );
                    }

                    if ($allRowsCount >= $this->maxRecords) {
                        $truncated = true;
                        break 2;
                    }
                }

                continue;
            }

            $row = $this->normalizeRecord($record, $record);

            if ($row !== null) {
                if ($row['serviceId'] !== null) {
                    $observedServiceIds[(int) $row['serviceId']] = true;
                }

                $this->accumulateProductivityRow(
                    $row,
                    $filters,
                    $availableUsers,
                    $availableAreas,
                    $allRowsCount,
                    $filteredRowsCount,
                    $recordRange,
                    $filteredRange,
                    $summary,
                    $shiftSummary,
                    $userSummary,
                    $areaSummary
                );
            }

            if ($allRowsCount >= $this->maxRecords) {
                $truncated = true;
                break;
            }
        }

        uasort($availableUsers, fn (array $a, array $b) => strcmp($a['label'], $b['label']));
        $availableAreas = array_keys($availableAreas);
        sort($availableAreas, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'availableUsers' => array_values($availableUsers),
            'availableAreas' => $availableAreas,
            'allRowsCount' => $allRowsCount,
            'filteredRowsCount' => $filteredRowsCount,
            'recordRange' => $this->finalizeRecordRange($recordRange),
            'filteredRange' => $this->finalizeRecordRange($filteredRange),
            'summary' => $this->finalizeMainSummary($summary),
            'shiftSummary' => $this->finalizeShiftSummary($shiftSummary),
            'userSummary' => $this->finalizeUserSummary($userSummary),
            'areaSummary' => $this->finalizeAreaSummary($areaSummary),
            'truncated' => $truncated,
            'observedServiceIds' => array_values(array_map('intval', array_keys($observedServiceIds))),
        ];
    }

    private function accumulateProductivityRow(
        array $row,
        array $filters,
        array &$availableUsers,
        array &$availableAreas,
        int &$allRowsCount,
        int &$filteredRowsCount,
        array &$recordRange,
        array &$filteredRange,
        array &$summary,
        array &$shiftSummary,
        array &$userSummary,
        array &$areaSummary
    ): void {
        // El endpoint ya viene separado por tipo de atención. No se vuelve a
        // clasificar localmente para evitar mezclar Hospitalización y Consulta externa.

        $allRowsCount++;
        $availableUsers[$row['userKey']] = [
            'key' => $row['userKey'],
            'label' => $row['userId'] . ' - ' . $row['userName'],
        ];
        $availableAreas[$row['area']] = true;
        $this->updateRecordRange($recordRange, $row['validatedAtRaw'] ?? $row['createdAtRaw'] ?? null);

        if (!$this->rowMatchesFilters($row, $filters)) {
            return;
        }

        $filteredRowsCount++;
        $this->updateRecordRange($filteredRange, $row['validatedAtRaw'] ?? $row['createdAtRaw'] ?? null);

        $order = $row['order'];
        $opTime = $row['opTime'];

        if (!array_key_exists($order, $summary['orders'])) {
            $summary['orders'][$order] = $opTime;
        } elseif ($opTime !== null && ($summary['orders'][$order] === null || $opTime > $summary['orders'][$order])) {
            $summary['orders'][$order] = $opTime;
        }

        $summary['examsTotal']++;
        if ($row['timeBucket'] === 'in_range') {
            $summary['examsInRange']++;
        } elseif ($row['timeBucket'] === 'warning') {
            $summary['examsWarning']++;
            $summary['examsOutRange']++;
        } else {
            $summary['examsCritical']++;
            $summary['examsOutRange']++;
        }

        if (is_numeric($opTime)) {
            $summary['opTimeSum'] += $opTime;
            $summary['opTimeCount']++;
        }

        $shift = isset($shiftSummary[$row['shift']]) ? $row['shift'] : 'unknown';
        $shiftSummary[$shift]['orders'][$order] = true;
        $shiftSummary[$shift]['examsTotal']++;
        if ($row['timeBucket'] === 'in_range') {
            $shiftSummary[$shift]['examsInRange']++;
        } elseif ($row['timeBucket'] === 'warning') {
            $shiftSummary[$shift]['examsWarning']++;
            $shiftSummary[$shift]['examsOutRange']++;
        } else {
            $shiftSummary[$shift]['examsCritical']++;
            $shiftSummary[$shift]['examsOutRange']++;
        }

        $userKey = $row['userKey'];
        if (!isset($userSummary[$userKey])) {
            $userSummary[$userKey] = [
                'userKey' => $userKey,
                'userId' => $row['userId'],
                'userName' => $row['userName'],
                'orders' => [],
                'ordersTotal' => 0,
                'examsTotal' => 0,
                'examsInRange' => 0,
                'examsWarning' => 0,
                'examsCritical' => 0,
                'examsOutRange' => 0,
                'opTimeSum' => 0,
                'opTimeCount' => 0,
                'averageOpTime' => 0,
            ];
        }

        $userSummary[$userKey]['orders'][$order] = true;
        $userSummary[$userKey]['examsTotal']++;
        if ($row['timeBucket'] === 'in_range') {
            $userSummary[$userKey]['examsInRange']++;
        } elseif ($row['timeBucket'] === 'warning') {
            $userSummary[$userKey]['examsWarning']++;
            $userSummary[$userKey]['examsOutRange']++;
        } else {
            $userSummary[$userKey]['examsCritical']++;
            $userSummary[$userKey]['examsOutRange']++;
        }
        if (is_numeric($opTime)) {
            $userSummary[$userKey]['opTimeSum'] += $opTime;
            $userSummary[$userKey]['opTimeCount']++;
        }

        $area = $row['area'];
        if (!isset($areaSummary[$area])) {
            $areaSummary[$area] = [
                'area' => $area,
                'orders' => [],
                'ordersTotal' => 0,
                'examsTotal' => 0,
                'examsInRange' => 0,
                'examsWarning' => 0,
                'examsCritical' => 0,
                'examsOutRange' => 0,
            ];
        }

        $areaSummary[$area]['orders'][$order] = true;
        $areaSummary[$area]['examsTotal']++;
        if ($row['timeBucket'] === 'in_range') {
            $areaSummary[$area]['examsInRange']++;
        } elseif ($row['timeBucket'] === 'warning') {
            $areaSummary[$area]['examsWarning']++;
            $areaSummary[$area]['examsOutRange']++;
        } else {
            $areaSummary[$area]['examsCritical']++;
            $areaSummary[$area]['examsOutRange']++;
        }
    }

    private function rowMatchesFilters(array $row, array $filters): bool
    {
        $rowDate = $row['validatedDate'] ?? null;

        if (!empty($filters['date_from']) && $rowDate < $filters['date_from']) {
            return false;
        }

        if (!empty($filters['date_to']) && $rowDate > $filters['date_to']) {
            return false;
        }

        if (!empty($filters['shift']) && $row['shift'] !== $filters['shift']) {
            return false;
        }

        if (!empty($filters['user_key']) && $row['userKey'] !== $filters['user_key']) {
            return false;
        }

        if (!empty($filters['area']) && $row['area'] !== $filters['area']) {
            return false;
        }

        return $this->rowMatchesServiceScope($row, $filters);
    }

    /**
     * Conservado por compatibilidad con versiones previas del controlador.
     * La separación actual se realiza en backend mediante el sufijo /0 o /1.
     */
    private function rowMatchesServiceScope(array $row, array $filters): bool
    {
        return true;
    }

    private function finalizeMainSummary(array $summary): array
    {
        $ordersInRange = 0;
        $ordersOutRange = 0;

        foreach ($summary['orders'] as $maxOpTime) {
            if ($maxOpTime !== null && $maxOpTime <= $this->opportunityLimitMinutes) {
                $ordersInRange++;
            } else {
                $ordersOutRange++;
            }
        }

        $ordersTotal = count($summary['orders']);
        $examsTotal = $summary['examsTotal'];

        return [
            'ordersTotal' => $ordersTotal,
            'ordersInRange' => $ordersInRange,
            'ordersOutRange' => $ordersOutRange,
            'ordersOutRangePercent' => $this->percent($ordersOutRange, $ordersTotal),
            'examsTotal' => $examsTotal,
            'examsInRange' => $summary['examsInRange'],
            'examsWarning' => $summary['examsWarning'],
            'examsCritical' => $summary['examsCritical'],
            'examsOutRange' => $summary['examsOutRange'],
            'examsInRangePercent' => $this->percent($summary['examsInRange'], $examsTotal),
            'examsWarningPercent' => $this->percent($summary['examsWarning'], $examsTotal),
            'examsCriticalPercent' => $this->percent($summary['examsCritical'], $examsTotal),
            'examsOutRangePercent' => $this->percent($summary['examsOutRange'], $examsTotal),
            'averageOpTime' => $summary['opTimeCount'] > 0
                ? (int) round($summary['opTimeSum'] / $summary['opTimeCount'])
                : 0,
        ];
    }

    private function finalizeShiftSummary(array $summary): array
    {
        foreach ($summary as $key => $item) {
            $summary[$key]['ordersTotal'] = count($item['orders']);
            unset($summary[$key]['orders']);
        }

        return array_values($summary);
    }

    private function finalizeUserSummary(array $summary): array
    {
        foreach ($summary as $key => $item) {
            $summary[$key]['ordersTotal'] = count($item['orders']);
            $summary[$key]['averageOpTime'] = $item['opTimeCount'] > 0
                ? (int) round($item['opTimeSum'] / $item['opTimeCount'])
                : 0;
            $summary[$key]['examsInRangePercent'] = $this->percent($item['examsInRange'], $item['examsTotal']);
            $summary[$key]['examsWarningPercent'] = $this->percent($item['examsWarning'], $item['examsTotal']);
            $summary[$key]['examsCriticalPercent'] = $this->percent($item['examsCritical'], $item['examsTotal']);
            $summary[$key]['examsOutRangePercent'] = $this->percent($item['examsOutRange'], $item['examsTotal']);
            unset($summary[$key]['orders'], $summary[$key]['opTimeSum'], $summary[$key]['opTimeCount']);
        }

        usort($summary, fn (array $a, array $b) => $b['examsTotal'] <=> $a['examsTotal']);

        return array_values($summary);
    }

    private function finalizeAreaSummary(array $summary): array
    {
        foreach ($summary as $key => $item) {
            $summary[$key]['ordersTotal'] = count($item['orders']);
            $summary[$key]['examsInRangePercent'] = $this->percent($item['examsInRange'], $item['examsTotal']);
            $summary[$key]['examsWarningPercent'] = $this->percent($item['examsWarning'], $item['examsTotal']);
            $summary[$key]['examsCriticalPercent'] = $this->percent($item['examsCritical'], $item['examsTotal']);
            $summary[$key]['examsOutRangePercent'] = $this->percent($item['examsOutRange'], $item['examsTotal']);
            unset($summary[$key]['orders']);
        }

        usort($summary, fn (array $a, array $b) => $b['examsTotal'] <=> $a['examsTotal']);

        return array_values($summary);
    }

    private function updateRecordRange(array &$range, $rawDate): void
    {
        $timestamp = $this->timestamp($rawDate);

        if ($timestamp === null) {
            return;
        }

        if ($range['first'] === null || $timestamp < $range['first']) {
            $range['first'] = $timestamp;
        }

        if ($range['last'] === null || $timestamp > $range['last']) {
            $range['last'] = $timestamp;
        }
    }

    private function finalizeRecordRange(array $range): array
    {
        if ($range['first'] === null || $range['last'] === null) {
            return [
                'first' => '-',
                'last' => '-',
                'label' => 'Sin registros en el rango seleccionado',
                'days' => 0,
            ];
        }

        $first = Carbon::createFromTimestamp($range['first'])->startOfDay();
        $last = Carbon::createFromTimestamp($range['last'])->startOfDay();

        return [
            'first' => $first->format('d/m/Y'),
            'last' => $last->format('d/m/Y'),
            'label' => $first->format('d/m/Y') . ' a ' . $last->format('d/m/Y'),
            'days' => max($first->diffInDays($last) + 1, 1),
        ];
    }

    private function timestamp($value): ?int
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : $timestamp;
    }

    private function normalizeDateRange($dateFrom, $dateTo): array
    {
        try {
            $from = Carbon::parse($dateFrom)->startOfDay();
        } catch (\Throwable $exception) {
            $from = now()->startOfMonth()->startOfDay();
        }

        try {
            $to = Carbon::parse($dateTo)->startOfDay();
        } catch (\Throwable $exception) {
            $to = now()->startOfDay();
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->toDateString(), $to->toDateString()];
    }

    private function monthPeriods(string $dateFrom, string $dateTo): array
    {
        $cursor = Carbon::parse($dateFrom)->startOfMonth();
        $end = Carbon::parse($dateTo)->startOfMonth();
        $periods = [];

        while ($cursor->lessThanOrEqualTo($end)) {
            $periods[] = $cursor->format('Ym');
            $cursor->addMonth();
        }

        return $periods;
    }

    private function formatPeriodLabel(string $period): string
    {
        if (!preg_match('/^(\\d{4})(\\d{2})$/', $period, $matches)) {
            return $period;
        }

        $months = [
            '01' => 'Enero',
            '02' => 'Febrero',
            '03' => 'Marzo',
            '04' => 'Abril',
            '05' => 'Mayo',
            '06' => 'Junio',
            '07' => 'Julio',
            '08' => 'Agosto',
            '09' => 'Septiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre',
        ];

        return ($months[$matches[2]] ?? $matches[2]) . ' ' . $matches[1];
    }

    private function buildDuplicatedVenopunctures(array $payloads): array
    {
        $rows = [];
        $patients = [];
        $orders = [];
        $profiles = [];
        $duplicatedTestsTotal = 0;

        foreach ($payloads as $payload) {
            $period = (string) ($payload['period'] ?? '');
            $patient = $payload['patient'] ?? null;

            if (!is_array($patient)) {
                continue;
            }

            $patientNumber = trim((string) ($patient['patientNumber'] ?? ''));
            $patientId = trim((string) ($patient['patientID'] ?? $patient['patientId'] ?? ''));
            $patientName = trim(implode(' ', array_filter([
                trim((string) ($patient['patientName'] ?? '')),
                trim((string) ($patient['fatherLastName'] ?? '')),
                trim((string) ($patient['motherLastName'] ?? '')),
            ])));

            $patientKey = $patientId !== ''
                ? $patientId
                : ($patientNumber !== '' ? $patientNumber : $patientName);

            if ($patientKey !== '') {
                $patients[$patientKey] = true;
            }

            $patientOrders = $patient['orders'] ?? [];

            if (!is_array($patientOrders)) {
                continue;
            }

            foreach ($patientOrders as $order) {
                if (!is_array($order)) {
                    continue;
                }

                $orderNumber = trim((string) ($order['orderNumber'] ?? $order['order'] ?? ''));
                if ($orderNumber !== '') {
                    $orders[$period . '__' . $orderNumber] = true;
                }

                $orderProfiles = $order['profiles'] ?? [];
                if (!is_array($orderProfiles)) {
                    continue;
                }

                foreach ($orderProfiles as $profile) {
                    if (!is_array($profile)) {
                        continue;
                    }

                    $duplicatedTests = $profile['duplicatedTests'] ?? [];
                    if (!is_array($duplicatedTests) || empty($duplicatedTests)) {
                        continue;
                    }

                    $profileId = trim((string) ($profile['profileID'] ?? $profile['profileId'] ?? ''));
                    $profileName = trim((string) ($profile['profileName'] ?? ''));
                    $profileAbbreviation = trim((string) ($profile['profileAbbreviation'] ?? ''));
                    $profileKey = $period . '__' . $orderNumber . '__' . ($profileId !== '' ? $profileId : $profileName);
                    $profiles[$profileKey] = true;

                    $tests = [];
                    foreach ($duplicatedTests as $test) {
                        if (!is_array($test)) {
                            continue;
                        }

                        $testName = trim((string) ($test['testName'] ?? ''));
                        $testAbbreviation = trim((string) ($test['testAbbreviation'] ?? ''));

                        $tests[] = [
                            'id' => (string) ($test['testID'] ?? $test['testId'] ?? ''),
                            'abbreviation' => $testAbbreviation,
                            'name' => $testName !== '' ? $testName : 'Estudio sin nombre',
                            'label' => trim(($testAbbreviation !== '' ? $testAbbreviation . ' · ' : '') . ($testName !== '' ? $testName : 'Estudio sin nombre')),
                        ];
                    }

                    if (empty($tests)) {
                        continue;
                    }

                    $duplicatedTestsTotal += count($tests);

                    $rows[] = [
                        'period' => $period,
                        'periodLabel' => $this->formatPeriodLabel($period),
                        'patientNumber' => $patientNumber !== '' ? $patientNumber : '-',
                        'patientId' => $patientId !== '' ? $patientId : '-',
                        'patientName' => $patientName !== '' ? $patientName : 'Paciente sin nombre',
                        'orderNumber' => $orderNumber !== '' ? $orderNumber : '-',
                        'profileId' => $profileId !== '' ? $profileId : '-',
                        'profileAbbreviation' => $profileAbbreviation,
                        'profileName' => $profileName !== '' ? $profileName : 'Perfil sin nombre',
                        'testsCount' => count($tests),
                        'tests' => $tests,
                    ];
                }
            }
        }

        usort($rows, function (array $a, array $b) {
            $periodCompare = strcmp($b['period'], $a['period']);
            if ($periodCompare !== 0) {
                return $periodCompare;
            }

            return strcmp((string) $b['orderNumber'], (string) $a['orderNumber']);
        });

        return [
            'summary' => [
                'patients' => count($patients),
                'orders' => count($orders),
                'profiles' => count($profiles),
                'duplicatedTests' => $duplicatedTestsTotal,
                'rows' => count($rows),
            ],
            'rows' => $rows,
        ];
    }

    private function extractRecordList(array $rawRecords): array
    {
        $rows = [];

        $appendRecords = function (array $records, array $context = []) use (&$rows): void {
            foreach ($records as $record) {
                if (!is_array($record)) {
                    continue;
                }

                $rows[] = !empty($context)
                    ? array_merge($context, $record)
                    : $record;
            }
        };

        // Respuesta directa: [{...}, {...}]
        if (array_is_list($rawRecords)) {
            $containsGroups = false;

            foreach ($rawRecords as $group) {
                if (!is_array($group)) {
                    continue;
                }

                $nested = null;
                foreach (['data', 'records', 'items', 'results', 'productivity'] as $key) {
                    if (isset($group[$key]) && is_array($group[$key])) {
                        $nested = $group[$key];
                        break;
                    }
                }

                if ($nested === null) {
                    continue;
                }

                $containsGroups = true;
                $context = [];

                foreach (['serviceID', 'serviceId', 'service_id', 'serviceName', 'service_name', 'service'] as $field) {
                    if (array_key_exists($field, $group)) {
                        $context[$field] = $group[$field];
                    }
                }

                $appendRecords($nested, $context);
            }

            if ($containsGroups) {
                return $rows;
            }

            return $rawRecords;
        }

        // Respuesta envuelta: {data:[...]}, {records:[...]}, etc.
        foreach (['data', 'records', 'items', 'results', 'productivity'] as $key) {
            if (isset($rawRecords[$key]) && is_array($rawRecords[$key])) {
                return $this->extractRecordList($rawRecords[$key]);
            }
        }

        // Respuesta agrupada por ID de servicio:
        // {"0":[...], "1":[...], "4":[...]}
        foreach ($rawRecords as $key => $records) {
            if (!is_array($records)) {
                continue;
            }

            $context = is_numeric($key) ? ['serviceID' => (int) $key] : [];
            $appendRecords($records, $context);
        }

        return !empty($rows) ? $rows : $rawRecords;
    }

    private function normalizeRows(array $records): array
    {
        $rows = [];

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $tests = $record['tests'] ?? $record['exams'] ?? $record['studies'] ?? null;

            if (is_array($tests) && !empty($tests)) {
                foreach ($tests as $test) {
                    if (!is_array($test)) {
                        continue;
                    }

                    $rows[] = $this->normalizeRecord(array_merge($record, $test), $record);
                }

                continue;
            }

            $rows[] = $this->normalizeRecord($record, $record);
        }

        $rows = array_values(array_filter($rows));

        usort($rows, function (array $a, array $b) {
            return strcmp((string) $b['validatedAtRaw'], (string) $a['validatedAtRaw']);
        });

        return $rows;
    }

    private function normalizeRecord(array $record, array $parentRecord): ?array
    {
        $orderNumber = trim((string) $this->getFieldFromSources($record, $parentRecord, [
            'order', 'Order', 'ORDER', 'orden', 'Orden', 'ORDEN', 'orderNumber', 'order_number', 'numOrden', 'numeroOrden', 'folio',
        ], ''));

        if ($orderNumber === '') {
            return null;
        }

        // El endpoint puede devolver estudios en varios estados. Para este tablero
        // solo se consideran los registros cuyo estado actual sea validado.
        // Catálogo recibido: 4 = validada y 6 = validada.
        $statusRaw = $this->getFieldFromSources($record, $parentRecord, [
            'status', 'Status', 'STATUS', 'testStatus', 'test_status', 'studyStatus',
            'resultStatus', 'estado', 'Estado', 'estatus', 'Estatus',
        ], null);

        $status = is_numeric($statusRaw) ? (int) $statusRaw : null;

        if ($status === null || !in_array($status, $this->validatedStatuses, true)) {
            return null;
        }

        $type = strtoupper(trim((string) $this->getFieldFromSources($record, $parentRecord, [
            'type', 'Type', 'TYPE', 'priority', 'Priority', 'prioridad', 'Prioridad', 'serviceType', 'tipo', 'Tipo',
        ], '')));
        $type = in_array($type, ['R', 'S'], true) ? $type : '-';

        $serviceIdRaw = $this->getFieldFromSources($record, $parentRecord, [
            'serviceID', 'serviceId', 'service_id', 'ServiceID', 'SERVICEID',
            'originServiceID', 'origin_service_id', 'idServicio', 'servicioId',
        ], null);
        $serviceId = is_numeric($serviceIdRaw) ? (int) $serviceIdRaw : null;

        $serviceName = trim((string) $this->getFieldFromSources($record, $parentRecord, [
            'serviceName', 'service_name', 'ServiceName', 'SERVICE_NAME',
            'service', 'Service', 'servicio', 'Servicio', 'originService',
        ], ''));
        $serviceGroup = $this->normalizeServiceGroup($serviceName);

        $area = strtoupper(trim((string) $this->getFieldFromSources($record, $parentRecord, [
            'area', 'Area', 'AREA', 'department', 'Department', 'departamento', 'Departamento', 'section', 'seccion', 'Seccion', 'sección', 'Sección',
        ], '')));

        $testId = trim((string) $this->getFieldFromSources($record, $parentRecord, [
            'testID', 'testId', 'TestID', 'TESTID', 'test_id', 'id_estudio', 'studyId', 'examId', 'examID', 'idExamen',
        ], ''));

        $testName = trim((string) $this->getFieldFromSources($record, $parentRecord, [
            'testName', 'TestName', 'TESTNAME', 'test_name', 'name', 'Name', 'studyName', 'examName', 'nombreEstudio', 'estudio', 'examen',
        ], 'Estudio sin nombre'));

        $userKeyFromApi = trim((string) $this->getFieldFromSources($record, $parentRecord, [
            'userKey', 'user_key', 'UserKey', 'validatorKey', 'validatedByKey', 'usuarioKey',
        ], ''));

        $userId = trim((string) $this->getFieldFromSources($record, $parentRecord, [
            'userId', 'userID', 'user_id', 'UserID', 'USERID', 'validatorId', 'validatedById', 'idUser', 'usuarioId', 'idUsuario', 'user',
        ], ''));

        $userName = trim((string) $this->getFieldFromSources($record, $parentRecord, [
            'userName', 'user_name', 'UserName', 'USERNAME', 'validatorName', 'validatedByName', 'nameUser', 'usuarioNombre', 'nombreUsuario', 'validatedBy', 'validator',
        ], ''));

        if ($userKeyFromApi !== '' && str_contains($userKeyFromApi, '__')) {
            [$apiUserId, $apiUserName] = array_pad(explode('__', $userKeyFromApi, 2), 2, '');
            $userId = $userId !== '' ? $userId : trim($apiUserId);
            $userName = $userName !== '' ? $userName : trim($apiUserName);
        }

        $userName = $userName !== '' ? $userName : 'Sin usuario';
        $userKey = $this->buildUserKey($userId, $userName);

        $shiftRaw = $this->getFieldFromSources($record, $parentRecord, [
            'shift', 'Shift', 'turno', 'Turno', 'workShift', 'work_shift', 'jornada', 'Jornada',
        ], 'unknown');
        $shift = $this->normalizeShift($shiftRaw);

        $createdAtRaw = $this->getFieldFromSources($record, $parentRecord, [
            'createdAt', 'created_at', 'created', 'Created', 'CREATED', 'orderCreatedAt', 'fechaCreacion', 'fecha_orden', 'fechaOrden',
        ], null);

        $validatedAtRaw = $this->getFieldFromSources($record, $parentRecord, [
            'validatedAt', 'validated_at', 'validated', 'Validated', 'VALIDATED', 'resultValidatedAt', 'fechaValidacion', 'fecha_validacion', 'validationDate',
        ], null);

        $opTime = $this->normalizeOpportunityTimeFromSources($record, $parentRecord, $createdAtRaw, $validatedAtRaw);
        $opTimeStatusRaw = $this->getFieldFromSources($record, $parentRecord, [
            'opTimeStatus', 'op_time_status', 'opportunityStatus', 'tiempoOportunidadStatus',
        ], null);
        $opTimeStatus = is_numeric($opTimeStatusRaw) ? (int) $opTimeStatusRaw : null;

        $timeBucket = match ($opTimeStatus) {
            1 => 'in_range',
            2 => 'warning',
            3 => 'critical',
            default => match (true) {
                $opTime !== null && $opTime <= $this->opportunityLimitMinutes => 'in_range',
                $opTime !== null && $opTime <= $this->warningLimitMinutes => 'warning',
                default => 'critical',
            },
        };

        $isInRange = $timeBucket === 'in_range';

        return [
            'order' => $orderNumber,
            'type' => $type,
            'typeLabel' => $type === 'S' ? 'Urgencia' : ($type === 'R' ? 'Rutina' : 'Sin prioridad'),
            'serviceId' => $serviceId,
            'serviceName' => $serviceName,
            'serviceGroup' => $serviceGroup,
            'area' => $area !== '' ? $area : 'SIN ÁREA',
            'testId' => $testId !== '' ? $testId : '-',
            'testName' => $testName,
            'userId' => $userId !== '' ? $userId : 'SIN_ID',
            'userName' => $userName,
            'userKey' => $userKey,
            'shift' => $shift,
            'shiftLabel' => $this->shiftCatalog[$shift] ?? 'Sin turno',
            'createdAtRaw' => $createdAtRaw,
            'validatedAtRaw' => $validatedAtRaw,
            'validatedDate' => $this->dateOnly($validatedAtRaw ?: $createdAtRaw),
            'opTime' => $opTime,
            'status' => $status,
            'opTimeStatus' => $opTimeStatus,
            'timeBucket' => $timeBucket,
            'isInRange' => $isInRange,
        ];
    }

    private function applyFilters(array $rows, array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $shift = $filters['shift'] ?? null;
        $userKey = $filters['user_key'] ?? null;
        $area = $filters['area'] ?? null;

        return array_values(array_filter($rows, function (array $row) use ($dateFrom, $dateTo, $shift, $userKey, $area) {
            $rowDate = $row['validatedDate'] ?? null;

            if (!empty($dateFrom) && $rowDate < $dateFrom) {
                return false;
            }

            if (!empty($dateTo) && $rowDate > $dateTo) {
                return false;
            }

            if (!empty($shift) && $row['shift'] !== $shift) {
                return false;
            }

            if (!empty($userKey) && $row['userKey'] !== $userKey) {
                return false;
            }

            if (!empty($area) && $row['area'] !== $area) {
                return false;
            }

            return true;
        }));
    }

    private function buildSummary(array $rows): array
    {
        $orders = $this->orderOpportunityMap($rows);

        $ordersInRange = 0;
        $ordersOutRange = 0;

        foreach ($orders as $order) {
            if (($order['maxOpTime'] ?? null) !== null && $order['maxOpTime'] <= $this->opportunityLimitMinutes) {
                $ordersInRange++;
            } else {
                $ordersOutRange++;
            }
        }

        $examsInRange = count(array_filter($rows, fn ($row) => $row['isInRange'] === true));
        $examsOutRange = max(count($rows) - $examsInRange, 0);

        $opTimes = array_values(array_filter(array_column($rows, 'opTime'), fn ($value) => is_numeric($value)));
        $averageOpTime = count($opTimes) > 0 ? round(array_sum($opTimes) / count($opTimes)) : 0;
        $ordersTotal = count($orders);
        $examsTotal = count($rows);

        return [
            'ordersTotal' => $ordersTotal,
            'ordersInRange' => $ordersInRange,
            'ordersOutRange' => $ordersOutRange,
            'ordersOutRangePercent' => $this->percent($ordersOutRange, $ordersTotal),
            'examsTotal' => $examsTotal,
            'examsInRange' => $examsInRange,
            'examsOutRange' => $examsOutRange,
            'examsInRangePercent' => $this->percent($examsInRange, $examsTotal),
            'examsOutRangePercent' => $this->percent($examsOutRange, $examsTotal),
            'averageOpTime' => $averageOpTime,
        ];
    }

    private function buildShiftSummary(array $rows): array
    {
        $summary = [];

        foreach ($this->shiftCatalog as $shiftKey => $shiftLabel) {
            $summary[$shiftKey] = [
                'shift' => $shiftKey,
                'label' => $shiftLabel,
                'orders' => [],
                'ordersTotal' => 0,
                'examsTotal' => 0,
                'examsInRange' => 0,
                'examsOutRange' => 0,
            ];
        }

        foreach ($rows as $row) {
            $shift = $row['shift'] ?? 'unknown';

            if (!isset($summary[$shift])) {
                $shift = 'unknown';
            }

            $summary[$shift]['orders'][$row['order']] = true;
            $summary[$shift]['examsTotal']++;

            if ($row['isInRange']) {
                $summary[$shift]['examsInRange']++;
            } else {
                $summary[$shift]['examsOutRange']++;
            }
        }

        foreach ($summary as $key => $item) {
            $summary[$key]['ordersTotal'] = count($item['orders']);
            unset($summary[$key]['orders']);
        }

        return array_values($summary);
    }

    private function buildUserSummary(array $rows): array
    {
        $summary = [];

        foreach ($rows as $row) {
            $key = $row['userKey'];

            if (!isset($summary[$key])) {
                $summary[$key] = [
                    'userKey' => $key,
                    'userId' => $row['userId'],
                    'userName' => $row['userName'],
                    'orders' => [],
                    'ordersTotal' => 0,
                    'examsTotal' => 0,
                    'examsInRange' => 0,
                    'examsOutRange' => 0,
                    'opTimeSum' => 0,
                    'opTimeCount' => 0,
                    'averageOpTime' => 0,
                ];
            }

            $summary[$key]['orders'][$row['order']] = true;
            $summary[$key]['examsTotal']++;

            if ($row['isInRange']) {
                $summary[$key]['examsInRange']++;
            } else {
                $summary[$key]['examsOutRange']++;
            }

            if (is_numeric($row['opTime'])) {
                $summary[$key]['opTimeSum'] += $row['opTime'];
                $summary[$key]['opTimeCount']++;
            }
        }

        foreach ($summary as $key => $item) {
            $summary[$key]['ordersTotal'] = count($item['orders']);
            $summary[$key]['averageOpTime'] = $item['opTimeCount'] > 0
                ? round($item['opTimeSum'] / $item['opTimeCount'])
                : 0;
            $summary[$key]['examsInRangePercent'] = $this->percent($summary[$key]['examsInRange'], $summary[$key]['examsTotal']);
            $summary[$key]['examsOutRangePercent'] = $this->percent($summary[$key]['examsOutRange'], $summary[$key]['examsTotal']);

            unset($summary[$key]['orders'], $summary[$key]['opTimeSum'], $summary[$key]['opTimeCount']);
        }

        usort($summary, fn ($a, $b) => $b['examsTotal'] <=> $a['examsTotal']);

        return array_values($summary);
    }

    private function buildAreaSummary(array $rows): array
    {
        $summary = [];

        foreach ($rows as $row) {
            $area = $row['area'];

            if (!isset($summary[$area])) {
                $summary[$area] = [
                    'area' => $area,
                    'orders' => [],
                    'ordersTotal' => 0,
                    'examsTotal' => 0,
                    'examsInRange' => 0,
                    'examsOutRange' => 0,
                ];
            }

            $summary[$area]['orders'][$row['order']] = true;
            $summary[$area]['examsTotal']++;

            if ($row['isInRange']) {
                $summary[$area]['examsInRange']++;
            } else {
                $summary[$area]['examsOutRange']++;
            }
        }

        foreach ($summary as $key => $item) {
            $summary[$key]['ordersTotal'] = count($item['orders']);
            $summary[$key]['examsInRangePercent'] = $this->percent($summary[$key]['examsInRange'], $summary[$key]['examsTotal']);
            $summary[$key]['examsOutRangePercent'] = $this->percent($summary[$key]['examsOutRange'], $summary[$key]['examsTotal']);
            unset($summary[$key]['orders']);
        }

        usort($summary, fn ($a, $b) => $b['examsTotal'] <=> $a['examsTotal']);

        return array_values($summary);
    }

    private function orderOpportunityMap(array $rows): array
    {
        $orders = [];

        foreach ($rows as $row) {
            $order = $row['order'];
            $opTime = $row['opTime'];

            if (!isset($orders[$order])) {
                $orders[$order] = [
                    'order' => $order,
                    'maxOpTime' => $opTime,
                ];

                continue;
            }

            if ($opTime !== null && ($orders[$order]['maxOpTime'] === null || $opTime > $orders[$order]['maxOpTime'])) {
                $orders[$order]['maxOpTime'] = $opTime;
            }
        }

        return $orders;
    }

    private function availableUsers(array $rows): array
    {
        $users = [];

        foreach ($rows as $row) {
            $users[$row['userKey']] = [
                'key' => $row['userKey'],
                'label' => $row['userId'] . ' - ' . $row['userName'],
            ];
        }

        uasort($users, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return array_values($users);
    }

    private function availableAreas(array $rows): array
    {
        $areas = array_values(array_unique(array_column($rows, 'area')));
        sort($areas, SORT_NATURAL | SORT_FLAG_CASE);

        return $areas;
    }

    private function buildRecordRange(array $rows): array
    {
        $dates = [];

        foreach ($rows as $row) {
            $rawDate = $row['validatedAtRaw'] ?? $row['createdAtRaw'] ?? null;

            if (empty($rawDate)) {
                continue;
            }

            try {
                $dates[] = Carbon::parse($rawDate);
            } catch (\Throwable $exception) {
                continue;
            }
        }

        if (empty($dates)) {
            return [
                'first' => '-',
                'last' => '-',
                'label' => 'Sin registros en el rango seleccionado',
                'days' => 0,
            ];
        }

        usort($dates, fn (Carbon $a, Carbon $b) => $a->timestamp <=> $b->timestamp);

        $first = $dates[0];
        $last = $dates[count($dates) - 1];

        return [
            'first' => $first->format('d/m/Y'),
            'last' => $last->format('d/m/Y'),
            'label' => $first->format('d/m/Y') . ' a ' . $last->format('d/m/Y'),
            'days' => max($first->diffInDays($last) + 1, 1),
        ];
    }

    private function percent(int $value, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($value / $total) * 100);
    }

    private function normalizeOpportunityTimeFromSources(
        array $record,
        array $parentRecord,
        $createdAtRaw,
        $validatedAtRaw
    ): ?int {
        $opTime = $this->getFieldFromSources($record, $parentRecord, [
            'opTime', 'op_time', 'opportunityTime', 'opportunity_time', 'tat', 'turnaroundTime', 'tiempoOportunidad', 'tiempo_oportunidad', 'minutes', 'minutos',
        ], null);

        if (is_numeric($opTime)) {
            return (int) round((float) $opTime);
        }

        $createdAt = $this->timestamp($createdAtRaw);
        $validatedAt = $this->timestamp($validatedAtRaw);

        if ($createdAt === null || $validatedAt === null || $validatedAt < $createdAt) {
            return null;
        }

        return (int) floor(($validatedAt - $createdAt) / 60);
    }

    private function normalizeServiceGroup(?string $serviceName): ?string
    {
        $value = $this->normalizeText((string) $serviceName);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, 'HOSPITAL')) {
            return 'hospitalizacion';
        }

        if (
            str_contains($value, 'CONSULTA')
            || str_contains($value, 'EXTERNA')
            || str_contains($value, 'AMBULATOR')
        ) {
            return 'consulta_externa';
        }

        return null;
    }

    private function normalizeShift($value): string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return 'unknown';
        }

        $normalized = $this->normalizeText($raw);

        $map = [
            'morning' => ['morning', 'matutino', 'm', 'manana', 'mañana', 'turno1', 'turno 1', '1'],
            'evening' => ['evening', 'afternoon', 'vespertino', 'v', 'tarde', 'turno2', 'turno 2', '2'],
            'night' => ['night', 'nocturno', 'n', 'noche', 'turno3', 'turno 3', '3'],
            'unknown' => ['unknown', 'sin turno', 'sinturno', 'na', 'n/a', '-'],
        ];

        foreach ($map as $shiftKey => $values) {
            foreach ($values as $acceptedValue) {
                if ($normalized === $this->normalizeText($acceptedValue)) {
                    return $shiftKey;
                }
            }
        }

        return array_key_exists($normalized, $this->shiftCatalog) ? $normalized : 'unknown';
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'ü' => 'u', 'Ü' => 'u',
        ]);

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function buildUserKey(string $userId, string $userName): string
    {
        $safeUserId = $userId !== '' ? $userId : 'SIN_ID';
        $safeUserName = $userName !== '' ? $userName : 'Sin usuario';

        return $safeUserId . '__' . $safeUserName;
    }

    private function getFieldFromSources(array $record, array $parentRecord, array $possibleKeys, $default = null)
    {
        foreach ($possibleKeys as $key) {
            if (array_key_exists($key, $record)) {
                return $record[$key];
            }

            if (array_key_exists($key, $parentRecord)) {
                return $parentRecord[$key];
            }
        }

        return $default;
    }

    private function getField(array $record, array $possibleKeys, $default = null)
    {
        foreach ($possibleKeys as $key) {
            if (array_key_exists($key, $record)) {
                return $record[$key];
            }
        }

        return $default;
    }

    private function opTimeClass(?int $minutes): string
    {
        if ($minutes === null) {
            return 'op-empty';
        }

        if ($minutes <= $this->opportunityLimitMinutes) {
            return 'op-ok';
        }

        if ($minutes <= $this->warningLimitMinutes) {
            return 'op-warning';
        }

        return 'op-danger';
    }

    private function formatDate($value): string
    {
        $timestamp = $this->timestamp($value);

        return $timestamp === null ? '-' : date('d/m/Y H:i', $timestamp);
    }

    private function dateOnly($value): string
    {
        $timestamp = $this->timestamp($value);

        return $timestamp === null ? now()->toDateString() : date('Y-m-d', $timestamp);
    }

    private function mockProductivityRecords(): array
    {
        $base = now();

        return [
            [
                'order' => '260608001',
                'type' => 'R',
                'area' => 'HEM',
                'testID' => 'BH001',
                'testName' => 'Biometría hemática',
                'userId' => '101',
                'userName' => 'Ana López',
                'shift' => 'morning',
                'createdAt' => $base->copy()->subHours(5)->format('Y-m-d H:i:s'),
                'validatedAt' => $base->copy()->subHours(4)->subMinutes(18)->format('Y-m-d H:i:s'),
                'status' => 4,
                'opTime' => 42,
            ],
            [
                'order' => '260608001',
                'type' => 'R',
                'area' => 'BIO',
                'testID' => 'GLU',
                'testName' => 'Glucosa',
                'userId' => '102',
                'userName' => 'Luis Hernández',
                'shift' => 'morning',
                'createdAt' => $base->copy()->subHours(5)->format('Y-m-d H:i:s'),
                'validatedAt' => $base->copy()->subHours(4)->subMinutes(10)->format('Y-m-d H:i:s'),
                'status' => 4,
                'opTime' => 50,
            ],
            [
                'order' => '260608002',
                'type' => 'S',
                'area' => 'COA',
                'testID' => 'TP',
                'testName' => 'Tiempo de protrombina',
                'userId' => '103',
                'userName' => 'María Torres',
                'shift' => 'morning',
                'createdAt' => $base->copy()->subHours(4)->format('Y-m-d H:i:s'),
                'validatedAt' => $base->copy()->subHours(2)->subMinutes(20)->format('Y-m-d H:i:s'),
                'status' => 4,
                'opTime' => 100,
            ],
            [
                'order' => '260608003',
                'type' => 'R',
                'area' => 'INM',
                'testID' => 'TSH',
                'testName' => 'TSH',
                'userId' => '102',
                'userName' => 'Luis Hernández',
                'shift' => 'evening',
                'createdAt' => $base->copy()->subHours(3)->format('Y-m-d H:i:s'),
                'validatedAt' => $base->copy()->subHours(2)->subMinutes(5)->format('Y-m-d H:i:s'),
                'status' => 4,
                'opTime' => 55,
            ],
            [
                'order' => '260608004',
                'type' => 'S',
                'area' => 'BIO',
                'testID' => 'UREA',
                'testName' => 'Urea',
                'userId' => '101',
                'userName' => 'Ana López',
                'shift' => 'evening',
                'createdAt' => $base->copy()->subHours(3)->subMinutes(20)->format('Y-m-d H:i:s'),
                'validatedAt' => $base->copy()->subHours(1)->subMinutes(55)->format('Y-m-d H:i:s'),
                'status' => 4,
                'opTime' => 85,
            ],
            [
                'order' => '260608005',
                'type' => 'R',
                'area' => 'MIC',
                'testID' => 'UROC',
                'testName' => 'Urocultivo',
                'userId' => '104',
                'userName' => 'Carlos Méndez',
                'shift' => 'night',
                'createdAt' => $base->copy()->subDay()->setTime(22, 10)->format('Y-m-d H:i:s'),
                'validatedAt' => $base->copy()->subDay()->setTime(23, 2)->format('Y-m-d H:i:s'),
                'status' => 4,
                'opTime' => 52,
            ],
            [
                'order' => '260608006',
                'type' => 'R',
                'area' => 'HEM',
                'testID' => 'RET',
                'testName' => 'Reticulocitos',
                'userId' => '103',
                'userName' => 'María Torres',
                'shift' => 'night',
                'createdAt' => $base->copy()->subDay()->setTime(21, 15)->format('Y-m-d H:i:s'),
                'validatedAt' => $base->copy()->subDay()->setTime(23, 12)->format('Y-m-d H:i:s'),
                'status' => 4,
                'opTime' => 117,
            ],
        ];
    }
}
