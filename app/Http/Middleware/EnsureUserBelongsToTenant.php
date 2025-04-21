<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('Middleware EnsureUserBelongsToTenant: Entrando a handle().'); // Log inicio
        
        $user = Auth::user();
        $tenant = Filament::getTenant();
        
        // Información detallada para debugging
        if ($user && $tenant) {
            Log::debug('EnsureUserBelongsToTenant - Detalles:', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_tenant_id' => $user->tenant_id,
                'is_admin' => $user->is_admin ? 'true' : 'false',
                'is_tenant_admin' => $user->is_tenant_admin ? 'true' : 'false',
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'tenant_slug' => $tenant->slug,
                'comparación_ids' => ((string)$user->tenant_id === (string)$tenant->id) ? 'true' : 'false',
            ]);
        }

        // Verificar si el usuario tiene acceso al tenant
        // - Administradores globales tienen acceso a todos los tenants
        // - Admins de tenant solo a su propio tenant
        // - Usuarios regulares solo al tenant al que pertenecen
        $userBelongsToTenant = false;
        
        if ($user && $tenant) {
            if ($user->is_admin) {
                $userBelongsToTenant = true;
                Log::info('EnsureUserBelongsToTenant: Acceso permitido - Usuario es administrador global');
            } 
            elseif ($user->is_tenant_admin && (string)$user->tenant_id === (string)$tenant->id) {
                $userBelongsToTenant = true;
                Log::info('EnsureUserBelongsToTenant: Acceso permitido - Usuario es admin del tenant');
            }
            elseif ((string)$user->tenant_id === (string)$tenant->id) {
                $userBelongsToTenant = true;
                Log::info('EnsureUserBelongsToTenant: Acceso permitido - Usuario pertenece al tenant');
            }
        }
             
        if ($userBelongsToTenant) {
            Log::info('Middleware EnsureUserBelongsToTenant: Acceso permitido para usuario ID ' . $user->id . ' al tenant ID ' . $tenant->id);
            $response = $next($request);
            Log::info('Middleware EnsureUserBelongsToTenant: Saliendo de handle() - Acceso permitido.'); // Log fin permitido
            return $response;
        }
        
        Log::warning('Middleware EnsureUserBelongsToTenant: Acceso DENEGADO para usuario ID ' . $user->id . ' al tenant ID ' . $tenant->id);
        Log::error('Middleware EnsureUserBelongsToTenant: Abortando con 403.'); // Log abort
        abort(403, sprintf(
            'No tienes acceso a este tenant. [Usuario ID: %s, Tenant ID solicitado: %s, Tu tenant ID: %s]',
            $user->id,
            $tenant->id,
            $user->tenant_id
        ));
    }
}