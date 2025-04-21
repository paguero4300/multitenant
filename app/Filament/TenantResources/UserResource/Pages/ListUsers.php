<?php

namespace App\Filament\TenantResources\UserResource\Pages;

use App\Filament\TenantResources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        // Registrar para debugging
        $user = Auth::user();
        $tenant = Filament::getTenant();
        
        Log::debug('TenantUserResource::ListUsers - Header Actions', [
            'user_id' => $user ? $user->id : null,
            'is_tenant_admin' => $user && $user->is_tenant_admin ? 'true' : 'false',
            'tenant_id' => $tenant ? $tenant->id : null,
        ]);
        
        return [
            Actions\CreateAction::make()
                ->label('Crear usuario')
                ->icon('heroicon-o-user-plus'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // Puedes agregar widgets de resumen aquí si lo deseas
        ];
    }
}
