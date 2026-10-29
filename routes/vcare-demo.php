<?php

use App\Support\DashboardAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * El modo demo requiere configuración local, API desconectada
 * y una solicitud desde loopback.
 */
$demoPermitido = static function (Request $request): bool {
    return app()->environment('local')
        && config('vcare_demo.enabled', false) === true
        && trim((string) config('services.go_api.base_url', '')) === ''
        && in_array(
            $request->server('REMOTE_ADDR'),
            ['127.0.0.1', '::1'],
            true
        )
        && in_array(
            $request->getHost(),
            ['127.0.0.1', 'localhost', '::1'],
            true
        );
};

Route::get('/demo', function (Request $request) use ($demoPermitido) {
    abort_unless($demoPermitido($request), 404);

    return view('auth.demo');
})->name('demo.index');

Route::post('/demo/entrar', function (Request $request) use ($demoPermitido) {
    abort_unless($demoPermitido($request), 404);

    // Crear una sesión independiente sin token de API.
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    $request->session()->put('vcare_demo_session', true);
    $request->session()->put('auth_user', [
        'id' => 'demo-local',
        'name' => 'Usuario de demostración',
        'email' => 'demo@example.invalid',
        'role' => 'demo',
        'permissionLevel' => 1,
        'dashboardAccess' => DashboardAccess::keys(),
        'status' => 'active',
    ]);

    return redirect()->route('dashboards.home');
})->name('demo.enter');