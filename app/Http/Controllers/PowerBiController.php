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
     *
     * @param PowerBiDashboard $dashboard
     * @return \Illuminate\View\View
     */
    public function adminPreview(PowerBiDashboard $dashboard)
    {
        // Generamos token para el proxy
        $token = $this->generateAdminProxyToken($dashboard);
        
        // URL del proxy
        $proxyUrl = route('admin.power-bi.proxy', ['token' => $token]);
        
        return view('admin.power-bi.preview', [
            'dashboard' => $dashboard,
            'embedUrl' => $proxyUrl,
            'title' => $dashboard->title,
            'description' => $dashboard->description,
        ]);
    }
    
    /**
     * Genera un token proxy para administradores
     *
     * @param PowerBiDashboard $dashboard
     * @return string
     */
    public function generateAdminProxyToken(PowerBiDashboard $dashboard)
    {
        // Crear un token que expire en 2 horas con la información necesaria
        $payload = [
            'dashboard_id' => $dashboard->id,
            'embed_url' => $dashboard->embed_url,
            'expires' => now()->addHours(2)->timestamp,
            'nonce' => Str::random(16), // Prevenir ataques de repetición
            'is_admin' => true, // Marcar como token de administrador
        ];
        
        return Crypt::encrypt($payload);
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
     * Esta función está diseñada para la URL /cliente/{tenant}/power-bi-dashboards
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
            
            // Generar URL proxy encriptada para ocultar la URL original
            $proxyUrl = route('cliente.power-bi.proxy', [
                'tenant' => $tenant->slug,
                'token' => $this->generateProxyToken($dashboard)
            ]);
            
            // Renderizar la vista directa sin UI adicional
            return view('tenant.power-bi.direct', [
                'dashboard' => $dashboard,
                'embedUrl' => $proxyUrl,
                'tenant' => $tenant,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar el dashboard directo: ' . $e->getMessage());
            abort(500, 'Error al cargar el dashboard');
        }
    }
    
    /**
     * Muestra un dashboard específico para un tenant
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
        
        // Generar URL proxy encriptada para ocultar la URL original
        $proxyUrl = route('tenant.power-bi.proxy', [
            'tenant' => $tenant,
            'token' => $this->generateProxyToken($dashboard)
        ]);
        
        return view('tenant.power-bi.show', [
            'dashboard' => $dashboard,
            'embedUrl' => $proxyUrl,
            'tenant' => $tenant,
        ]);
    }
    
    /**
     * Genera un token proxy encriptado para un dashboard
     *
     * @param PowerBiDashboard $dashboard
     * @return string
     */
    protected function generateProxyToken(PowerBiDashboard $dashboard)
    {
        // Crear un token que expire en 1 hora con la información necesaria
        $payload = [
            'dashboard_id' => $dashboard->id,
            'embed_url' => $dashboard->embed_url,
            'expires' => now()->addHour()->timestamp,
            'nonce' => Str::random(16), // Prevenir ataques de repetición
        ];
        
        return Crypt::encrypt($payload);
    }
    
    /**
     * Proxy para enmascarar la URL original del dashboard de Power BI
     * Versión para administradores
     * 
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function adminProxy(Request $request, $token)
    {
        try {
            // Desencriptar el token y validar
            $payload = Crypt::decrypt($token);
            
            if (now()->timestamp > $payload['expires']) {
                abort(403, 'El token ha expirado');
            }
            
            // Verificar que sea un token de administrador
            if (!isset($payload['is_admin']) || !$payload['is_admin']) {
                abort(403, 'Token inválido para administradores');
            }
            
            // Obtener el dashboard
            $dashboard = PowerBiDashboard::findOrFail($payload['dashboard_id']);
            
            // Verificar que el usuario actual sea administrador global o de tenant
            $user = $request->user();
            $userHasAccess = false;
            
            if ($user) {
                if ($user->is_admin) {
                    // Administrador global tiene acceso
                    $userHasAccess = true;
                    Log::info('adminProxy - Acceso permitido: usuario es administrador global');
                } else if ($user->is_tenant_admin) {
                    // Administrador de tenant también tiene acceso
                    $userHasAccess = true;
                    Log::info('adminProxy - Acceso permitido: usuario es administrador de tenant');
                }
            }
            
            if (!$userHasAccess) {
                Log::warning('adminProxy - Acceso denegado: usuario no tiene permisos', [
                    'user_id' => $user ? $user->id : null,
                    'user_email' => $user ? $user->email : null,
                    'is_admin' => $user && $user->is_admin ? 'true' : 'false',
                    'is_tenant_admin' => $user && $user->is_tenant_admin ? 'true' : 'false'
                ]);
                abort(403, 'Acceso no autorizado');
            }
            
            // Intentar registrar el acceso pero capturar cualquier error
            try {
                if (class_exists('App\\Models\\PowerBiDashboardAccessLog')) {
                    // Verificamos primero si la tabla existe
                    if (Schema::hasTable('power_bi_dashboard_access_logs')) {
                        $logData = [
                            'dashboard_id' => $dashboard->id,
                            'user_id' => $request->user() ? $request->user()->id : null,
                            'access_ip' => $request->ip(),
                        ];
                        
                        // Si la columna existe, agregamos el flag
                        if (Schema::hasColumn('power_bi_dashboard_access_logs', 'is_admin_access')) {
                            $logData['is_admin_access'] = true;
                        }
                        
                        // Si la columna existe, agregamos el tenant_id
                        if (Schema::hasColumn('power_bi_dashboard_access_logs', 'tenant_id')) {
                            // Si es admin de tenant, registrar su tenant_id
                            if ($request->user() && $request->user()->is_tenant_admin) {
                                $logData['tenant_id'] = $request->user()->tenant_id;
                            } else {
                                $logData['tenant_id'] = null;
                            }
                        }
                        
                        PowerBiDashboardAccessLog::create($logData);
                    }
                }
            } catch (\Exception $logError) {
                // Simplemente registramos el error y continuamos, no interrumpimos la visualización
                Log::warning('Error al registrar acceso al dashboard: ' . $logError->getMessage());
            }
            
            // Renderizar la vista con el dashboard
            return response()->view('admin.power-bi.embed', [
                'dashboard' => $dashboard,
                'embedUrl' => $dashboard->embed_url,
            ])->header('Permissions-Policy', 'clipboard-read=*, clipboard-write=*');
        } catch (\Exception $e) {
            // Registrar el error para debug
            Log::error('Error en adminProxy: ' . $e->getMessage());
            
            // Mostrar un mensaje más descriptivo al usuario
            return response()->view('errors.dashboard', [
                'message' => 'No se pudo cargar el dashboard: ' . $e->getMessage(),
                'error' => $e,
            ], 403);
        }
    }
    
    /**
     * Proxy para enmascarar la URL original del dashboard de Power BI
     * Versión para tenants
     *
     * @param Request $request
     * @param Tenant $tenant
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function proxy(Request $request, Tenant $tenant, $token)
    {
        // Log completo de la solicitud para diagnóstico
        Log::info('PowerBiController::proxy - SOLICITUD RECIBIDA', [
            'request_url' => $request->fullUrl(),
            'request_path' => $request->path(),
            'request_method' => $request->method(),
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'tenant_slug' => $tenant->slug,
        ]);
        
        try {
            // Desencriptar el token y validar
            $payload = Crypt::decrypt($token);
            
            if (now()->timestamp > $payload['expires']) {
                abort(403, 'El token ha expirado');
            }
            
            // Obtener el dashboard
            $dashboard = PowerBiDashboard::findOrFail($payload['dashboard_id']);
            
            // Verificar que el tenant tenga acceso
            $hasAccess = $tenant->powerBiDashboards()
                ->where('power_bi_dashboards.id', $dashboard->id)
                ->where('is_active', true)
                ->exists();
                
            if (!$hasAccess) {
                abort(403, 'No tienes acceso a este dashboard');
            }
            
            // Verificar que el usuario tenga acceso al tenant
            $user = $request->user();
            
            // Registrar información para debugging
            Log::debug('PowerBiController::proxy - Verificando acceso de usuario', [
                'user_id' => $user ? $user->id : null,
                'user_email' => $user ? $user->email : null,
                'user_tenant_id' => $user ? $user->tenant_id : null,
                'tenant_id' => $tenant->id,
                'is_admin' => $user && $user->is_admin ? 'true' : 'false',
                'is_tenant_admin' => $user && $user->is_tenant_admin ? 'true' : 'false',
                'tenant_match' => $user && ((string)$user->tenant_id === (string)$tenant->id) ? 'true' : 'false',
            ]);
            
            // Verificar el acceso según el rol del usuario
            $userHasAccess = false;
            
            if ($user) {
                if ($user->is_admin) {
                    // Administrador global tiene acceso a todos los dashboards
                    $userHasAccess = true;
                    Log::info('PowerBiController::proxy - Acceso permitido: usuario es administrador global');
                } 
                else if ($user->is_tenant_admin && (string)$user->tenant_id === (string)$tenant->id) {
                    // Tenant admin tiene acceso a dashboards de su tenant
                    $userHasAccess = true;
                    Log::info('PowerBiController::proxy - Acceso permitido: usuario es admin del tenant');
                }
                else if ((string)$user->tenant_id === (string)$tenant->id) {
                    // Usuario regular tiene acceso a dashboards de su tenant
                    $userHasAccess = true;
                    Log::info('PowerBiController::proxy - Acceso permitido: usuario pertenece al tenant');
                }
            }
                 
            if (!$userHasAccess) {
                Log::warning('PowerBiController::proxy - Acceso denegado a dashboard', [
                    'user_id' => $user ? $user->id : null,
                    'tenant_id' => $tenant->id,
                    'dashboard_id' => $dashboard->id,
                ]);
                abort(403, 'No tienes permisos para acceder a esta sección');
            }
            
            // Verificar si se está solicitando un recurso secundario (como hash-manifest.js)
            $originalUrl = $request->url();
            $resourcePath = $request->path();
            $isResourceRequest = str_contains($resourcePath, '.js') || str_contains($resourcePath, '.css') || str_contains($resourcePath, '.map') || str_contains($resourcePath, '.png') || str_contains($resourcePath, '.woff');
            
            Log::debug('PowerBiController::proxy - Solicitud recibida', [
                'url' => $originalUrl,
                'path' => $resourcePath,
                'is_resource' => $isResourceRequest ? 'true' : 'false'
            ]);
            
            // Construir encabezados para pasar a Power BI
            $headers = $request->header();
            
            // Eliminar encabezados que no deberían pasarse
            unset($headers['host']);
            unset($headers['cookie']);
            
            // Caso especial para hash-manifest.js que causa recargas infinitas
            if (str_contains($resourcePath, 'hash-manifest.js')) {
                Log::warning('PowerBiController::proxy - INTERCEPTANDO hash-manifest.js', [
                    'resource_path' => $resourcePath,
                    'full_url' => $request->fullUrl(),
                    'embed_url' => $payload['embed_url'],
                    'token_expires' => date('Y-m-d H:i:s', $payload['expires']),
                    'dashboard_id' => $payload['dashboard_id'],
                ]);
                
                // Devolvemos un archivo JavaScript vacío para evitar el error y las recargas
                $jsContent = "// Empty hash-manifest.js to prevent reloading - " . time() . "\n"; 
                return response($jsContent, 200)
                    ->header('Content-Type', 'application/javascript')
                    ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Access-Control-Allow-Origin', '*');
            }
            
            // Si es una solicitud de recurso, intentar acceder directamente a la URL original
            // sin pasar por la URL de incrustación
            if ($isResourceRequest) {
                // Extraer el dominio base de la URL de incrustación
                $embedUrlParts = parse_url($payload['embed_url']);
                $baseUrl = $embedUrlParts['scheme'] . '://' . $embedUrlParts['host'];
                
                // Construir la URL completa del recurso
                $resourceUrl = $baseUrl . '/' . $resourcePath;
                
                Log::debug('PowerBiController::proxy - Accediendo directamente al recurso', [
                    'resource_url' => $resourceUrl
                ]);
                
                // Intentar obtener el recurso directamente
                $response = Http::withHeaders($headers)->get($resourceUrl);
                
                // Si el recurso no se encuentra (404), devolver un contenido vacío del tipo adecuado
                // para evitar errores en la página principal
                if ($response->status() == 404) {
                    $contentType = 'text/plain';
                    
                    if (str_contains($resourcePath, '.js')) {
                        $contentType = 'application/javascript';
                    } else if (str_contains($resourcePath, '.css')) {
                        $contentType = 'text/css';
                    } else if (str_contains($resourcePath, '.png') || str_contains($resourcePath, '.jpg') || str_contains($resourcePath, '.jpeg')) {
                        $contentType = 'image/png';
                    }
                    
                    Log::warning('PowerBiController::proxy - RECURSO 404 INTERCEPTADO', [
                        'resource_path' => $resourcePath,
                        'resource_url' => $resourceUrl,
                        'content_type' => $contentType,
                        'status_code' => $response->status(),
                        'response_headers' => $response->headers(),
                        'request_url' => $request->fullUrl(),
                        'payload_embed_url' => $payload['embed_url']
                    ]);
                    
                    return response("// Empty resource for " . $resourcePath . " - " . time(), 200)
                        ->header('Content-Type', $contentType)
                        ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
                        ->header('Pragma', 'no-cache')
                        ->header('Access-Control-Allow-Origin', '*');
                }
            } else {
                // Para la página principal, usar la URL de incrustación normal
                $response = Http::withHeaders($headers)->get($payload['embed_url']);
            }
            
            // Filtrar los encabezados problemáticos que causan el error de codificación chunked
            $safeHeaders = [];
            foreach ($response->headers() as $key => $values) {
                // Excluir encabezados relacionados con la codificación de transferencia
                if (!in_array(strtolower($key), ['transfer-encoding', 'connection', 'keep-alive'])) {
                    $safeHeaders[$key] = $values;
                }
            }
            
            // Agregar encabezados CORS para evitar restricciones de acceso a recursos
            $safeHeaders['Access-Control-Allow-Origin'] = ['*'];
            $safeHeaders['Access-Control-Allow-Methods'] = ['GET, POST, OPTIONS'];
            $safeHeaders['Access-Control-Allow-Headers'] = ['Origin, Content-Type, Accept'];
            $safeHeaders['Cache-Control'] = ['no-store, no-cache, must-revalidate'];
            $safeHeaders['Pragma'] = ['no-cache'];
            
            // Log de la respuesta que estamos por enviar
            Log::info('PowerBiController::proxy - RESPUESTA CONSTRUIDA', [
                'resource_path' => $resourcePath,
                'response_status' => $response->status(),
                'response_content_length' => strlen($response->body()),
                'headers_count' => count($safeHeaders),
                'is_resource' => $isResourceRequest ? 'true' : 'false',
            ]);
            
            // Retornar la respuesta con encabezados seguros
            return response($response->body(), $response->status())
                ->withHeaders($safeHeaders);
        } catch (\Exception $e) {
            Log::error('PowerBiController::proxy - ERROR FATAL', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'tenant_slug' => $tenant->slug,
                'request_url' => $request->fullUrl(),
                'request_path' => $request->path(),
                'request_method' => $request->method(),
                'user_id' => $request->user() ? $request->user()->id : null,
                'user_email' => $request->user() ? $request->user()->email : null,
            ]);
            
            // En caso de hash-manifest.js, aún intentamos resolver
            if (str_contains($request->path(), 'hash-manifest.js')) {
                Log::warning('PowerBiController::proxy - Intentando recuperar de error para hash-manifest.js');
                return response("// Error recovered hash-manifest.js - " . time() . "\n", 200)
                    ->header('Content-Type', 'application/javascript')
                    ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Access-Control-Allow-Origin', '*');
            }
            
            // Para otros errores, mostramos el mensaje
            abort(403, 'Error en la carga del dashboard: ' . $e->getMessage());
        }
    }
}
