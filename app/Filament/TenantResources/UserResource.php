<?php

namespace App\Filament\TenantResources;

use App\Filament\TenantResources\UserResource\Pages;
use App\Models\User;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Facades\Filament;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Define la relación con el tenant (esto es clave para la multi-tenancy)
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 1;

    // Aseguramos que el recurso sea visible solo para administradores de tenant en el panel de tenant
    public static function shouldRegisterNavigation(): bool
    {
        // Registramos para debugging
        $user = Auth::user();
        $tenant = Filament::getTenant();

        Log::debug('TenantUserResource::shouldRegisterNavigation', [
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'is_admin' => $user && $user->is_admin ? 'true' : 'false',
            'is_tenant_admin' => $user && $user->is_tenant_admin ? 'true' : 'false',
            'tenant_id' => $tenant ? $tenant->id : null,
            'user_tenant_id' => $user ? $user->tenant_id : null,
        ]);

        // Solo debe ser visible para administradores de tenant
        if (!$user || !$tenant) {
            return false;
        }

        // Verificar si el usuario es admin de tenant
        if (!$user->is_tenant_admin) {
            return false;
        }

        // Verificar si el usuario puede acceder a este tenant
        return $user->canAccessTenant($tenant);
    }

    public static function form(Form $form): Form
    {
        // Obtenemos el usuario autenticado y el tenant actual
        $user = Auth::user();
        $tenant = Filament::getTenant();

        return $form
            ->columns(12) // Usamos 12 columnas para aplicar mejor la proporción áurea (aproximadamente 7:5:3)
            ->schema([
                // Sección de información personal (Principio de proximidad - agrupando datos relacionados)
                Forms\Components\Section::make('Información personal')
                    ->description('Datos principales del usuario')
                    ->icon('heroicon-o-user')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Nombre completo del usuario')
                            ->required()
                            ->columnSpan(7) // Proporción áurea aproximada
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-user'),

                        Forms\Components\TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->prefixIcon('heroicon-o-envelope')
                            ->placeholder('ejemplo@correo.com')
                            ->required()
                            ->columnSpan(12)
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        // Selector de tenant - permite seleccionar entre los tenants accesibles
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Tenant al que pertenecerá el usuario')
                            ->default(fn() => $tenant ? $tenant->id : null)
                            ->options(function () {
                                $user = Auth::user();
                                if (!$user) return [];

                                // Obtener todos los tenants a los que el usuario tiene acceso
                                return $user->additionalTenants()
                                    ->pluck('name', 'id')
                                    ->union([$user->tenant_id => $user->tenant->name])
                                    ->toArray();
                            }),
                    ])
                    ->columnSpan(7), // Espacio principal (Proporción áurea)

                // Sección de seguridad (Principio de similitud - elementos que comparten un propósito)
                Forms\Components\Section::make('Seguridad')
                    ->description('Credenciales de acceso')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255)
                            ->label(fn (string $operation): string =>
                                $operation === 'create' ? 'Contraseña' : 'Contraseña (dejar en blanco para mantener la actual)')
                            ->autocomplete(false)
                            ->revealable()
                            ->helperText('Mínimo 8 caracteres recomendados'),
                    ])
                    ->columnSpan(5), // Espacio complementario (Proporción áurea)

                // Sección de permisos (solo para edición)
                Forms\Components\Section::make('Permisos de usuario')
                    ->description('Nivel de acceso dentro de la organización')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Forms\Components\Toggle::make('is_tenant_admin')
                            ->label('Administrador de organización')
                            ->helperText('Tiene permisos para administrar todos los recursos de esta organización.')
                            ->onIcon('heroicon-o-shield-check')
                            ->offIcon('heroicon-o-shield-exclamation')
                            ->default(false),
                    ])
                    ->columnSpan(12), // Ancho completo para esta sección
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record): string => $record->email)
                    ->icon('heroicon-o-user')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->copyable() // Permite copiar el correo con un clic
                    ->copyMessage('Correo copiado')
                    ->copyMessageDuration(1500),

                Tables\Columns\IconColumn::make('is_tenant_admin')
                    ->label('Admin. Organización')
                    ->alignCenter()
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->tooltip(fn (User $record): string =>
                        $record->is_tenant_admin
                            ? 'Administrador de Organización'
                            : 'Usuario Regular'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                // Filtro de tenant para mostrar usuarios de un tenant específico
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->relationship('tenant', 'name')
                    ->preload()
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['value']) && $data['value']) {
                            return $query->where('tenant_id', $data['value']);
                        }
                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('role')
                    ->label('Rol')
                    ->options([
                        'tenant_admin' => 'Administrador de Organización',
                        'user' => 'Usuario Regular',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value']) {
                            'tenant_admin' => $query->where('is_tenant_admin', true),
                            'user' => $query->where('is_tenant_admin', false),
                            default => $query,
                        };
                    }),

                // Filtro para mostrar solo usuarios del tenant actual
                Tables\Filters\Filter::make('current_tenant_only')
                    ->label('Solo tenant actual')
                    ->toggle()
                    ->query(function (Builder $query) {
                        $tenant = Filament::getTenant();
                        if ($tenant) {
                            return $query->where('tenant_id', $tenant->id);
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Ver detalles')
                        ->icon('heroicon-o-eye'),
                    Tables\Actions\EditAction::make()->label('Editar')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\DeleteAction::make()->label('Eliminar')
                        ->icon('heroicon-o-trash')
                        ->hidden(function (User $record) {
                            return $record->id === Auth::id();
                        }),
                ])
                ->tooltip('Acciones')
                ->iconButton()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->icon('heroicon-o-trash')
                        ->deselectRecordsAfterCompletion(),
                ])
                ->icon('heroicon-o-check')
                ->label('Acciones en grupo'),
            ])
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('No hay usuarios')
            ->emptyStateDescription('Crea tu primer usuario haciendo clic en el botón "Crear usuario".');
    }

    public static function getRelations(): array
    {
        return [
            // No necesitamos relaciones por ahora
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Usuario';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Usuarios';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    // Método para determinar si el usuario actual puede acceder al recurso
    public static function canAccess(): bool
    {
        $user = Auth::user();
        $tenant = Filament::getTenant();

        if (!$user || !$tenant) {
            return false;
        }

        // Verificar si el usuario es admin de tenant
        if (!$user->is_tenant_admin) {
            return false;
        }

        // Verificar si el usuario puede acceder a este tenant
        return $user->canAccessTenant($tenant);
    }

    // Método que modifica la consulta para mostrar usuarios de todos los tenants accesibles
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $tenant = Filament::getTenant();
        $user = Auth::user();

        // Logging para debug
        Log::debug('TenantUserResource::getEloquentQuery', [
            'user_id' => $user ? $user->id : null,
            'tenant_id' => $tenant ? $tenant->id : null,
        ]);

        if (!$user || !$tenant) {
            return $query->whereRaw('1 = 0'); // No mostrar nada si no hay usuario o tenant
        }

        // Nunca mostrar administradores globales
        $query->where('is_admin', false);

        // Si es admin de tenant, mostrar usuarios de todos los tenants a los que tiene acceso
        if ($user->is_tenant_admin) {
            // Obtener los IDs de todos los tenants a los que tiene acceso
            $accessibleTenants = $user->getTenants(Filament::getPanel('tenant'))->pluck('id')->toArray();

            Log::debug('TenantUserResource::getEloquentQuery - Tenants accesibles', [
                'user_id' => $user->id,
                'accessible_tenants' => $accessibleTenants,
                'current_tenant' => $tenant->id,
            ]);

            // Mostrar usuarios de todos los tenants accesibles
            $query->whereIn('tenant_id', $accessibleTenants);

            // Añadir un filtro para mostrar solo usuarios del tenant actual si se desea
            if (request()->has('current_tenant_only') && request()->input('current_tenant_only')) {
                $query->where('tenant_id', $tenant->id);
            }
        } else {
            // Para cualquier otro caso, solo mostrar usuarios del tenant actual
            $query->where('tenant_id', $tenant->id);
        }

        return $query;
    }
}
