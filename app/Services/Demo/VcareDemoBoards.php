<?php

declare(strict_types=1);

namespace App\Services\Demo;

use DateTimeImmutable;

/**
 * Datos ficticios compartidos por Estudios, Consulta externa y TV.
 *
 * Reutiliza los números de orden de VcareDemoData.
 * No realiza consultas HTTP ni accede a información clínica real.
 */
final class VcareDemoBoards
{
    public static function enabled(): bool
    {
        return VcareDemoMoreData::enabled();
    }

    /**
     * Enruta las lecturas de demostración según el método original.
     */
    public static function dispatch(string $method, array $args): array
    {
        return match ($method) {
            'getOrdersByYear' => self::ordersByYear((int) ($args[0] ?? 0)),
            'getTestsByYear' => self::testsByYear((int) ($args[0] ?? 0)),
            'getStudiesWheel' => self::wheel((string) ($args[0] ?? '')),
            'getPaginatedTests' => self::paginatedTests(
                (string) ($args[0] ?? '0'),
                (int) ($args[1] ?? 50)
            ),
            'getOpportunityTimes' => self::opportunityTimes(
                (string) ($args[0] ?? '')
            ),
            'getTvOpportunityTimes' => self::opportunityTimes(
                now()->format('Ymd')
            ),
            'getPhlebotomyQueue' => self::phlebotomyQueue(
                (string) ($args[0] ?? '0'),
                (int) ($args[1] ?? 50)
            ),
            default => [],
        };
    }

    /**
     * Calcula exactamente la misma cantidad diaria que Toma de muestra.
     */
    private static function dayTotals(string $day): array
    {
        $seed = (int) substr($day, -2);
        $attended = 0;

        for ($door = 1; $door <= 6; $door++) {
            for ($hour = 7; $hour <= 15; $hour++) {
                $attended += 2 + (($door * 7 + $hour * 3 + $seed) % 4);
            }
        }

        return [
            'attended' => $attended,
            'appointments' => $attended + 12,
        ];
    }

    /**
     * Devuelve los totales mensuales para un año.
     */
    private static function months(int $year): array
    {
        if ($year < 2000 || $year > 2100) {
            return [];
        }

        $months = [];
        $today = now()->toDateString();

        for ($month = 1; $month <= 12; $month++) {
            $attended = 0;
            $appointments = 0;

            $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            for ($day = 1; $day <= $days; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

                // Los días futuros no tienen registros ficticios acumulados.
                if ($date > $today) {
                    continue;
                }

                $totals = self::dayTotals(str_replace('-', '', $date));

                $attended += $totals['attended'];
                $appointments += $totals['appointments'];
            }

            $months[] = [
                'month' => $month,
                'attended' => $attended,
                'appointments' => $appointments,
            ];
        }

        return $months;
    }

    /**
     * Distribuye enteros entre categorías sin perder registros.
     */
    private static function distribute(int $total, array $weights): array
    {
        $values = [];
        $assigned = 0;
        $last = count($weights) - 1;
        $sum = max(1, array_sum($weights));

        foreach ($weights as $index => $weight) {
            $count = $index === $last
                ? $total - $assigned
                : (int) floor($total * $weight / $sum);

            $values[] = $count;
            $assigned += $count;
        }

        return $values;
    }

    /**
     * GET /orders-by-year/{year}.
     */
    private static function ordersByYear(int $year): array
    {
        $months = [];

        foreach (self::months($year) as $row) {
            $months[] = [
                'month' => $row['month'],
                'orders' => $row['appointments'],
            ];
        }

        return [
            'year' => $year,
            'months' => $months,
        ];
    }

    /**
     * GET /tests-by-year/{year}.
     *
     * El controlador original calcula los totales desde áreas y estados.
     */
    private static function testsByYear(int $year): array
    {
        $months = [];
        $areaCodes = ['HEM', 'BIO', 'COA', 'INM', 'MIC'];
        $statusCodes = [0, 1, 2, 4, 6];

        foreach (self::months($year) as $row) {
            $areaCounts = self::distribute(
                $row['attended'] * 2,
                [30, 25, 20, 15, 10]
            );

            $areas = [];

            foreach ($areaCodes as $index => $areaCode) {
                $counts = self::distribute(
                    $areaCounts[$index],
                    [12, 6, 9, 53, 20]
                );

                $statuses = [];

                foreach ($statusCodes as $statusIndex => $statusCode) {
                    $statuses[] = [
                        'status' => $statusCode,
                        'count' => $counts[$statusIndex],
                    ];
                }

                $areas[] = [
                    'area' => $areaCode,
                    'statuses' => $statuses,
                ];
            }

            $months[] = [
                'month' => $row['month'],
                'areas' => $areas,
            ];
        }

        return [
            'year' => $year,
            'months' => $months,
        ];
    }

    /**
     * Recupera las mismas órdenes sintéticas del mapa de Toma de muestra.
     */
    private static function dayRecords(string $day): array
    {
        $date = DateTimeImmutable::createFromFormat('!Ymd', $day);

        if (!$date || $date->format('Ymd') !== $day) {
            return [];
        }

        $sample = VcareDemoData::sampleCollection($day);
        $records = [];
        $index = 0;

        $areaCodes = ['HEM', 'BIO', 'COA', 'INM', 'MIC'];

        foreach (($sample['averageTimeByDoorAndHour'] ?? []) as $cell) {
            $door = (int) ($cell['doorId'] ?? $cell['door'] ?? 1);
            $hour = (int) ($cell['hour'] ?? 7);

            foreach (($cell['orderNumbers'] ?? []) as $order) {
                $minute = ($index * 7 + $door * 3) % 55;

                $records[] = [
                    'orderNumber' => (string) $order,
                    'doorID' => $door,
                    'hour' => $hour,
                    'index' => $index,
                    'area' => $areaCodes[$index % count($areaCodes)],
                    'moment' => sprintf(
                        '%s %02d:%02d:00',
                        $date->format('Y-m-d'),
                        $hour,
                        $minute
                    ),
                ];

                $index++;
            }
        }

        return $records;
    }

    private static function opportunityMinutes(int $index): int
    {
        return 25 + (($index * 13) % 91);
    }

    private static function opportunityStatus(int $minutes): int
    {
        return $minutes <= 60 ? 1 : ($minutes <= 90 ? 2 : 3);
    }

    /**
     * GET /rueda/{date}: órdenes para Consulta externa.
     */
    private static function wheel(string $day): array
    {
        $orders = [];

        foreach (self::dayRecords($day) as $record) {
            $index = $record['index'];

            // Una muestra visual distribuida entre todas las horas.
            if ($index % 2 !== 0) {
                continue;
            }

            $minutes = self::opportunityMinutes($index);
            $moment = new DateTimeImmutable($record['moment']);

            $orders[] = [
                'orderNumber' => $record['orderNumber'],
                'fecha_cita' => $record['moment'],
                'seenAt' => $moment->modify('+2 minutes')->format('Y-m-d H:i:s'),
                'attention_time' => $minutes,
                'attention_time_status' => self::opportunityStatus($minutes),
                'seg_hours' => $record['hour'] - 5,
                'doorID' => $record['doorID'],
                'phlebotomist' => [
                    'id' => ($index % 6) + 1,
                    'firstName' => 'Personal',
                    'lastName' => 'Demo ' . (($index % 6) + 1),
                    'username' => 'demo-' . (($index % 6) + 1),
                ],
                'phlebotomy_start_at' => $moment
                    ->modify('+8 minutes')->format('Y-m-d H:i:s'),
                'phlebotomy_end_at' => $moment
                    ->modify('+17 minutes')->format('Y-m-d H:i:s'),
                'phlebotomy_time' => 9,
                'phlebotomy_time_status' => 1,
                'tubesCount' => 2 + ($index % 4),
                'pendingAttention' => false,
                'areas' => [[
                    'areaName' => $record['area'],
                    'profiles' => [[
                        'profileName' => 'Perfil de demostración',
                        'tests' => [
                            ['testName' => 'Glucosa', 'result' => '', 'unit' => ''],
                            ['testName' => 'Biometría hemática', 'result' => '', 'unit' => ''],
                        ],
                    ]],
                ]],
            ];
        }

        return [
            'pending_appointments_count' => 12,
            'orders' => $orders,
        ];
    }

    /**
     * GET /opp-times/{date} y /opp-times-1.
     *
     * Ambas pantallas comparten números de orden, estados y tiempos.
     */
    private static function opportunityTimes(string $day): array
    {
        $orders = [];

        foreach (self::dayRecords($day) as $record) {
            $index = $record['index'];

            if ($index % 2 !== 0) {
                continue;
            }

            $minutes = self::opportunityMinutes($index);
            $status = [0, 1, 2, 4][$index % 4];

            $orders[] = [
                'orderNumber' => $record['orderNumber'],
                'typeInitial' => $index % 8 === 0 ? 'S' : 'R',
                'orderStatus' => $status,
                'orderCreatedDate' => substr($day, 0, 4)
                    . '-' . substr($day, 4, 2)
                    . '-' . substr($day, 6, 2),
                'createdAt' => $record['moment'],
                'opTime' => $minutes,
                'serviceName' => $index % 3 === 0
                    ? 'Hospitalización'
                    : 'Consulta externa',
                'areas' => [[
                    'area' => $record['area'],
                    'status' => $status,
                ]],
            ];
        }

        return $orders;
    }

    /**
     * GET /phlebotomy_queue/{cursor}-{pageSize}.
     *
     * La cola usa los mismos números de orden que Toma de muestra.
     */
    private static function phlebotomyQueue(
        string $cursor,
        int $pageSize
    ): array {
        $day = now()->format('Ymd');

        $rows = self::dayRecords($day);
        $total = count($rows);

        $offset = ctype_digit($cursor) ? (int) $cursor : $total;
        $size = max(1, min(100, $pageSize));

        $page = array_slice($rows, $offset, $size);

        $data = [];

        foreach ($page as $record) {
            $index = $record['index'];
            $wait = 4 + (($index * 7) % 28);
            $venopuncture = 5 + ($index % 10);

            $data[] = [
                'order' => $record['orderNumber'],
                'doorID' => (string) $record['doorID'],
                'flebotomistaTime' => $wait,
                'flebotomistaTimeStatus' => $wait > 20 ? 2 : 1,
                'wait_time' => $wait,
                'VenopunctureTime' => $venopuncture,
                'VenopunctureTimeExceeded' => $venopuncture > 10,
                'complexity' => [
                    'score' => 1 + ($index % 5),
                    'estudios' => 2 + ($index % 5),
                ],
            ];
        }

        $sample = VcareDemoData::sampleCollection($day);
        $attended = (int) ($sample['summary']['totalPatients'] ?? 0);

        return [
            'data' => $data,
            'pageSize' => $size,
            'nextCursor' => ($offset + count($data)) < $total
                ? (string) ($offset + count($data))
                : null,
            'dailyTotals' => [
                'pendingOrders' => 12,
                'attendedOrders' => $attended,
            ],
        ];
    }

    /**
     * GET /studies/paginated: exámenes ficticios con paginación.
     */
    private static function paginatedTests(
        string $cursor,
        int $pageSize
    ): array {
        $rows = [];
        $day = now()->format('Ymd');

        foreach (self::dayRecords($day) as $record) {
            $index = $record['index'];
            $minutes = self::opportunityMinutes($index);

            $rows[] = [
                'orderNumber' => $record['orderNumber'],
                'areaInitial' => $record['area'],
                'profileName' => 'Examen ficticio ' . (1 + ($index % 8)),
                'validatedAt' => $record['moment'],
                'opTime' => $minutes,
                'opTimeStatus' => self::opportunityStatus($minutes),
                'status' => [0, 1, 2, 4, 6][$index % 5],
            ];
        }

        $total = count($rows);
        $offset = ctype_digit($cursor) ? (int) $cursor : $total;
        $size = in_array($pageSize, [50, 100], true) ? $pageSize : 50;

        $data = array_slice($rows, $offset, $size);

        return [
            'data' => $data,
            'pageSize' => $size,
            'nextCursor' => ($offset + count($data)) < $total
                ? (string) ($offset + count($data))
                : null,
        ];
    }
}