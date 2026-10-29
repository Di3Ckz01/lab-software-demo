<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OpportunityStatusController extends Controller
{
    private array $baseAreas = ['BIO', 'COA', 'END', 'HEM', 'INM', 'MIC', 'TOX', 'REM'];

    private array $statusCatalog = [
        0  => ['label' => 'Ordenada', 'class' => 'status-ordered'],
        1  => ['label' => 'Repetición', 'class' => 'status-repeat'],
        2  => ['label' => 'Con resultado', 'class' => 'status-result'],
        4  => ['label' => 'Validada', 'class' => 'status-validated'],
       /* 6  => ['label' => 'Validada', 'class' => 'status-validated'],
        9  => ['label' => 'Revisar', 'class' => 'status-review'],
        10 => ['label' => 'Ordenada', 'class' => 'status-ordered'],
        11 => ['label' => 'Repetición', 'class' => 'status-repeat'],
        12 => ['label' => 'Con resultado', 'class' => 'status-result'],
        13 => ['label' => 'Resultados impresos', 'class' => 'status-printed'],
        14 => ['label' => 'Validada', 'class' => 'status-validated'],
        15 => ['label' => 'Validación parcial', 'class' => 'status-partial-validation'],
        16 => ['label' => 'Impresión parcial', 'class' => 'status-partial-print'],
        17 => ['label' => 'Resultado parcial', 'class' => 'status-partial-result'],
        18 => ['label' => 'En proceso', 'class' => 'status-process'],
        19 => ['label' => 'Revisar', 'class' => 'status-review'],*/
    ];

    public function index(Request $request, GoApiService $goApiService)
    {
        // El tablero administrativo ahora trabaja con un único día.
        // Conservamos compatibilidad con enlaces anteriores que todavía envíen
        // date_from/date_to, tomando primero la fecha final del rango.
        $selectedDate = $request->query('date')
            ?? $request->query('date_to')
            ?? $request->query('date_from')
            ?? now()->toDateString();

        $selectedDate = $this->normalizeCreatedDate($selectedDate) ?? now()->toDateString();

        $filters = [
            'date' => $selectedDate,
            'type' => strtoupper((string) $request->query('type', '')),
            'service' => (string) $request->query('service', ''),
            'area' => strtoupper((string) $request->query('area', '')),
        ];

        // El backend recibe la fecha directamente en la ruta:
        // GET /opp-times/20260713
        $apiDate = Carbon::createFromFormat('Y-m-d', $selectedDate)->format('Ymd');
        $orders = $goApiService->getOpportunityTimes($apiDate);

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        $errorMessage = null;

        if (!is_array($orders)) {
            $orders = [];
            $errorMessage = $this->apiErrorMessage($goApiService, 'No se pudo obtener información desde la API /opp-times/{date}.');
        }

        $allRows = $this->normalizeRows($orders);
        $rows = $this->applyFilters($allRows, $filters);
        $areas = $this->availableAreas($allRows);

        return view('dashboards.opportunity-status', [
            'rows' => $rows,
            'areas' => $areas,
            'services' => $this->availableServices($allRows),
            'statusCatalog' => $this->statusCatalog,
            'summary' => $this->buildSummary($rows, count($allRows)),
            'alertsByArea' => $this->buildAlertsByArea($rows),
            'filters' => $filters,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function tv()
    {
        return view('dashboards.tv');
    }

    public function tvData(Request $request, GoApiService $goApiService)
    {
        $filters = [
            'date_from' => $request->query('date_from', '2026-06-21'),
            'date_to' => $request->query('date_to', now()->toDateString()),
            'type' => strtoupper((string) $request->query('type', '')),
            'service' => (string) $request->query('service', ''),
            'area' => strtoupper((string) $request->query('area', '')),
        ];

        $orders = $goApiService->getTvOpportunityTimes();

        if (!is_array($orders)) {
            return response()->json([
                'ok' => false,
                'message' => 'No se pudo obtener información desde la API /opp-times-1.',
                'rows' => [],
                'areas' => $this->baseAreas,
                'statusCatalog' => $this->statusCatalog,
                'updated_at' => now()->format('d/m/Y H:i:s'),
            ], 500);
        }

        $allRows = $this->normalizeRows($orders);
        $rows = $this->applyFilters($allRows, $filters);
        $areas = $this->availableAreas($allRows);

        return response()->json([
            'ok' => true,
            'rows' => array_map(function (array $row) use ($areas) {
                $areaStatuses = [];

                foreach ($areas as $area) {
                    $statusCode = $row['areaStatuses'][$area] ?? null;

                    $areaStatuses[$area] = [
                        'code' => $statusCode,
                        'label' => $this->statusLabel($statusCode),
                        'class' => $this->statusClass($statusCode),
                    ];
                }

                return [
                    'order' => $row['order'],
                    'type' => $row['type'],
                    'typeLabel' => $row['typeLabel'],
                    'service' => $row['service'],
                    'createdAt' => $row['createdAt'],
                    'opTime' => $row['opTime'],
                    'opTimeLabel' => $row['opTimeLabel'],
                    'opTimeClass' => $row['opTimeClass'],
                    'statusCode' => $row['statusCode'],
                    'statusLabel' => $row['statusLabel'],
                    'statusClass' => $row['statusClass'],
                    'areaStatuses' => $areaStatuses,
                ];
            }, $rows),
            'areas' => $areas,
            'summary' => $this->buildSummary($rows, count($allRows)),
            'filters' => $filters,
            'updated_at' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    private function normalizeRows(array $orders): array
    {
        $rows = [];

        foreach ($orders as $orderRecord) {
            if (!is_array($orderRecord)) {
                continue;
            }

            $orderNumber = $orderRecord['orderNumber']
                ?? $orderRecord['order']
                ?? $orderRecord['Order']
                ?? $orderRecord['orden']
                ?? null;

            if (empty($orderNumber)) {
                continue;
            }

            $type = strtoupper(trim((string) (
                $orderRecord['typeInitial']
                ?? $orderRecord['type']
                ?? $orderRecord['priority']
                ?? ''
            )));
            $type = in_array($type, ['R', 'S'], true) ? $type : '-';

            $opTime = $orderRecord['opTime'] ?? $orderRecord['op_time'] ?? null;
            $opTime = is_numeric($opTime) ? (int) $opTime : null;

            $orderStatusCode = $this->normalizeStatusCode(
                $orderRecord['orderStatus']
                ?? $orderRecord['status']
                ?? $orderRecord['statusOrder']
                ?? null
            );

            $createdDate = $this->normalizeCreatedDate(
                $orderRecord['orderCreatedDate']
                ?? $orderRecord['createdAt']
                ?? $orderRecord['created_at']
                ?? null
            );

            $areaStatuses = $this->normalizeAreaStatuses($orderRecord['areas'] ?? []);
            $service = trim((string) ($orderRecord['serviceName'] ?? $orderRecord['service'] ?? '-'));
            $areasText = implode(', ', array_keys(array_filter($areaStatuses, fn ($value) => $value !== null)));

            $rows[] = [
                'order' => (string) $orderNumber,
                'type' => $type,
                'typeLabel' => $type === 'S' ? 'Urgencia' : ($type === 'R' ? 'Rutina' : 'Sin tipo'),
                'opTime' => $opTime,
                'opTimeClass' => $this->opTimeClass($opTime),
                'opTimeLabel' => $opTime !== null ? $opTime . ' min' : 'Sin tiempo',
                'service' => $service !== '' ? $service : '-',
                'createdDate' => $createdDate,
                'createdAt' => $this->formatDateForView($createdDate),
                'statusCode' => $orderStatusCode,
                'statusLabel' => $this->statusLabel($orderStatusCode),
                'statusClass' => $this->statusClass($orderStatusCode),
                'areaStatuses' => $areaStatuses,
                'areasText' => $areasText !== '' ? $areasText : '-',
            ];
        }

        usort($rows, function (array $a, array $b) {
            return (string) $b['order'] <=> (string) $a['order'];
        });

        return $rows;
    }

    private function normalizeAreaStatuses($areas): array
    {
        $areaStatuses = [];

        foreach ($this->baseAreas as $area) {
            $areaStatuses[$area] = null;
        }

        if (!is_array($areas)) {
            return $areaStatuses;
        }

        foreach ($areas as $areaRecord) {
            if (!is_array($areaRecord)) {
                continue;
            }

            $areaCode = strtoupper(trim((string) (
                $areaRecord['area']
                ?? $areaRecord['areaCode']
                ?? $areaRecord['code']
                ?? $areaRecord['name']
                ?? $areaRecord['areaName']
                ?? ''
            )));

            if ($areaCode === '') {
                continue;
            }

            $statusCode = $this->normalizeStatusCode(
                $areaRecord['status']
                ?? $areaRecord['statusCode']
                ?? $areaRecord['orderStatus']
                ?? $areaRecord['areaStatus']
                ?? null
            );

            $areaStatuses[$areaCode] = $statusCode;
        }

        return $areaStatuses;
    }

    private function applyFilters(array $rows, array $filters): array
    {
        $date = $this->safeDate($filters['date'] ?? null);
        $from = $date ? null : $this->safeDate($filters['date_from'] ?? null);
        $to = $date ? null : $this->safeDate($filters['date_to'] ?? null);
        $type = strtoupper((string) ($filters['type'] ?? ''));
        $service = (string) ($filters['service'] ?? '');
        $area = strtoupper((string) ($filters['area'] ?? ''));

        return array_values(array_filter($rows, function (array $row) use ($date, $from, $to, $type, $service, $area) {
            $created = $this->safeDate($row['createdDate'] ?? null);

            // Cuando se selecciona un día, solamente se muestran registros que
            // tengan fecha válida y correspondan exactamente a ese día.
            if ($date && (!$created || !$created->isSameDay($date))) {
                return false;
            }

            // Compatibilidad con el tablero TV, que todavía puede trabajar con rango.
            if (!$date && $from && $created && $created->lt($from->copy()->startOfDay())) {
                return false;
            }

            if (!$date && $to && $created && $created->gt($to->copy()->endOfDay())) {
                return false;
            }

            if ($type !== '' && ($row['type'] ?? '') !== $type) {
                return false;
            }

            if ($service !== '' && ($row['service'] ?? '') !== $service) {
                return false;
            }

            if ($area !== '') {
                $areaStatuses = $row['areaStatuses'] ?? [];
                if (!array_key_exists($area, $areaStatuses) || $areaStatuses[$area] === null) {
                    return false;
                }
            }

            return true;
        }));
    }

    private function buildSummary(array $rows, int $totalReceived): array
    {
        $shown = count($rows);
        $routine = 0;
        $urgent = 0;
        $review = 0;
        $inProcess = 0;
        $partialValidation = 0;
        $printed = 0;
        $withinRange = 0;
        $warningRange = 0;
        $outOfRange = 0;
        $opTimeSum = 0;
        $opTimeCount = 0;

        foreach ($rows as $row) {
            if (($row['type'] ?? null) === 'R') {
                $routine++;
            }

            if (($row['type'] ?? null) === 'S') {
                $urgent++;
            }

            $statusCode = $row['statusCode'] ?? null;

            if (in_array($statusCode, [9, 19], true)) {
                $review++;
            }

            if ($statusCode === 18) {
                $inProcess++;
            }

            if ($statusCode === 15) {
                $partialValidation++;
            }

            if ($statusCode === 13) {
                $printed++;
            }

            if (isset($row['opTime']) && is_numeric($row['opTime'])) {
                $minutes = (int) $row['opTime'];
                $opTimeSum += $minutes;
                $opTimeCount++;

                if ($minutes <= 60) {
                    $withinRange++;
                } elseif ($minutes <= 90) {
                    $warningRange++;
                } else {
                    $outOfRange++;
                }
            }
        }

        $compliance = $opTimeCount > 0 ? round(($withinRange / $opTimeCount) * 100, 1) : 0;

        return [
            'totalReceived' => $totalReceived,
            'totalShown' => $shown,
            'routine' => $routine,
            'urgent' => $urgent,
            'review' => $review,
            'inProcess' => $inProcess,
            'partialValidation' => $partialValidation,
            'printed' => $printed,
            'withinRange' => $withinRange,
            'warningRange' => $warningRange,
            'outOfRange' => $outOfRange,
            'compliance' => $compliance,
            'averageOpTime' => $opTimeCount > 0 ? round($opTimeSum / $opTimeCount) : 0,
        ];
    }

    private function buildAlertsByArea(array $rows): array
    {
        $alerts = [];

        foreach ($rows as $row) {
            $minutes = $row['opTime'] ?? null;
            if (!is_numeric($minutes) || (int) $minutes <= 90) {
                continue;
            }

            $areaKeys = array_keys(array_filter($row['areaStatuses'] ?? [], fn ($value) => $value !== null));

            if (empty($areaKeys)) {
                $areaKeys = [$row['service'] ?: 'Sin área'];
            }

            foreach ($areaKeys as $area) {
                $alerts[$area] = ($alerts[$area] ?? 0) + 1;
            }
        }

        arsort($alerts);

        return array_slice($alerts, 0, 5, true);
    }

    private function availableServices(array $rows): array
    {
        $services = array_values(array_unique(array_filter(array_map(fn ($row) => $row['service'] ?? null, $rows))));
        sort($services);
        return $services;
    }

    private function availableAreas(array $rows): array
    {
        $areas = $this->baseAreas;

        foreach ($rows as $row) {
            foreach (array_keys($row['areaStatuses'] ?? []) as $area) {
                if (!in_array($area, $areas, true)) {
                    $areas[] = $area;
                }
            }
        }

        return $areas;
    }

    private function normalizeStatusCode($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function statusLabel(?int $statusCode): string
    {
        if ($statusCode === null) {
            return 'Sin estado';
        }

        return $this->statusCatalog[$statusCode]['label'] ?? 'Estado ' . $statusCode;
    }

    private function statusClass(?int $statusCode): string
    {
        if ($statusCode === null) {
            return 'status-empty';
        }

        return $this->statusCatalog[$statusCode]['class'] ?? 'status-unknown';
    }

    private function opTimeClass(?int $minutes): string
    {
        if ($minutes === null) {
            return 'op-empty';
        }

        if ($minutes <= 60) {
            return 'op-ok';
        }

        if ($minutes <= 90) {
            return 'op-warning';
        }

        return 'op-danger';
    }

    private function normalizeCreatedDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $raw = trim((string) $value);

        try {
            if (preg_match('/^\d{8}$/', $raw)) {
                return Carbon::createFromFormat('Ymd', $raw)->toDateString();
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
                return Carbon::parse($raw)->toDateString();
            }

            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function safeDate($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function formatDateForView(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $exception) {
            return '-';
        }
    }
}
