<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // La demostración no utiliza tokens clínicos.
        // Solo funciona localmente, con activación explícita.
        if (session('vcare_demo_session') === true) {
            $demoAllowed =
                app()->environment('local')
                && config('vcare_demo.enabled', false) === true
                && trim((string) config('services.go_api.base_url', '')) === ''
                && in_array(
                    $request->server('REMOTE_ADDR'),
                    ['127.0.0.1', '::1'],
                    true
                )
                && in_array(
                    $request->getHost(),
                    ['localhost', '127.0.0.1', '::1'],
                    true
                );

            if (!$demoAllowed || !session()->has('auth_user')) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }

            // Las vistas pueden consultarse, pero no modificar datos.
            if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
                abort(403, 'Operación no permitida en demostración.');
            }

            // Evita solicitudes HTTP externas mediante Laravel.
            \Illuminate\Support\Facades\Http::preventStrayRequests();

            return $next($request);
        }
        if (!session()->has('auth_user')) {
            return redirect()->route('login');
        }

        if (!session()->has('go_api_token')) {
            $message = session('api_session_message', 'Tu sesión expiró. Inicia sesión nuevamente.');

            $request->session()->forget([
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

        return $next($request);
    }
}
