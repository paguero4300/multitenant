<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');
        $user = $request->user();
        
        // Log inicial básico
        Log::debug('TenantMiddleware - Inicio de verificación', [
            'ruta' => $request->path(),
            'user_id' => $user ? $user->id : 'no autenticado',
        ]);
        
        // Verificar si el tenant existe y convertirlo a objeto si es un slug
        if (! $tenant instanceof Tenant) {
            try {
                $tenant = Tenant::where('slug', $tenant)->firstOrFail();
                $request->route()->setParameter('tenant', $tenant);
            } catch (\Exception $e) {
                Log::error('TenantMiddleware - Tenant no encontrado', ['slug' => $tenant]);
                abort(404, 'Organización no encontrada.');
            }
        }
        
        // Verificar si hay un usuario autenticado
        if (! $user) {
            abort(403, 'Debes iniciar sesión para acceder a este recurso.');
        }
        
        // 1. PRIMERO: Administradores globales pueden acceder a cualquier tenant
        if ($user->is_admin == 1) {
            Log::info('TenantMiddleware - Administrador global accediendo a tenant', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
            ]);
            return $next($request);
        }
        
        // 2. SEGUNDO: Administradores de tenant solo pueden acceder a su propio tenant
        if ($user->is_tenant_admin == 1) {
            // Comparar IDs como strings para evitar problemas de tipo
            if ((string)$user->tenant_id === (string)$tenant->id) {
                Log::info('TenantMiddleware - Admin de tenant accediendo a su tenant', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                ]);
                return $next($request);
            }
            
            Log::warning('TenantMiddleware - Admin de tenant intentando acceder a otro tenant', [
                'user_id' => $user->id,
                'user_tenant_id' => $user->tenant_id,
                'tenant_solicitado' => $tenant->id,
            ]);
            
            abort(403, 'No tienes permisos para acceder a esta organización como administrador.');
        }
        
        // 3. TERCERO: Usuarios normales solo pueden acceder a su propio tenant
        // Comparar IDs como strings para evitar problemas de tipo
        if ((string)$user->tenant_id === (string)$tenant->id) {
            Log::info('TenantMiddleware - Usuario regular accediendo a su tenant', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
            ]);
            return $next($request);
        }
        
        // 4. Cualquier otro caso: denegar acceso
        Log::warning('TenantMiddleware - Acceso denegado a tenant no autorizado', [
            'user_id' => $user->id,
            'user_tenant_id' => $user->tenant_id,
            'tenant_solicitado' => $tenant->id,
        ]);
        
        abort(403, 'No tienes permisos para acceder a esta organización.');
    }
}
