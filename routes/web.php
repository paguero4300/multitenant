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
        
    // DEPRECATED: Las siguientes rutas de proxy serán eliminadas en futuras versiones
    // Se mantienen temporalmente por compatibilidad con código existente
    // Las URLs de Power BI ahora se usan directamente sin enmascaramiento

    // Ruta de proxy para administradores
    // Route::get('/admin/power-bi/proxy/{token}/{any?}', [PowerBiController::class, 'adminProxy'])
    //     ->where('any', '^(.*)$')
    //     ->name('power-bi.proxy');
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
    
    // DEPRECATED: Las siguientes rutas de proxy serán eliminadas en futuras versiones
    // Se mantienen temporalmente por compatibilidad con código existente
    // Las URLs de Power BI ahora se usan directamente sin enmascaramiento

    // Proxy para tenants
    // Route::get('/tenant/{tenant}/power-bi/proxy/{token}/{any?}', [PowerBiController::class, 'proxy'])
    //     ->where('any', '.*')
    //     ->name('power-bi.proxy');
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
    
    // Proxy para clientes (duplicado)
    // Route::get('/cliente/{tenant}/power-bi/proxy/{token}/{any?}', [PowerBiController::class, 'proxy'])
    //     ->where('any', '.*')
    //     ->name('power-bi.proxy');
});

// Ruta de prueba - acceso directo sin proxy - KISS
Route::get('/test-direct-dashboard/{dashboard}', [PowerBiController::class, 'testDirectDashboard'])
    ->name('test.direct-dashboard');

// Ruta súper simple usando el SDK oficial de Power BI - máximo KISS
Route::get('/super-simple/{dashboard}', [PowerBiController::class, 'testSuperSimple'])
    ->name('test.super-simple');

// Ruta ultra simple - la más básica posible - KISS extremo
Route::get('/ultra-simple/{dashboard}', [PowerBiController::class, 'ultraSimple'])
    ->name('test.ultra-simple');

// Ruta sandbox con proxy local
Route::get('/sandbox-dashboard/{dashboard}', [PowerBiController::class, 'sandboxDashboard'])
    ->name('sandbox.dashboard');

// API para servir el contenido del dashboard
Route::get('/api/dashboard/embed/{dashboard}', [PowerBiController::class, 'apiEmbedDashboard'])
    ->name('api.dashboard.embed');

// Ruta directa extrema - redirige directamente sin iframe ni proxy
Route::get('/direct-embed/{dashboard}', [PowerBiController::class, 'directEmbed'])
    ->name('direct.embed');

// Ruta con iframe y pantalla de carga
Route::get('/embed-params/{dashboard}', [PowerBiController::class, 'embedWithParams'])
    ->name('embed.with-params');
