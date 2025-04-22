<?php

namespace App\Http\Controllers;

use App\Models\PowerBiDashboard;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PowerBiDashboardViewController extends Controller
{
    /**
     * Muestra un dashboard en pantalla completa de forma simplificada
     * Sin proxy, URL directa
     */
    public function show(Request $request, Tenant $tenant, PowerBiDashboard $dashboard)
    {
        try {
            // Verificar acceso
            $hasAccess = $tenant->powerBiDashboards()
                ->where('power_bi_dashboards.id', $dashboard->id)
                ->exists();
                
            if (!$hasAccess) {
                abort(403, 'Acceso denegado');
            }
            
            // Usar directamente la URL del dashboard sin proxy
            $embedUrl = $dashboard->embed_url;
            
            // Registrar acceso para estadísticas
            Log::info('Full Screen Dashboard View', [
                'dashboard_id' => $dashboard->id,
                'tenant_id' => $tenant->id,
                'user_id' => $request->user() ? $request->user()->id : null,
                'direct_url' => true
            ]);
            
            return view('tenant.power-bi.direct', [
                'dashboard' => $dashboard,
                'embedUrl' => $embedUrl,
                'tenant' => $tenant,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar dashboard: ' . $e->getMessage());
            abort(500, 'Error al cargar el dashboard');
        }
    }
}
