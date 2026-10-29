<?php

namespace App\Http\Middleware;

use App\Support\DashboardAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDashboardAccess
{
    public function handle(Request $request, Closure $next, string $dashboardKey): Response
    {
        $authUser = session('auth_user');

        if (!$authUser) {
            return redirect()->route('login');
        }

        if (!DashboardAccess::userCan($dashboardKey, $authUser)) {
            $fallbackRoute = DashboardAccess::firstAllowedRoute($authUser);

            return redirect()
                ->route($fallbackRoute)
                ->with('error', 'No tienes permiso para acceder a este tablero.');
        }

        return $next($request);
    }
}
