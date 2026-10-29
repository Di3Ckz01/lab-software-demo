<?php

declare(strict_types=1);

namespace App\Services\Demo;

/**
 * Genera los resúmenes anuales ficticios del tablero Órdenes.
 *
 * Los totales provienen del mismo generador utilizado en Estudios.
 * No contiene información clínica real ni consultas externas.
 */
final class VcareDemoOrders
{
    public static function summaries(): array
    {
        $currentYear = (int) now()->format('Y');
        $summaries = [];

        foreach (range($currentYear, $currentYear - 2) as $year) {
            $response = VcareDemoBoards::dispatch(
                'getOrdersByYear',
                [$year]
            );

            $yearDetail = [];
            $services = [
                'C' => 0,
                'R' => 0,
                'S' => 0,
            ];

            $yearTotal = 0;
            $activeMonths = 0;
            $highestMonth = null;
            $highestTotal = 0;

            foreach (($response['months'] ?? []) as $month) {
                $monthNumber = (int) ($month['month'] ?? 0);
                $orders = max(0, (int) ($month['orders'] ?? 0));

                if ($monthNumber < 1 || $monthNumber > 12) {
                    continue;
                }

                // Distribución estable y reproducible por servicio.
                $serviceC = (int) floor($orders * 0.12);
                $urgent = (int) floor($orders * 0.09);
                $routine = $orders - $serviceC - $urgent;

                $monthKey = str_pad(
                    (string) $monthNumber,
                    2,
                    '0',
                    STR_PAD_LEFT
                );

                $yearDetail[$monthKey] = [
                    'C' => $serviceC,
                    'R' => $routine,
                    'S' => $urgent,
                ];

                $services['C'] += $serviceC;
                $services['R'] += $routine;
                $services['S'] += $urgent;

                $yearTotal += $orders;

                if ($orders > 0) {
                    $activeMonths++;
                }

                if ($orders > $highestTotal) {
                    $highestTotal = $orders;
                    $highestMonth = $monthNumber;
                }
            }

            $summaries[] = [
                'year' => $year,
                'year_detail' => $yearDetail,
                'services' => $services,
                'total' => $yearTotal,
                'rutine' => $services['R'],
                'urgencias' => $services['S'],
                'monthlyAvg' => $activeMonths > 0
                    ? (int) round($yearTotal / $activeMonths)
                    : 0,
                'inDemand_month' => $highestMonth !== null
                    ? sprintf('%04d%02d', $year, $highestMonth)
                    : null,
            ];
        }

        return $summaries;
    }
}