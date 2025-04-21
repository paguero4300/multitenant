<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    // Filtramos el listado según el rol del usuario autenticado
    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        $user = Auth::user();
        
        // Administradores Generales ven todos los usuarios
        if ($user->is_admin) {
            return $query;
        }
        
        // Administradores de Tenant solo ven usuarios de su tenant
        if ($user->is_tenant_admin) {
            return $query->where('tenant_id', $user->tenant_id);
        }
        
        // Si por alguna razón un usuario regular accede, solo ve su propio usuario
        return $query->where('id', $user->id);
    }
}
