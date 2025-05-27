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
        // Verificar si el usuario está autenticado
        if (! $request->user()) {
            // Usuario no autenticado
            return redirect()->route('login')->with('error', 'Debes iniciar sesión para acceder a esta sección');
        }

        $user = $request->user();

        // Si el usuario es administrador general, permitir acceso
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Si no es administrador general, verificar si tiene tenant asignado
        if (!$user->tenant) {
            // Usuario sin tenant asignado - redirigir al login con mensaje de error
            return redirect()->route('login')->with('error', 'Tu cuenta no tiene una organización asignada. Contacta al administrador.');
        }

        // Si es administrador de organización, mostrar mensaje específico
        if ($user->isTenantAdmin()) {
            return redirect()->route('tenant.power-bi.index', ['tenant' => $user->tenant->slug])
                ->with('error', 'Como administrador de organización, solo puedes acceder al panel de tu organización');
        }

        // Usuario normal con tenant asignado
        return redirect()->route('tenant.power-bi.index', ['tenant' => $user->tenant->slug])
            ->with('error', 'No tienes permisos para acceder al panel de administrador');
    }
}
