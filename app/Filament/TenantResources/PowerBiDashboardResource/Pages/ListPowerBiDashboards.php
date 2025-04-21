<?php

namespace App\Filament\TenantResources\PowerBiDashboardResource\Pages;

use App\Filament\TenantResources\PowerBiDashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class ListPowerBiDashboards extends ListRecords
{
    protected static string $resource = PowerBiDashboardResource::class;

    // Permitimos a los administradores de tenant crear dashboards desde su propio panel
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Dashboard')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
        ];
    }
    
    // Este método asegura que los dashboards se filtren por el tenant actual
    // Aunque Filament ya hace esto automáticamente basado en $tenantRelationshipName
    // Lo incluimos para tener mayor control y claridad del código
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Obtenemos el tenant actual
        $tenant = Filament::getTenant();
        
        if ($tenant) {
            // Filtramos para mostrar solo los dashboards asociados al tenant actual
            // Esto se hace usando la relación muchos a muchos definida en el modelo PowerBiDashboard
            $query->whereHas('tenants', function (Builder $innerQuery) use ($tenant) {
                $innerQuery->where('tenants.id', $tenant->id);
            });
            
            // Podríamos añadir aquí filtros adicionales basados en roles o permisos
            // Por ejemplo, si ciertos usuarios solo pueden ver dashboards con ciertos atributos
        }
        
        return $query;
    }
}
