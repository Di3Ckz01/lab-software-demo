<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionLevel
{
    public function handle(Request $request, Closure $next, int $level = 1): Response
    {
        $authUser = session('auth_user');

        if (!$authUser) {
            return redirect()->route('login');
        }

        $userLevel = (int) ($authUser['permissionLevel'] ?? 0);

        if ($userLevel < $level) {
            return redirect()
                ->route('dashboards.pacientes-mes')
                ->with('error', 'No tienes permisos suficientes para acceder a esta sección.');
        }

        return $next($request);
    }
}