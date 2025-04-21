<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PowerBiController;

Route::get('/', function () {
    return view('welcome');
});

// Rutas de autenticación básicas
Route::get('/login', function() {
    return 'Página de login';
})->name('login');

Route::post('/logout', function() {
    return redirect('/');
})->name('logout');

// Rutas para administradores (protegidas por middleware auth y admin)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Ruta de vista previa para administradores
    Route::get('/power-bi/dashboard/{dashboard}/preview', [PowerBiController::class, 'adminPreview'])
        ->name('power-bi.preview');
        
    // Ruta de proxy para ocultar URL original del dashboard para administradores
    Route::get('/power-bi/proxy/{token}', [PowerBiController::class, 'adminProxy'])
        ->name('power-bi.proxy');
});

// Rutas para tenants (protegidas por middleware auth y tenant)
Route::middleware(['auth', 'tenant'])->prefix('tenant/{tenant}')->name('tenant.')->group(function () {
    // Vista directa para la ruta power-bi-dashboards (con guión) - UI mínima
    Route::get('/power-bi-dashboards', [PowerBiController::class, 'showDirectDashboard'])
        ->name('power-bi-dashboards');
    
    // Lista de dashboards disponibles para el tenant
    Route::get('/power-bi/dashboards', [PowerBiController::class, 'tenantDashboards'])
        ->name('power-bi.index');
    
    // Vista de un dashboard específico
    Route::get('/power-bi/dashboard/{dashboard}', [PowerBiController::class, 'showDashboard'])
        ->name('power-bi.show');
    
    // Vista de dashboard en pantalla completa (solo dashboard sin interfaz administrativa)
    Route::get('/power-bi/dashboard/{dashboard}/fullscreen', [\App\Http\Controllers\PowerBiDashboardViewController::class, 'show'])
        ->name('power-bi.fullscreen');
    
    // Proxy para enmascarar la URL original
    Route::get('/power-bi/proxy/{token}', [PowerBiController::class, 'proxy'])
        ->name('power-bi.proxy');
});

// Duplicar las rutas para el prefijo 'cliente' para que funcionen con el panel de Filament
Route::middleware(['auth', 'tenant'])->prefix('cliente/{tenant}')->name('cliente.')->group(function () {
    // Vista directa para la ruta power-bi-dashboards (con guión) - UI mínima
    Route::get('/power-bi-dashboards', [PowerBiController::class, 'showDirectDashboard'])
        ->name('power-bi-dashboards');
        
    // Lista de dashboards disponibles para el tenant
    Route::get('/power-bi/dashboards', [PowerBiController::class, 'tenantDashboards'])
        ->name('power-bi.index');
    
    // Vista de un dashboard específico
    Route::get('/power-bi/dashboard/{dashboard}', [PowerBiController::class, 'showDashboard'])
        ->name('power-bi.show');
    
    // Vista de dashboard en pantalla completa (solo dashboard sin interfaz administrativa)
    Route::get('/power-bi/dashboard/{dashboard}/fullscreen', [\App\Http\Controllers\PowerBiDashboardViewController::class, 'show'])
        ->name('power-bi.fullscreen');
    
    // Proxy para enmascarar la URL original
    Route::get('/power-bi/proxy/{token}', [PowerBiController::class, 'proxy'])
        ->name('power-bi.proxy');
});
