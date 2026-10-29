<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudiesController extends Controller
{
    /**
     * Tablero: Estudios.
     *
     * Este controlador consume la API /orders, pero el tablero cuenta ESTUDIOS.
     *
     * Estructura actual de la API:
     * - Cada registro principal es una orden.
     * - La orden tiene campos como:
     *   _id, order, customer, service, status, type, createdAt
     * - Dentro de cada orden viene un arreglo "tests".
     * - Cada elemento de "tests" es un estudio.
     *
     * IMPORTANTE:
     * Para el estatus del tablero usamos el campo "status" de la orden.
     * NO usamos "testStatus", porque backend indicó que ese campo no aplica para nosotros.
     */
    public function index(Request $request, GoApiService $goApiService)
    {
        $year = (int) $request->get('year', date('Y'));

        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $selectedMonth = $request->get('month');
        $selectedMonth = ($selectedMonth !== null && $selectedMonth !== '')
            ? (int) $selectedMonth
            : null;

        if ($selectedMonth !== null && ($selectedMonth < 1 || $selectedMonth > 12)) {
            $selectedMonth = null;
        }

        $selectedArea = trim((string) $request->get('area', ''));
        $selectedStatus = $request->get('status');
        $selectedStatus = ($selectedStatus !== null && $selectedStatus !== '')
            ? (string) $selectedStatus
            : null;

        $months = $this->months();

        /*
         * El tablero superior ahora usa dos endpoints agregados y rápidos:
         * - /orders-by-year/{year}: órdenes únicas por mes.
         * - /tests-by-year/{year}: estudios por mes, área y estatus.
         *
         * La rueda y la tabla paginada se siguen cargando por separado mediante
         * sus rutas AJAX existentes.
         */
        $ordersSummary = $goApiService->getOrdersByYear($year);
        $ordersError = is_array($ordersSummary)
            ? null
            : $goApiService->lastErrorMessage();

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        $testsSummary = $goApiService->getTestsByYear($year);
        $testsError = is_array($testsSummary)
            ? null
            : $goApiService->lastErrorMessage();

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        if (!is_array($ordersSummary) && !is_array($testsSummary)) {
            $message = trim(implode(' ', array_filter([
                $ordersError ?: 'No se pudo obtener el resumen anual de órdenes.',
                $testsError ?: 'No se pudo obtener el resumen anual de estudios.',
            ])));

            return view('dashboards.estudios', $this->emptyDashboardData($year, $message));
        }

        /*
         * Mapas base de enero a diciembre.
         */
        $monthlyOrders = array_fill(1, 12, 0);
        $monthlyStudies = array_fill(1, 12, 0);

        /*
         * Procesamos órdenes únicas por mes.
         */
        if (is_array($ordersSummary)) {
            foreach (($ordersSummary['months'] ?? []) as $monthRecord) {
                if (!is_array($monthRecord)) {
                    continue;
                }

                $monthNumber = (int) ($monthRecord['month'] ?? 0);

                if ($monthNumber < 1 || $monthNumber > 12) {
                    continue;
                }

                $monthlyOrders[$monthNumber] = max(0, (int) ($monthRecord['orders'] ?? 0));
            }
        }

        /*
         * Catálogos y conteos derivados de /tests-by-year/{year}.
         */
        $availableAreasMap = [];
        $availableStatuses = $this->statusOptions();
        $rawAreaCounts = [];
        $statusCounts = [];
        $pendingStudies = 0;
        $validatedStudies = 0;

        if (is_array($testsSummary)) {
            foreach (($testsSummary['months'] ?? []) as $monthRecord) {
                if (!is_array($monthRecord)) {
                    continue;
                }

                $monthNumber = (int) ($monthRecord['month'] ?? 0);

                if ($monthNumber < 1 || $monthNumber > 12) {
                    continue;
                }

                foreach (($monthRecord['areas'] ?? []) as $areaRecord) {
                    if (!is_array($areaRecord)) {
                        continue;
                    }

                    $areaCode = trim((string) ($areaRecord['area'] ?? ''));
                    $areaCode = $areaCode !== '' ? $areaCode : 'SIN_AREA';
                    $availableAreasMap[$areaCode] = $this->areaLabel($areaCode);

                    foreach (($areaRecord['statuses'] ?? []) as $statusRecord) {
                        if (!is_array($statusRecord)) {
                            continue;
                        }

                        $statusCode = (int) ($statusRecord['status'] ?? 0);
                        $count = max(0, (int) ($statusRecord['count'] ?? 0));

                        if ($count === 0) {
                            continue;
                        }

                        $statusKey = (string) $statusCode;

                        if (!array_key_exists($statusKey, $availableStatuses)) {
                            $availableStatuses[$statusKey] = $this->statusLabel($statusCode);
                        }

                        if ($selectedMonth !== null && $selectedMonth !== $monthNumber) {
                            continue;
                        }

                        if ($selectedArea !== '' && $selectedArea !== $areaCode) {
                            continue;
                        }

                        if ($selectedStatus !== null && !$this->statusMatches($selectedStatus, $statusCode)) {
                            continue;
                        }

                        $monthlyStudies[$monthNumber] += $count;
                        $rawAreaCounts[$areaCode] = ($rawAreaCounts[$areaCode] ?? 0) + $count;

                        $statusLabel = $this->statusLabel($statusCode);
                        $statusCounts[$statusLabel] = ($statusCounts[$statusLabel] ?? 0) + $count;

                        if ($statusCode === 0) {
                            $pendingStudies += $count;
                        }

                        if (in_array($statusCode, [4, 6], true)) {
                            $validatedStudies += $count;
                        }
                    }
                }
            }
        }

        /*
         * Si el usuario filtra un mes, la serie de órdenes muestra únicamente
         * ese mes. El endpoint de órdenes no desglosa por área o estatus, por lo
         * que esos dos filtros solo pueden aplicarse a los estudios.
         */
        $monthlyOrdersForChart = $monthlyOrders;

        if ($selectedMonth !== null) {
            foreach ($monthlyOrdersForChart as $monthNumber => $value) {
                if ($monthNumber !== $selectedMonth) {
                    $monthlyOrdersForChart[$monthNumber] = 0;
                }
            }
        }

        $uniqueOrders = $selectedMonth !== null
            ? ($monthlyOrders[$selectedMonth] ?? 0)
            : array_sum($monthlyOrders);

        $totalStudies = array_sum($monthlyStudies);
        $averageStudiesPerOrder = $uniqueOrders > 0
            ? $totalStudies / $uniqueOrders
            : 0;

        /*
         * Orden y etiquetas de áreas.
         */
        asort($availableAreasMap, SORT_NATURAL | SORT_FLAG_CASE);
        $availableAreas = array_keys($availableAreasMap);

        arsort($rawAreaCounts);
        $areaCounts = [];

        foreach ($rawAreaCounts as $areaCode => $count) {
            $areaCounts[$availableAreasMap[$areaCode] ?? $this->areaLabel($areaCode)] = $count;
        }

        ksort($availableStatuses, SORT_NUMERIC);
        arsort($statusCounts);

        $categories = array_values($months);
        $series = [
            [
                'name' => 'Estudios',
                'data' => array_values($monthlyStudies),
            ],
            [
                'name' => 'Órdenes únicas',
                'data' => array_values($monthlyOrdersForChart),
            ],
        ];

        $partialErrors = array_filter([
            !is_array($ordersSummary)
                ? ($ordersError ?: 'No se pudo obtener el resumen anual de órdenes.')
                : null,
            !is_array($testsSummary)
                ? ($testsError ?: 'No se pudo obtener el resumen anual de estudios.')
                : null,
        ]);

        $ordersScopeNote = ($selectedArea !== '' || $selectedStatus !== null)
            ? 'Las órdenes únicas son totales del año o mes seleccionado. El endpoint de órdenes no las desglosa por área ni estatus.'
            : null;

        return view('dashboards.estudios', [
            'year' => $year,
            'months' => $months,
            'selectedMonth' => $selectedMonth,
            'selectedArea' => $selectedArea,
            'selectedStatus' => $selectedStatus,
            'search' => '',
            'availableYears' => $this->yearOptions($year),
            'availableAreas' => $availableAreas,
            'areaLabels' => $availableAreasMap,
            'availableStatuses' => $availableStatuses,
            'categories' => $categories,
            'series' => $series,
            'areaCounts' => $areaCounts,
            'testCounts' => [],
            'statusCounts' => $statusCounts,
            'tableRecords' => [],
            'totalStudies' => $totalStudies,
            'uniqueOrders' => $uniqueOrders,
            'averageStudiesPerOrder' => $averageStudiesPerOrder,
            'pendingStudies' => $pendingStudies,
            'validatedStudies' => $validatedStudies,
            'ordersScopeNote' => $ordersScopeNote,
            'topStudiesPending' => true,
            'error' => !empty($partialErrors) ? implode(' ', $partialErrors) : null,
        ]);
    }


    /**
     * Tablero independiente: Consulta externa.
     */
    public function wheel(Request $request)
    {
        $date = trim((string) $request->query('date', now()->format('Y-m-d')));
        $dateObject = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $dateErrors = \DateTimeImmutable::getLastErrors();

        $hasDateErrors = is_array($dateErrors)
            && (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0);

        if (!$dateObject || $hasDateErrors || $dateObject->format('Y-m-d') !== $date) {
            $date = now()->format('Y-m-d');
        }

        return view('dashboards.studies-wheel', [
            'wheelDate' => $date,
        ]);
    }

    /**
     * Devuelve únicamente los datos agregados de la rueda por área.
     *
     * Esta ruta se consume con JavaScript para que la página pueda abrir
     * mientras el endpoint /rueda/yyyymmdd termina de responder.
     */
    public function wheelData(Request $request, GoApiService $goApiService): JsonResponse
    {
        $date = trim((string) $request->query('date', now()->format('Y-m-d')));
        $dateObject = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $dateErrors = \DateTimeImmutable::getLastErrors();

        $hasDateErrors = is_array($dateErrors)
            && (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0);

        if (!$dateObject || $hasDateErrors || $dateObject->format('Y-m-d') !== $date) {
            return response()->json([
                'success' => false,
                'message' => 'Selecciona una fecha válida.',
            ], 422);
        }

        $apiDate = $dateObject->format('Ymd');
        $records = $goApiService->getStudiesWheel($apiDate);

        if (session('api_session_expired')) {
            return response()->json([
                'success' => false,
                'sessionExpired' => true,
                'message' => session('api_session_message', 'Tu sesión expiró. Inicia sesión nuevamente.'),
                'redirect' => route('login'),
            ], 401);
        }

        if (!is_array($records)) {
            return response()->json([
                'success' => false,
                'message' => $this->apiErrorMessage(
                    $goApiService,
                    'No se pudo obtener la distribución por área.'
                ),
            ], 502);
        }

        $explicitPendingAttentionCount = null;

        $pendingKeys = [
            // Nombre vigente del endpoint.
            'pending_appointments_count',
            // Compatibilidad con respuestas anteriores.
            'pendingAttentionCount',
            'pendingAppointments',
            'pendingCount',
        ];

        foreach ($pendingKeys as $pendingKey) {
            if (isset($records[$pendingKey]) && is_numeric($records[$pendingKey])) {
                $explicitPendingAttentionCount = max(0, (int) $records[$pendingKey]);
                break;
            }
        }

        if ($explicitPendingAttentionCount === null && isset($records['summary']) && is_array($records['summary'])) {
            foreach ($pendingKeys as $pendingKey) {
                if (isset($records['summary'][$pendingKey]) && is_numeric($records['summary'][$pendingKey])) {
                    $explicitPendingAttentionCount = max(0, (int) $records['summary'][$pendingKey]);
                    break;
                }
            }
        }

        /*
         * Nuevo formato del endpoint /rueda:
         * [
         *   {
         *     "orderNumber": 202606220561,
         *     "areas": [
         *       {
         *         "areaName": "HEMATOLOGIA",
         *         "profiles": [
         *           {
         *             "profileName": "Citología Hemática",
         *             "tests": ["Volumen Plaquetario Medio", "..."]
         *           }
         *         ]
         *       }
         *     ]
         *   }
         * ]
         *
         * La vista de la rueda espera un arreglo normalizado de "orders".
         * Para no tocar el diseño, aquí convertimos cada orden-área en un punto
         * y agrupamos los perfiles/pruebas dentro del tooltip.
         */
        if (isset($records['orders']) && is_array($records['orders'])) {
            $sourceRecords = $records['orders'];
        } elseif (isset($records['data']) && is_array($records['data'])) {
            $sourceRecords = $records['data'];
        } else {
            $sourceRecords = $records;
        }

        $orders = [];
        $hourlyCounts = [];
        $semaphoreCounts = [
            'green' => 0,
            'yellow' => 0,
            'red' => 0,
            'unknown' => 0,
        ];
        $uniqueOrderNumbers = [];
        $totalTests = 0;
        $totalProfiles = 0;
        $seenDateCounts = [];
        $appointmentDateCounts = [];
        $statusMismatchCount = 0;
        $legacyStatusConflictCount = 0;
        $pendingAttentionCount = 0;

        $normalizeApiDateTime = static function ($value): string {
            $value = trim((string) $value);

            if ($value === '' || str_starts_with($value, '0001-01-01')) {
                return '';
            }

            $timestamp = strtotime($value);

            if ($timestamp === false || (int) date('Y', $timestamp) < 2000) {
                return '';
            }

            return $value;
        };

        $resolveAppointment = static function (array $record) use ($normalizeApiDateTime): string {
            $appointmentKeys = [
                'fecha_cita',
                'appointmentDate',
                'appointment_at',
                'appointmentAt',
                'createdAt',
                'date',
            ];

            foreach ($appointmentKeys as $key) {
                $value = $normalizeApiDateTime($record[$key] ?? '');

                if ($value !== '') {
                    return $value;
                }
            }

            return '';
        };

        $resolveHisTime = static function (array $record) use ($normalizeApiDateTime): string {
            /*
             * Hora registrada por el HIS. Se aceptan varios nombres para
             * mantener compatibilidad mientras el backend estabiliza el campo.
             */
            $hisKeys = [
                // Nuevo nombre enviado por el endpoint de la rueda.
                'seenAt',
                'seen_at',
                'hora_his',
                'horaHis',
                'horaHIS',
                'hisTime',
                'his_time',
                'timeHis',
                'time_his',
                'fecha_his',
                'fechaHis',
                'hisDate',
                'his_date',
            ];

            foreach ($hisKeys as $key) {
                $value = $normalizeApiDateTime($record[$key] ?? '');

                if ($value !== '') {
                    return $value;
                }
            }

            return '';
        };

        $resolveTurnaroundMinutes = static function (array $record): ?int {
            /*
             * El endpoint vigente entrega el tiempo real en "opTime".
             * "seg_hours" NO representa minutos transcurridos: identifica el
             * bloque horario (1 = 6-7 a.m., 2 = 7-8 a.m., etc.).
             */
            $minuteKeys = [
                // Nombre vigente del backend.
                'attention_time',
                // Compatibilidad con respuestas anteriores.
                'opTime',
                // Compatibilidad con la versión anterior que tenía doble p.
                'oppTime',
                'opp_time',
                'op_time',
                'opportunityMinutes',
                'elapsedMinutes',
                'turnaroundMinutes',
                'minutes',
                'timeMinutes',
                'seg_minutes',
                'segMinutes',
            ];

            foreach ($minuteKeys as $key) {
                if (!array_key_exists($key, $record)) {
                    continue;
                }

                $value = $record[$key];

                if (is_numeric($value)) {
                    $minutes = (int) round((float) $value);

                    return $minutes >= 0 ? $minutes : null;
                }

                if (is_string($value)) {
                    $normalizedValue = trim(str_replace([',', ' min', ' mins', ' minutos'], ['', '', '', ''], $value));

                    if ($normalizedValue !== '' && is_numeric($normalizedValue)) {
                        $minutes = (int) round((float) $normalizedValue);

                        return $minutes >= 0 ? $minutes : null;
                    }
                }
            }

            return null;
        };

        $resolveSemaphore = static function (array $record, ?int $minutes): string {
            /*
             * El backend ya clasifica cada orden:
             * 1 = verde, 2 = amarillo, 3 = rojo.
             * Se usa ese estado como fuente principal y opTime como respaldo.
             */
            $statusValue = $record['attention_time_status']
                ?? $record['opTimeStatus']
                ?? $record['oppTimeStatus']
                ?? $record['op_time_status']
                ?? $record['opp_time_status']
                ?? null;

            if (is_numeric($statusValue)) {
                return match ((int) $statusValue) {
                    1 => 'green',
                    2 => 'yellow',
                    3 => 'red',
                    default => 'unknown',
                };
            }

            if ($minutes === null) {
                return 'unknown';
            }

            if ($minutes <= 60) {
                return 'green';
            }

            if ($minutes <= 90) {
                return 'yellow';
            }

            return 'red';
        };

        $registerDate = static function (string $value, array &$counter): void {
            $value = trim($value);

            if ($value === '') {
                return;
            }

            $timestamp = strtotime($value);

            if ($timestamp === false) {
                return;
            }

            $normalizedDate = date('Y-m-d', $timestamp);
            $counter[$normalizedDate] = ($counter[$normalizedDate] ?? 0) + 1;
        };

        $resolveHourSlot = static function (array $record, string $appointment, string $hisTime): ?string {
            /*
             * seg_hours es la fuente principal del anillo:
             * 1 = 6-7 a.m., 2 = 7-8 a.m., ... 8 = 1-2 p.m.
             * El valor 0 y cualquier hora fuera del rango se agrupan en
             * "Otros horarios". La fecha/hora solo se usa como respaldo.
             */
            $segmentValue = $record['seg_hours'] ?? $record['segHours'] ?? null;

            if (is_numeric($segmentValue)) {
                $segment = (int) $segmentValue;

                if ($segment >= 1 && $segment <= 8) {
                    $hour = $segment + 5;

                    return str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
                }

                return 'OTROS';
            }

            $sourceTime = trim($appointment) !== ''
                ? trim($appointment)
                : trim($hisTime);

            if ($sourceTime === '') {
                return null;
            }

            $timestamp = strtotime($sourceTime);

            if ($timestamp === false) {
                return null;
            }

            $hour = (int) date('G', $timestamp);

            if ($hour < 6 || $hour > 13) {
                return 'OTROS';
            }

            return str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
        };

        // Normaliza exámenes recibidos como texto u objeto.
        // Los nombres se muestran sin resultados clínicos simulados.
        $normalizeTest = static function (mixed $test): ?array {
            if (is_string($test)) {
                $name = trim($test);

                return $name !== ''
                    ? ['testName' => $name, 'result' => '', 'unit' => '']
                    : null;
            }

            if (!is_array($test)) {
                return null;
            }

            $name = trim((string) (
                $test['testName']
                ?? $test['name']
                ?? $test['test']
                ?? ''
            ));

            if ($name === '') {
                return null;
            }

            return [
                'testName' => $name,
                'result' => is_scalar($test['result'] ?? null)
                    ? (string) $test['result']
                    : '',
                'unit' => is_scalar($test['unit'] ?? null)
                    ? (string) $test['unit']
                    : '',
            ];
        };

        foreach ($sourceRecords as $record) {
            if (!is_array($record)) {
                continue;
            }

            $orderNumber = trim((string) ($record['orderNumber'] ?? $record['order'] ?? ''));
            $orderKey = $orderNumber !== '' ? $orderNumber : 'Sin número';
            $uniqueOrderNumbers[$orderKey] = true;

            $appointmentTime = $resolveAppointment($record);
            $hisTime = $resolveHisTime($record);
            $turnaroundMinutes = $resolveTurnaroundMinutes($record);
            $semaphore = $resolveSemaphore($record, $turnaroundMinutes);
            $hourSlot = $resolveHourSlot($record, $appointmentTime, $hisTime);
            $rawOpTimeStatus = $record['attention_time_status']
                ?? $record['opTimeStatus']
                ?? $record['oppTimeStatus']
                ?? $record['op_time_status']
                ?? $record['opp_time_status']
                ?? null;
            $opTimeStatus = is_numeric($rawOpTimeStatus)
                ? (int) $rawOpTimeStatus
                : null;
            $segmentHours = is_numeric($record['seg_hours'] ?? null)
                ? (int) $record['seg_hours']
                : null;

            $rawPhlebotomist = $record['phlebotomist'] ?? null;
            $phlebotomistId = 0;
            $phlebotomistName = '';
            $phlebotomistUsername = '';

            if (is_array($rawPhlebotomist)) {
                $phlebotomistId = (int) ($rawPhlebotomist['id'] ?? 0);
                $phlebotomistName = trim(implode(' ', array_filter([
                    (string) ($rawPhlebotomist['firstName'] ?? ''),
                    (string) ($rawPhlebotomist['lastName'] ?? ''),
                ])));
                $phlebotomistUsername = trim((string) ($rawPhlebotomist['username'] ?? ''));
            } elseif (is_string($rawPhlebotomist)) {
                $phlebotomistName = trim($rawPhlebotomist);
            }

            $hasAssignedPhlebotomist = $phlebotomistId > 0
                || $phlebotomistName !== ''
                || $phlebotomistUsername !== '';

            $phlebotomyStartAt = $normalizeApiDateTime($record['phlebotomy_start_at'] ?? '');
            $phlebotomyEndAt = $normalizeApiDateTime($record['phlebotomy_end_at'] ?? '');

            $rawPhlebotomyTime = $record['phlebotomy_time'] ?? null;
            $phlebotomyTime = is_numeric($rawPhlebotomyTime) && (float) $rawPhlebotomyTime >= 0
                ? (int) round((float) $rawPhlebotomyTime)
                : null;

            $rawPhlebotomyTimeStatus = $record['phlebotomy_time_status'] ?? null;
            $phlebotomyTimeStatus = is_numeric($rawPhlebotomyTimeStatus)
                ? (int) $rawPhlebotomyTimeStatus
                : null;

            $explicitPendingFlag = $record['pendingAttention']
                ?? $record['isPendingAttention']
                ?? $record['pending']
                ?? null;
            $appointmentStatus = mb_strtolower(trim((string) (
                $record['appointmentStatus']
                ?? $record['statusAppointment']
                ?? $record['citaStatus']
                ?? ''
            )), 'UTF-8');

            $isPendingAttention = $explicitPendingFlag === true
                || $explicitPendingFlag === 1
                || $explicitPendingFlag === '1'
                || in_array($appointmentStatus, ['pending', 'pendiente', 'waiting', 'en espera'], true)
                || ($opTimeStatus === 0 && $phlebotomyTimeStatus === 0)
                || ($appointmentTime !== '' && ($turnaroundMinutes ?? 0) <= 0 && !$hasAssignedPhlebotomist);

            if ($isPendingAttention) {
                $pendingAttentionCount++;
            }

            $canonicalStatus = $record['attention_time_status']
                ?? $record['opTimeStatus']
                ?? $record['op_time_status']
                ?? null;
            $legacyStatus = $record['oppTimeStatus'] ?? $record['opp_time_status'] ?? null;

            if (is_numeric($canonicalStatus) && is_numeric($legacyStatus)
                && (int) $canonicalStatus !== (int) $legacyStatus) {
                $legacyStatusConflictCount++;
            }

            if ($turnaroundMinutes !== null && is_numeric($canonicalStatus)) {
                $derivedStatus = $turnaroundMinutes <= 60
                    ? 1
                    : ($turnaroundMinutes <= 90 ? 2 : 3);

                if ((int) $canonicalStatus !== $derivedStatus) {
                    $statusMismatchCount++;
                }
            }

            $registerDate($hisTime, $seenDateCounts);
            $registerDate($appointmentTime, $appointmentDateCounts);

            $rawAreas = isset($record['areas']) && is_array($record['areas'])
                ? $record['areas']
                : [];

            $areas = [];
            $profileNames = [];
            $tests = [];

            if (!empty($rawAreas)) {
                foreach ($rawAreas as $areaRecord) {
                    if (!is_array($areaRecord)) {
                        continue;
                    }

                    $area = trim((string) ($areaRecord['areaName'] ?? $areaRecord['area'] ?? ''));
                    $area = $area !== '' ? mb_strtoupper($area, 'UTF-8') : 'SIN ÁREA';

                    $profiles = isset($areaRecord['profiles']) && is_array($areaRecord['profiles'])
                        ? $areaRecord['profiles']
                        : [];

                    $normalizedProfiles = [];

                    foreach ($profiles as $profile) {
                        if (!is_array($profile)) {
                            continue;
                        }

                        $profileName = trim((string) ($profile['profileName'] ?? $profile['profile'] ?? $profile['name'] ?? ''));
                        $rawProfileTests = isset($profile['tests']) && is_array($profile['tests'])
                            ? $profile['tests']
                            : [];
                        $profileTests = [];

                        foreach ($rawProfileTests as $test) {
                            $normalizedTest = $normalizeTest($test);

                            if ($normalizedTest !== null) {
                                $profileTests[] = $normalizedTest;
                                $tests[] = $normalizedTest;
                            }
                        }

                        if ($profileName !== '') {
                            $normalizedProfiles[] = [
                                'profileName' => $profileName,
                                'tests' => $profileTests,
                            ];
                            $profileNames[] = $profileName;
                        }
                    }

                    if (empty($normalizedProfiles) && isset($areaRecord['tests']) && is_array($areaRecord['tests'])) {
                        $directAreaTests = [];

                        foreach ($areaRecord['tests'] as $test) {
                            $normalizedTest = $normalizeTest($test);

                            if ($normalizedTest !== null) {
                                $directAreaTests[] = $normalizedTest;
                                $tests[] = $normalizedTest;
                            }
                        }

                        if (!empty($directAreaTests)) {
                            $normalizedProfiles[] = [
                                'profileName' => 'Estudios del área',
                                'tests' => $directAreaTests,
                            ];
                            $profileNames[] = 'Estudios del área';
                        }
                    }

                    $areas[] = [
                        'areaName' => $area,
                        'profiles' => $normalizedProfiles,
                    ];
                }
            } else {
                $area = trim((string) ($record['areaName'] ?? $record['area'] ?? ''));
                $area = $area !== '' ? mb_strtoupper($area, 'UTF-8') : 'SIN ÁREA';
                $profileName = trim((string) ($record['profileName'] ?? $record['profile'] ?? ''));

                $normalizedProfiles = [];
                $rawTests = isset($record['tests']) && is_array($record['tests'])
                    ? $record['tests']
                    : [];
                $flatProfileTests = [];

                foreach ($rawTests as $test) {
                    $normalizedTest = $normalizeTest($test);

                    if ($normalizedTest !== null) {
                        $flatProfileTests[] = $normalizedTest;
                        $tests[] = $normalizedTest;
                    }
                }

                if ($profileName !== '') {
                    $normalizedProfiles[] = [
                        'profileName' => $profileName,
                        'tests' => $flatProfileTests,
                    ];
                    $profileNames[] = $profileName;
                } elseif (!empty($flatProfileTests)) {
                    $normalizedProfiles[] = [
                        'profileName' => 'Estudios de la orden',
                        'tests' => $flatProfileTests,
                    ];
                    $profileNames[] = 'Estudios de la orden';
                }

                $areas[] = [
                    'areaName' => $area,
                    'profiles' => $normalizedProfiles,
                ];
            }

            $profileNames = array_values(array_unique(array_filter($profileNames)));
            $totalProfiles += count($profileNames);
            $areaNames = array_values(array_unique(array_map(
                static fn (array $areaRecord): string => (string) ($areaRecord['areaName'] ?? 'SIN ÁREA'),
                $areas
            )));

            $tests = array_values($tests);
            $totalTests += count($tests);

            if ($hourSlot !== null) {
                $hourlyCounts[$hourSlot] = ($hourlyCounts[$hourSlot] ?? 0) + 1;
            } else {
                $hourlyCounts['SIN_HORA'] = ($hourlyCounts['SIN_HORA'] ?? 0) + 1;
            }

            $semaphoreCounts[$semaphore] = ($semaphoreCounts[$semaphore] ?? 0) + 1;

            $orders[] = [
                'orderNumber' => $orderKey,
                'appointmentTime' => $appointmentTime,
                'hisTime' => $hisTime,
                'hourSlot' => $hourSlot,
                'turnaroundMinutes' => $turnaroundMinutes,
                'opTimeStatus' => $opTimeStatus,
                'attentionTimeStatus' => $opTimeStatus,
                'segHours' => $segmentHours,
                'semaphore' => $semaphore,
                'doorID' => isset($record['doorID']) && is_numeric($record['doorID'])
                    ? (int) $record['doorID']
                    : null,
                'phlebotomist' => $phlebotomistName,
                'phlebotomistUsername' => $phlebotomistUsername,
                'phlebotomyStartAt' => $phlebotomyStartAt,
                'phlebotomyEndAt' => $phlebotomyEndAt,
                'phlebotomyTime' => $phlebotomyTime,
                'phlebotomyTimeStatus' => $phlebotomyTimeStatus,
                'tubesCount' => isset($record['tubesCount']) && is_numeric($record['tubesCount'])
                    ? max(0, (int) $record['tubesCount'])
                    : null,
                'isPendingAttention' => $isPendingAttention,
                'areas' => $areas,
                'areaNames' => $areaNames,
                'profileNames' => $profileNames,
                'profileName' => !empty($profileNames) ? implode(', ', $profileNames) : 'Sin perfil',
                'testsCount' => count($tests) > 0 ? count($tests) : count($profileNames),
                'tests' => $tests,
            ];
        }

        usort($orders, static function (array $left, array $right): int {
            $leftDateTime = trim((string) ($left['appointmentTime'] ?? ''));
            $rightDateTime = trim((string) ($right['appointmentTime'] ?? ''));

            if ($leftDateTime === '') {
                $leftDateTime = trim((string) ($left['hisTime'] ?? ''));
            }

            if ($rightDateTime === '') {
                $rightDateTime = trim((string) ($right['hisTime'] ?? ''));
            }

            $leftTime = strtotime($leftDateTime) ?: PHP_INT_MAX;
            $rightTime = strtotime($rightDateTime) ?: PHP_INT_MAX;

            if ($leftTime !== $rightTime) {
                return $leftTime <=> $rightTime;
            }

            return strcmp((string) ($left['orderNumber'] ?? ''), (string) ($right['orderNumber'] ?? ''));
        });

        $totalOrders = count($uniqueOrderNumbers);

        $baseSlots = [];
        for ($hour = 6; $hour <= 13; $hour++) {
            $key = str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
            $labelHour = $hour > 12 ? $hour - 12 : $hour;
            $nextHour = $hour + 1;
            $labelNextHour = $nextHour > 12 ? $nextHour - 12 : $nextHour;
            $baseSlots[] = [
                'key' => $key,
                'label' => sprintf('%d:00 %s - %d:00 %s', $labelHour, $hour < 12 ? 'a.m.' : 'p.m.', $labelNextHour, $nextHour < 12 ? 'a.m.' : 'p.m.'),
                'count' => $hourlyCounts[$key] ?? 0,
            ];
        }

        $otherHoursCount = ($hourlyCounts['OTROS'] ?? 0) + ($hourlyCounts['SIN_HORA'] ?? 0);

        if ($otherHoursCount > 0) {
            $baseSlots[] = [
                'key' => 'OTROS',
                'label' => 'Otros horarios',
                'count' => $otherHoursCount,
            ];
        }

        arsort($seenDateCounts);
        arsort($appointmentDateCounts);

        $dataDate = array_key_first($seenDateCounts)
            ?? array_key_first($appointmentDateCounts)
            ?? null;
        $dateMismatch = $dataDate !== null && $dataDate !== $date;

        return response()->json([
            'success' => true,
            'date' => $date,
            'apiDate' => $apiDate,
            'requestedDate' => $date,
            'dataDate' => $dataDate,
            'dateMismatch' => $dateMismatch,
            'total' => $totalOrders,
            'totalOrders' => $totalOrders,
            'totalTests' => $totalTests,
            'totalProfiles' => $totalProfiles,
            'pendingAttentionCount' => $explicitPendingAttentionCount ?? $pendingAttentionCount,
            'semaphoreSummary' => [
                'green' => [
                    'label' => 'Verde',
                    'range' => '≤ 60 min',
                    'count' => $semaphoreCounts['green'] ?? 0,
                ],
                'yellow' => [
                    'label' => 'Amarillo',
                    'range' => '61 a 90 min',
                    'count' => $semaphoreCounts['yellow'] ?? 0,
                ],
                'red' => [
                    'label' => 'Rojo',
                    'range' => '> 90 min',
                    'count' => $semaphoreCounts['red'] ?? 0,
                ],
                'unknown' => [
                    'label' => 'Sin dato',
                    'range' => 'No disponible',
                    'count' => $semaphoreCounts['unknown'] ?? 0,
                ],
            ],
            'hourlySummary' => $baseSlots,
            'consistency' => [
                'statusMismatchCount' => $statusMismatchCount,
                'legacyStatusConflictCount' => $legacyStatusConflictCount,
            ],
            'orders' => $orders,
        ]);
    }


    /**
     * Devuelve bloques paginados para la tabla "Últimos registros de estudios".
     *
     * Laravel conserva la paginación visual de 10, 25 o 50 registros,
     * mientras que el backend entrega bloques de 50 mediante cursor.
     */
    public function paginatedTestsData(Request $request, GoApiService $goApiService): JsonResponse
    {
        $cursor = trim((string) $request->query('cursor', '0'));
        $cursor = $cursor !== '' ? $cursor : '0';
        $pageSize = (int) $request->query('pageSize', 50);
        $pageSize = in_array($pageSize, [50, 100], true) ? $pageSize : 50;

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $cursor)) {
            return response()->json([
                'success' => false,
                'message' => 'El cursor de paginación no es válido.',
            ], 422);
        }

        $response = $goApiService->getPaginatedTests($cursor, $pageSize);

        if (session('api_session_expired')) {
            return response()->json([
                'success' => false,
                'sessionExpired' => true,
                'message' => session('api_session_message', 'Tu sesión expiró. Inicia sesión nuevamente.'),
                'redirect' => route('login'),
            ], 401);
        }

        if (!is_array($response)) {
            return response()->json([
                'success' => false,
                'message' => $this->apiErrorMessage(
                    $goApiService,
                    'No se pudieron obtener los últimos registros de estudios.'
                ),
            ], 502);
        }

        $records = $response['data'] ?? [];

        if (!is_array($records)) {
            $records = [];
        }

        $normalized = [];

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $statusCode = (int) ($record['status'] ?? 0);
            $validatedAt = $record['validatedAt'] ?? null;
            $validatedTimestamp = $this->isValidDate($validatedAt)
                ? strtotime($validatedAt)
                : null;

            $opTimeRaw = $record['opTime'] ?? null;
            $opTime = is_numeric($opTimeRaw) ? (int) $opTimeRaw : null;

            $profileName = trim((string) (
                $record['profileName']
                ?? $record['testName']
                ?? ''
            ));

            $normalized[] = [
                'order' => $record['orderNumber'] ?? '-',
                'area' => trim((string) ($record['areaInitial'] ?? '')),

                // La columna "Estudio" mostrará profileName.
                'testName' => $profileName !== ''
                    ? $profileName
                    : 'Sin nombre',

                'validatedAt' => $validatedAt,
                'validatedReadable' => $this->formatDate($validatedTimestamp),
                'opTime' => $opTime,
                'opTimeStatus' => $record['opTimeStatus'] ?? null,
                'statusCode' => $statusCode,
                'status' => $this->statusLabel($statusCode),
                'statusClass' => $this->statusClass($statusCode),
            ];
        }

        $nextCursor = trim((string) ($response['nextCursor'] ?? ''));
        $apiPageSize = (int) ($response['pageSize'] ?? $pageSize);

        return response()->json([
            'success' => true,
            'data' => $normalized,
            'nextCursor' => $nextCursor !== '' ? $nextCursor : null,
            'pageSize' => $apiPageSize,
            'hasMore' => $nextCursor !== '' && count($normalized) > 0,
        ]);
    }

    /**
     * Años disponibles en el selector. Los endpoints aceptan el año dentro de
     * la URL, por lo que ofrecemos el año actual y los cinco anteriores.
     */
    private function yearOptions(int $selectedYear): array
    {
        $currentYear = (int) date('Y');
        $years = range($currentYear, max(2000, $currentYear - 5));

        if (!in_array($selectedYear, $years, true)) {
            $years[] = $selectedYear;
        }

        rsort($years, SORT_NUMERIC);

        return array_values(array_unique($years));
    }

    /**
     * Etiqueta visual de un código de área.
     *
     * Mientras backend entrega el catálogo oficial, se muestra el código. Se
     * puede agregar un mapa en services.go_api.studies_area_labels sin cambiar
     * el controlador.
     */
    private function areaLabel(string $areaCode): string
    {
        $labels = config('services.go_api.studies_area_labels', []);

        if (is_array($labels) && isset($labels[$areaCode])) {
            return (string) $labels[$areaCode];
        }

        return $areaCode === 'SIN_AREA'
            ? 'Sin área'
            : 'Área ' . $areaCode;
    }

    /**
     * Opciones oficiales de status.
     *
     * IMPORTANTE:
     * Estos valores vienen del campo "status" de la orden.
     */
    private function statusOptions(): array
    {
        return [
            '0' => 'Ordenada',
            '1' => 'Repetición',
            '2' => 'Con resultado',
            '4' => 'Validada',
            '6' => 'Validada',
        ];
    }

    /**
     * Obtiene el campo status de la orden.
     *
     * NO lee testStatus.
     */
    private function normalizeOrderStatusCode(array $orderRecord): int
    {
        $status = $this->getField($orderRecord, [
            'status',
            'Status',
            'STATUS',
        ], 0);

        return (int) $status;
    }

    /**
     * Convierte el código de status a texto.
     */
    private function statusLabel(int $statusCode): string
    {
        return match ($statusCode) {
            0 => 'Ordenada',
            1 => 'Repetición',
            2 => 'Con resultado',
            4, 6 => 'Validada',
            default => 'Estatus ' . $statusCode,
        };
    }

    /**
     * Clase CSS para pintar el badge de status.
     */
    private function statusClass(int $statusCode): string
    {
        return match ($statusCode) {
            0 => 'badge-ordered',
            1 => 'badge-repeat',
            2 => 'badge-result',
            4, 6 => 'badge-validated',
            default => 'badge-unknown',
        };
    }

    /**
     * Valida si el status seleccionado coincide con el status de la orden.
     *
     * También dejamos compatibilidad con valores anteriores:
     * - pending = status 0
     * - validated = status 4 o 6
     */
    private function statusMatches($selectedStatus, int $orderStatusCode): bool
    {
        $selectedStatus = (string) $selectedStatus;

        if ($selectedStatus === 'pending') {
            return $orderStatusCode === 0;
        }

        if ($selectedStatus === 'validated') {
            return in_array($orderStatusCode, [4, 6], true);
        }

        return (string) $orderStatusCode === $selectedStatus;
    }

    /**
     * Busca un campo dentro de un arreglo usando varias posibles llaves.
     */
    private function getField(array $record, array $possibleKeys, $default = null)
    {
        foreach ($possibleKeys as $key) {
            if (array_key_exists($key, $record)) {
                return $record[$key];
            }
        }

        return $default;
    }

    /**
     * Normaliza el área del estudio.
     */
    private function normalizeArea(array $test): string
    {
        $area = $this->getField($test, [
            'area',
            'Area',
            'AREA',
        ], '');

        return strtoupper(trim((string) $area));
    }

    /**
     * Obtiene la fecha del estudio.
     */
    private function getStudyDate(array $test, array $orderRecord): ?int
    {
        $validatedDate = $this->getField($test, [
            'validatedDate',
            'validated',
            'validated_date',
        ]);

        if ($this->isValidDate($validatedDate)) {
            return strtotime($validatedDate);
        }

        $created = $this->getField($test, [
            'created',
            'Created',
            'CREATED',
        ]);

        if ($this->isValidDate($created)) {
            return strtotime($created);
        }

        $createdAt = $this->getField($orderRecord, [
            'createdAt',
            'created',
            'Created',
            'CREATED',
        ]);

        if ($this->isValidDate($createdAt)) {
            return strtotime($createdAt);
        }

        $orderDate = $this->getDateFromOrder($orderRecord['order'] ?? null);

        if (!$orderDate) {
            return null;
        }

        return strtotime(
            $orderDate['year'] . '-' .
            str_pad($orderDate['month'], 2, '0', STR_PAD_LEFT) . '-' .
            str_pad($orderDate['day'], 2, '0', STR_PAD_LEFT)
        );
    }

    /**
     * Valida si una fecha es utilizable.
     */
    private function isValidDate($value): bool
    {
        if (!$value || !is_string($value)) {
            return false;
        }

        if (str_starts_with($value, '0001-01-01')) {
            return false;
        }

        return strtotime($value) !== false;
    }

    /**
     * Formatea una fecha para mostrar en tabla.
     */
    private function formatDate(?int $timestamp): string
    {
        if (!$timestamp) {
            return '-';
        }

        return date('d/m/Y H:i', $timestamp);
    }

    /**
     * Busca texto en distintos campos del registro.
     */
    private function recordMatchesSearch(string $search, array $values): bool
    {
        if ($search === '') {
            return true;
        }

        $search = mb_strtolower($search);

        foreach ($values as $value) {
            $value = mb_strtolower((string) $value);

            if (str_contains($value, $search)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Meses para filtros y gráficas.
     */
    private function months(): array
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }

    /**
     * Obtiene fecha desde el número de orden.
     *
     * Ejemplo:
     * 202605130001
     * 2026 = año
     * 05 = mes
     * 13 = día
     */
    private function getDateFromOrder($order): ?array
    {
        if (!$order) {
            return null;
        }

        $order = trim((string) $order);

        if (strlen($order) < 8) {
            return null;
        }

        $year = (int) substr($order, 0, 4);
        $month = (int) substr($order, 4, 2);
        $day = (int) substr($order, 6, 2);

        if ($month < 1 || $month > 12) {
            return null;
        }

        if ($day < 1 || $day > 31) {
            return null;
        }

        return [
            'year' => $year,
            'month' => $month,
            'day' => $day,
        ];
    }

    /**
     * Datos vacíos para evitar errores si falla la API.
     */
    private function emptyDashboardData(int $year, string $error): array
    {
        return [
            'year' => $year,
            'months' => $this->months(),
            'selectedMonth' => null,
            'selectedArea' => '',
            'selectedStatus' => null,
            'search' => '',
            'availableYears' => $this->yearOptions($year),
            'availableAreas' => [],
            'areaLabels' => [],
            'availableStatuses' => $this->statusOptions(),
            'categories' => array_values($this->months()),
            'series' => [
                ['name' => 'Estudios', 'data' => array_fill(0, 12, 0)],
                ['name' => 'Órdenes únicas', 'data' => array_fill(0, 12, 0)],
            ],
            'areaCounts' => [],
            'testCounts' => [],
            'statusCounts' => [],
            'tableRecords' => [],
            'totalStudies' => 0,
            'uniqueOrders' => 0,
            'averageStudiesPerOrder' => 0,
            'pendingStudies' => 0,
            'validatedStudies' => 0,
            'ordersScopeNote' => null,
            'topStudiesPending' => true,
            'error' => $error,
        ];
    }
}