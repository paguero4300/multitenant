<?php

namespace App\Providers\Filament;

use App\Models\Tenant;
use App\Filament\TenantPages\PowerBiDashboards;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        Log::info('TenantPanelProvider: Entrando al método panel.'); // Log inicio
        
        // Utiliza el dominio directamente desde APP_URL
        $domain = parse_url(config('app.url'), PHP_URL_HOST);
        
        $panel = $panel
            ->id('tenant')
            ->domain($domain) // Usa dashboards.test como dominio
            ->path('') // No necesitamos una ruta base adicional
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->tenant(Tenant::class, slugAttribute: 'slug')
            ->tenantRoutePrefix('cliente') // /cliente/{slug}
            ->tenantMiddleware([
                \App\Http\Middleware\EnsureUserBelongsToTenant::class,
            ], isPersistent: true)
            ->discoverResources(in: app_path('Filament/TenantResources'), for: 'App\\Filament\\TenantResources')
            ->discoverPages(in: app_path('Filament/TenantPages'), for: 'App\\Filament\\TenantPages')
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            // Eliminamos todas las referencias a las páginas PowerBiDashboards 
            // ya que ahora estamos usando un recurso en su lugar
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
            
        Log::info('TenantPanelProvider: Saliendo del método panel.'); // Log fin
        return $panel;
    }
}