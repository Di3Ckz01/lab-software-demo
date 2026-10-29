<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudiesController;
use App\Http\Controllers\UserController;
use App\Services\GoApiService;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\OpportunityStatusController;
use App\Http\Controllers\ProductivityController;
use App\Http\Controllers\SampleCollectionController;



Route::get('/', function () {
    if (!session()->has('auth_user')) {
        return redirect()->route('login');
    }

    return redirect()->route('dashboards.home');
});

Route::get('/login', [AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.post');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Rutas temporales de prueba de API
|--------------------------------------------------------------------------
| Úsalas mientras validamos la conexión. Cuando todo funcione, puedes
| eliminarlas o moverlas dentro del middleware auth.session.custom.
*/



Route::middleware(['auth.session.custom'])->group(function () {

    Route::get('/inicio', [DashboardController::class, 'home'])
        ->name('dashboards.home');

    Route::get('/tv-dashboard', [OpportunityStatusController::class, 'tv'])
        ->middleware('dashboard.access:tv-dashboard')
        ->name('dashboards.tv');

    Route::get('/tv-dashboard/opportunity-status-data', [OpportunityStatusController::class, 'tvData'])
        ->middleware('dashboard.access:tv-dashboard')
        ->name('dashboards.tv.opportunity-status-data');

    Route::get('/dashboards/pacientes-mes', [DashboardController::class, 'pacientesMes'])
        ->middleware('dashboard.access:pacientes-mes')
        ->name('dashboards.pacientes-mes');

    Route::get('/dashboards/estudios', [StudiesController::class, 'index'])
        ->middleware('dashboard.access:estudios')
        ->name('dashboards.estudios');

    Route::get('/dashboards/consulta-externa', [StudiesController::class, 'wheel'])
        ->middleware('dashboard.access:consulta-externa')
        ->name('dashboards.rueda');

    Route::get('/dashboards/consulta-externa/data', [StudiesController::class, 'wheelData'])
        ->middleware('dashboard.access:consulta-externa')
        ->name('dashboards.rueda.data');

    // Compatibilidad con el enlace técnico utilizado en versiones anteriores.
    Route::get('/dashboards/rueda', function () {
        return redirect()->route('dashboards.rueda');
    })->middleware('dashboard.access:consulta-externa');
    
    Route::get('/dashboards/estudios/rueda-data', [StudiesController::class, 'wheelData'])
        ->middleware('dashboard.access:consulta-externa')
        ->name('dashboards.estudios.rueda-data');

    Route::get('/dashboards/estudios/paginated-tests-data', [StudiesController::class, 'paginatedTestsData'])
        ->middleware('dashboard.access:estudios')
        ->name('dashboards.estudios.paginated-tests-data');

    Route::get('/dashboards/tiempo-oportunidad-estados', [OpportunityStatusController::class, 'index'])
        ->middleware('dashboard.access:opportunity-status')
        ->name('dashboards.opportunity-status');

    Route::get('/dashboards/productividad', [ProductivityController::class, 'index'])
        ->middleware('dashboard.access:productividad')
        ->name('dashboards.productividad');

    Route::get('/dashboards/toma-muestra', [SampleCollectionController::class, 'index'])
        ->middleware('dashboard.access:sample-collection')
        ->name('dashboards.sample-collection');

    Route::get('/dashboards/toma-muestra/data', [SampleCollectionController::class, 'data'])
        ->middleware('dashboard.access:sample-collection')
        ->name('dashboards.sample-collection.data');

    Route::get('/pantalla/toma-muestra', [SampleCollectionController::class, 'tv'])
        ->middleware('dashboard.access:sample-collection-tv')
        ->name('dashboards.sample-collection-tv');

    Route::get('/pantalla/toma-muestra/data', [SampleCollectionController::class, 'tvData'])
        ->middleware('dashboard.access:sample-collection-tv')
        ->name('dashboards.sample-collection-tv.data');

    Route::get('/dashboards/venopunciones', [ProductivityController::class, 'venopunctures'])
        ->middleware('dashboard.access:venopunciones')
        ->name('dashboards.venopunciones');

    Route::get('/dashboards/venopunciones/data', [ProductivityController::class, 'duplicatedVenopuncturesData'])
        ->middleware('dashboard.access:venopunciones')
        ->name('dashboards.venopunciones.data');

    // Alias conservado para no romper enlaces anteriores del tablero de productividad.
    Route::get('/dashboards/productividad/duplicated-venopunctures-data', [ProductivityController::class, 'duplicatedVenopuncturesData'])
        ->middleware('dashboard.access:venopunciones')
        ->name('dashboards.productividad.duplicated-venopunctures-data');

    Route::middleware(['dashboard.access:users'])->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])
            ->name('users.index');

        Route::get('/usuarios/crear', [UserController::class, 'create'])
            ->name('users.create');

        Route::post('/usuarios', [UserController::class, 'store'])
            ->name('users.store');

        Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])
            ->name('users.edit');

        Route::put('/usuarios/{user}', [UserController::class, 'update'])
            ->name('users.update');
    });
});


Route::middleware(['auth.session.custom'])->group(function () {
    require __DIR__.'/vcare-additions.php';
});
require __DIR__.'/vcare-demo.php';
