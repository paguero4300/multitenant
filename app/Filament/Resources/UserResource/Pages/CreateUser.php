<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Models\Tenant;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    
    // Método para configurar valores por defecto al crear un usuario
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        
        // Registrar información de depuración
        Log::info('CreateUser - Datos antes de modificar:', [
            'data' => $data,
            'auth_user' => [
                'id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'tenant_type' => gettype($user->tenant_id),
                'is_admin' => $user->is_admin ? 'true' : 'false',
                'is_tenant_admin' => $user->is_tenant_admin ? 'true' : 'false',
            ]
        ]);
        
        // Si el usuario es un administrador de tenant, forzar tenant_id a su propio tenant
        if ($user && !$user->is_admin && $user->is_tenant_admin) {
            // Aseguramos que tenant_id sea un entero válido
            if (!empty($user->tenant_id)) {
                $data['tenant_id'] = (int)$user->tenant_id;
            }
            
            // Asegurar que no pueda crear administradores generales
            $data['is_admin'] = false;
        }
        
        // Asegurar que todos los valores boolean sean realmente booleanos
        if (isset($data['is_admin'])) {
            $data['is_admin'] = (bool)$data['is_admin'];
        }
        
        if (isset($data['is_tenant_admin'])) {
            $data['is_tenant_admin'] = (bool)$data['is_tenant_admin'];
        }
        
        // Registrar los datos finales
        Log::info('CreateUser - Datos finales:', ['data' => $data]);
        
        return $data;
    }
    
    // Método para validar que el usuario tenga permisos para crear este registro
    protected function handleRecordCreation(array $data): Model
    {
        $user = Auth::user();
        
        // Verificar que un admin de tenant no pueda crear usuarios fuera de su tenant
        if ($user && !$user->is_admin && $user->is_tenant_admin && isset($data['tenant_id'])) {
            // Convertir ambos valores a string para comparación segura
            if ((string)$data['tenant_id'] !== (string)$user->tenant_id) {
                abort(403, sprintf(
                    'No puedes crear usuarios para otros tenants. [Tu tenant: %s, Tenant solicitado: %s]',
                    $user->tenant_id,
                    $data['tenant_id']
                ));
            }
        }
        
        try {
            // Crear el usuario
            $newUser = parent::handleRecordCreation($data);
            
            // Verificar que el tenant_id sea correcto después de crear
            if ($newUser->tenant_id !== null) {
                // Obtener el objeto Tenant para verificar que existe
                $tenant = Tenant::find($newUser->tenant_id);
                
                // Si el tenant no existe o el ID no coincide, podría haber un problema
                if (!$tenant) {
                    Log::error('Usuario creado con tenant_id inválido', [
                        'user_id' => $newUser->id,
                        'tenant_id' => $newUser->tenant_id
                    ]);
                } else {
                    Log::info('Usuario creado correctamente con tenant', [
                        'user_id' => $newUser->id,
                        'tenant_id' => $newUser->tenant_id,
                        'tenant_name' => $tenant->name
                    ]);
                }
            }
            
            return $newUser;
        } catch (\Exception $e) {
            Log::error('Error al crear usuario', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
}
