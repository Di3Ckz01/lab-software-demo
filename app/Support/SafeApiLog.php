<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log as LaravelLog;

/**
 * Registro seguro para las llamadas HTTP de V-Care.
 *
 * No registra mensajes originales, cuerpos HTTP, tokens,
 * correos, identificadores, filtros ni URLs.
 *
 * Conserva únicamente metadatos numéricos permitidos.
 */
final class SafeApiLog
{
    /**
     * Intercepta las llamadas originales a Log::nivel().
     *
     * Los mensajes informativos y de depuración se omiten.
     * Los errores se registran sin contenido sensible.
     */
    public static function __callStatic(
        string $method,
        array $arguments
    ): void {
        $level = strtolower($method);

        $allowedLevels = [
            'warning',
            'error',
            'critical',
            'alert',
            'emergency',
        ];

        if (!in_array($level, $allowedLevels, true)) {
            return;
        }

        $originalContext = $arguments[1] ?? [];

        if (!is_array($originalContext)) {
            $originalContext = [];
        }

        $safeContext = [
            'component' => 'remote_api',
        ];

        // Lista de permitidos: no utilizamos una lista de bloqueo.
        $allowedFields = [
            'status',
            'body_bytes',
            'max_response_bytes',
            'page_size',
        ];

        foreach ($allowedFields as $field) {
            $value = $originalContext[$field] ?? null;

            if (is_int($value) && $value >= 0) {
                $safeContext[$field] = $value;
            }
        }

        LaravelLog::log(
            $level,
            'Evento de API V-Care. Detalles protegidos.',
            $safeContext
        );
    }
}