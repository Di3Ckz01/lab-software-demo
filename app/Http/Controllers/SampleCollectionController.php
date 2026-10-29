<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SampleCollectionController extends Controller
{
    public function index(Request $request, GoApiService $goApiService): View|\Illuminate\Http\RedirectResponse
    {
        $date = $this->normalizeDate($request->query('date'));
        $requestedDoor = $request->query('door', 'all');
        $apiDate = Carbon::createFromFormat('Y-m-d', $date)->format('Ymd');
        $response = $goApiService->getSampleCollection($apiDate);

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        $errorMessage = null;

        if (is_array($response) && ($response['success'] ?? true) !== false) {
            $initialData = $this->normalizeDashboardData($response, $date);
            $selectedDoor = $this->normalizeDoor($requestedDoor, $initialData['doors']);
            $initialData = $this->filterDashboardByDoor($initialData, $selectedDoor);
        } else {
            $selectedDoor = 'all';
            $initialData = $this->emptyDashboardData($date);
            $errorMessage = $this->apiErrorMessage(
                $goApiService,
                'No se pudo obtener información desde la API /tomaDeMuestra.'
            );
        }

        return view('dashboards.sample-collection', [
            'initialData' => $initialData,
            'selectedDate' => $date,
            'selectedDoor' => $selectedDoor,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function data(Request $request, GoApiService $goApiService): JsonResponse
    {
        $date = trim((string) $request->query('date', ''));
        $requestedDoor = $request->query('door', 'all');

        $apiDate = Carbon::createFromFormat('Y-m-d', $date)->format('Ymd');
        $response = $goApiService->getSampleCollection($apiDate);

        if (session('api_session_expired')) {
            return response()->json([
                'ok' => false,
                'sessionExpired' => true,
                'message' => session('api_session_message', 'Tu sesión expiró. Inicia sesión nuevamente.'),
                'redirect' => route('login'),
            ], 401);
        }

        if (!is_array($response)) {
            return response()->json([
                'ok' => false,
                'message' => $this->apiErrorMessage(
                    $goApiService,
                    'No se pudo obtener información desde la API /tomaDeMuestra.'
                ),
            ], 502);
        }

        if (($response['success'] ?? true) === false) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($response['message'] ?? 'La API no pudo completar la consulta.'),
            ], 502);
        }

        $dashboardData = $this->normalizeDashboardData($response, $date);
        $selectedDoor = $this->normalizeDoor($requestedDoor, $dashboardData['doors']);

        return response()->json([
            'ok' => true,
            'data' => $this->filterDashboardByDoor($dashboardData, $selectedDoor),
        ]);
    }

    /**
     * Consulta la trazabilidad de una orden seleccionada desde el mapa horario.
     */
    public function orderTrace(string $orderNumber, GoApiService $goApiService): JsonResponse
    {
        if (!preg_match('/^\d{12}$/', $orderNumber)) {
            return response()->json(['ok' => false, 'message' => 'El número de orden debe tener 12 dígitos.'], 400);
        }

        $date = substr($orderNumber, 0, 8);
        if (!checkdate((int) substr($date, 4, 2), (int) substr($date, 6, 2), (int) substr($date, 0, 4))) {
            return response()->json(['ok' => false, 'message' => 'La fecha incluida en el número de orden no es válida.'], 400);
        }

        $response = $goApiService->getOrderTrace($orderNumber);

        if ($sessionResponse = $this->sessionExpiredJson()) {
            return $sessionResponse;
        }

        if (is_array($response) && (int) ($response['__httpStatus'] ?? 0) === 404) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($response['message'] ?? 'Orden no encontrada.'),
            ], 404);
        }

        if (!is_array($response)) {
            return response()->json([
                'ok' => false,
                'message' => $this->apiErrorMessage($goApiService, 'No se pudo consultar la trazabilidad de la orden.'),
            ], 502);
        }

        return response()->json(['ok' => true, 'data' => $response]);
    }

    public function tv(Request $request, GoApiService $goApiService): View|\Illuminate\Http\RedirectResponse
    {
        $date = now()->format('Y-m-d');
        $pageSize = 50;
        $response = $goApiService->getPhlebotomyQueue('0', $pageSize);

        $errorMessage = null;

        if (is_array($response)) {
            $initialQueue = $this->normalizeQueueData($response, $date, '0', $pageSize);
        } else {
            $initialQueue = $this->emptyQueueData($date, '0', $pageSize);
            $errorMessage = $this->apiErrorMessage(
                $goApiService,
                'No se pudo obtener la cola desde la API /phlebotomy_queue/{cursor}-{pageSize}.'
            );
        }

        return view('dashboards.sample-collection-tv', [
            'initialQueue' => $initialQueue,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function tvData(Request $request, GoApiService $goApiService): JsonResponse
    {
        $date = now()->format('Y-m-d');
        $cursor = trim((string) $request->query('cursor', '0'));
        $pageSize = $this->normalizeQueuePageSize($request->query('pageSize', 50));

        if (!$this->isValidQueueCursor($cursor)) {
            return response()->json([
                'ok' => false,
                'message' => 'El cursor de paginación no es válido.',
            ], 422);
        }

        $response = $goApiService->getPhlebotomyQueue($cursor, $pageSize);

        if (!is_array($response)) {
            return response()->json([
                'ok' => false,
                'message' => $this->apiErrorMessage(
                    $goApiService,
                    'No se pudo obtener la cola desde la API /phlebotomy_queue/{cursor}-{pageSize}.'
                ),
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'data' => $this->normalizeQueueData($response, $date, $cursor, $pageSize),
            'updated_at' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    private function normalizeQueueData(
        array $response,
        string $date,
        string $cursor,
        int $requestedPageSize
    ): array {
        $rows = is_array($response['data'] ?? null) ? $response['data'] : [];
        $orders = [];
        $doors = [];
        $scores = [];
        $waitTimes = [];
        $venopunctureTimes = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $order = trim((string) ($row['order'] ?? $row['orderNumber'] ?? ''));
            $doorId = trim((string) ($row['doorID'] ?? $row['doorId'] ?? $row['door_id'] ?? ''));

            if ($order === '' || $doorId === '') {
                continue;
            }

            $complexity = is_array($row['complexity'] ?? null) ? $row['complexity'] : [];
            $score = is_numeric($complexity['score'] ?? null) ? (float) $complexity['score'] : 0.0;
            $score = max(1.0, min(5.0, $score));
            $studies = is_numeric($complexity['estudios'] ?? null) ? (int) $complexity['estudios'] : 0;
            $time = is_numeric($row['flebotomistaTime'] ?? null) ? (int) $row['flebotomistaTime'] : null;
            $waitTime = is_numeric($row['wait_time'] ?? null) ? (int) $row['wait_time'] : $time;
            $venopunctureRaw = $row['VenopunctureTime']
                ?? $row['venopunctureTime']
                ?? $row['venopuncture_time']
                ?? null;
            $venopunctureExceededRaw = $row['VenopunctureTimeExceeded']
                ?? $row['venopunctureTimeExceeded']
                ?? $row['venopuncture_time_exceeded']
                ?? false;
            $venopunctureTime = is_numeric($venopunctureRaw) ? (int) $venopunctureRaw : null;
            $venopunctureExceeded = filter_var($venopunctureExceededRaw, FILTER_VALIDATE_BOOLEAN);

            if ($time !== null && $time < 0) {
                $time = null;
            }
            if ($waitTime !== null && $waitTime < 0) {
                $waitTime = null;
            }
            if ($venopunctureTime !== null && $venopunctureTime < 0) {
                $venopunctureTime = null;
            }
            if ($venopunctureTime !== null && $venopunctureTime > 10) {
                $venopunctureExceeded = true;
            }

            $orders[] = [
                'order' => $order,
                'doorID' => $doorId,
                'doorName' => 'Puerta ' . $doorId,
                'flebotomistaTime' => $time,
                'flebotomistaTimeStatus' => is_numeric($row['flebotomistaTimeStatus'] ?? null)
                    ? (int) $row['flebotomistaTimeStatus']
                    : 0,
                'waitTime' => $waitTime,
                'venopunctureTime' => $venopunctureTime,
                'venopunctureTimeExceeded' => $venopunctureExceeded,
                'score' => round($score, 2),
                'studies' => max(0, $studies),
            ];

            $doors[$doorId] = [
                'doorID' => $doorId,
                'doorName' => 'Puerta ' . $doorId,
            ];
            $scores[] = $score;
            if ($waitTime !== null) {
                $waitTimes[] = $waitTime;
            }
            if ($venopunctureTime !== null) {
                $venopunctureTimes[] = $venopunctureTime;
            }
        }

        uksort($doors, static function (string $a, string $b): int {
            if (ctype_digit($a) && ctype_digit($b)) {
                return (int) $a <=> (int) $b;
            }

            return strnatcasecmp($a, $b);
        });

        $nextCursor = $response['nextCursor'] ?? $response['next_cursor'] ?? null;
        $nextCursor = is_scalar($nextCursor) && trim((string) $nextCursor) !== ''
            ? trim((string) $nextCursor)
            : null;

        $pageSize = is_numeric($response['pageSize'] ?? null)
            ? $this->normalizeQueuePageSize($response['pageSize'])
            : $requestedPageSize;

        // Totales diarios enviados por el endpoint de la pantalla TV.
        // Se leen de forma independiente a la página actual para que el encabezado
        // muestre las cifras reales del día aunque sólo se estén pintando 50 órdenes.
        $dailyTotals = is_array($response['dailyTotals'] ?? null)
            ? $response['dailyTotals']
            : (is_array($response['daily_totals'] ?? null) ? $response['daily_totals'] : []);

        $pendingOrders = is_numeric($dailyTotals['pendingOrders'] ?? $dailyTotals['pending_orders'] ?? null)
            ? (int) ($dailyTotals['pendingOrders'] ?? $dailyTotals['pending_orders'])
            : 0;

        $attendedOrders = is_numeric($dailyTotals['attendedOrders'] ?? $dailyTotals['attended_orders'] ?? null)
            ? (int) ($dailyTotals['attendedOrders'] ?? $dailyTotals['attended_orders'])
            : 0;

        return [
            'date' => $date,
            'cursor' => $cursor,
            'nextCursor' => $nextCursor,
            'pageSize' => $pageSize,
            'orders' => $orders,
            'doors' => array_values($doors),
            'summary' => [
                'orders' => count($orders),
                'activeDoors' => count($doors),
                'averageWaitTime' => count($waitTimes) > 0
                    ? round(array_sum($waitTimes) / count($waitTimes), 1)
                    : 0.0,
                'averageVenopunctureTime' => count($venopunctureTimes) > 0
                    ? round(array_sum($venopunctureTimes) / count($venopunctureTimes), 1)
                    : 0.0,
                'pendingOrders' => max(0, $pendingOrders),
                'attendedOrders' => max(0, $attendedOrders),
            ],
        ];
    }

    private function emptyQueueData(string $date, string $cursor, int $pageSize): array
    {
        return [
            'date' => $date,
            'cursor' => $cursor,
            'nextCursor' => null,
            'pageSize' => $pageSize,
            'orders' => [],
            'doors' => [],
            'summary' => [
                'orders' => 0,
                'activeDoors' => 0,
                'averageWaitTime' => 0.0,
                'averageVenopunctureTime' => 0.0,
            ],
        ];
    }

    private function normalizeQueuePageSize(mixed $pageSize): int
    {
        $pageSize = is_numeric($pageSize) ? (int) $pageSize : 20;

        return max(1, $pageSize);
    }

    private function isValidQueueCursor(string $cursor): bool
    {
        return $cursor !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $cursor) === 1;
    }

    private function normalizeDate(mixed $date): string
    {
        $date = is_string($date) ? trim($date) : '';

        return $this->isValidDate($date)
            ? $date
            : now()->format('Y-m-d');
    }

    private function isValidDate(string $date): bool
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

    private function normalizeDashboardData(array $response, string $requestedDate): array
    {
        if (
            isset($response['data'])
            && is_array($response['data'])
            && !isset($response['summary'])
        ) {
            $response = $response['data'];
        }

        $summarySource = is_array($response['summary'] ?? null)
            ? $response['summary']
            : [];

        // Total real de citas del día (atendidas y no atendidas). El backend
        // lo entrega fuera de summary y no debe cambiar al filtrar por puerta.
        $totalAppointments = $this->number(
            $response,
            ['totalAppointments', 'total_appointments'],
            0
        );

        $totalPatients = $this->number($summarySource, ['totalPatients', 'total_patients', 'total'], 0);
        $patientsWithin60 = $this->number($summarySource, ['patientsWithin60', 'patients_within_60', 'within60'], 0);
        $patientsOver60 = $this->number($summarySource, ['patientsOver60', 'patients_over_60', 'over60'], 0);
        $within60Percentage = $this->number($summarySource, ['within60Percentage', 'within_60_percentage'], null);
        $over60Percentage = $this->number($summarySource, ['over60Percentage', 'over_60_percentage'], null);
        $averageAttentionMinutes = $this->number(
            $summarySource,
            ['averageAttentionMinutes', 'average_attention_minutes', 'averageMinutes'],
            0
        );

        if ($within60Percentage === null && $totalPatients > 0) {
            $within60Percentage = round(($patientsWithin60 / $totalPatients) * 100, 2);
        }

        if ($over60Percentage === null && $totalPatients > 0) {
            $over60Percentage = round(($patientsOver60 / $totalPatients) * 100, 2);
        }

        $rawPatientsByHour = is_array($response['patientsByHour'] ?? null)
            ? $response['patientsByHour']
            : [];
        $rawHeatmapRows = is_array($response['averageTimeByDoorAndHour'] ?? null)
            ? $response['averageTimeByDoorAndHour']
            : [];
        $numericHoursAreClock = $this->usesClockHourNumbers($rawPatientsByHour, $rawHeatmapRows);

        $patientsByHour = $this->normalizePatientsByHour($rawPatientsByHour, $numericHoursAreClock);
        $patientsByDoor = $this->normalizePatientsByDoor($response['patientsByDoor'] ?? []);
        $heatmapRows = $this->normalizeHeatmapRows($rawHeatmapRows, $numericHoursAreClock);

        $hours = $this->normalizeHours($response['hours'] ?? [], $patientsByHour, $heatmapRows, $numericHoursAreClock);
        $doors = $this->normalizeDoors($response['doors'] ?? [], $patientsByDoor, $heatmapRows);

        return [
            'mode' => 'api',
            'modeLabel' => 'API /tomaDeMuestra',
            'generatedAt' => (string) ($response['generatedAt'] ?? now()->toIso8601String()),
            'totalAppointments' => (int) round($totalAppointments),
            'filters' => [
                'date' => (string) ($response['filters']['startDate'] ?? $requestedDate),
            ],
            'summary' => [
                'totalPatients' => (int) round($totalPatients),
                'patientsWithin60' => (int) round($patientsWithin60),
                'within60Percentage' => round((float) ($within60Percentage ?? 0), 2),
                'patientsOver60' => (int) round($patientsOver60),
                'over60Percentage' => round((float) ($over60Percentage ?? 0), 2),
                'averageAttentionMinutes' => round((float) $averageAttentionMinutes, 1),
                'averageStatus' => $this->timeStatus((float) $averageAttentionMinutes),
            ],
            'patientsByHour' => $patientsByHour,
            'patientsByDoor' => $patientsByDoor,
            'averageTimeByDoorAndHour' => $heatmapRows,
            'hours' => $hours,
            'doors' => $doors,
        ];
    }

    private function normalizePatientsByHour(mixed $rows, bool $numericHoursAreClock): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $hour = $this->normalizeHourSlot((string) ($row['hour'] ?? $row['hora'] ?? ''), false, $numericHoursAreClock);
            if ($hour === null) {
                continue;
            }

            $average = $this->number($row, ['averageAttentionMinutes', 'average_attention_minutes', 'averageMinutes'], 0);

            $normalized[] = [
                'hour' => $hour,
                'totalPatients' => (int) round($this->number($row, ['totalPatients', 'total_patients', 'patients'], 0)),
                'percentage' => round((float) $this->number($row, ['percentage', 'porcentaje'], 0), 2),
                'averageAttentionMinutes' => round((float) $average, 1),
                'status' => $average > 0 ? $this->timeStatus((float) $average) : 'empty',
            ];
        }

        return $normalized;
    }

    private function normalizePatientsByDoor(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $doorId = (string) ($row['doorID'] ?? $row['doorId'] ?? $row['door_id'] ?? ($index + 1));
            $doorName = trim((string) ($row['doorName'] ?? $row['door_name'] ?? $row['door'] ?? $row['name'] ?? ''));

            if ($doorName === '') {
                $doorName = 'Puerta ' . $doorId;
            }

            $average = $this->number($row, ['averageAttentionMinutes', 'average_attention_minutes', 'averageMinutes'], 0);

            $normalized[] = [
                'doorID' => $doorId,
                'doorName' => $doorName,
                'totalPatients' => (int) round($this->number($row, ['totalPatients', 'total_patients', 'patients'], 0)),
                'percentage' => round((float) $this->number($row, ['percentage', 'porcentaje'], 0), 2),
                'averageAttentionMinutes' => round((float) $average, 1),
                'patientsWithin60' => (int) round($this->number($row, ['patientsWithin60', 'patients_within_60', 'within60'], 0)),
                'within60Percentage' => round((float) $this->number($row, ['within60Percentage', 'within_60_percentage'], 0), 2),
                'patientsOver60' => (int) round($this->number($row, ['patientsOver60', 'patients_over_60', 'over60'], 0)),
                'over60Percentage' => round((float) $this->number($row, ['over60Percentage', 'over_60_percentage'], 0), 2),
                'status' => $average > 0 ? $this->timeStatus((float) $average) : 'empty',
            ];
        }

        return $normalized;
    }

    private function normalizeHeatmapRows(mixed $rows, bool $numericHoursAreClock): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $grouped = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $doorId = (string) ($row['doorID'] ?? $row['doorId'] ?? $row['door_id'] ?? ($index + 1));
            $doorName = trim((string) ($row['doorName'] ?? $row['door_name'] ?? $row['door'] ?? $row['name'] ?? ''));
            if ($doorName === '') {
                $doorName = 'Puerta ' . $doorId;
            }

            if (!isset($grouped[$doorId])) {
                $grouped[$doorId] = [
                    'doorID' => $doorId,
                    'doorName' => $doorName,
                    'totalPatients' => 0,
                    'averageAttentionMinutes' => 0.0,
                    'weightedMinutes' => 0.0,
                    'status' => 'empty',
                    'hours' => [],
                ];
            }

            if (isset($row['hours']) && is_array($row['hours'])) {
                foreach ($row['hours'] as $cell) {
                    if (!is_array($cell)) {
                        continue;
                    }

                    $this->appendHeatmapCell($grouped[$doorId], $cell, $numericHoursAreClock);
                }

                $reportedTotal = $this->number($row, ['totalPatients', 'total_patients', 'patients'], null);
                $reportedAverage = $this->number($row, ['averageAttentionMinutes', 'average_attention_minutes', 'averageMinutes'], null);

                if ($reportedTotal !== null) {
                    $grouped[$doorId]['totalPatients'] = (int) round($reportedTotal);
                }

                if ($reportedAverage !== null) {
                    $grouped[$doorId]['averageAttentionMinutes'] = round((float) $reportedAverage, 1);
                    $grouped[$doorId]['status'] = $this->timeStatus((float) $reportedAverage);
                }

                continue;
            }

            $this->appendHeatmapCell($grouped[$doorId], $row, $numericHoursAreClock);
        }

        foreach ($grouped as &$group) {
            if ($group['averageAttentionMinutes'] <= 0 && $group['totalPatients'] > 0) {
                $group['averageAttentionMinutes'] = round($group['weightedMinutes'] / $group['totalPatients'], 1);
                $group['status'] = $this->timeStatus($group['averageAttentionMinutes']);
            }

            unset($group['weightedMinutes']);
            usort($group['hours'], fn (array $a, array $b): int => strcmp($a['hour'], $b['hour']));
        }
        unset($group);

        return array_values($grouped);
    }

    private function appendHeatmapCell(array &$group, array $cell, bool $numericHoursAreClock): void
    {
        // El mapa del backend etiqueta los bloques desde 1; se alinean con
        // la serie horaria, que los numera desde 0.
        $hour = $this->normalizeHourSlot((string) ($cell['hour'] ?? $cell['hora'] ?? ''), !$numericHoursAreClock, $numericHoursAreClock);
        if ($hour === null) {
            return;
        }

        $patients = (int) round($this->number($cell, ['totalPatients', 'total_patients', 'patients'], 0));
        $average = (float) $this->number($cell, ['averageAttentionMinutes', 'average_attention_minutes', 'averageMinutes'], 0);

        $group['hours'][] = [
            'hour' => $hour,
            'totalPatients' => $patients,
            'averageAttentionMinutes' => round($average, 1),
            'status' => $average > 0 ? $this->timeStatus($average) : 'empty',
            'orderNumbers' => $this->normalizeOrderNumbers(
                $cell['orderNumbers'] ?? $cell['order_numbers']
                    ?? $cell['ordersNumbers'] ?? $cell['orders_numbers']
                    ?? $cell['orderNumberList'] ?? $cell['order_number_list']
                    ?? $cell['ordersList'] ?? $cell['orders_list']
                    ?? $cell['orderList'] ?? $cell['order_list']
                    ?? $cell['orderIds'] ?? $cell['order_ids']
                    ?? $cell['orders'] ?? $cell['orderNumber'] ?? $cell['order_number']
                    ?? $cell['orderNo'] ?? $cell['order_no'] ?? $cell['numeroOrden']
                    ?? $cell['numero_orden'] ?? $cell['order'] ?? []
            ),
        ];

        $group['totalPatients'] += $patients;
        $group['weightedMinutes'] += $patients * $average;
    }

    private function normalizeHours(mixed $hours, array $patientsByHour, array $heatmapRows, bool $numericHoursAreClock): array
    {
        $values = [];

        if (is_array($hours)) {
            foreach ($hours as $hour) {
                if (is_string($hour) || is_numeric($hour)) {
                    $slot = $this->normalizeHourSlot((string) $hour, false, $numericHoursAreClock);
                    if ($slot !== null) {
                        $values[] = $slot;
                    }
                }
            }
        }

        foreach ($patientsByHour as $row) {
            $values[] = $row['hour'];
        }

        foreach ($heatmapRows as $row) {
            foreach ($row['hours'] ?? [] as $cell) {
                $values[] = $cell['hour'];
            }
        }

        $values = array_values(array_unique(array_filter(
            $values,
            static fn (string $value): bool => $value !== ''
        )));
        usort($values, static fn (string $a, string $b): int => (int) $a <=> (int) $b);

        return $values;
    }

    /**
     * Convierte identificadores de hora a un índice común: 0 representa
     * 06:00–07:00, 1 representa 07:00–08:00, etc. El heatmap llega en base 1.
     */
    private function normalizeHourSlot(string $value, bool $oneBased, bool $numericIsClock = false): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})(?::\d{2})?/', $value, $match) && str_contains($value, ':')) {
            $hour = (int) $match[1];
            if ($hour >= 0 && $hour <= 23) {
                return (string) ($hour - 6);
            }
        }

        if (!preg_match('/^\d+$/', $value)) {
            return $value;
        }

        $slot = (int) $value;
        if ($numericIsClock && $slot >= 0 && $slot <= 23) {
            return (string) ($slot - 6);
        }

        if ($oneBased && $slot > 0) {
            $slot--;
        }

        return (string) $slot;
    }

    /** Detecta si las horas numéricas del endpoint son horas del reloj (6–23) o índices de bloque. */
    private function usesClockHourNumbers(array ...$collections): bool
    {
        $hours = [];
        $collect = function (array $rows) use (&$collect, &$hours): void {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $value = $row['hour'] ?? $row['hora'] ?? null;
                if (is_numeric($value)) {
                    $hours[] = (int) $value;
                }

                if (is_array($row['hours'] ?? null)) {
                    $collect($row['hours']);
                }
            }
        };

        foreach ($collections as $collection) {
            $collect($collection);
        }

        if ($hours === []) {
            return false;
        }

        $minimum = min($hours);
        $maximum = max($hours);

        return $maximum > 8 || ($minimum >= 6 && $maximum <= 23);
    }

    /** Normaliza números de orden devueltos dentro de cada bloque horario. */
    private function normalizeOrderNumbers(mixed $value): array
    {
        $orderNumbers = [];
        $this->collectOrderNumbers($value, $orderNumbers);

        return array_values($orderNumbers);
    }

    /** Recorre respuestas en forma de lista, objeto o texto separado por comas. */
    private function collectOrderNumbers(mixed $value, array &$orderNumbers): void
    {
        if (is_string($value) || is_numeric($value)) {
            $text = trim((string) $value);
            if (preg_match('/^\d{12}$/', $text)) {
                $orderNumbers[$text] = $text;
                return;
            }

            // Algunos servicios serializan varios números en una sola cadena.
            if (preg_match_all('/(?<!\d)\d{12}(?!\d)/', $text, $matches)) {
                foreach ($matches[0] as $orderNumber) {
                    $orderNumbers[$orderNumber] = $orderNumber;
                }
            }
            return;
        }

        if (!is_array($value)) {
            return;
        }

        foreach ([
            'orderNumber', 'order_number', 'orderNo', 'order_no', 'numeroOrden', 'numero_orden',
            'order', 'number', 'orders', 'orderNumbers', 'order_numbers', 'ordersNumbers',
            'orders_numbers', 'orderNumberList', 'order_number_list', 'ordersList', 'orders_list',
            'orderList', 'order_list', 'orderIds', 'order_ids', 'data', 'items', 'results',
        ] as $key) {
            if (array_key_exists($key, $value)) {
                $this->collectOrderNumbers($value[$key], $orderNumbers);
                return;
            }
        }

        foreach ($value as $item) {
            $this->collectOrderNumbers($item, $orderNumbers);
        }
    }

    private function normalizeDoors(mixed $doors, array $patientsByDoor, array $heatmapRows): array
    {
        $normalized = [];

        if (is_array($doors)) {
            foreach ($doors as $index => $door) {
                if (!is_array($door)) {
                    continue;
                }

                $id = (string) ($door['doorID'] ?? $door['doorId'] ?? $door['door_id'] ?? ($index + 1));
                $name = trim((string) ($door['doorName'] ?? $door['door_name'] ?? $door['name'] ?? ''));
                $normalized[$id] = [
                    'doorID' => $id,
                    'doorName' => $name !== '' ? $name : 'Puerta ' . $id,
                ];
            }
        }

        foreach (array_merge($patientsByDoor, $heatmapRows) as $door) {
            $id = (string) ($door['doorID'] ?? '');
            if ($id === '') {
                continue;
            }

            $normalized[$id] = [
                'doorID' => $id,
                'doorName' => (string) ($door['doorName'] ?? ('Puerta ' . $id)),
            ];
        }

        return array_values($normalized);
    }

    private function normalizeDoor(mixed $door, array $doors): string
    {
        $door = is_scalar($door) ? trim((string) $door) : 'all';

        if ($door === '' || $door === 'all') {
            return 'all';
        }

        foreach ($doors as $availableDoor) {
            if ((string) ($availableDoor['doorID'] ?? '') === $door) {
                return $door;
            }
        }

        return 'all';
    }

    /**
     * El endpoint recibe únicamente la fecha. La selección de puerta se aplica
     * en Laravel usando patientsByDoor y averageTimeByDoorAndHour del mismo JSON.
     */
    private function filterDashboardByDoor(array $data, string $selectedDoor): array
    {
        $data['filters']['doorID'] = $selectedDoor;

        if ($selectedDoor === 'all') {
            return $data;
        }

        $doorSummary = collect($data['patientsByDoor'] ?? [])->first(
            fn (array $row): bool => (string) ($row['doorID'] ?? '') === $selectedDoor
        );

        $doorHeatmap = collect($data['averageTimeByDoorAndHour'] ?? [])->first(
            fn (array $row): bool => (string) ($row['doorID'] ?? '') === $selectedDoor
        );

        if (!is_array($doorSummary) && !is_array($doorHeatmap)) {
            $data['filters']['doorID'] = 'all';
            return $data;
        }

        $totalPatients = (int) ($doorSummary['totalPatients'] ?? $doorHeatmap['totalPatients'] ?? 0);
        $averageMinutes = (float) ($doorSummary['averageAttentionMinutes'] ?? $doorHeatmap['averageAttentionMinutes'] ?? 0);
        $patientsWithin60 = (int) ($doorSummary['patientsWithin60'] ?? 0);
        $patientsOver60 = (int) ($doorSummary['patientsOver60'] ?? max(0, $totalPatients - $patientsWithin60));

        $withinPercentage = $doorSummary['within60Percentage'] ?? null;
        $overPercentage = $doorSummary['over60Percentage'] ?? null;

        if (($withinPercentage === null || (float) $withinPercentage === 0.0) && $totalPatients > 0 && $patientsWithin60 > 0) {
            $withinPercentage = round(($patientsWithin60 / $totalPatients) * 100, 2);
        }

        if (($overPercentage === null || (float) $overPercentage === 0.0) && $totalPatients > 0 && $patientsOver60 > 0) {
            $overPercentage = round(($patientsOver60 / $totalPatients) * 100, 2);
        }

        $hourRows = [];
        foreach (($doorHeatmap['hours'] ?? []) as $hourRow) {
            $hourPatients = (int) ($hourRow['totalPatients'] ?? 0);
            $hourRows[] = [
                'hour' => (string) ($hourRow['hour'] ?? ''),
                'totalPatients' => $hourPatients,
                'percentage' => $totalPatients > 0
                    ? round(($hourPatients / $totalPatients) * 100, 2)
                    : 0,
                'averageAttentionMinutes' => round((float) ($hourRow['averageAttentionMinutes'] ?? 0), 1),
                'status' => (string) ($hourRow['status'] ?? 'empty'),
            ];
        }

        $filteredDoorSummary = $doorSummary;
        if (is_array($filteredDoorSummary)) {
            $filteredDoorSummary['percentage'] = $totalPatients > 0 ? 100.0 : 0.0;
        }

        $data['summary'] = [
            'totalPatients' => $totalPatients,
            'patientsWithin60' => $patientsWithin60,
            'within60Percentage' => round((float) ($withinPercentage ?? 0), 2),
            'patientsOver60' => $patientsOver60,
            'over60Percentage' => round((float) ($overPercentage ?? 0), 2),
            'averageAttentionMinutes' => round($averageMinutes, 1),
            'averageStatus' => $this->timeStatus($averageMinutes),
        ];
        $data['patientsByHour'] = $hourRows;
        $data['patientsByDoor'] = is_array($filteredDoorSummary) ? [$filteredDoorSummary] : [];
        $data['averageTimeByDoorAndHour'] = is_array($doorHeatmap) ? [$doorHeatmap] : [];
        $data['hours'] = array_values(array_filter(array_map(
            fn (array $row): string => (string) ($row['hour'] ?? ''),
            $hourRows
        ), static fn (string $hour): bool => $hour !== ''));

        return $data;
    }

    private function emptyDashboardData(string $date): array
    {
        return [
            'mode' => 'api',
            'modeLabel' => 'API /tomaDeMuestra',
            'generatedAt' => null,
            'totalAppointments' => 0,
            'filters' => ['date' => $date],
            'summary' => [
                'totalPatients' => 0,
                'patientsWithin60' => 0,
                'within60Percentage' => 0,
                'patientsOver60' => 0,
                'over60Percentage' => 0,
                'averageAttentionMinutes' => 0,
                'averageStatus' => 'empty',
            ],
            'patientsByHour' => [],
            'patientsByDoor' => [],
            'averageTimeByDoorAndHour' => [],
            'hours' => [],
            'doors' => [],
        ];
    }

    private function number(array $source, array $keys, int|float|null $default = 0): int|float|null
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $source) && is_numeric($source[$key])) {
                return (float) $source[$key];
            }
        }

        return $default;
    }

    private function timeStatus(float $minutes): string
    {
        if ($minutes <= 0) {
            return 'empty';
        }

        if ($minutes <= 60) {
            return 'good';
        }

        if ($minutes <= 90) {
            return 'warning';
        }

        return 'danger';
    }

    /**
     * Devuelve al buscador una respuesta JSON clara cuando la sesión de la API expiró.
     */
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
