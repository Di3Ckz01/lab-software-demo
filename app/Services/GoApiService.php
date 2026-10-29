<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Support\SafeApiLog as Log;

class GoApiService
{
    use \App\Services\Concerns\VcareExtendedApi;

    private string $baseUrl;
    private int $timeout;
    private bool $useMock;
    private string $authEndpoint;
    private string $ordersEndpoint;
    private string $studiesWheelEndpoint;
    private string $ordersByYearEndpoint;
    private string $testsByYearEndpoint;
    private int $testsByYearTimeout;
    private string $studiesPaginatedEndpoint;
    private string $sampleCollectionEndpoint;
    private string $phlebotomyQueueEndpoint;
    private string $productivityEndpoint;
    private string $duplicatedVenopuncturesEndpoint;
    private int $productivityTimeout;
    private int $productivityMaxResponseBytes;
    private int $duplicatedVenopuncturesTimeout;
    private string $usersEndpoint;
    private string $allUsersEndpoint;
    private string $userUpdateEndpoint;
    private string $userUpdateMethod;
    private ?string $lastErrorMessage = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.go_api.base_url', ''), '/');
        $this->timeout = (int) config('services.go_api.timeout', 30);
        $this->useMock = filter_var(config('services.go_api.use_mock', false), FILTER_VALIDATE_BOOLEAN);

        $this->authEndpoint = '/' . ltrim((string) config('services.go_api.auth_endpoint', '/login'), '/');
        $this->ordersEndpoint = '/' . ltrim((string) config('services.go_api.orders_endpoint', '/orders'), '/');
        $this->studiesWheelEndpoint = '/' . ltrim((string) config('services.go_api.studies_wheel_endpoint', '/rueda'), '/');
        $this->ordersByYearEndpoint = '/' . ltrim((string) config('services.go_api.orders_by_year_endpoint', '/orders-by-year/{year}'), '/');
        $this->testsByYearEndpoint = '/' . ltrim((string) config('services.go_api.tests_by_year_endpoint', '/tests-by-year/{year}'), '/');
        $this->testsByYearTimeout = max(60, (int) config('services.go_api.tests_by_year_timeout', 180));
        $this->studiesPaginatedEndpoint = '/' . ltrim((string) config('services.go_api.studies_paginated_endpoint', '/paginated-tests/{cursor}-{pageSize}'), '/');
        $this->sampleCollectionEndpoint = '/' . ltrim((string) config('services.go_api.sample_collection_endpoint', '/tomaDeMuestra/{day}'), '/');
        $this->phlebotomyQueueEndpoint = '/' . ltrim((string) config('services.go_api.phlebotomy_queue_endpoint', '/phlebotomy_queue/{cursor}-{pageSize}'), '/');
        $this->productivityEndpoint = '/' . ltrim((string) config('services.go_api.productivity_endpoint', '/productivity/{date}'), '/');
        $this->duplicatedVenopuncturesEndpoint = '/' . ltrim((string) config('services.go_api.duplicated_venopunctures_endpoint', '/duplicated-venopunctures'), '/');
        $this->productivityTimeout = (int) config('services.go_api.productivity_timeout', 360);
        $this->productivityMaxResponseBytes = max(1, (int) config('services.go_api.productivity_max_response_mb', 64)) * 1024 * 1024;
        $this->duplicatedVenopuncturesTimeout = (int) config('services.go_api.duplicated_venopunctures_timeout', 180);
        $this->usersEndpoint = '/' . ltrim((string) config('services.go_api.users_endpoint', '/users'), '/');
        $this->allUsersEndpoint = '/' . ltrim((string) config('services.go_api.all_users_endpoint', '/all-users'), '/');
        $this->userUpdateEndpoint = '/' . ltrim((string) config('services.go_api.user_update_endpoint', '/edit-user'), '/');
        $this->userUpdateMethod = strtoupper((string) config('services.go_api.user_update_method', 'POST'));
    }

    public function useMock(): bool
    {
        return $this->useMock || $this->baseUrl === '';
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function lastErrorMessage(): ?string
    {
        return session('api_last_error.message') ?: $this->lastErrorMessage;
    }

    public function clearLastError(): void
    {
        $this->lastErrorMessage = null;
        session()->forget('api_last_error');
    }

    private function apiRequest(?int $timeout = null)
    {
        return Http::acceptJson()
            ->withHeaders([
                'ngrok-skip-browser-warning' => 'true',
            ])
            ->withOptions([
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,
                CURLOPT_SSL_OPTIONS    => CURLSSLOPT_NO_REVOKE | CURLSSLOPT_ALLOW_BEAST,
            ])
            ->connectTimeout(20)
            ->timeout($timeout ?? $this->timeout);
    }

    public function login(string $email, string $password): ?array
    {
        $this->clearLastError();
        session()->forget(['api_session_expired', 'api_session_message']);

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');

            Log::warning('API Go no configurada o mock activo al iniciar sesión', [
                'base_url' => $this->baseUrl,
                'use_mock' => $this->useMock,
            ]);

            return null;
        }

        $url = $this->baseUrl . $this->authEndpoint;

        try {
            Log::info('API Go login - antes de enviar petición', [
                'url' => $url,
                'endpoint' => $this->authEndpoint,
                'base_url' => $this->baseUrl,
                'email' => $email,
            ]);

            $response = $this->apiRequest()
                ->asJson()
                ->post($url, [
                    'email' => $email,
                    'password' => $password,
                ]);

            Log::info('API Go login - respuesta recibida', [
                'url' => $url,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'failed' => $response->failed(),
                'body_bytes' => strlen($response->body()),
                'body_preview' => mb_substr($response->body(), 0, 500),
                'headers' => $response->headers(),
            ]);

            if ($response->failed()) {
                $message = $this->friendlyErrorMessage($response->status(), 'No se pudo autenticar contra la API.');
                $this->registerApiError($message, [
                    'endpoint' => $this->authEndpoint,
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                Log::error('Error al iniciar sesión contra API Go', [
                    'endpoint' => $this->authEndpoint,
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $json = $response->json();

            if (!is_array($json)) {
                $this->registerApiError('La API respondió, pero no regresó un JSON válido.');

                Log::error('Login API Go respondió, pero no regresó JSON válido', [
                    'endpoint' => $this->authEndpoint,
                    'url' => $url,
                    'body' => $response->body(),
                ]);

                return null;
            }

            if (($json['authenticated'] ?? false) !== true || empty($json['token'])) {
                $this->registerApiError('Correo o contraseña incorrectos.');

                Log::warning('Credenciales rechazadas por API Go', [
                    'endpoint' => $this->authEndpoint,
                    'url' => $url,
                    'response' => $json,
                ]);

                return null;
            }

            $this->clearLastError();

            return $json;
        } catch (\Throwable $exception) {
            $this->registerApiError('No se pudo conectar con la API. Verifica conexión o disponibilidad del servidor.');

            Log::error('Excepción al iniciar sesión contra API Go', [
                'endpoint' => $this->authEndpoint,
                'url' => $url,
                'message' => $exception->getMessage(),
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return null;
        }
    }

    public function getOrders(array $filters = []): ?array
    {
        // Respuesta sintética exclusiva del modo demostración.
        if (\App\Services\Demo\VcareDemoBoards::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoOrders::summaries();
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');

            Log::warning('API Go no configurada o mock activo al consultar órdenes', [
                'base_url' => $this->baseUrl,
                'use_mock' => $this->useMock,
                'endpoint' => $this->ordersEndpoint,
            ]);

            return null;
        }

        return $this->getData($this->ordersEndpoint, $filters);
    }


    /**
     * Obtiene el total de órdenes únicas agrupado por mes para un año.
     *
     * Ejemplo: /orders-by-year/2026
     */
    public function getOrdersByYear(int $year): ?array
    {
        // Respuesta sintética exclusiva del modo demostración local.
        if (\App\Services\Demo\VcareDemoBoards::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoBoards::dispatch(
                'getOrdersByYear',
                func_get_args()
            );
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');
            return null;
        }

        if ($year < 2000 || $year > 2100) {
            $this->registerApiError('El año solicitado para órdenes no es válido.');
            return null;
        }

        $endpoint = str_replace('{year}', (string) $year, $this->ordersByYearEndpoint);

        return $this->getData($endpoint);
    }

    /**
     * Obtiene los estudios agrupados por mes, área y estatus para un año.
     *
     * Ejemplo: /tests-by-year/2026
     */
    public function getTestsByYear(int $year): ?array
    {
        // Respuesta sintética exclusiva del modo demostración local.
        if (\App\Services\Demo\VcareDemoBoards::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoBoards::dispatch(
                'getTestsByYear',
                func_get_args()
            );
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');
            return null;
        }

        if ($year < 2000 || $year > 2100) {
            $this->registerApiError('El año solicitado para estudios no es válido.');
            return null;
        }

        $endpoint = str_replace('{year}', (string) $year, $this->testsByYearEndpoint);

        // Este endpoint anual tarda más que el timeout general de la API.
        // Se usa un límite independiente para evitar que Laravel cierre la
        // petición antes de que el backend termine de construir el resumen.
        return $this->getData(
            endpoint: $endpoint,
            timeout: $this->testsByYearTimeout,
            retryTimes: 2
        );
    }

    /**
     * Obtiene los registros utilizados por la rueda de distribución por área.
     *
     * El backend recibe la fecha como parte de la ruta:
     * GET /rueda/20260713
     */
    public function getStudiesWheel(string $date): ?array
    {
        // Respuesta sintética exclusiva del modo demostración local.
        if (\App\Services\Demo\VcareDemoBoards::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoBoards::dispatch(
                'getStudiesWheel',
                func_get_args()
            );
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');

            Log::warning('API Go no configurada o mock activo al consultar la rueda de estudios', [
                'base_url' => $this->baseUrl,
                'use_mock' => $this->useMock,
                'date' => $date,
            ]);

            return null;
        }

        $date = trim($date);

        if (!preg_match('/^\d{8}$/', $date)) {
            $this->registerApiError('La fecha de la rueda no tiene el formato esperado YYYYMMDD.');

            Log::warning('Fecha inválida al consultar la rueda de estudios', [
                'date' => $date,
            ]);

            return null;
        }

        $configuredEndpoint = trim($this->studiesWheelEndpoint);

        if ($configuredEndpoint === '') {
            $configuredEndpoint = '/rueda/{date}';
        }

        if (str_contains($configuredEndpoint, '{date}')) {
            $endpoint = str_replace('{date}', $date, $configuredEndpoint);
        } elseif (str_contains($configuredEndpoint, ':date')) {
            $endpoint = str_replace(':date', $date, $configuredEndpoint);
        } elseif (preg_match('#/date/?$#i', $configuredEndpoint)) {
            // También acepta temporalmente GO_API_STUDIES_WHEEL_ENDPOINT=/rueda/date.
            $endpoint = preg_replace('#/date/?$#i', '/' . $date, $configuredEndpoint);
        } else {
            // Compatibilidad si el .env todavía contiene únicamente /rueda.
            $endpoint = rtrim($configuredEndpoint, '/') . '/' . $date;
        }

        $endpoint = '/' . ltrim((string) $endpoint, '/');

        return $this->getData($endpoint, [], true, 120);
    }


    /**
     * Obtiene la información diaria del tablero de Toma de Muestra.
     *
     * Ejemplo del backend: GET /tomaDeMuestra/20260622
     */
    public function getSampleCollection(string $day): ?array
    {
        // Datos ficticios: nunca se consulta la API clínica.
        if (\App\Services\Demo\VcareDemoData::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoData::sampleCollection($day);
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');

            Log::warning('API Go no configurada o mock activo al consultar Toma de Muestra', [
                'base_url' => $this->baseUrl,
                'use_mock' => $this->useMock,
                'day' => $day,
            ]);

            return null;
        }

        $day = trim($day);

        if (!preg_match('/^\d{8}$/', $day)) {
            $this->registerApiError('La fecha de Toma de Muestra no tiene el formato esperado YYYYMMDD.');

            Log::warning('Fecha inválida al consultar Toma de Muestra', [
                'day' => $day,
            ]);

            return null;
        }

        $configuredEndpoint = trim($this->sampleCollectionEndpoint);

        if ($configuredEndpoint === '') {
            $configuredEndpoint = '/tomaDeMuestra/{day}';
        }

        if (
            str_contains($configuredEndpoint, '{day}')
            || str_contains($configuredEndpoint, '{date}')
            || str_contains($configuredEndpoint, '{fecha}')
        ) {
            $endpoint = str_replace(
                ['{day}', '{date}', '{fecha}'],
                [$day, $day, $day],
                $configuredEndpoint
            );
        } else {
            $endpoint = rtrim($configuredEndpoint, '/') . '/' . $day;
        }

        $endpoint = '/' . ltrim($endpoint, '/');

        // Conserva el objeto completo: success, generatedAt, filters, summary,
        // patientsByHour, patientsByDoor y averageTimeByDoorAndHour.
        return $this->getData($endpoint, [], false, 120);
    }


    /**
     * Obtiene una página de la cola de toma de muestra.
     *
     * Ejemplos del backend:
     * GET /phlebotomy_queue/0-50
     * GET /phlebotomy_queue/{nextCursor}-50
     *
     * Este endpoint requiere el Bearer token guardado en la sesión.
     */
    public function getPhlebotomyQueue(
        string $cursor = '0',
        int $pageSize = 50
    ): ?array {
        // Respuesta sintética exclusiva del modo demostración local.
        if (\App\Services\Demo\VcareDemoBoards::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoBoards::dispatch(
                'getPhlebotomyQueue',
                func_get_args()
            );
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');

            Log::warning('API Go no configurada o mock activo al consultar la cola de toma de muestra', [
                'base_url' => $this->baseUrl,
                'use_mock' => $this->useMock,
                'cursor' => $cursor,
                'page_size' => $pageSize,
            ]);

            return null;
        }

        $cursor = trim($cursor) !== '' ? trim($cursor) : '0';
        $pageSize = max(1, $pageSize);

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $cursor)) {
            $this->registerApiError('El cursor de la cola de toma de muestra no es válido.');
            return null;
        }

        $configuredEndpoint = trim($this->phlebotomyQueueEndpoint);
        if ($configuredEndpoint === '') {
            $configuredEndpoint = '/phlebotomy_queue/{cursor}-{pageSize}';
        }

        if (str_contains($configuredEndpoint, '{cursor}') || str_contains($configuredEndpoint, '{pageSize}')) {
            $endpoint = str_replace(
                ['{cursor}', '{pageSize}'],
                [$cursor, (string) $pageSize],
                $configuredEndpoint
            );
        } else {
            $endpoint = rtrim($configuredEndpoint, '/') . '/' . $cursor . '-' . $pageSize;
        }

        $endpoint = '/' . ltrim($endpoint, '/');

        // El endpoint /phlebotomy_queue requiere autenticación. Reutilizamos
        // getData() para enviar automáticamente el Bearer token de la sesión.
        // unwrapData=false conserva el objeto completo: nextCursor, pageSize,
        // data y dailyTotals.
        return $this->getData($endpoint, [], false, 120, null, 2);
    }



    /**
     * Obtiene un bloque paginado de estudios.
     *
     * Ejemplos del backend:
     * /paginated-tests/0-50
     * /paginated-tests/202606081062_6a29f16d455028a14b619822-50
     */
    public function getPaginatedTests(string $cursor = '0', int $pageSize = 50): ?array
    {
        // Respuesta sintética exclusiva del modo demostración local.
        if (\App\Services\Demo\VcareDemoBoards::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoBoards::dispatch(
                'getPaginatedTests',
                func_get_args()
            );
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');

            Log::warning('API Go no configurada o mock activo al consultar estudios paginados', [
                'base_url' => $this->baseUrl,
                'use_mock' => $this->useMock,
                'cursor' => $cursor,
                'page_size' => $pageSize,
            ]);

            return null;
        }

        $cursor = trim($cursor);
        $cursor = $cursor !== '' ? $cursor : '0';
        $pageSize = in_array($pageSize, [50, 100], true) ? $pageSize : 50;

        // El cursor solo debe contener caracteres seguros para el segmento URL.
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $cursor)) {
            $this->registerApiError('El cursor recibido para la paginación no es válido.');
            return null;
        }

        $endpoint = str_replace(
            ['{cursor}', '{pageSize}'],
            [$cursor, (string) $pageSize],
            $this->studiesPaginatedEndpoint
        );

        // Este endpoint necesita conservar el objeto completo porque, además de data,
        // devuelve nextCursor y pageSize.
        return $this->getData($endpoint, [], false);
    }

    public function getPacientes(array $filters = []): ?array
    {
        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');

            Log::warning('API Go no configurada o mock activo al consultar /pacientes', [
                'base_url' => $this->baseUrl,
                'use_mock' => $this->useMock,
            ]);

            return null;
        }

        return $this->getData('/pacientes', $filters);
    }

    public function getPacienteByDocumento(string $documento): ?array
    {
        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');

            Log::warning('API Go no configurada o mock activo al consultar /paciente/{documento}', [
                'base_url' => $this->baseUrl,
                'use_mock' => $this->useMock,
                'documento' => $documento,
            ]);

            return null;
        }

        return $this->getData('/paciente/' . urlencode($documento));
    }

    public function createUser(array $userData): ?array
    {
        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');

            Log::warning('API Go no configurada o mock activo al crear usuario', [
                'base_url' => $this->baseUrl,
                'use_mock' => $this->useMock,
            ]);

            return null;
        }

        return $this->postData($this->usersEndpoint, $userData);
    }

    public function updateUser(string $userId, array $userData): ?array
    {
        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');

            Log::warning('API Go no configurada o mock activo al actualizar usuario', [
                'base_url' => $this->baseUrl,
                'use_mock' => $this->useMock,
                'user_id' => $userId,
            ]);

            return null;
        }

        $endpoint = $this->buildUserUpdateEndpoint($userId);

        $method = in_array($this->userUpdateMethod, ['PUT', 'PATCH', 'POST'], true)
            ? $this->userUpdateMethod
            : 'POST';

        return $this->sendData($method, $endpoint, $userData);
    }


    private function buildUserUpdateEndpoint(string $userId): string
    {
        $endpoint = trim($this->userUpdateEndpoint);

        if ($endpoint === '' || $endpoint === '/') {
            $endpoint = rtrim($this->usersEndpoint, '/') . '/{id}';
        }

        if (str_contains($endpoint, '{id}')) {
            return str_replace('{id}', rawurlencode($userId), $endpoint);
        }

        if (str_contains($endpoint, ':id')) {
            return str_replace(':id', rawurlencode($userId), $endpoint);
        }

        // Endpoint tipo POST /edit-user: el ID viaja en el JSON del body.
        return $endpoint;
    }

    public function getAllUsers(): ?array
    {
        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');

            Log::warning('API Go no configurada o mock activo al consultar /all-users', [
                'base_url' => $this->baseUrl,
                'use_mock' => $this->useMock,
            ]);

            return null;
        }

        return $this->getData($this->allUsersEndpoint);
    }

    /**
     * Obtiene el tablero diario de tiempo de oportunidad.
     *
     * El backend recibe la fecha como parte de la ruta:
     * GET /opp-times/20260713
     */
    public function getOpportunityTimes(string $date): ?array
    {
        // Respuesta sintética exclusiva del modo demostración local.
        if (\App\Services\Demo\VcareDemoBoards::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoBoards::dispatch(
                'getOpportunityTimes',
                func_get_args()
            );
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');
            return null;
        }

        $date = trim($date);

        if (!preg_match('/^\d{8}$/', $date)) {
            $this->registerApiError('La fecha de tiempo de oportunidad no tiene el formato esperado YYYYMMDD.');

            Log::warning('Fecha inválida al consultar /opp-times/{date}', [
                'date' => $date,
            ]);

            return null;
        }

        $configuredEndpoint = trim((string) config(
            'services.go_api.opp_times_endpoint',
            '/opp-times/{date}'
        ));

        if ($configuredEndpoint === '') {
            $configuredEndpoint = '/opp-times/{date}';
        }

        if (str_contains($configuredEndpoint, '{date}')) {
            $endpoint = str_replace('{date}', $date, $configuredEndpoint);
        } elseif (str_contains($configuredEndpoint, ':date')) {
            $endpoint = str_replace(':date', $date, $configuredEndpoint);
        } elseif (preg_match('#/date/?$#i', $configuredEndpoint)) {
            // También acepta GO_API_OPP_TIMES_ENDPOINT=/opp-times/date.
            $endpoint = preg_replace('#/date/?$#i', '/' . $date, $configuredEndpoint);
        } else {
            // Compatibilidad si el .env todavía contiene solamente /opp-times.
            $endpoint = rtrim($configuredEndpoint, '/') . '/' . $date;
        }

        $endpoint = '/' . ltrim((string) $endpoint, '/');

        return $this->getData($endpoint, [], true, 120);
    }

    /**
     * Endpoint independiente para el tablero mostrado en pantalla completa/TV.
     */
    public function getTvOpportunityTimes(array $filters = []): ?array
    {
        // Respuesta sintética exclusiva del modo demostración local.
        if (\App\Services\Demo\VcareDemoBoards::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoBoards::dispatch(
                'getTvOpportunityTimes',
                func_get_args()
            );
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');
            return null;
        }

        $endpoint = (string) config('services.go_api.opp_times_tv_endpoint', '/opp-times-1');

        return $this->getData($endpoint, $filters, true, 120);
    }

    public function getOrdersSummary(array $filters = []): ?array
    {
        $date = (string) (
            $filters['date']
            ?? $filters['date_to']
            ?? $filters['date_from']
            ?? now()->format('Ymd')
        );

        // Acepta tanto YYYY-MM-DD como YYYYMMDD para conservar compatibilidad.
        $date = preg_replace('/[^0-9]/', '', $date) ?? '';

        return $this->getOpportunityTimes($date);
    }

    /**
     * Consulta Productividad mediante los endpoints diarios separados:
     *
     * GET /productivity/{date}   -> tablero general
     * GET /productivity/{date}/0 -> consulta externa
     * GET /productivity/{date}/1 -> hospitalización
     *
     * No se envían query params ni body JSON.
     */
    public function getProductivityByDate(?string $date, ?int $scopeSuffix = null): ?array
    {
        // La demostración entrega registros locales, nunca consultas clínicas.
        if (\App\Services\Demo\VcareDemoMoreData::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoMoreData::productivity(
                $date,
                $scopeSuffix
            );
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');
            return null;
        }

        $apiDate = $this->normalizeApiDate($date);

        if (!$apiDate) {
            $this->registerApiError('La fecha solicitada para productividad no es válida. Usa YYYY-MM-DD o YYYYMMDD.');
            return null;
        }

        $configuredEndpoint = trim($this->productivityEndpoint);

        if ($configuredEndpoint === '') {
            $configuredEndpoint = '/productivity/{date}';
        }

        if (
            str_contains($configuredEndpoint, '{date}')
            || str_contains($configuredEndpoint, '{fecha}')
            || str_contains($configuredEndpoint, '{period}')
        ) {
            $endpoint = str_replace(
                ['{date}', '{fecha}', '{period}'],
                [$apiDate, $apiDate, $apiDate],
                $configuredEndpoint
            );
        } else {
            $endpoint = rtrim($configuredEndpoint, '/') . '/' . $apiDate;
        }

        if ($scopeSuffix !== null) {
            $endpoint = rtrim($endpoint, '/') . '/' . rawurlencode((string) $scopeSuffix);
        }

        $endpoint = '/' . ltrim($endpoint, '/');

        return $this->getData(
            $endpoint,
            [],
            true,
            $this->productivityTimeout,
            $this->productivityMaxResponseBytes
        );
    }

    /**
     * Compatibilidad con llamadas anteriores. Productividad trabaja por día,
     * por lo que se toma dateFrom y se consulta el tablero general.
     */
    public function getProductivityByDateRange(?string $dateFrom, ?string $dateTo): ?array
    {
        return $this->getProductivityByDate($dateFrom, null);
    }

    /**
     * Compatibilidad con el nombre usado por versiones previas del controlador.
     * Un único ID 0 se traduce a Consulta externa y un único ID 1 a Hospitalización.
     * Cualquier otra combinación consulta el tablero general.
     */
    public function getProductivityByServiceDateRange(
        ?string $dateFrom,
        ?string $dateTo,
        array $serviceIds
    ): ?array {
        $normalized = array_values(array_unique(array_map(
            static fn ($value) => (int) $value,
            array_filter($serviceIds, static fn ($value) => is_numeric($value))
        )));

        $scopeSuffix = count($normalized) === 1 && in_array($normalized[0], [0, 1], true)
            ? $normalized[0]
            : null;

        return $this->getProductivityByDate($dateFrom, $scopeSuffix);
    }

    /**
     * Mantiene compatibilidad con el controlador actual.
     *
     * Aunque el nombre diga "ByMonth", ahora usa date_from/date_to del request
     * para consultar el endpoint diario o por rango:
     * GET /productivity/YYYYMMDD-YYYYMMDD
     */
    public function getProductivityByMonth(string $period): ?array
    {
        $dateFrom = request()->query('date_from');
        $dateTo = request()->query('date_to');

        if (!empty($dateFrom) && !empty($dateTo)) {
            return $this->getProductivityByDateRange($dateFrom, $dateTo);
        }

        // Fallback: si el controlador viejo solo manda YYYYMM, consultamos todo el mes.
        if (preg_match('/^\d{6}$/', $period)) {
            $monthStart = \DateTimeImmutable::createFromFormat('!Ym', $period);

            if ($monthStart === false) {
                $this->registerApiError('El periodo solicitado para productividad no es válido. Usa el formato YYYYMM.');
                return null;
            }

            $start = $monthStart->format('Ymd');
            $end = $monthStart->modify('last day of this month')->format('Ymd');

            return $this->getProductivityByDateRange($start, $end);
        }

        // Fallback adicional: si por algún motivo llega 20260620-20260625.
        if (preg_match('/^(\d{8})-(\d{8})$/', $period, $matches)) {
            return $this->getProductivityByDateRange($matches[1], $matches[2]);
        }

        $this->registerApiError('El periodo solicitado para productividad no es válido.');
        return null;
    }

    /**
     * Normaliza fechas YYYY-MM-DD o YYYYMMDD a YYYYMMDD.
     */
    private function normalizeApiDate(?string $date): ?string
    {
        $date = trim((string) $date);

        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{8}$/', $date)) {
            return $date;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return str_replace('-', '', $date);
        }

        return null;
    }

    /**
     * Consulta venopunciones duplicadas de un mes en formato YYYYMM.
     *
     * Antes el backend esperaba /duplicated-venopunctures/{period}.
     * Ahora el endpoint correcto es /duplicated-venopunctures.
     * El periodo se envía como query string para conservar el filtro del tablero:
     * /duplicated-venopunctures?period=202606
     */
    public function getDuplicatedVenopuncturesByMonth(string $period): ?array
    {
        // Lectura ficticia exclusiva de la demostración local.
        if (\App\Services\Demo\VcareDemoMoreData::enabled()) {
            $this->clearLastError();

            return \App\Services\Demo\VcareDemoVenopunctures::byMonth(
                $period
            );
        }

        if ($this->useMock()) {
            $this->registerApiError('La API no está configurada o el modo mock está activo.');
            return null;
        }

        if (!preg_match('/^\d{6}$/', $period)) {
            $this->registerApiError('El periodo solicitado para venopunciones duplicadas no es válido. Usa el formato YYYYMM.');
            return null;
        }

        // Por si config/services.php o .env aún tienen /duplicated-venopunctures/{period}, forzamos el nuevo endpoint.
        $endpoint = trim($this->duplicatedVenopuncturesEndpoint);

        if ($endpoint === '' || str_contains($endpoint, '{period}') || str_contains($endpoint, ':period')) {
            $endpoint = '/duplicated-venopunctures';
        }

        $endpoint = '/' . ltrim($endpoint, '/');

        return $this->getData($endpoint, [
            'period' => $period,
        ], true, $this->duplicatedVenopuncturesTimeout);
    }

    /**
     * Compatibilidad temporal con llamadas anteriores.
     * Si se recibe date_from, se consulta el mes correspondiente.
     */
    public function getProductivity(array $filters = []): ?array
    {
        $date = $filters['date_from'] ?? now()->toDateString();

        try {
            $period = \Illuminate\Support\Carbon::parse($date)->format('Ym');
        } catch (\Throwable $exception) {
            $period = now()->format('Ym');
        }

        return $this->getProductivityByMonth($period);
    }

    private function getToken(): ?string
    {
        return session('go_api_token');
    }

    private function postData(string $endpoint, array $payload = []): ?array
    {
        return $this->sendData('POST', $endpoint, $payload);
    }

    private function sendData(string $method, string $endpoint, array $payload = []): ?array
    {
        $method = strtoupper($method);
        $endpoint = '/' . ltrim($endpoint, '/');
        $url = $this->baseUrl . $endpoint;

        $token = $this->getToken();

        if (empty($token)) {
            $this->registerExpiredSession('Tu sesión expiró. Inicia sesión nuevamente.', [
                'endpoint' => $endpoint,
                'url' => $url,
            ]);

            Log::error('No se pudo enviar información a API Go porque no hay token en sesión', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
            ]);

            return [
                'created' => false,
                'message' => 'Tu sesión expiró. Inicia sesión nuevamente.',
            ];
        }

        try {
            $response = $this->apiRequest()
                ->asJson()
                ->withToken($token)
                ->send($method, $url, [
                    'json' => $payload,
                ]);

            if ($response->status() === 401) {
                $this->registerExpiredSession('Tu sesión expiró o el token fue rechazado. Inicia sesión nuevamente.', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                Log::warning('Token rechazado por API Go al enviar información', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'created' => false,
                    'message' => 'Tu sesión expiró o el token fue rechazado. Inicia sesión nuevamente.',
                ];
            }

            if ($response->status() === 403) {
                $this->registerApiError('No tienes permisos en la API para realizar esta acción.', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                Log::warning('Usuario sin permisos para realizar esta acción', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'created' => false,
                    'message' => 'No tienes permisos para realizar esta acción.',
                ];
            }

            $json = $response->json();

            if ($response->failed()) {
                $message = $this->friendlyErrorMessage($response->status(), 'No se pudo completar la solicitud en la API.');
                $this->registerApiError($message, [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                Log::error('Error al enviar información a API Go', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return is_array($json) ? array_merge([
                    'created' => false,
                    'message' => $message,
                ], $json) : [
                    'created' => false,
                    'message' => $message,
                ];
            }

            if (!is_array($json)) {
                $this->registerApiError('La API respondió, pero no regresó un JSON válido.');

                Log::error('La API Go respondió, pero no regresó JSON válido al enviar información', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'body' => $response->body(),
                ]);

                return [
                    'created' => false,
                    'message' => 'La API respondió, pero no regresó un JSON válido.',
                ];
            }

            $this->clearLastError();

            return $json;
        } catch (\Throwable $exception) {
            $this->registerApiError('Ocurrió un error al conectar con la API. Verifica conexión o disponibilidad del servidor.', [
                'endpoint' => $endpoint,
                'url' => $url,
            ]);

            Log::error('Excepción al enviar información a API Go', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
                'message' => $exception->getMessage(),
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return [
                'created' => false,
                'message' => 'Ocurrió un error al conectar con la API.',
            ];
        }
    }

    /**
     * Ejecuta una consulta GET autenticada enviando filtros en un body JSON.
     *
     * Se utiliza exclusivamente para endpoints cuyo contrato exige GET con
     * cuerpo JSON, como /productivity/{date}.
     */
    private function getQueryDataWithJsonBody(
        string $endpoint,
        array $payload = [],
        bool $unwrapData = true,
        ?int $timeout = null,
        ?int $maxResponseBytes = null
    ): ?array {
        $endpoint = '/' . ltrim($endpoint, '/');
        $url = $this->baseUrl . $endpoint;
        $token = $this->getToken();

        if (empty($token)) {
            $this->registerExpiredSession('Tu sesión expiró. Inicia sesión nuevamente.', [
                'endpoint' => $endpoint,
                'url' => $url,
            ]);

            Log::error('No se pudo consultar API Go por GET con body porque no hay token en sesión', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
            ]);

            return null;
        }

        try {
            Log::info('API Go GET con body - antes de enviar petición', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
                'has_token' => true,
            ]);

            $response = $this->apiRequest($timeout)
                ->asJson()
                ->withToken($token)
                ->retry(1, 300)
                ->send('GET', $url, [
                    'json' => $payload,
                ]);

            $body = $response->body();
            $bodyBytes = strlen($body);

            Log::info('API Go GET con body - respuesta recibida', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body_bytes' => $bodyBytes,
                'body_preview' => mb_substr($body, 0, 500),
            ]);

            if ($response->status() === 401) {
                $this->registerExpiredSession('Tu sesión expiró o el token fue rechazado. Inicia sesión nuevamente.', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            if ($response->status() === 403) {
                $this->registerApiError('No tienes permisos en la API para consultar esta información.', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            if ($response->failed()) {
                $message = $this->friendlyErrorMessage(
                    $response->status(),
                    'No se pudo obtener información desde la API.'
                );

                $this->registerApiError($message, [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                    'payload' => $payload,
                ]);

                Log::error('Error al consumir API Go mediante GET con body', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                    'payload' => $payload,
                    'body' => $body,
                ]);

                return null;
            }

            if ($maxResponseBytes !== null && $bodyBytes > $maxResponseBytes) {
                $limitMb = round($maxResponseBytes / 1024 / 1024);
                $receivedMb = round($bodyBytes / 1024 / 1024, 1);
                $message = "La API devolvió {$receivedMb} MB para Productividad y supera el límite seguro de {$limitMb} MB. Selecciona un solo día o solicita un endpoint resumido/paginado.";

                $this->registerApiError($message, [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'body_bytes' => $bodyBytes,
                    'max_response_bytes' => $maxResponseBytes,
                    'payload' => $payload,
                ]);

                return null;
            }

            $json = json_decode($body, true);

            if (!is_array($json)) {
                $this->registerApiError('La API respondió, pero no regresó un JSON válido.');
                return null;
            }

            $this->clearLastError();

            if ($unwrapData && array_key_exists('data', $json)) {
                return is_array($json['data']) ? $json['data'] : null;
            }

            return $json;
        } catch (\Throwable $exception) {
            $this->registerApiError('No se pudo conectar con la API. Verifica conexión o disponibilidad del servidor.', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
            ]);

            Log::error('Excepción al consumir API Go mediante GET con body', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
                'message' => $exception->getMessage(),
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return null;
        }
    }

    /**
     * Ejecuta una consulta POST autenticada y devuelve la respuesta JSON.
     * Se usa para endpoints de lectura que reciben filtros en el body.
     */
    private function postQueryData(
        string $endpoint,
        array $payload = [],
        bool $unwrapData = true,
        ?int $timeout = null,
        ?int $maxResponseBytes = null
    ): ?array {
        $endpoint = '/' . ltrim($endpoint, '/');
        $url = $this->baseUrl . $endpoint;
        $token = $this->getToken();

        if (empty($token)) {
            $this->registerExpiredSession('Tu sesión expiró. Inicia sesión nuevamente.', [
                'endpoint' => $endpoint,
                'url' => $url,
            ]);

            Log::error('No se pudo consultar API Go por POST porque no hay token en sesión', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
            ]);

            return null;
        }

        try {
            Log::info('API Go POST consulta - antes de enviar petición', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
                'has_token' => true,
            ]);

            $response = $this->apiRequest($timeout)
                ->asJson()
                ->withToken($token)
                ->retry(1, 300)
                ->post($url, $payload);

            $body = $response->body();
            $bodyBytes = strlen($body);

            Log::info('API Go POST consulta - respuesta recibida', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body_bytes' => $bodyBytes,
                'body_preview' => mb_substr($body, 0, 500),
            ]);

            if ($response->status() === 401) {
                $this->registerExpiredSession('Tu sesión expiró o el token fue rechazado. Inicia sesión nuevamente.', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            if ($response->status() === 403) {
                $this->registerApiError('No tienes permisos en la API para consultar esta información.', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            if ($response->failed()) {
                $message = $this->friendlyErrorMessage($response->status(), 'No se pudo obtener información desde la API.');
                $this->registerApiError($message, [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                    'payload' => $payload,
                ]);

                Log::error('Error al consumir API Go mediante POST', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'status' => $response->status(),
                    'payload' => $payload,
                    'body' => $body,
                ]);
                return null;
            }

            if ($maxResponseBytes !== null && $bodyBytes > $maxResponseBytes) {
                $limitMb = round($maxResponseBytes / 1024 / 1024);
                $receivedMb = round($bodyBytes / 1024 / 1024, 1);
                $message = "La API devolvió {$receivedMb} MB para Productividad y supera el límite seguro de {$limitMb} MB. Selecciona un solo día o solicita un endpoint resumido/paginado.";

                $this->registerApiError($message, [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'body_bytes' => $bodyBytes,
                    'max_response_bytes' => $maxResponseBytes,
                    'payload' => $payload,
                ]);
                return null;
            }

            $json = json_decode($body, true);

            if (!is_array($json)) {
                $this->registerApiError('La API respondió, pero no regresó un JSON válido.');
                return null;
            }

            $this->clearLastError();

            if ($unwrapData && array_key_exists('data', $json)) {
                return is_array($json['data']) ? $json['data'] : null;
            }

            return $json;
        } catch (\Throwable $exception) {
            $this->registerApiError('No se pudo conectar con la API. Verifica conexión o disponibilidad del servidor.', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
            ]);

            Log::error('Excepción al consumir API Go mediante POST', [
                'endpoint' => $endpoint,
                'url' => $url,
                'payload' => $payload,
                'message' => $exception->getMessage(),
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
            return null;
        }
    }

    private function getData(
        string $endpoint,
        array $query = [],
        bool $unwrapData = true,
        ?int $timeout = null,
        ?int $maxResponseBytes = null,
        int $retryTimes = 1
    ): ?array
    {
        $endpoint = '/' . ltrim($endpoint, '/');
        $url = $this->baseUrl . $endpoint;

        $token = $this->getToken();

        if (empty($token)) {
            $this->registerExpiredSession('Tu sesión expiró. Inicia sesión nuevamente.', [
                'endpoint' => $endpoint,
                'url' => $url,
                'query' => $query,
            ]);

            Log::error('No se pudo consultar API Go porque no hay token en sesión', [
                'endpoint' => $endpoint,
                'url' => $url,
                'query' => $query,
            ]);

            return null;
        }

        try {
            Log::info('API Go GET - antes de enviar petición', [
                'endpoint' => $endpoint,
                'url' => $url,
                'query' => $query,
                'has_token' => !empty($token),
                'token_preview' => !empty($token) ? substr($token, 0, 12) . '...' : null,
            ]);

            $response = $this->apiRequest($timeout)
                ->withToken($token)
                ->retry(max(1, $retryTimes), 750)
                ->get($url, $query);

            Log::info('API Go GET - respuesta recibida', [
                'endpoint' => $endpoint,
                'url' => $url,
                'query' => $query,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'failed' => $response->failed(),
                'body_bytes' => strlen($response->body()),
                'body_preview' => mb_substr($response->body(), 0, 500),
                'headers' => $response->headers(),
            ]);

            if ($response->status() === 401) {
                $this->registerExpiredSession('Tu sesión expiró o el token fue rechazado. Inicia sesión nuevamente.', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                Log::warning('Token rechazado por API Go', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'query' => $query,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            if ($response->status() === 403) {
                $this->registerApiError('No tienes permisos en la API para consultar esta información.', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                Log::warning('Usuario sin permisos para consultar información en API Go', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'query' => $query,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            if ($response->failed()) {
                $message = $this->friendlyErrorMessage($response->status(), 'No se pudo obtener información desde la API.');
                $this->registerApiError($message, [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                Log::error('Error al consumir API Go', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'query' => $query,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $bodyBytes = strlen($response->body());

            if ($maxResponseBytes !== null && $bodyBytes > $maxResponseBytes) {
                $limitMb = round($maxResponseBytes / 1024 / 1024);
                $receivedMb = round($bodyBytes / 1024 / 1024, 1);
                $message = "La API devolvió {$receivedMb} MB para Productividad y supera el límite seguro de {$limitMb} MB. Reduce el rango a una semana o solicita un endpoint resumido/paginado.";

                $this->registerApiError($message, [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'query' => $query,
                    'body_bytes' => $bodyBytes,
                    'max_response_bytes' => $maxResponseBytes,
                ]);

                Log::warning('Respuesta de API omitida por exceder el límite de memoria', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'body_bytes' => $bodyBytes,
                    'max_response_bytes' => $maxResponseBytes,
                ]);

                return null;
            }

            $json = $response->json();

            if (!is_array($json)) {
                $this->registerApiError('La API respondió, pero no regresó un JSON válido.');

                Log::error('La API Go respondió, pero no regresó JSON válido', [
                    'endpoint' => $endpoint,
                    'url' => $url,
                    'body' => $response->body(),
                ]);

                return null;
            }

            $this->clearLastError();

            if ($unwrapData && array_key_exists('data', $json)) {
                return is_array($json['data']) ? $json['data'] : null;
            }

            return $json;
        } catch (\Throwable $exception) {
            $this->registerApiError('No se pudo conectar con la API. Verifica conexión o disponibilidad del servidor.', [
                'endpoint' => $endpoint,
                'url' => $url,
                'query' => $query,
            ]);

            Log::error('Excepción al consumir API Go', [
                'endpoint' => $endpoint,
                'url' => $url,
                'query' => $query,
                'message' => $exception->getMessage(),
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return null;
        }
    }

    /**
     * Registra errores sin persistir información sensible en sesión.
     *
     * Conserva el mensaje original para las vistas existentes,
     * pero descarta URLs, cuerpos HTTP, tokens, filtros y
     * cualquier otro contexto no autorizado.
     */
    private function registerApiError(string $message, array $context = []): void
    {
        $this->lastErrorMessage = $message;

        $safeContext = [];

        // Solo se conserva un código de estado HTTP válido.
        $status = $context['status'] ?? null;

        if (
            is_int($status)
            || (is_string($status) && ctype_digit($status))
        ) {
            $status = (int) $status;

            if ($status >= 100 && $status <= 599) {
                $safeContext['status'] = $status;
            }
        }

        // Las métricas numéricas son útiles y no contienen
        // información de pacientes ni credenciales.
        foreach (['body_bytes', 'max_response_bytes'] as $key) {
            $value = $context[$key] ?? null;

            if (is_int($value) && $value >= 0) {
                $safeContext[$key] = $value;
            }
        }

        session()->put('api_last_error', [
            'message' => $message,
            'context' => $safeContext,
            'at' => now()->toDateTimeString(),
        ]);
    }

    private function registerExpiredSession(string $message, array $context = []): void
    {
        $this->registerApiError($message, $context);

        session()->put('api_session_expired', true);
        session()->put('api_session_message', $message);
        session()->forget(['go_api_token', 'auth_token']);
    }

    private function friendlyErrorMessage(int $status, string $fallback): string
    {
        return match (true) {
            $status === 401 => 'Tu sesión expiró o el token fue rechazado. Inicia sesión nuevamente.',
            $status === 403 => 'No tienes permisos en la API para realizar esta acción.',
            $status === 404 => 'El endpoint solicitado no existe en la API. Revisa la ruta configurada.',
            $status === 408 => 'La API tardó demasiado en responder. Intenta nuevamente.',
            $status === 422 => 'La API rechazó la solicitud por datos incompletos o inválidos.',
            $status === 429 => 'La API recibió demasiadas solicitudes. Intenta nuevamente en unos minutos.',
            $status >= 500 => 'El servidor de la API presentó un error. Intenta nuevamente o verifica con backend.',
            default => $fallback,
        };
    }
}
