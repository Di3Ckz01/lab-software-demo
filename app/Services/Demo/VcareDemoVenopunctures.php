<?php

declare(strict_types=1);

namespace App\Services\Demo;

use DateTimeImmutable;

/**
 * Simula las venopunciones duplicadas por mes.
 *
 * No contiene información de pacientes reales.
 * Cada periodo genera datos repetibles para las pruebas.
 */
final class VcareDemoVenopunctures
{
    public static function byMonth(string $period): array
    {
        if (!preg_match('/^\d{6}$/D', $period)) {
            return ['data' => []];
        }

        $date = DateTimeImmutable::createFromFormat('!Ym', $period);

        if (!$date || $date->format('Ym') !== $period) {
            return ['data' => []];
        }

        $patients = [];

        $catalog = [
            ['GLU', 'Glucosa'],
            ['CRE', 'Creatinina'],
            ['URE', 'Urea'],
            ['BH', 'Biometría hemática'],
            ['TP', 'Tiempo de protrombina'],
            ['TSH', 'Hormona estimulante tiroidea'],
            ['PCR', 'Proteína C reactiva'],
        ];

        $seed = (int) $date->format('m');

        for ($patient = 1; $patient <= 12; $patient++) {
            $orders = [];
            $orderCount = 1 + ($patient % 2);

            for ($order = 1; $order <= $orderCount; $order++) {
                $profiles = [];

                $profileCount = 1 + (($patient + $order) % 2);

                for ($profile = 1; $profile <= $profileCount; $profile++) {
                    $duplicatedTests = [];

                    $testCount = 1 + (
                        ($patient + $order + $profile + $seed) % 3
                    );

                    for ($test = 0; $test < $testCount; $test++) {
                        $index = (
                            $patient + $order + $profile + $test + $seed
                        ) % count($catalog);

                        $item = $catalog[$index];

                        $duplicatedTests[] = [
                            'testID' => 'DEMO-' . $item[0],
                            'testAbbreviation' => $item[0],
                            'testName' => $item[1],
                        ];
                    }

                    $profiles[] = [
                        'profileID' => 'DEMO-PRO-' . $profile,
                        'profileName' => $profile === 1
                            ? 'Perfil metabólico (Demo)'
                            : 'Perfil complementario (Demo)',
                        'profileAbbreviation' => 'PD-' . $profile,
                        'duplicatedTests' => $duplicatedTests,
                    ];
                }

                $sequence = ($patient * 10) + $order;

                $orders[] = [
                    'orderNumber' => $period . sprintf('%06d', $sequence),
                    'profiles' => $profiles,
                ];
            }

            $patients[] = [
                'patientNumber' => sprintf('DEMO-%03d', $patient),
                'patientID' => sprintf('FICTICIO-%03d', $patient),
                'patientName' => sprintf('Paciente Ficticio %02d', $patient),
                'fatherLastName' => '',
                'motherLastName' => '',
                'orders' => $orders,
            ];
        }

        return [
            'demo' => true,
            'data' => $patients,
        ];
    }
}