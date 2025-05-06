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
        // Obtener el tenant actual y el usuario autenticado
        $tenant = Filament::getTenant();
        $user = Auth::user();

        // Registrar para debugging
        Log::debug('TenantUserResource::CreateUser - beforeCreate', [
            'creating_user_id' => $user ? $user->id : null,
            'is_tenant_admin' => $user && $user->is_tenant_admin ? 'true' : 'false',
            'tenant_id' => $tenant ? $tenant->id : null,
            'form_data' => $this->data,
        ]);

        // Verificar que el tenant seleccionado sea uno al que el usuario tiene acceso
        if ($user && isset($this->data['tenant_id'])) {
            // Obtener los tenants a los que el usuario tiene acceso
            $accessibleTenants = $user->additionalTenants()->pluck('tenants.id')->toArray();
            $accessibleTenants[] = $user->tenant_id;

            // Si el tenant seleccionado no está en la lista de accesibles, usar el tenant principal
            if (!in_array($this->data['tenant_id'], $accessibleTenants)) {
                $this->data['tenant_id'] = $user->tenant_id;
                Log::warning('TenantUserResource::CreateUser - Tenant no autorizado, usando tenant principal', [
                    'user_id' => $user->id,
                    'selected_tenant' => $this->data['tenant_id'],
                    'accessible_tenants' => $accessibleTenants,
                    'assigned_tenant' => $user->tenant_id,
                ]);
            }
        } elseif ($tenant) {
            // Si no se seleccionó un tenant, usar el tenant actual
            $this->data['tenant_id'] = $tenant->id;
        }

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
