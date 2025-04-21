<?php

namespace App\Filament\TenantResources\PowerBiDashboardResource\Pages;

use App\Filament\TenantResources\PowerBiDashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class EditPowerBiDashboard extends EditRecord
{
    protected static string $resource = PowerBiDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash')
                ->color('danger'),
                
            Actions\ViewAction::make()
                ->label('Vista previa')
                ->icon('heroicon-o-presentation-chart-bar')
                ->color('info')
                ->modalHeading(fn (): string => "Dashboard: {$this->record->title}")
                ->modalDescription(fn (): string => $this->record->description ?? 'Dashboard de Power BI')
                ->modalIcon('heroicon-o-presentation-chart-bar')
                ->modalWidth('7xl')
                ->modalAlignment('center')
                // Cargar contenido desde una vista Blade dedicada
                ->modalContent(fn (): \Illuminate\Contracts\View\View => view(
                    'filament.resources.power-bi-dashboard.modal-content',
                    ['record' => $this->record]
                ))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->extraAttributes(['class' => '!max-w-full !h-[90vh]']),
        ];
    }
    
    protected function beforeSave(): void
    {
        // Registrar para debugging
        $tenant = Filament::getTenant();
        $user = Auth::user();
        
        Log::debug('TenantPowerBiDashboardResource::EditPowerBiDashboard - beforeSave', [
            'editing_user_id' => $user ? $user->id : null,
            'is_tenant_admin' => $user && $user->is_tenant_admin ? 'true' : 'false',
            'tenant_id' => $tenant ? $tenant->id : null,
            'dashboard_id' => $this->record->id,
        ]);
    }
    
    protected function afterSave(): void
    {
        // Asegurar que el dashboard sigue asociado al tenant actual
        $tenant = Filament::getTenant();
        
        if ($tenant && !$this->record->tenants()->where('tenant_id', $tenant->id)->exists()) {
            $this->record->tenants()->attach($tenant->id);
        }
        
        // Notificar cambios
        Notification::make()
            ->title('Dashboard actualizado')
            ->success()
            ->send();
            
        // Registro para auditoría
        $user = Auth::user();
        Log::info('TenantPowerBiDashboardResource::EditPowerBiDashboard - Dashboard editado exitosamente', [
            'edited_by' => $user ? $user->id : null,
            'tenant_id' => $tenant ? $tenant->id : null,
            'dashboard_id' => $this->record->id,
        ]);
    }
}
