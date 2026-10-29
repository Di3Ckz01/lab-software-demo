<?php

declare(strict_types=1);

namespace App\Services\Demo;

use DateTimeImmutable;

/**
 * Resumen ejecutivo ficticio para Analítica de Toma de muestra.
 *
 * Reutiliza la simulación diaria de Toma de muestra para conservar
 * los mismos pacientes atendidos en ambos tableros.
 *
 * No accede a datos clínicos, archivos ni servicios HTTP.
 */
final class VcareDemoAnalytics
{
    /**
     * Construye el contrato de GET /executive-summary/{from}-{to}.
     */
    public static function summary(
        string $from,
        string $to,
        int $graceMinutes = 15,
        int $multiOrderThreshold = 2
    ): array {
        $start = DateTimeImmutable::createFromFormat('!Ymd', $from);
        $end = DateTimeImmutable::createFromFormat('!Ymd', $to);

        if (
            !$start || !$end ||
            $start->format('Ymd') !== $from ||
            $end->format('Ymd') !== $to ||
            $start > $end ||
            $start->diff($end)->days > 365
        ) {
            return [];
        }

        $ageGroups = ['0-17', '18-39', '40-59', '60-69', '70-79', '80+'];
        $ageWeights = [9, 33, 28, 15, 10, 5];
        $ageMidpoints = [9, 28, 49, 65, 74, 84];

        $staff = [
            'Flebotomista Demo 01',
            'Flebotomista Demo 02',
            'Flebotomista Demo 03',
            'Flebotomista Demo 04',
            'Flebotomista Demo 05',
            'Flebotomista Demo 06',
            'Flebotomista Demo 07',
            'Flebotomista Demo 08',
        ];

        $staffWeights = [17, 15, 14, 13, 12, 11, 10, 8];

        $monthly = [];
        $ages = array_fill_keys($ageGroups, 0);
        $phlebotomists = array_fill_keys($staff, 0);

        $appointments = 0;
        $attended = 0;
        $pending = 0;
        $notArrived = 0;

        $weightedWait = 0.0;
        $weightedPhlebotomy = 0.0;
        $weightedAge = 0.0;

        for (
            $cursor = $start;
            $cursor <= $end;
            $cursor = $cursor->modify('+1 day')
        ) {
            $day = $cursor->format('Ymd');

            // Datos idénticos a los usados por Toma de muestra.
            $sample = VcareDemoData::sampleCollection($day);

            $dayAttended = max(
                0,
                (int) ($sample['summary']['totalPatients'] ?? 0)
            );

            $dayAppointments = max(
                $dayAttended,
                (int) ($sample['totalAppointments'] ?? $dayAttended)
            );

            $dayPending = $dayAppointments - $dayAttended;

            $seed = (int) $cursor->format('z') + (int) $cursor->format('n');

            $appointments += $dayAppointments;
            $attended += $dayAttended;
            $pending += $dayPending;

            $notArrived += min($dayPending, 4 + ($seed % 5));

            $waitMinutes = 15 + ($seed % 9) * 1.25;
            $phlebotomyMinutes = 6.5 + ($seed % 7) * 0.55;

            $weightedWait += $dayAttended * $waitMinutes;
            $weightedPhlebotomy += $dayAttended * $phlebotomyMinutes;

            // Estados mensuales para la gráfica de citas.
            $month = $cursor->format('Ym');

            if (!isset($monthly[$month])) {
                $monthly[$month] = [
                    'month' => $month,
                    'pendingAppointment' => 0,
                    'attendedAppointment' => 0,
                ];
            }

            $monthly[$month]['pendingAppointment'] += $dayPending;
            $monthly[$month]['attendedAppointment'] += $dayAttended;

            // Distribuir los pacientes entre los grupos de edad.
            $dailyAges = self::distribute($dayAttended, $ageWeights);

            foreach ($ageGroups as $index => $name) {
                $count = $dailyAges[$index];

                $ages[$name] += $count;
                $weightedAge += $count * $ageMidpoints[$index];
            }

            // Distribución entre flebotomistas ficticios.
            $dailyStaff = self::distribute($dayAttended, $staffWeights);

            foreach ($staff as $index => $name) {
                $phlebotomists[$name] += $dailyStaff[$index];
            }
        }

        ksort($monthly);

        $byAgeGroup = [];

        foreach ($ages as $name => $count) {
            $byAgeGroup[] = [
                'name' => $name,
                'count' => $count,
            ];
        }

        $byPhlebotomist = [];

        foreach ($phlebotomists as $name => $count) {
            $byPhlebotomist[] = [
                'name' => $name,
                'count' => $count,
            ];
        }

        usort(
            $byPhlebotomist,
            static fn (array $a, array $b): int =>
                $b['count'] <=> $a['count']
        );

        return [
            'demo' => true,
            'generatedAt' => now()->toIso8601String(),
            'filters' => [
                'from' => $from,
                'to' => $to,
                'graceMinutes' => $graceMinutes,
                'n' => $multiOrderThreshold,
            ],
            'errors' => [],

            'orders' => [
                'total' => $appointments,

                'states' => [
                    'attendedAppointment' => $attended,
                    'pendingAppointment' => $pending,
                    'byMonth' => array_values($monthly),
                ],

                'appointments' => [
                    'compliance' => [
                        'notArrived' => $notArrived,
                    ],
                    'waitTime' => [
                        'avgMinutes' => $attended > 0
                            ? round($weightedWait / $attended, 2)
                            : 0,
                    ],
                    'phlebotomyTime' => [
                        'avgMinutes' => $attended > 0
                            ? round($weightedPhlebotomy / $attended, 2)
                            : 0,
                    ],
                ],

                'attention' => [
                    'byPhlebotomist' => $byPhlebotomist,
                ],
            ],

            'patients' => [
                'avgAge' => $attended > 0
                    ? round($weightedAge / $attended, 1)
                    : 0,
                'byAgeGroup' => $byAgeGroup,
            ],
        ];
    }

    /**
     * Reparte un total entre categorías sin perder registros
     * por redondeo.
     */
    private static function distribute(
        int $total,
        array $weights
    ): array {
        $result = [];
        $assigned = 0;
        $last = count($weights) - 1;
        $weightTotal = array_sum($weights);

        foreach ($weights as $index => $weight) {
            if ($index === $last) {
                $count = $total - $assigned;
            } else {
                $count = (int) floor(
                    $total * $weight / max(1, $weightTotal)
                );
            }

            $result[] = $count;
            $assigned += $count;
        }

        return $result;
    }
}