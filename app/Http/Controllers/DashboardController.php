<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function home()
    {
        return view('dashboards.home');
    }

    public function pacientesMes(Request $request, GoApiService $goApiService)
    {
        $requestedYear = (int) $request->get('year', date('Y'));
        $selectedService = $this->normalizeServiceFilter($request->get('service'));

        $ordersSummary = $goApiService->getOrders();

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        if (!is_array($ordersSummary)) {
            return view('dashboards.pacientes-mes', $this->emptyDashboardData(
                $requestedYear,
                $selectedService,
                $this->apiErrorMessage($goApiService, 'No se pudo obtener información desde la API /orders.')
            ));
        }

        $summaries = $this->normalizeSummaryCollection($ordersSummary);

        if ($summaries === []) {
            return view('dashboards.pacientes-mes', $this->emptyDashboardData(
                $requestedYear,
                $selectedService,
                'La API respondió, pero no se encontró el nuevo resumen anual esperado para el tablero.'
            ));
        }

        $availableYears = array_keys($summaries);
        rsort($availableYears, SORT_NUMERIC);

        $year = array_key_exists($requestedYear, $summaries)
            ? $requestedYear
            : (int) ($availableYears[0] ?? $requestedYear);

        $summary = $summaries[$year] ?? [];
        $months = $this->months();
        $yearDetail = is_array($summary['year_detail'] ?? null)
            ? $summary['year_detail']
            : [];

        $monthlyC = [];
        $monthlyRutine = [];
        $monthlyUrgencias = [];
        $monthlyTotal = [];
        $monthlyRows = [];

        foreach ($months as $monthNumber => $monthName) {
            $monthKey = str_pad((string) $monthNumber, 2, '0', STR_PAD_LEFT);
            $monthData = is_array($yearDetail[$monthKey] ?? null)
                ? $yearDetail[$monthKey]
                : [];

            $countC = $this->toInt($monthData['C'] ?? 0);
            $countR = $this->toInt($monthData['R'] ?? 0);
            $countS = $this->toInt($monthData['S'] ?? 0);
            $monthTotal = $countC + $countR + $countS;

            $monthlyC[$monthNumber] = $countC;
            $monthlyRutine[$monthNumber] = $countR;
            $monthlyUrgencias[$monthNumber] = $countS;
            $monthlyTotal[$monthNumber] = $monthTotal;

            $selectedMonthValue = match ($selectedService) {
                'C' => $countC,
                'R' => $countR,
                'S' => $countS,
                default => $monthTotal,
            };

            $monthlyRows[] = [
                'month' => $monthName,
                'C' => $countC,
                'R' => $countR,
                'S' => $countS,
                'total' => $monthTotal,
                'selected' => $selectedMonthValue,
            ];
        }

        $serviceTotals = $this->normalizeServiceTotals(
            is_array($summary['services'] ?? null) ? $summary['services'] : [],
            $summary,
            $monthlyC,
            $monthlyRutine,
            $monthlyUrgencias
        );

        $displayMonthly = match ($selectedService) {
            'C' => $monthlyC,
            'R' => $monthlyRutine,
            'S' => $monthlyUrgencias,
            default => $monthlyTotal,
        };

        $total = match ($selectedService) {
            'C', 'R', 'S' => $serviceTotals[$selectedService],
            default => $this->toInt($summary['total'] ?? array_sum($monthlyTotal)),
        };

        $monthsWithData = count(array_filter($displayMonthly, static fn ($value) => $value > 0));

        if ($selectedService === '' && isset($summary['monthlyAvg']) && is_numeric($summary['monthlyAvg'])) {
            $promedio = $this->toInt($summary['monthlyAvg']);
            $averageSource = 'Promedio informado por backend';
        } else {
            $promedio = $monthsWithData > 0
                ? (int) round($total / $monthsWithData)
                : 0;
            $averageSource = 'Calculado con meses con datos';
        }

        $maxMonthNumber = $this->resolveMaxMonthNumber(
            $summary,
            $year,
            $displayMonthly,
            $selectedService
        );

        $maxMonthName = $maxMonthNumber !== null
            ? ($months[$maxMonthNumber] ?? 'Sin datos')
            : 'Sin datos';

        $maxMonthValue = $maxMonthNumber !== null
            ? ($displayMonthly[$maxMonthNumber] ?? 0)
            : 0;

        $availableServices = [
            ['value' => 'C', 'label' => 'Servicio C'],
            ['value' => 'R', 'label' => 'Rutina'],
            ['value' => 'S', 'label' => 'Urgencias'],
        ];

        $serviceCounts = [
            'Servicio C' => $serviceTotals['C'],
            'Rutina' => $serviceTotals['R'],
            'Urgencias' => $serviceTotals['S'],
        ];

        if ($selectedService !== '') {
            $serviceLabel = $this->serviceLabel($selectedService);
            $serviceCounts = [
                $serviceLabel => $serviceTotals[$selectedService],
            ];
        }

        $series = $this->buildChartSeries(
            $selectedService,
            $monthlyC,
            $monthlyRutine,
            $monthlyUrgencias,
            $monthlyTotal
        );

        return view('dashboards.pacientes-mes', [
            'year' => $year,
            'selectedService' => $selectedService,
            'selectedServiceLabel' => $selectedService !== ''
                ? $this->serviceLabel($selectedService)
                : 'Todos',
            'availableYears' => $availableYears,
            'availableServices' => $availableServices,
            'categories' => array_values($months),
            'series' => $series,
            'total' => $total,
            'promedio' => $promedio,
            'averageSource' => $averageSource,
            'maxMonthName' => $maxMonthName,
            'maxMonthValue' => $maxMonthValue,
            'totalC' => $serviceTotals['C'],
            'totalRutine' => $serviceTotals['R'],
            'totalUrgencias' => $serviceTotals['S'],
            'serviceCounts' => $serviceCounts,
            'monthlyRows' => $monthlyRows,
            'error' => null,
        ]);
    }

    private function normalizeSummaryCollection(array $payload): array
    {
        if ($this->looksLikeSummaryRecord($payload)) {
            $payload = [$payload];
        }

        $summaries = [];

        foreach ($payload as $record) {
            if (!is_array($record) || !$this->looksLikeSummaryRecord($record)) {
                continue;
            }

            $year = $this->toInt($record['year'] ?? 0);

            if ($year <= 0) {
                continue;
            }

            $summaries[$year] = $record;
        }

        return $summaries;
    }

    private function looksLikeSummaryRecord(array $record): bool
    {
        return array_key_exists('year', $record)
            && (
                array_key_exists('year_detail', $record)
                || array_key_exists('services', $record)
                || array_key_exists('total', $record)
            );
    }

    private function normalizeServiceTotals(
        array $services,
        array $summary,
        array $monthlyC,
        array $monthlyRutine,
        array $monthlyUrgencias
    ): array {
        return [
            'C' => $this->toInt($services['C'] ?? array_sum($monthlyC)),
            'R' => $this->toInt($services['R'] ?? $summary['rutine'] ?? array_sum($monthlyRutine)),
            'S' => $this->toInt($services['S'] ?? $summary['urgencias'] ?? array_sum($monthlyUrgencias)),
        ];
    }

    private function buildChartSeries(
        string $selectedService,
        array $monthlyC,
        array $monthlyRutine,
        array $monthlyUrgencias,
        array $monthlyTotal
    ): array {
        if ($selectedService !== '') {
            $data = match ($selectedService) {
                'C' => array_values($monthlyC),
                'R' => array_values($monthlyRutine),
                'S' => array_values($monthlyUrgencias),
            };

            return [[
                'name' => $this->serviceLabel($selectedService),
                'type' => 'column',
                'data' => $data,
            ]];
        }

        return [
            [
                'name' => 'Servicio C',
                'type' => 'column',
                'data' => array_values($monthlyC),
                'stack' => 'servicios',
            ],
            [
                'name' => 'Rutina',
                'type' => 'column',
                'data' => array_values($monthlyRutine),
                'stack' => 'servicios',
            ],
            [
                'name' => 'Urgencias',
                'type' => 'column',
                'data' => array_values($monthlyUrgencias),
                'stack' => 'servicios',
            ],
            [
                'name' => 'Total órdenes',
                'type' => 'spline',
                'data' => array_values($monthlyTotal),
                'zIndex' => 10,
            ],
        ];
    }

    private function resolveMaxMonthNumber(
        array $summary,
        int $year,
        array $monthlyData,
        string $selectedService
    ): ?int {
        if ($selectedService === '') {
            $inDemandMonth = (string) ($summary['inDemand_month'] ?? '');

            if (preg_match('/^(\d{4})(\d{2})$/', $inDemandMonth, $matches)) {
                $summaryYear = (int) $matches[1];
                $month = (int) $matches[2];

                if ($summaryYear === $year && $month >= 1 && $month <= 12) {
                    return $month;
                }
            }
        }

        return $this->getMaxMonthNumber($monthlyData);
    }

    private function getMaxMonthNumber(array $monthlyData): ?int
    {
        if (array_sum($monthlyData) === 0) {
            return null;
        }

        $maxValue = max($monthlyData);

        foreach ($monthlyData as $month => $value) {
            if ($value === $maxValue) {
                return (int) $month;
            }
        }

        return null;
    }

    private function normalizeServiceFilter($value): string
    {
        $value = strtoupper(trim((string) $value));

        return match ($value) {
            'C', 'SERVICIO C' => 'C',
            'R', 'RUTINE', 'RUTINA', 'ROUTINE' => 'R',
            'S', 'URG', 'URGENCIA', 'URGENCIAS' => 'S',
            default => '',
        };
    }

    private function serviceLabel(string $service): string
    {
        return match ($service) {
            'C' => 'Servicio C',
            'R' => 'Rutina',
            'S' => 'Urgencias',
            default => 'Todos',
        };
    }

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

    private function toInt($value): int
    {
        return is_numeric($value) ? (int) round((float) $value) : 0;
    }

    private function emptyDashboardData(int $year, string $selectedService, string $error): array
    {
        return [
            'year' => $year,
            'selectedService' => $selectedService,
            'selectedServiceLabel' => $selectedService !== ''
                ? $this->serviceLabel($selectedService)
                : 'Todos',
            'availableYears' => [$year],
            'availableServices' => [
                ['value' => 'C', 'label' => 'Servicio C'],
                ['value' => 'R', 'label' => 'Rutina'],
                ['value' => 'S', 'label' => 'Urgencias'],
            ],
            'categories' => array_values($this->months()),
            'series' => [],
            'total' => 0,
            'promedio' => 0,
            'averageSource' => 'Sin información disponible',
            'maxMonthName' => 'Sin datos',
            'maxMonthValue' => 0,
            'totalC' => 0,
            'totalRutine' => 0,
            'totalUrgencias' => 0,
            'serviceCounts' => [],
            'monthlyRows' => [],
            'error' => $error,
        ];
    }
}
