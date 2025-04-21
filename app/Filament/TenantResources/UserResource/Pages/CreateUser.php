<?php

namespace App\Filament\TenantResources\UserResource\Pages;

use App\Filament\TenantResources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    protected function beforeCreate(): void
    {
        // Asegurarnos de que el usuario pertenezca al tenant actual
        $tenant = Filament::getTenant();
        
        // Registrar para debugging
        $user = Auth::user();
        Log::debug('TenantUserResource::CreateUser - beforeCreate', [
            'creating_user_id' => $user ? $user->id : null,
            'is_tenant_admin' => $user && $user->is_tenant_admin ? 'true' : 'false',
            'tenant_id' => $tenant ? $tenant->id : null,
            'form_data' => $this->data,
        ]);
        
        // Garantizar que el tenant_id sea el correcto
        $this->data['tenant_id'] = $tenant->id;
        
        // Asegurarse de que nunca se cree un admin global desde este panel
        $this->data['is_admin'] = false;
    }
    
    protected function afterCreate(): void
    {
        // Registro adicional después de la creación
        $tenant = Filament::getTenant();
        $user = Auth::user();
        
        Log::info('TenantUserResource::CreateUser - Usuario creado exitosamente', [
            'created_by' => $user ? $user->id : null,
            'tenant_id' => $tenant ? $tenant->id : null,
            'new_user_id' => $this->record->id,
            'new_user_email' => $this->record->email,
        ]);
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
