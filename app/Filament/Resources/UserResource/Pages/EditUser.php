<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    // Método que se ejecuta antes de cargar la página
    protected function beforeFill(): void
    {
        // Verificar que el usuario tenga permiso para editar este registro
        $user = Auth::user();
        $record = $this->getRecord();

        // Si es admin general puede editar cualquier usuario
        if ($user->is_admin) {
            return;
        }

        // Si es admin de tenant solo puede editar usuarios de su tenant que no sean admin generales
        if ($user->is_tenant_admin) {
            if ($record->tenant_id !== $user->tenant_id || $record->is_admin) {
                abort(403, 'No tienes permiso para editar este usuario');
            }
            return;
        }

        // Si es usuario regular, solo puede editar su propio perfil
        if ($record->id !== $user->id) {
            abort(403, 'No tienes permiso para editar este usuario');
        }
    }

    // Método para validar y ajustar los datos antes de guardar
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();
        $record = $this->getRecord();

        // Un administrador de tenant no puede cambiar el tenant de un usuario
        if (!$user->is_admin && $user->is_tenant_admin) {
            $data['tenant_id'] = $record->tenant_id;

            // No puede convertir usuarios en administradores generales
            $data['is_admin'] = false;
        }

        // Un usuario regular solo puede editar datos básicos de su perfil
        if (!$user->is_admin && !$user->is_tenant_admin) {
            $data['is_admin'] = $record->is_admin;
            $data['is_tenant_admin'] = $record->is_tenant_admin;
            $data['tenant_id'] = $record->tenant_id;
        }

        return $data;
    }

    // Método para guardar los tenants adicionales después de guardar el usuario
    protected function afterSave(): void
    {
        $user = Auth::user();

        // Administradores globales pueden asignar cualquier tenant adicional
        if ($user->is_admin && isset($this->data['additionalTenants'])) {
            $this->record->additionalTenants()->sync($this->data['additionalTenants']);
        }
        // Administradores de tenant solo pueden asignar tenants a los que ellos tienen acceso
        elseif ($user->is_tenant_admin && isset($this->data['additionalTenants'])) {
            // Obtener los tenants a los que el admin de tenant tiene acceso
            $allowedTenants = $user->getTenants(Filament::getPanel('admin'))->pluck('id')->toArray();

            // Filtrar los tenants seleccionados para asegurarse de que solo se asignen los permitidos
            $filteredTenants = array_intersect($this->data['additionalTenants'], $allowedTenants);

            // Sincronizar los tenants filtrados
            $this->record->additionalTenants()->sync($filteredTenants);

            Log::info('Admin de tenant asignando tenants adicionales', [
                'user_id' => $user->id,
                'edited_user_id' => $this->record->id,
                'allowed_tenants' => $allowedTenants,
                'requested_tenants' => $this->data['additionalTenants'],
                'assigned_tenants' => $filteredTenants
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        $record = $this->getRecord();

        // No permitir eliminar el propio usuario o si un admin de tenant intenta eliminar a un admin general
        $deleteAction = Actions\DeleteAction::make()
            ->hidden(function () use ($user, $record) {
                return $record->id === $user->id ||
                       (!$user->is_admin && $record->is_admin);
            });

        return [
            $deleteAction,
        ];
    }
}
