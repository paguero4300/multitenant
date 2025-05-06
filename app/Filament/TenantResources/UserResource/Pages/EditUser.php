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
        // Obtener el tenant actual y el usuario autenticado
        $tenant = Filament::getTenant();
        $user = Auth::user();

        // Registrar para debugging
        Log::debug('TenantUserResource::EditUser - beforeSave', [
            'editing_user_id' => $user ? $user->id : null,
            'is_tenant_admin' => $user && $user->is_tenant_admin ? 'true' : 'false',
            'tenant_id' => $tenant ? $tenant->id : null,
            'edited_user_id' => $this->record->id,
            'form_data' => $this->data,
        ]);

        // Verificar que el tenant seleccionado sea uno al que el usuario tiene acceso
        if ($user && isset($this->data['tenant_id'])) {
            // Obtener los tenants a los que el usuario tiene acceso
            $accessibleTenants = $user->additionalTenants()->pluck('tenants.id')->toArray();
            $accessibleTenants[] = $user->tenant_id;

            // Si el tenant seleccionado no está en la lista de accesibles, usar el tenant original
            if (!in_array($this->data['tenant_id'], $accessibleTenants)) {
                $this->data['tenant_id'] = $this->record->tenant_id;
                Log::warning('TenantUserResource::EditUser - Tenant no autorizado, manteniendo tenant original', [
                    'user_id' => $user->id,
                    'selected_tenant' => $this->data['tenant_id'],
                    'accessible_tenants' => $accessibleTenants,
                    'original_tenant' => $this->record->tenant_id,
                ]);
            }
        }

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
