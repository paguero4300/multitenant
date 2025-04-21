<?php

namespace App\Filament\Resources\PowerBiDashboardResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TenantsRelationManager extends RelationManager
{
    protected static string $relationship = 'tenants';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Tenants con acceso';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    // Método para verificar si el usuario actual es un administrador general
    private function isAdmin(): bool
    {
        $user = Auth::user();
        return $user && $user->is_admin;
    }
    
    // Método para verificar si el usuario actual es un administrador de tenant
    private function isTenantAdmin(): bool
    {
        $user = Auth::user();
        return $user && $user->is_tenant_admin && !$user->is_admin;
    }
    
    // Método para filtrar los tenants que el usuario puede ver
    private function filterTenantsByPermission(Builder $query): Builder
    {
        // Si es tenant_admin, solo puede ver su propio tenant
        if ($this->isTenantAdmin()) {
            $tenantId = Auth::user()->tenant_id;
            // Especificar claramente la tabla para evitar ambigüedad en la columna 'id'
            $query->where('tenants.id', $tenantId);
        }
        
        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $this->filterTenantsByPermission($query))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo')
                    ->placeholder('Todos los tenants')
                    ->trueLabel('Tenants activos')
                    ->falseLabel('Tenants inactivos'),
            ])
            ->headerActions([
                // Solo admin general puede agregar tenants
                Tables\Actions\AttachAction::make()
                    ->label('Asociar Tenant')
                    ->visible(fn () => $this->isAdmin())
                    ->preloadRecordSelect()
                    // Filtramos los tenants que se pueden asociar según los permisos del usuario
                    ->recordSelectOptionsQuery(fn (Builder $query) => $this->filterTenantsByPermission($query)),
            ])
            ->actions([
                // Solo admin general puede desasociar tenants
                Tables\Actions\DetachAction::make()
                    ->label('Desasociar')
                    ->visible(fn () => $this->isAdmin()),
            ])
            ->bulkActions([
                // Solo admin general puede realizar acciones en masa
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Desasociar seleccionados'),
                ])
                ->visible(fn () => $this->isAdmin()),
            ]);
    }
}
