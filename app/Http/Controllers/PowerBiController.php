<?php

namespace App\Http\Controllers;

use App\Models\PowerBiDashboard;
use App\Models\PowerBiDashboardAccessLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PowerBiController extends Controller
{
    /**
     * Muestra la vista previa de un dashboard para los administradores
     * Sin proxy, URL directa
     *
     * @param PowerBiDashboard $dashboard
     * @return \Illuminate\View\View
     */
    public function adminPreview(PowerBiDashboard $dashboard)
    {
        // Usar directamente la URL de embed
        $embedUrl = $dashboard->embed_url;
        
        // Registrar acceso para estadísticas
        Log::info('Admin Preview Dashboard', [
            'dashboard_id' => $dashboard->id,
            'admin_id' => request()->user() ? request()->user()->id : null,
            'direct_url' => true
        ]);
        
        return view('admin.power-bi.preview', [
            'dashboard' => $dashboard,
            'embedUrl' => $embedUrl,
            'title' => $dashboard->title,
            'description' => $dashboard->description,
        ]);
    }
    
    /**
     * Muestra la lista de dashboards disponibles para un tenant
     *
     * @param Tenant $tenant
     * @return \Illuminate\View\View
     */
    public function tenantDashboards(Tenant $tenant)
    {
        // Obtener los dashboards activos a los que el tenant tiene acceso
        $dashboards = $tenant->powerBiDashboards()
            ->where('is_active', true)
            ->get();
        
        return view('tenant.power-bi.index', [
            'dashboards' => $dashboards,
            'tenant' => $tenant,
        ]);
    }
    
    /**
     * Muestra directamente el primer dashboard activo del tenant sin UI adicional
     * Sin proxy, URL directa
     *
     * @param Tenant $tenant
     * @return \Illuminate\View\View
     */
    public function showDirectDashboard(Tenant $tenant)
    {
        // Obtener el primer dashboard activo del tenant
        $dashboard = $tenant->powerBiDashboards()
            ->where('is_active', true)
            ->first();
            
        if (!$dashboard) {
            abort(404, 'No hay dashboards activos disponibles para este tenant');
        }
        
        try {
            // Registrar el acceso
            try {
                PowerBiDashboardAccessLog::create([
                    'dashboard_id' => $dashboard->id,
                    'tenant_id' => $tenant->id,
                    'user_id' => request()->user() ? request()->user()->id : null,
                    'access_ip' => request()->ip(),
                ]);
            } catch (\Exception $e) {
                Log::warning('Error al registrar acceso al dashboard: ' . $e->getMessage());
            }
            
            // Usar directamente la URL de embed
            $embedUrl = $dashboard->embed_url;
            
            // Renderizar la vista directa sin UI adicional
            return view('tenant.power-bi.direct', [
                'dashboard' => $dashboard,
                'embedUrl' => $embedUrl,
                'tenant' => $tenant,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar el dashboard directo: ' . $e->getMessage());
            abort(500, 'Error al cargar el dashboard');
        }
    }
    
    /**
     * Muestra un dashboard específico para un tenant
     * Sin proxy, URL directa
     *
     * @param Tenant $tenant
     * @param PowerBiDashboard $dashboard
     * @return \Illuminate\View\View
     */
    public function showDashboard(Tenant $tenant, PowerBiDashboard $dashboard)
    {
        // Verificar que el tenant tenga acceso a este dashboard
        $hasAccess = $tenant->powerBiDashboards()
            ->where('power_bi_dashboards.id', $dashboard->id)
            ->where('is_active', true)
            ->exists();
            
        if (!$hasAccess) {
            abort(403, 'No tienes acceso a este dashboard');
        }
        
        // Registrar el acceso
        PowerBiDashboardAccessLog::create([
            'dashboard_id' => $dashboard->id,
            'tenant_id' => $tenant->id,
            'user_id' => request()->user() ? request()->user()->id : null,
            'access_ip' => request()->ip(),
        ]);
        
        // Usar directamente la URL de embed
        $embedUrl = $dashboard->embed_url;
        
        return view('tenant.power-bi.show', [
            'dashboard' => $dashboard,
            'embedUrl' => $embedUrl,
            'tenant' => $tenant,
        ]);
    }
    
    /**
     * Método completo para proxy de Power BI que maneja todas las solicitudes
     * @deprecated Esta función será eliminada en futuras versiones. Use URLs directas de Power BI.
     * 
     * @param Request $request
     * @param string $token
     * @param string|null $any
     * @return \Illuminate\Http\Response
     */
    public function proxy(Request $request, $token, $any = null)
    {
        // Advertencia de uso obsoleto
        Log::warning('Uso de método obsoleto: PowerBiController::proxy. Use URLs directas de Power BI.', [
            'url' => $request->fullUrl()
        ]);
        
        try {
            // Desencriptar token
            $decrypted = Crypt::decrypt($token);
            if (!isset($decrypted['report_url'])) {
                throw new \Exception('Token inválido - falta report_url');
            }
            
            // Obtener URL del reporte
            $reportUrl = $decrypted['report_url'];
            
            // Devolver redirección directa a la URL del dashboard
            return redirect()->away($reportUrl);
            
        } catch (\Exception $e) {
            // Registrar el error
            Log::error('PowerBI Proxy Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
            ]);
            
            // Devolver mensaje de error
            abort(500, 'Error al cargar el dashboard. El proxy está obsoleto, use URLs directas.');
        }
    }
    
    /**
     * Alias para compatibilidad con rutas existentes
     * @deprecated Esta función será eliminada en futuras versiones. Use URLs directas de Power BI.
     */
    public function adminProxy(Request $request, $token, $any = null)
    {
        return $this->proxy($request, $token, $any);
    }

    /**
     * Genera un token proxy simplificado
     * @deprecated Esta función será eliminada en futuras versiones. Use URLs directas de Power BI.
     *
     * @param PowerBiDashboard $dashboard
     * @return string
     */
    protected function generateProxyToken(PowerBiDashboard $dashboard)
    {
        // Advertencia de uso obsoleto
        Log::warning('Uso de método obsoleto: PowerBiController::generateProxyToken. Use URLs directas de Power BI.');
        
        // Simplificar el payload con solo la información necesaria
        $payload = [
            'report_url' => $dashboard->embed_url,
            'expires' => now()->addHours(2)->timestamp,
        ];
        
        return Crypt::encrypt($payload);
    }
    
    /**
     * Alias de generateProxyToken para compatibilidad
     * @deprecated Esta función será eliminada en futuras versiones. Use URLs directas de Power BI.
     */
    public function generateAdminProxyToken(PowerBiDashboard $dashboard)
    {
        return $this->generateProxyToken($dashboard);
    }

    /**
     * Método de prueba simple para mostrar un dashboard directo sin proxy
     * Siguiendo el principio KISS
     * 
     * @param PowerBiDashboard $dashboard
     * @return \Illuminate\View\View
     */
    public function testDirectDashboard(PowerBiDashboard $dashboard)
    {
        // Usar directamente la URL de embed de Power BI
        $embedUrl = $dashboard->embed_url;
        
        // Registrar información para depuración
        \Illuminate\Support\Facades\Log::info('Cargando dashboard directo', [
            'dashboard_id' => $dashboard->id,
            'título' => $dashboard->title,
            'embed_url' => $embedUrl
        ]);
        
        // Devolver la vista simple con solo un iframe
        return view('test-dashboard', [
            'embedUrl' => $embedUrl
        ]);
    }

    /**
     * Método de prueba usando JavaScript oficial de Power BI
     * La solución más simple y directa siguiendo KISS
     * 
     * @param PowerBiDashboard $dashboard
     * @return \Illuminate\View\View
     */
    public function testSuperSimple(PowerBiDashboard $dashboard)
    {
        // Registrar información para depuración
        \Illuminate\Support\Facades\Log::info('Cargando dashboard súper simple', [
            'dashboard_id' => $dashboard->id,
            'título' => $dashboard->title,
            'embed_url' => $dashboard->embed_url
        ]);
        
        // Devolver vista que usa JavaScript oficicial de Power BI
        return view('super-simple-dashboard', [
            'embedUrl' => $dashboard->embed_url
        ]);
    }

    /**
     * Método ultra simple - La versión más básica posible
     * 
     * @param PowerBiDashboard $dashboard
     * @return \Illuminate\View\View
     */
    public function ultraSimple(PowerBiDashboard $dashboard)
    {
        // Registrar información para depuración
        \Illuminate\Support\Facades\Log::info('Cargando dashboard ultra simple', [
            'dashboard_id' => $dashboard->id,
            'título' => $dashboard->title,
            'embed_url' => $dashboard->embed_url
        ]);
        
        // Devolver vista ultra simple
        return view('ultra-simple-dashboard', [
            'embedUrl' => $dashboard->embed_url
        ]);
    }

    /**
     * Vista sandbox que carga el dashboard a través de un proxy local
     * 
     * @param PowerBiDashboard $dashboard
     * @return \Illuminate\View\View
     */
    public function sandboxDashboard(PowerBiDashboard $dashboard)
    {
        // Registrar información para depuración
        \Illuminate\Support\Facades\Log::info('Cargando dashboard en sandbox', [
            'dashboard_id' => $dashboard->id,
            'título' => $dashboard->title,
            'embed_url' => $dashboard->embed_url
        ]);
        
        // Devolver vista sandbox
        return view('sandbox-dashboard', [
            'dashboard' => $dashboard
        ]);
    }
    
    /**
     * API endpoint que sirve el contenido del dashboard
     * Este método actúa como un proxy local
     * 
     * @param PowerBiDashboard $dashboard
     * @return \Illuminate\Http\Response
     */
    public function apiEmbedDashboard(PowerBiDashboard $dashboard)
    {
        // Registrar acceso
        \Illuminate\Support\Facades\Log::info('API Embed Dashboard', [
            'dashboard_id' => $dashboard->id,
            'ip' => request()->ip()
        ]);
        
        try {
            // Obtener contenido desde Power BI
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => request()->header('User-Agent', 'Mozilla/5.0'),
                'Accept' => '*/*',
                'Accept-Language' => 'es-ES,es'
            ])->get($dashboard->embed_url);
            
            // Devolver contenido con los headers apropiados
            return response($response->body())
                ->header('Content-Type', 'text/html')
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('Content-Security-Policy', "frame-ancestors 'self'");
        } catch (\Exception $e) {
            // Registrar error
            \Illuminate\Support\Facades\Log::error('Error en API Embed', [
                'error' => $e->getMessage(),
                'dashboard_id' => $dashboard->id
            ]);
            
            // Devolver página de error
            return response()->view('errors.dashboard', [
                'message' => 'No se pudo cargar el dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método directo extremo - simplemente redirecciona al dashboard
     * La solución más simple posible (KISS absoluto)
     * 
     * @param PowerBiDashboard $dashboard
     * @return \Illuminate\View\View
     */
    public function directEmbed(PowerBiDashboard $dashboard)
    {
        // Registrar información para depuración
        \Illuminate\Support\Facades\Log::info('Redirigiendo directamente a dashboard', [
            'dashboard_id' => $dashboard->id,
            'título' => $dashboard->title,
            'embed_url' => $dashboard->embed_url
        ]);
        
        // Simplemente redireccionar al dashboard usando la vista
        return view('direct-embed-dashboard', [
            'dashboard' => $dashboard
        ]);
    }

    /**
     * Método alternativo utilizando un iframe con carga diferida
     * 
     * @param PowerBiDashboard $dashboard
     * @return \Illuminate\View\View
     */
    public function embedWithParams(PowerBiDashboard $dashboard)
    {
        // Registrar información para depuración
        \Illuminate\Support\Facades\Log::info('Cargando dashboard con parámetros', [
            'dashboard_id' => $dashboard->id,
            'título' => $dashboard->title,
            'embed_url' => $dashboard->embed_url
        ]);
        
        // Retornar vista con iframe y pantalla de carga
        return view('embed-with-params', [
            'dashboard' => $dashboard
        ]);
    }
}
