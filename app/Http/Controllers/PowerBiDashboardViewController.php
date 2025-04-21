<?php

namespace App\Http\Controllers;

use App\Models\PowerBiDashboard;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PowerBiDashboardViewController extends Controller
{
    /**
     * Muestra un dashboard de Power BI en pantalla completa
     */
    public function show(Request $request, Tenant $tenant, PowerBiDashboard $dashboard)
    {
        // Verificar que el dashboard pertenezca al tenant
        $dashboardBelongsToTenant = $dashboard->tenants()->where('tenants.id', $tenant->id)->exists();
        
        if (!$dashboardBelongsToTenant) {
            abort(404, 'Este dashboard no pertenece al tenant seleccionado');
        }
        
        // Verificar que el usuario tenga acceso al tenant
        $user = Auth::user();
        $userHasAccess = $user->is_admin || 
                         $user->is_tenant_admin || 
                         $user->tenants->contains('id', $tenant->id);
                         
        if (!$userHasAccess) {
            abort(403, 'No tienes acceso a este tenant');
        }
        
        // Generar token para el proxy
        try {
            $token = app(PowerBiController::class)->generateAdminProxyToken($dashboard);
            $proxyUrl = route('tenant.power-bi.proxy', ['token' => $token, 'tenant' => $tenant->slug]);
            
            return view('power-bi-dashboard.fullscreen', [
                'dashboard' => $dashboard,
                'tenant' => $tenant,
                'proxyUrl' => $proxyUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Error al generar proxy URL en PowerBiDashboardViewController: ' . $e->getMessage());
            abort(500, 'Error al cargar el dashboard');
        }
    }
}
