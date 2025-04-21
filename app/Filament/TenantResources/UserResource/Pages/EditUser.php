<?php

namespace App\Filament\TenantResources\UserResource\Pages;

use App\Filament\TenantResources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash')
                ->hidden(fn () => $this->record->id === Auth::id()),
                
            Actions\ViewAction::make()
                ->label('Ver')
                ->icon('heroicon-o-eye'),
        ];
    }
    
    protected function beforeSave(): void
    {
        // Asegurarnos de que el usuario pertenezca al tenant actual
        $tenant = Filament::getTenant();
        
        // Registrar para debugging
        $user = Auth::user();
        Log::debug('TenantUserResource::EditUser - beforeSave', [
            'editing_user_id' => $user ? $user->id : null,
            'is_tenant_admin' => $user && $user->is_tenant_admin ? 'true' : 'false',
            'tenant_id' => $tenant ? $tenant->id : null,
            'edited_user_id' => $this->record->id,
        ]);
        
        // Garantizar que el tenant_id sea el correcto y nunca cambie
        $this->data['tenant_id'] = $tenant->id;
        
        // Asegurarse de que nunca se convierta en admin global desde este panel
        $this->data['is_admin'] = false;
    }
    
    protected function afterSave(): void
    {
        $tenant = Filament::getTenant();
        $user = Auth::user();
        
        // Notificar cambios
        Notification::make()
            ->title('Usuario actualizado')
            ->success()
            ->send();
            
        Log::info('TenantUserResource::EditUser - Usuario editado exitosamente', [
            'edited_by' => $user ? $user->id : null,
            'tenant_id' => $tenant ? $tenant->id : null,
            'edited_user_id' => $this->record->id,
        ]);
    }
}
