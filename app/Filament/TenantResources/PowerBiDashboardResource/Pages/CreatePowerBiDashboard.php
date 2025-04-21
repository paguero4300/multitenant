<?php

namespace App\Filament\TenantResources\PowerBiDashboardResource\Pages;

use App\Filament\TenantResources\PowerBiDashboardResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreatePowerBiDashboard extends CreateRecord
{
    protected static string $resource = PowerBiDashboardResource::class;
    
    protected function beforeCreate(): void
    {
        // Asegurarnos de que el dashboard se asocie al tenant actual
        $tenant = Filament::getTenant();
        
        // Registrar para debugging
        $user = Auth::user();
        Log::debug('TenantPowerBiDashboardResource::CreatePowerBiDashboard - beforeCreate', [
            'creating_user_id' => $user ? $user->id : null,
            'is_tenant_admin' => $user && $user->is_tenant_admin ? 'true' : 'false',
            'tenant_id' => $tenant ? $tenant->id : null,
        ]);
    }
    
    protected function afterCreate(): void
    {
        // Obtener el tenant actual
        $tenant = Filament::getTenant();
        
        if ($tenant) {
            // Verificar si la relación ya existe para evitar errores de clave duplicada
            $relationExists = $this->record->tenants()
                ->where('tenants.id', $tenant->id)
                ->exists();
            
            // Solo asociar si la relación no existe ya
            if (!$relationExists) {
                $this->record->tenants()->attach($tenant->id);
                Log::debug('TenantPowerBiDashboardResource::CreatePowerBiDashboard - Asociación creada', [
                    'dashboard_id' => $this->record->id,
                    'tenant_id' => $tenant->id,
                ]);
            } else {
                Log::debug('TenantPowerBiDashboardResource::CreatePowerBiDashboard - Asociación ya existente', [
                    'dashboard_id' => $this->record->id,
                    'tenant_id' => $tenant->id,
                ]);
            }
            
            // Registro adicional después de la creación
            $user = Auth::user();
            Log::info('TenantPowerBiDashboardResource::CreatePowerBiDashboard - Dashboard creado exitosamente', [
                'created_by' => $user ? $user->id : null,
                'tenant_id' => $tenant->id,
                'dashboard_id' => $this->record->id,
                'dashboard_title' => $this->record->title,
            ]);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
