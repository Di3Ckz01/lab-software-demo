<?php

namespace App\Services\Demo;

use DateTimeImmutable;

/**
 * Datos exclusivamente ficticios para demostrar V-Care.
 *
 * No consulta bases de datos ni servicios externos.
 * Sus cifras son deterministas para cada fecha.
 */
final class VcareDemoData
{
    public static function enabled(): bool
    {
        return app()->environment('local')
            && config('vcare_demo.enabled', false) === true
            && session('vcare_demo_session') === true
            && trim((string) config('services.go_api.base_url', '')) === '';
    }

    /**
     * Simula la respuesta de GET /tomaDeMuestra/{day}.
     */
    public static function sampleCollection(string $day): array
    {
        $date = DateTimeImmutable::createFromFormat('!Ymd', $day);

        if (!$date || $date->format('Ymd') !== $day) {
            return [];
        }

        $seed = (int) substr($day, -2);

        $byHour = [];
        $byDoor = [];
        $heatmap = [];

        $total = 0;
        $within60 = 0;
        $over60 = 0;
        $weightedMinutes = 0;

        for ($hour = 7; $hour <= 15; $hour++) {
            $byHour[$hour] = [
                'hour' => (string) $hour,
                'totalPatients' => 0,
                'patients' => 0,
                'averageAttentionMinutes' => 0,
                '_weighted' => 0,
            ];
        }

        for ($door = 1; $door <= 6; $door++) {
            $byDoor[$door] = [
                'door' => (string) $door,
                'doorId' => (string) $door,
                'doorName' => "Puerta {$door}",
                'totalPatients' => 0,
                'patients' => 0,
                'averageAttentionMinutes' => 0,
                '_weighted' => 0,
            ];

            for ($hour = 7; $hour <= 15; $hour++) {
                $count = 2 + (($door * 7 + $hour * 3 + $seed) % 4);
                $minutes = 25 + (($door * 9 + $hour * 5 + $seed) % 47);

                $orders = [];

                for ($i = 0; $i < $count; $i++) {
                    $suffix = $hour * 100 + $door * 10 + $i;

                    $orders[] = $day . str_pad(
                        (string) $suffix,
                        4,
                        '0',
                        STR_PAD_LEFT
                    );
                }

                $total += $count;
                $weightedMinutes += $count * $minutes;

                if ($minutes <= 60) {
                    $within60 += $count;
                } else {
                    $over60 += $count;
                }

                $byHour[$hour]['totalPatients'] += $count;
                $byHour[$hour]['patients'] += $count;
                $byHour[$hour]['_weighted'] += $count * $minutes;

                $byDoor[$door]['totalPatients'] += $count;
                $byDoor[$door]['patients'] += $count;
                $byDoor[$door]['_weighted'] += $count * $minutes;

                $heatmap[] = [
                    'door' => (string) $door,
                    'doorId' => (string) $door,
                    'doorName' => "Puerta {$door}",
                    'hour' => (string) $hour,
                    'totalPatients' => $count,
                    'patients' => $count,
                    'averageAttentionMinutes' => $minutes,
                    'orderNumbers' => $orders,
                ];
            }
        }

        foreach ($byHour as &$row) {
            $row['averageAttentionMinutes'] = round(
                $row['_weighted'] / $row['totalPatients'],
                1
            );

            unset($row['_weighted']);
        }
        unset($row);

        foreach ($byDoor as &$row) {
            $row['averageAttentionMinutes'] = round(
                $row['_weighted'] / $row['totalPatients'],
                1
            );

            unset($row['_weighted']);
        }
        unset($row);

        return [
            'success' => true,
            'demo' => true,
            'generatedAt' => now()->toIso8601String(),

            'filters' => [
                'startDate' => $date->format('Y-m-d'),
                'endDate' => $date->format('Y-m-d'),
            ],

            'totalAppointments' => $total + 12,

            'summary' => [
                'totalPatients' => $total,
                'patientsWithin60' => $within60,
                'patientsOver60' => $over60,
                'averageAttentionMinutes' => round(
                    $weightedMinutes / max(1, $total),
                    1
                ),
            ],

            'hours' => array_map('strval', range(7, 15)),
            'doors' => array_map('strval', range(1, 6)),

            'patientsByHour' => array_values($byHour),
            'patientsByDoor' => array_values($byDoor),
            'averageTimeByDoorAndHour' => $heatmap,
        ];
    }
}