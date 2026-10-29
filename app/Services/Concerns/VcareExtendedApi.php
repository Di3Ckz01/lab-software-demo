<?php

declare(strict_types=1);

namespace App\Services\Concerns;

/**
 * API complementaria para los modulos incorporados a V-Care.
 *
 * Reutiliza los mecanismos existentes de autenticacion,
 * solicitudes HTTP y manejo de errores de GoApiService.
 */
trait VcareExtendedApi
{
    private function vcareEndpoint(
        string $key,
        string $default,
        array $parameters = []
    ): string {
        $endpoint = (string) config('services.go_api.' . $key, $default);

        return '/' . ltrim(strtr($endpoint, $parameters), '/');
    }

    private function vcareRead(
        string $key,
        string $default,
        array $parameters = [],
        array $query = [],
        int $timeout = 120
    ): ?array {
        // Intercepta las lecturas demo antes de construir una petición HTTP.
        if (\App\Services\Demo\VcareDemoMoreData::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoMoreData::read(
                $key,
                $parameters,
                $query
            );
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no esta configurada.');
            return null;
        }

        return $this->getData(
            $this->vcareEndpoint($key, $default, $parameters),
            $query,
            false,
            $timeout
        );
    }

    private function vcareWrite(
        string $key,
        string $default,
        array $payload
    ): ?array {
        if ($this->useMock()) {
            $this->registerApiError('La API no esta configurada.');
            return null;
        }

        return $this->sendData(
            'POST',
            $this->vcareEndpoint($key, $default),
            $payload
        );
    }

    private function vcareValidDate(string $date): bool
    {
        if (!preg_match('/^\d{8}$/D', $date)) {
            return false;
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Ymd', $date);

        return $parsed !== false && $parsed->format('Ymd') === $date;
    }

    /**
     * Consultar la trazabilidad de una orden, conservando el HTTP 404.
     */
    public function getOrderTrace(string $orderNumber): ?array
    {
        if (!preg_match('/^\d{12}$/D', $orderNumber)) {
            $this->registerApiError('Numero de orden invalido.');
            return null;
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no esta configurada.');
            return null;
        }

        $token = $this->getToken();

        if (empty($token)) {
            $this->registerExpiredSession('Tu sesion expiro.', []);
            return null;
        }

        $endpoint = $this->vcareEndpoint(
            'order_trace_endpoint',
            '/order-trace/{orderNumber}',
            ['{orderNumber}' => $orderNumber]
        );

        try {
            $response = $this->apiRequest(120)
                ->withToken($token)
                ->get($this->baseUrl . $endpoint);

            if ($response->status() === 404) {
                return [
                    '__httpStatus' => 404,
                    'message' => 'Orden no encontrada.',
                ];
            }

            if ($response->status() === 401) {
                $this->registerExpiredSession('Tu sesion expiro.', []);
                return null;
            }

            if ($response->status() === 403) {
                $this->registerApiError('Acceso no autorizado.');
                return null;
            }

            if ($response->failed()) {
                $this->registerApiError('No se pudo consultar la orden.');
                return null;
            }

            $data = $response->json();

            return is_array($data) ? $data : null;
        } catch (\Throwable $exception) {
            $this->registerApiError('No se pudo conectar con la API.');
            return null;
        }
    }

    /**
     * Analitica ejecutiva dentro de un rango de fechas.
     */
    public function getExecutiveSummary(
        string $from,
        string $to,
        int $graceMinutes = 15,
        int $multiOrderThreshold = 2
    ): ?array {
        if (
            !$this->vcareValidDate($from) ||
            !$this->vcareValidDate($to) ||
            $from > $to ||
            $graceMinutes < 0 ||
            $graceMinutes > 240 ||
            $multiOrderThreshold < 1 ||
            $multiOrderThreshold > 50
        ) {
            $this->registerApiError('Parametros de analitica invalidos.');
            return null;
        }

        // La demostración utiliza únicamente datos sintéticos.
        if (\App\Services\Demo\VcareDemoMoreData::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoAnalytics::summary(
                $from,
                $to,
                $graceMinutes,
                $multiOrderThreshold
            );
        }

        return $this->vcareRead(
            'executive_summary_endpoint',
            '/executive-summary/{from}-{to}',
            ['{from}' => $from, '{to}' => $to],
            ['graceMinutes' => $graceMinutes, 'n' => $multiOrderThreshold],
            180
        );
    }

    /** Listado de proveedores. */
    public function getSuppliers(): ?array
    {
        return $this->vcareRead('supplier_endpoint', '/supplier');
    }

    /** Registro de proveedor. */
    public function createSupplier(array $payload): ?array
    {
        return $this->vcareWrite(
            'suppliers_create_endpoint',
            '/suppliers',
            $payload
        );
    }

    /** Actualizacion de proveedor. */
    public function updateSupplier(array $payload): ?array
    {
        return $this->vcareWrite(
            'supplier_edit_endpoint',
            '/edit-supplier',
            $payload
        );
    }

    /** Asignacion de estudio a proveedor. */
    public function assignStudyToSupplier(array $payload): ?array
    {
        return $this->vcareWrite(
            'supplier_assign_study_endpoint',
            '/suppliers/assign-study',
            $payload
        );
    }

    /** Estudios asociados a un proveedor. */
    public function getSupplierStudies(string $supplierID): ?array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $supplierID)) {
            $this->registerApiError('Identificador de proveedor invalido.');
            return null;
        }

        return $this->vcareRead(
            'supplier_studies_endpoint',
            '/suppliers/{supplierID}/studies',
            ['{supplierID}' => rawurlencode($supplierID)]
        );
    }

    /** Desasignacion mediante DELETE sin cuerpo JSON. */
    public function unassignStudyFromSupplier(
        string $supplierID,
        int $clinicalStudyID
    ): ?array {
        if (
            !preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $supplierID) ||
            $clinicalStudyID <= 0
        ) {
            $this->registerApiError('Datos de desasignacion invalidos.');
            return null;
        }

        if ($this->useMock()) {
            return null;
        }

        $token = $this->getToken();

        if (empty($token)) {
            $this->registerExpiredSession('Tu sesion expiro.', []);
            return null;
        }

        $endpoint = $this->vcareEndpoint(
            'supplier_unassign_study_endpoint',
            '/suppliers/{supplierID}/studies/{clinicalStudyID}',
            [
                '{supplierID}' => rawurlencode($supplierID),
                '{clinicalStudyID}' => (string) $clinicalStudyID,
            ]
        );

        try {
            $response = $this->apiRequest(120)
                ->withToken($token)
                ->delete($this->baseUrl . $endpoint);

            if ($response->status() === 401) {
                $this->registerExpiredSession('Tu sesion expiro.', []);
                return null;
            }

            if ($response->failed()) {
                $this->registerApiError('No se pudo desasignar el estudio.');
                return null;
            }

            $data = $response->json();

            return is_array($data) ? $data : ['success' => true];
        } catch (\Throwable $exception) {
            $this->registerApiError('No se pudo conectar con la API.');
            return null;
        }
    }

    /** Catalogo paginado de estudios clinicos. */
    public function getClinicalStudyCatalog(
        int $page = 0,
        ?string $clinicalStudyNumber = null
    ): ?array {
        if ($page < 0) {
            $this->registerApiError('Pagina invalida.');
            return null;
        }

        $query = [];

        if ($clinicalStudyNumber !== null && trim($clinicalStudyNumber) !== '') {
            $query['clinicalStudyNumber'] = trim($clinicalStudyNumber);
        }

        return $this->vcareRead(
            'clinical_studies_catalog_endpoint',
            '/clinical-studies/{page}',
            ['{page}' => (string) $page],
            $query
        );
    }

    /** Periodos disponibles para reportes. */
    public function getClinicalStudyAvailablePeriods(): ?array
    {
        return $this->vcareRead(
            'clinical_studies_available_periods_endpoint',
            '/clinical-studies/available-periods'
        );
    }

    /** Totales mensuales de validacion. */
    public function getClinicalStudyValidationTotals(
        int $year,
        int $monthNumber,
        ?string $supplierID = null
    ): ?array {
        return $this->vcareMonthlyStudyRequest(
            'clinical_studies_totals_endpoint',
            '/clinical-studies/{year}/{monthNumber}/totals',
            $year,
            $monthNumber,
            $supplierID
        );
    }

    /** Pendientes historicos de validacion. */
    public function getClinicalStudyValidationPending(
        int $year,
        int $monthNumber,
        ?string $supplierID = null
    ): ?array {
        return $this->vcareMonthlyStudyRequest(
            'clinical_studies_pending_endpoint',
            '/clinical-studies/{year}/{monthNumber}/pending',
            $year,
            $monthNumber,
            $supplierID
        );
    }

    private function vcareMonthlyStudyRequest(
        string $key,
        string $default,
        int $year,
        int $monthNumber,
        ?string $supplierID
    ): ?array {
        if ($year < 2000 || $year > 2100 || $monthNumber < 1 || $monthNumber > 12) {
            $this->registerApiError('Periodo invalido.');
            return null;
        }

        $query = [];

        if ($supplierID !== null && trim($supplierID) !== '') {
            $query['supplierID'] = trim($supplierID);
        }

        return $this->vcareRead(
            $key,
            $default,
            [
                '{year}' => (string) $year,
                '{monthNumber}' => (string) $monthNumber,
                '{month}' => (string) $monthNumber,
            ],
            $query,
            180
        );
    }

    /** Estudios subrogados entre dos fechas inclusivas. */
    public function getClinicalStudies(string $start, string $end): ?array
    {
        if (
            !$this->vcareValidDate($start) ||
            !$this->vcareValidDate($end) ||
            $start > $end
        ) {
            $this->registerApiError('Rango de fechas invalido.');
            return null;
        }

        return $this->vcareRead(
            'clinical_study_endpoint',
            '/clinicalStudy/{start}-{end}',
            ['{start}' => $start, '{end}' => $end]
        );
    }
}