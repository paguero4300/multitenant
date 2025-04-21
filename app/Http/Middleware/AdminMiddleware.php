<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado y es administrador general
        if (! $request->user()) {
            // Usuario no autenticado
            return redirect()->route('login')->with('error', 'Debes iniciar sesión para acceder a esta sección');
        }
        
        // Si el usuario está autenticado pero no es administrador general
        if (!$request->user()->isAdmin()) {
            // Si es administrador de organización, mostrar mensaje específico
            if ($request->user()->isTenantAdmin()) {
                return redirect()->route('tenant.power-bi.index', ['tenant' => $request->user()->tenant->slug])
                    ->with('error', 'Como administrador de organización, solo puedes acceder al panel de tu organización');
            } 
            
            // Usuario normal
            return redirect()->route('tenant.power-bi.index', ['tenant' => $request->user()->tenant->slug])
                ->with('error', 'No tienes permisos para acceder al panel de administrador');
        }
        
        return $next($request);
    }
}
