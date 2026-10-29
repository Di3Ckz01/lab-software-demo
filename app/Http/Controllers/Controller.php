<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Si GoApiService detectó token vencido/rechazado, cerramos la sesión local
     * y mandamos al login con un mensaje claro.
     */
    protected function redirectIfApiSessionExpired(): ?RedirectResponse
    {
        if (!session('api_session_expired')) {
            return null;
        }

        $message = session('api_session_message', 'Tu sesión expiró. Inicia sesión nuevamente.');

        session()->forget([
            'auth_user',
            'auth_token',
            'go_api_token',
            'go_api_user',
            'api_session_expired',
            'api_session_message',
            'api_last_error',
        ]);

        return redirect()
            ->route('login')
            ->withErrors([
                'login' => $message,
            ]);
    }

    /**
     * Mensaje estándar para pantallas que consumen la API.
     */
    protected function apiErrorMessage(GoApiService $goApiService, string $defaultMessage): string
    {
        return $goApiService->lastErrorMessage() ?: $defaultMessage;
    }
}
