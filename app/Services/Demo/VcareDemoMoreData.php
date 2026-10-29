<?php

declare(strict_types=1);

namespace App\Services\Demo;

use DateTimeImmutable;

/**
 * Proporciona información sintética para Productividad y Proveedores.
 *
 * Las respuestas se generan localmente, sin bases de datos ni HTTP.
 * La misma fecha produce siempre las mismas cifras.
 */
final class VcareDemoMoreData
{
    private const SUPPLIERS = [
        ['id' => 'DEMO-P1', 'name' => 'Laboratorio Norte (Demo)', 'status' => 'active'],
        ['id' => 'DEMO-P2', 'name' => 'Diagnóstico Central (Demo)', 'status' => 'active'],
        ['id' => 'DEMO-P3', 'name' => 'Servicios Especializados (Demo)', 'status' => 'active'],
    ];

    private const EXAMS = [
        ['001001', 'GLU', 'Glucosa sérica'],
        ['001002', 'URE', 'Urea'],
        ['001003', 'CRE', 'Creatinina'],
        ['001004', 'COL', 'Colesterol total'],
        ['001005', 'TRI', 'Triglicéridos'],
        ['001006', 'AU', 'Ácido úrico'],
        ['002001', 'BH', 'Biometría hemática'],
        ['002002', 'RET', 'Reticulocitos'],
        ['002003', 'VSG', 'Velocidad de sedimentación'],
        ['003001', 'TP', 'Tiempo de protrombina'],
        ['003002', 'TTP', 'Tiempo parcial de tromboplastina'],
        ['003003', 'FIB', 'Fibrinógeno'],
        ['004001', 'TSH', 'Hormona estimulante tiroidea'],
        ['004002', 'T4L', 'Tiroxina libre'],
        ['004003', 'HB1', 'Hemoglobina glucosilada'],
        ['005001', 'PCR', 'Proteína C reactiva'],
        ['005002', 'FR', 'Factor reumatoide'],
        ['005003', 'VITD', 'Vitamina D'],
        ['006001', 'EGO', 'Examen general de orina'],
        ['006002', 'UROC', 'Urocultivo'],
        ['006003', 'COPRO', 'Coproparasitoscópico'],
    ];

    /**
     * Mismas condiciones que el generador inicial.
     */
    public static function enabled(): bool
    {
        return VcareDemoData::enabled()
            && (bool) config('services.go_api.use_mock', false);
    }

    /**
     * Genera registros validados que el controlador de Productividad
     * transformará en sus indicadores, filtros y gráficas originales.
     */
    public static function productivity(
        ?string $date,
        ?int $scopeSuffix = null
    ): array {
        $raw = preg_replace('/[^0-9]/', '', (string) $date) ?: '';

        $day = DateTimeImmutable::createFromFormat('!Ymd', $raw);

        if (!$day || $day->format('Ymd') !== $raw) {
            return [];
        }

        $rows = [];
        $seed = (int) $day->format('d');
        $dateText = $day->format('Y-m-d');

        $areas = ['HEM', 'BIO', 'COA', 'INM', 'MIC'];
        $tests = [
            ['BH', 'Biometría hemática'],
            ['GLU', 'Glucosa'],
            ['TP', 'Tiempo de protrombina'],
            ['TSH', 'TSH'],
            ['UROC', 'Urocultivo'],
        ];

        for ($order = 1; $order <= 96; $order++) {
            $serviceID = ($order % 3 === 0) ? 1 : 0;

            if ($scopeSuffix !== null && $serviceID !== $scopeSuffix) {
                continue;
            }

            $hour = 7 + ($order % 10);
            $minute = ($order * 7 + $seed) % 50;

            $created = new DateTimeImmutable(
                sprintf('%s %02d:%02d:00', $dateText, $hour, $minute)
            );

            $orderNumber = $raw . str_pad(
                (string) $order,
                4,
                '0',
                STR_PAD_LEFT
            );

            $examCount = 1 + ($order % 3);

            for ($exam = 0; $exam < $examCount; $exam++) {
                $testIndex = ($order + $exam) % count($tests);
                $userNumber = 1 + (($order + $exam) % 5);

                $opTime = 23 + (
                    ($order * 11 + $exam * 17 + $seed) % 92
                );

                $validated = $created->modify(
                    '+' . $opTime . ' minutes'
                );

                $rows[] = [
                    'order' => $orderNumber,
                    'type' => $order % 7 === 0 ? 'S' : 'R',
                    'serviceID' => $serviceID,
                    'serviceName' => $serviceID === 0
                        ? 'Consulta externa'
                        : 'Hospitalización',
                    'area' => $areas[$testIndex],
                    'testID' => $tests[$testIndex][0],
                    'testName' => $tests[$testIndex][1],
                    'userId' => 'DEMO-' . $userNumber,
                    'userName' => 'Validador Demo ' . $userNumber,
                    'shift' => $hour < 14 ? 'morning' : 'evening',
                    'createdAt' => $created->format('Y-m-d H:i:s'),
                    'validatedAt' => $validated->format('Y-m-d H:i:s'),
                    'status' => $exam % 2 === 0 ? 4 : 6,
                    'opTime' => $opTime,
                ];
            }
        }

        return $rows;
    }

    /**
     * Respuestas ficticias compatibles con VcareExtendedApi.
     * Los nombres de las claves corresponden a config/services.php.
     */
    public static function read(
        string $key,
        array $parameters,
        array $query
    ): ?array {
        return match ($key) {
            'supplier_endpoint' => ['data' => self::SUPPLIERS],

            'clinical_studies_available_periods_endpoint'
                => self::availablePeriods(),

            'clinical_studies_catalog_endpoint'
                => self::catalog(
                    (int) ($parameters['{page}'] ?? 0),
                    (string) ($query['clinicalStudyNumber'] ?? '')
                ),

            'supplier_studies_endpoint'
                => self::supplierStudies(
                    (string) ($parameters['{supplierID}'] ?? '')
                ),

            'clinical_studies_totals_endpoint'
                => self::monthlyTotals(
                    (int) ($parameters['{year}'] ?? 0),
                    (int) ($parameters['{monthNumber}'] ?? 0),
                    (string) ($query['supplierID'] ?? '')
                ),

            'clinical_studies_pending_endpoint'
                => self::historicalPending(
                    (int) ($parameters['{year}'] ?? 0),
                    (int) ($parameters['{monthNumber}'] ?? 0),
                    (string) ($query['supplierID'] ?? '')
                ),

            'clinical_study_endpoint'
                => self::monthlyStudies(
                    (string) ($parameters['{start}'] ?? '')
                ),

            default => null,
        };
    }

    private static function studies(): array
    {
        $result = [];

        foreach (self::EXAMS as $index => $exam) {
            $supplier = self::SUPPLIERS[$index % count(self::SUPPLIERS)];

            $result[] = [
                'clinicalStudyID' => 1001 + $index,
                'clinicalStudyNumber' => $exam[0],
                'clinicalStudyAbbrev' => $exam[1],
                'clinicalStudyName' => $exam[2],
                'supplierID' => $supplier['id'],
                'supplierName' => $supplier['name'],
            ];
        }

        return $result;
    }

    private static function availablePeriods(): array
    {
        $nowYear = (int) now()->format('Y');
        $nowMonth = (int) now()->format('n');
        $years = [];

        foreach ([$nowYear - 1, $nowYear] as $year) {
            $lastMonth = $year === $nowYear ? $nowMonth : 12;

            $years[] = [
                'year' => $year,
                'months' => range(1, $lastMonth),
            ];
        }

        return ['years' => $years];
    }

    private static function catalog(
        int $page,
        string $number
    ): array {
        $studies = self::studies();

        if ($number !== '') {
            $studies = array_values(array_filter(
                $studies,
                static fn (array $row): bool =>
                    str_contains($row['clinicalStudyNumber'], $number)
            ));
        }

        $size = 50;

        return [
            'page' => $page,
            'pageSize' => $size,
            'hasMore' => ($page + 1) * $size < count($studies),
            'data' => array_slice($studies, $page * $size, $size),
        ];
    }

    private static function supplierStudies(string $supplierID): array
    {
        return [
            'studies' => array_values(array_filter(
                self::studies(),
                static fn (array $study): bool =>
                    $study['supplierID'] === $supplierID
            )),
        ];
    }

    private static function quantities(
        int $year,
        int $month
    ): array {
        $rows = [];

        foreach (self::studies() as $index => $study) {
            $quantity = 8 + (
                ($index * 7 + $year + $month * 3) % 22
            );

            $proper = (int) floor($quantity * 0.74);
            $late = (int) floor($quantity * 0.16);
            $pending = $quantity - $proper - $late;

            $rows[] = array_merge($study, [
                'clinicalStudyQuantity' => $quantity,
                'clinicalStudyStatus' => 4,
                '_proper' => $proper,
                '_late' => $late,
                '_pending' => $pending,
            ]);
        }

        return $rows;
    }

    private static function monthlyStudies(string $start): array
    {
        $day = DateTimeImmutable::createFromFormat('!Ymd', $start);

        if (!$day || $day->format('Ymd') !== $start) {
            return ['data' => []];
        }

        $rows = self::quantities(
            (int) $day->format('Y'),
            (int) $day->format('n')
        );

        // Los campos internos no forman parte de la respuesta pública.
        return [
            'data' => array_map(
                static function (array $row): array {
                    unset($row['_proper'], $row['_late'], $row['_pending']);
                    return $row;
                },
                $rows
            ),
        ];
    }

    private static function emptyTotals(): array
    {
        return [
            'properlyValidated' => 0,
            'unProperlyValidated' => 0,
            'currentPeriod_stillPending' => 0,
            'allStudies' => 0,
            'lastPeriods_stillPending' => 0,
        ];
    }

    private static function monthlyTotals(
        int $year,
        int $month,
        string $supplierID
    ): array {
        $total = self::emptyTotals();
        $bySupplier = [];

        foreach (self::SUPPLIERS as $supplier) {
            if ($supplierID !== '' && $supplier['id'] !== $supplierID) {
                continue;
            }

            $supplierTotal = self::emptyTotals();

            foreach (self::quantities($year, $month) as $study) {
                if ($study['supplierID'] !== $supplier['id']) {
                    continue;
                }

                $supplierTotal['properlyValidated'] += $study['_proper'];
                $supplierTotal['unProperlyValidated'] += $study['_late'];
                $supplierTotal['currentPeriod_stillPending'] += $study['_pending'];
                $supplierTotal['allStudies'] += $study['clinicalStudyQuantity'];
            }

            $supplierTotal['lastPeriods_stillPending'] = 4;

            foreach ($total as $key => $value) {
                $total[$key] += $supplierTotal[$key];
            }

            $bySupplier[] = [
                'supplierID' => $supplier['id'],
                'supplierName' => $supplier['name'],
                'supplierTotals' => $supplierTotal,
            ];
        }

        return [
            'year' => $year,
            'monthNumber' => $month,
            'monthTotals' => $total,
            'suppliers' => $bySupplier,
            'lastUpdatedAt' => now()->toIso8601String(),
        ];
    }

    private static function historicalPending(
        int $year,
        int $month,
        string $supplierID
    ): array {
        $reference = new DateTimeImmutable(
            sprintf('%04d-%02d-01', $year, $month)
        );

        $periods = [];
        $total = 0;

        for ($offset = 1; $offset <= 2; $offset++) {
            $period = $reference->modify("-{$offset} months");
            $orders = [];

            foreach (self::SUPPLIERS as $supplierIndex => $supplier) {
                if ($supplierID !== '' && $supplier['id'] !== $supplierID) {
                    continue;
                }

                for ($item = 1; $item <= 2; $item++) {
                    $studyIndex = $supplierIndex + ($item - 1) * 3;
                    $study = self::studies()[$studyIndex];

                    $orders[] = [
                        'orderNumber' => $period->format('Ymd')
                            . sprintf('%04d', $supplierIndex * 10 + $item),
                        'orderCreatedDate' => $period->format('Y-m-d'),
                        'clinicalStudyID' => $study['clinicalStudyID'],
                        'clinicalStudyName' => $study['clinicalStudyName'],
                        'validatedAt' => null,
                        'supplierID' => $supplier['id'],
                        'supplierName' => $supplier['name'],
                    ];
                }
            }

            $total += count($orders);

            $periods[] = [
                'year' => (int) $period->format('Y'),
                'month' => (int) $period->format('n'),
                'count' => count($orders),
                'orders' => $orders,
            ];
        }

        return [
            'year' => $year,
            'monthNumber' => $month,
            'supplierID' => $supplierID !== '' ? $supplierID : null,
            'lastPeriods_stillPending' => $total,
            'byPeriod' => $periods,
        ];
    }
}