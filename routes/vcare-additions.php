<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExecutiveSummaryController;
use App\Http\Controllers\SampleCollectionController;
use App\Http\Controllers\SupplierStudyController;
use App\Http\Controllers\OrderTraceController;

// Analítica de toma de muestra
Route::get('/dashboards/toma-muestra-analitica',
    [ExecutiveSummaryController::class, 'index'])
    ->middleware('dashboard.access:sample-collection-analytics')
    ->name('dashboards.sample-collection-analytics');

Route::get('/dashboards/toma-muestra-analitica/data',
    [ExecutiveSummaryController::class, 'data'])
    ->middleware('dashboard.access:sample-collection-analytics')
    ->name('dashboards.sample-collection-analytics.data');

// Seguimiento de órdenes
Route::get('/dashboards/toma-muestra/order-trace/{orderNumber}',
    [SampleCollectionController::class, 'orderTrace'])
    ->middleware('dashboard.access:sample-collection')
    ->name('dashboards.sample-collection.order-trace');

Route::get('/dashboards/toma-muestra/order-trace/{orderNumber}/pdf',
    [OrderTraceController::class, 'pdf'])
    ->middleware('dashboard.access:sample-collection')
    ->name('dashboards.sample-collection.order-trace.pdf');

// Proveedores
Route::get('/dashboards/supplier-studies',
    [SupplierStudyController::class, 'index'])
    ->middleware('dashboard.access:supplier-studies')
    ->name('dashboards.supplier-studies');

Route::get('/dashboards/supplier-studies/data',
    [SupplierStudyController::class, 'data'])
    ->middleware('dashboard.access:supplier-studies')
    ->name('dashboards.supplier-studies.data');

Route::get('/dashboards/supplier-studies/pending',
    [SupplierStudyController::class, 'historicalPending'])
    ->middleware('dashboard.access:supplier-studies')
    ->name('dashboards.supplier-studies.pending');

Route::get('/dashboards/supplier-studies/suppliers',
    [SupplierStudyController::class, 'suppliers'])
    ->middleware('dashboard.access:supplier-studies')
    ->name('dashboards.supplier-studies.suppliers');

Route::get('/dashboards/supplier-studies/catalog',
    [SupplierStudyController::class, 'catalog'])
    ->middleware('dashboard.access:supplier-studies')
    ->name('dashboards.supplier-studies.catalog');

Route::get('/dashboards/supplier-studies/assignments',
    [SupplierStudyController::class, 'assignments'])
    ->middleware('dashboard.access:supplier-studies')
    ->name('dashboards.supplier-studies.assignments');

Route::post('/dashboards/supplier-studies/suppliers',
    [SupplierStudyController::class, 'storeSupplier'])
    ->middleware('dashboard.access:supplier-studies')
    ->name('dashboards.supplier-studies.suppliers.store');

Route::post('/dashboards/supplier-studies/suppliers/edit',
    [SupplierStudyController::class, 'updateSupplier'])
    ->middleware('dashboard.access:supplier-studies')
    ->name('dashboards.supplier-studies.suppliers.update');

Route::post('/dashboards/supplier-studies/assign-study',
    [SupplierStudyController::class, 'assignStudy'])
    ->middleware('dashboard.access:supplier-studies')
    ->name('dashboards.supplier-studies.assign-study');