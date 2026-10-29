<?php

namespace App\Http\Controllers;

use App\Pdf\OrderTraceDomPdfDriver;
use App\Services\GoApiService;
use Carbon\Carbon;
use Throwable;

use function Spatie\LaravelPdf\Support\pdf;

class OrderTraceController extends Controller
{
    /**
     * Genera el reporte PDF de la trazabilidad de una orden de Toma de muestra.
     */
    public function pdf(string $orderNumber, GoApiService $goApiService): mixed
    {
        $this->validateOrderNumber($orderNumber);

        $response = $goApiService->getOrderTrace($orderNumber);

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        if (is_array($response) && (int) ($response['__httpStatus'] ?? 0) === 404) {
            abort(404, (string) ($response['message'] ?? 'Orden no encontrada.'));
        }

        if (!is_array($response)) {
            abort(502, $this->apiErrorMessage(
                $goApiService,
                'No se pudo consultar la trazabilidad de la orden para generar el PDF.'
            ));
        }

        if (($response['success'] ?? true) === false) {
            abort(502, (string) ($response['message'] ?? 'La API no pudo completar la consulta.'));
        }

        $report = $this->buildReport($response, $orderNumber);

        $footerText = 'Reporte generado desde Toma de muestra · Orden ' . $report['orderNumber'];

        return pdf('pdf.order-trace', [
            'report' => $report,
        ])
            // Driver DOMPDF personalizado para agregar pie y paginado real en todas las páginas.
            ->setDriver(new OrderTraceDomPdfDriver(
                config('laravel-pdf.dompdf', []),
                $footerText
            ))
            ->format('letter')
            ->margins(10, 11, 13, 11)
            ->headers([
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ])
            ->download('toma-muestra-' . $orderNumber . '.pdf');
    }

    /**
     * Valida únicamente el contrato oficial de 12 dígitos.
     */
    private function validateOrderNumber(string $orderNumber): void
    {
        if (!preg_match('/^\d{12}$/D', $orderNumber)) {
            abort(400, 'El número de orden debe tener 12 dígitos.');
        }
    }

    private function buildReport(array $trace, string $requestedOrderNumber): array
    {
        $order = is_array($trace['order'] ?? null) ? $trace['order'] : [];
        $patient = is_array($trace['patient'] ?? null) ? $trace['patient'] : [];
        $timeline = is_array($trace['timeline'] ?? null) ? $trace['timeline'] : [];
        $durations = is_array($trace['durations'] ?? null) ? $trace['durations'] : [];
        $studies = is_array($trace['studies'] ?? null) ? $trace['studies'] : [];
        $alerts = is_array($trace['alerts'] ?? null) ? $trace['alerts'] : [];
        $phlebotomist = is_array($order['phlebotomist'] ?? null) ? $order['phlebotomist'] : [];
        $authUser = session('auth_user', []);
        $generatedBy = is_array($authUser)
            ? trim((string) ($authUser['name'] ?? $authUser['email'] ?? ''))
            : '';

        $eventTimes = [];
        foreach ($timeline as $event) {
            if (!is_array($event)) {
                continue;
            }

            $eventName = trim((string) ($event['event'] ?? ''));
            if ($eventName !== '') {
                $eventTimes[$eventName] = $event['at'] ?? null;
            }
        }

        $patientName = trim((string) ($patient['fullName'] ?? ''));
        if ($patientName === '') {
            $patientName = trim(implode(' ', array_filter([
                $patient['firstName'] ?? null,
                $patient['fatherLastName'] ?? null,
                $patient['motherLastName'] ?? null,
            ], static fn ($value): bool => is_scalar($value) && trim((string) $value) !== '')));
        }

        $phlebotomistName = trim(implode(' ', array_filter([
            $phlebotomist['firstName'] ?? null,
            $phlebotomist['lastName'] ?? null,
        ], static fn ($value): bool => is_scalar($value) && trim((string) $value) !== '')));

        $createdAt = $order['orderCreatedAt'] ?? $order['orderCreatedDate'] ?? null;
        $attentionMinutes = $this->attentionMinutes($durations, $eventTimes);

        $normalizedStudies = [];
        foreach ($studies as $study) {
            if (!is_array($study)) {
                continue;
            }

            $tests = [];
            foreach ((is_array($study['tests'] ?? null) ? $study['tests'] : []) as $test) {
                if (!is_array($test)) {
                    continue;
                }

                $validatedBy = is_array($test['validatedBy'] ?? null) ? $test['validatedBy'] : [];
                $validatorName = trim(implode(' ', array_filter([
                    $validatedBy['firstName'] ?? null,
                    $validatedBy['lastName'] ?? null,
                ], static fn ($value): bool => is_scalar($value) && trim((string) $value) !== '')));

                $referenceValues = array_filter([
                    $test['referenceMin'] ?? null,
                    $test['referenceMax'] ?? null,
                ], static fn ($value): bool => $value !== null && $value !== '');

                $tests[] = [
                    'name' => $this->firstText([
                        $test['testName'] ?? null,
                        $test['testAbbreviation'] ?? null,
                        $test['testNumber'] ?? null,
                    ], 'Prueba'),
                    'result' => ($test['result'] ?? null) === null || ($test['result'] ?? '') === ''
                        ? '—'
                        : (string) $test['result'],
                    'unit' => trim((string) ($test['unit'] ?? '')),
                    'area' => $this->firstText([
                        $test['areaName'] ?? null,
                        $test['laboratory'] ?? null,
                    ], 'Área no especificada'),
                    'reference' => $referenceValues ? implode(' – ', array_map('strval', $referenceValues)) : '',
                    'validator' => $validatorName,
                ];
            }

            $normalizedStudies[] = [
                'name' => $this->firstText([
                    $study['studyName'] ?? null,
                    $study['studyAbbreviation'] ?? null,
                ], 'Estudio'),
                'number' => $this->firstText([$study['studyNumber'] ?? null], 'Sin código'),
                'isProfile' => (bool) ($study['isProfile'] ?? false),
                'validated' => (bool) ($study['validated'] ?? false),
                'tests' => $tests,
            ];
        }

        $normalizedAlerts = [];
        foreach ($alerts as $alert) {
            if (!is_array($alert)) {
                continue;
            }

            $normalizedAlerts[] = [
                'severity' => (string) ($alert['severity'] ?? 'info'),
                'message' => $this->firstText([
                    $alert['message'] ?? null,
                    $alert['label'] ?? null,
                    $alert['description'] ?? null,
                    $alert['code'] ?? null,
                ], 'Alerta de la orden'),
            ];
        }

        return [
            'orderNumber' => (string) ($order['orderNumber'] ?? $requestedOrderNumber),
            'state' => $this->firstText([
                is_array($order['state'] ?? null) ? ($order['state']['label'] ?? null) : null,
            ], 'Estado no disponible'),
            'patient' => [
                'name' => $patientName !== '' ? $patientName : 'Paciente no identificado',
                'temporal' => $this->displayValue($patient['patientID'] ?? null),
                'record' => $this->displayValue($patient['patientNumber'] ?? null),
                'birthDate' => $this->formatDate($patient['birthDate'] ?? null),
                'age' => $this->calculateAge($patient['birthDate'] ?? null, $createdAt),
                'gender' => $this->displayValue($patient['gender'] ?? null),
            ],
            'order' => [
                'createdAt' => $this->formatDateTime($createdAt),
                'service' => $this->displayValue($order['serviceName'] ?? null),
                'type' => $this->firstText([
                    $order['typeName'] ?? null,
                    $order['typeInitial'] ?? null,
                ], '—'),
            ],
            'phlebotomy' => [
                'door' => $this->firstText([$order['doorName'] ?? null], 'Sin registro'),
                'phlebotomist' => $phlebotomistName !== '' ? $phlebotomistName : 'Sin registro',
                'start' => $this->formatDateTime($eventTimes['phlebotomyStart'] ?? null),
                'end' => $this->formatDateTime($eventTimes['phlebotomyEnd'] ?? null),
            ],
            'timeline' => [
                [
                    'label' => 'Inicio de atención',
                    'value' => $this->formatDateTime($eventTimes['arrival'] ?? null),
                    'complete' => !empty($eventTimes['arrival']),
                ],
                [
                    'label' => 'Inicio de flebotomía',
                    'value' => $this->formatDateTime($eventTimes['phlebotomyStart'] ?? null),
                    'complete' => !empty($eventTimes['phlebotomyStart']),
                ],
                [
                    'label' => 'Fin de flebotomía',
                    'value' => $this->formatDateTime($eventTimes['phlebotomyEnd'] ?? null),
                    'complete' => !empty($eventTimes['phlebotomyEnd']),
                ],
            ],
            'durations' => [
                'waiting' => $this->formatMinutes($durations['waitingMinutes'] ?? null),
                'phlebotomy' => $this->formatMinutes($durations['phlebotomyMinutes'] ?? null),
                'total' => $this->formatMinutes($attentionMinutes),
            ],
            'studies' => $normalizedStudies,
            'alerts' => $normalizedAlerts,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $generatedBy !== '' ? $generatedBy : 'Usuario',
            'logoDataUri' => $this->logoDataUri(),
        ];
    }

    private function attentionMinutes(array $durations, array $eventTimes): ?int
    {
        if (is_numeric($durations['waitingMinutes'] ?? null) && is_numeric($durations['phlebotomyMinutes'] ?? null)) {
            return max(0, (int) round(
                (float) $durations['waitingMinutes'] + (float) $durations['phlebotomyMinutes']
            ));
        }

        $arrival = $eventTimes['arrival'] ?? null;
        $end = $eventTimes['phlebotomyEnd'] ?? null;

        if (!$arrival || !$end) {
            return null;
        }

        try {
            $start = Carbon::parse((string) $arrival);
            $finish = Carbon::parse((string) $end);

            if ($finish->lt($start)) {
                return null;
            }

            return (int) floor($start->diffInSeconds($finish) / 60);
        } catch (Throwable) {
            return null;
        }
    }

    private function formatDateTime(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return 'Sin registro';
        }

        try {
            return Carbon::parse((string) $value)->format('d/m/Y h:i A');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '—';
        }

        try {
            return Carbon::parse((string) $value)->format('d/m/Y');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function calculateAge(mixed $birthDate, mixed $referenceDate): string
    {
        if (!$birthDate) {
            return '—';
        }

        try {
            $birth = Carbon::parse((string) $birthDate)->startOfDay();
            $reference = $referenceDate
                ? Carbon::parse((string) $referenceDate)->startOfDay()
                : now()->startOfDay();

            if ($birth->gt($reference)) {
                return '—';
            }

            $age = (int) floor($birth->diffInYears($reference));

            return $age . ($age === 1 ? ' año' : ' años');
        } catch (Throwable) {
            return '—';
        }
    }

    private function formatMinutes(mixed $value): string
    {
        if (!is_numeric($value)) {
            return 'Pendiente';
        }

        $minutes = max(0, (int) round((float) $value));
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        if ($hours > 0 && $remaining > 0) {
            return $hours . ' h ' . $remaining . ' min';
        }

        if ($hours > 0) {
            return $hours . ($hours === 1 ? ' hora' : ' horas');
        }

        return $minutes . ' min';
    }

    private function displayValue(mixed $value, string $fallback = '—'): string
    {
        if ($value === null) {
            return $fallback;
        }

        $text = trim((string) $value);

        return $text !== '' ? $text : $fallback;
    }

    private function firstText(array $values, string $fallback): string
    {
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $text = trim((string) $value);
            if ($text !== '') {
                return $text;
            }
        }

        return $fallback;
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('images/vcare-logo.png');

        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($contents);
    }
}
