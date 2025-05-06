<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 1;

    // Aseguramos que el recurso sea visible para administradores en el panel correcto
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        // Solo debe ser visible para administradores
        return $user && ($user->is_admin || $user->is_tenant_admin);
    }

    public static function form(Form $form): Form
    {
        // Obtenemos el usuario autenticado
        $user = Auth::user();

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

                // Sección de asignación de roles y tenant - visible según el rol actual del usuario
                Forms\Components\Section::make('Asignación de tenants y roles')
                    ->description('Permisos y acceso al sistema')
                    ->icon('heroicon-o-key')
                    ->collapsible()
                    ->persistCollapsed(false)
                    ->columns(12)
                    ->schema([
                        // Selector de tenant - condicionado según el rol del usuario
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant Principal')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(7) // Proporción áurea aproximada
                            // Administradores generales ven todos los tenants, administradores de tenant solo ven el suyo
                            ->visible(function () {
                                $user = Auth::user();
                                return $user->is_admin || $user->is_tenant_admin;
                            })
                            // Administradores de tenant no pueden cambiar el tenant, pero admins generales sí pueden
                            ->disabled(function () {
                                $user = Auth::user();
                                // Si es admin general, siempre puede cambiar el tenant
                                if ($user->is_admin) {
                                    return false;
                                }
                                // Solo deshabilitar si es tenant_admin pero no admin general
                                return $user->is_tenant_admin;
                            })
                            // Si es admin de tenant, pre-establece su propio tenant_id
                            ->default(function () {
                                $user = Auth::user();
                                return $user->is_tenant_admin ? $user->tenant_id : null;
                            }),

                        // Rol de administrador general - solo visible para administradores generales
                        Forms\Components\Toggle::make('is_admin')
                            ->label('Administrador General')
                            ->helperText('Tiene acceso completo a todas las organizaciones y funcionalidades')
                            ->columnSpan(6)
                            ->inline(false)
                            ->visible(function () {
                                return Auth::user()->is_admin;
                            })
                            ->disabled(function ($record) {
                                return $record && $record->id === Auth::id();
                            }) // Un admin no puede quitarse su propio rol
                            // Si se activa el admin general, desactiva el admin de tenant
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $set('is_tenant_admin', false);
                                }
                            }),

                        // Rol de administrador de tenant - visible para admins generales siempre, y para admins de tenant solo si no es admin general
                        Forms\Components\Toggle::make('is_tenant_admin')
                            ->label('Administrador de Organización')
                            ->helperText('Puede administrar únicamente su propia organización')
                            ->columnSpan(6)
                            ->inline(false)
                            ->visible(function () {
                                $user = Auth::user();
                                return $user->is_admin || $user->is_tenant_admin;
                            })
                            // Deshabilitar si:
                            // 1. El usuario a editar es admin general
                            // 2. En el formulario actual está marcado como admin general
                            // 3. Un admin de tenant intenta quitarse su propio rol
                            ->disabled(function ($record, $get) {
                                $user = Auth::user();
                                // Caso 1 y 3
                                $disabledByRecord = ($record && $record->is_admin) ||
                                                  (!$user->is_admin && $record && $record->id === $user->id);
                                // Caso 2 - Si is_admin está marcado en el formulario
                                $disabledByForm = false;
                                if (isset($get) && is_callable($get)) {
                                    try {
                                        $isAdmin = $get('is_admin');
                                        $disabledByForm = $isAdmin;
                                    } catch (\Exception $e) {
                                        // Si hay error al obtener is_admin, no deshabilitar por este motivo
                                    }
                                }

                                return $disabledByRecord || $disabledByForm;
                            })
                            // Siempre guardamos el valor de is_tenant_admin
                            // Los permisos ya están controlados por visible() y disabled()
                            ->dehydrated(true),

                        // Selector de tenants adicionales - solo visible para administradores globales
                        Forms\Components\Select::make('additionalTenants')
                            ->label('Tenants adicionales')
                            ->helperText('Tenants adicionales a los que el usuario puede acceder')
                            ->relationship('additionalTenants', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpan(12)
                            // Visible para admins globales y admins de tenant
                            ->visible(fn () => Auth::user()->is_admin || Auth::user()->is_tenant_admin),
                    ])
                    ->columnSpan(12), // Ocupa todo el ancho para destacar su importancia
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped() // Mejora la legibilidad (Principio de figura-fondo)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Correo copiado')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant Principal')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                // Columna personalizada para mostrar el rol del usuario
                Tables\Columns\TextColumn::make('role')
                    ->label('Rol')
                    ->state(function (User $record): string {
                        if ($record->is_admin) return 'Administrador General';
                        if ($record->is_tenant_admin) return 'Administrador de Organización';
                        return 'Usuario Regular';
                    })
                    ->badge()

                    ->color(function (User $record): string {
                        if ($record->is_admin) return 'danger';
                        if ($record->is_tenant_admin) return 'warning';
                        return 'success';
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant')
                    ->relationship('tenant', 'name')
                    ->label('Tenant Principal')
                    ->preload()
                    ->searchable()
                    // Solo admins generales pueden filtrar por tenant
                    ->visible(function () {
                        return Auth::user()->is_admin;
                    }),

                Tables\Filters\SelectFilter::make('role')
                    ->label('Rol')
                    ->options([
                        'admin' => 'Administrador General',
                        'tenant_admin' => 'Administrador de Organización',
                        'user' => 'Usuario Regular',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value']) {
                            'admin' => $query->where('is_admin', true),
                            'tenant_admin' => $query->where('is_admin', false)->where('is_tenant_admin', true),
                            'user' => $query->where('is_admin', false)->where('is_tenant_admin', false),
                            default => $query,
                        };
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
        return $user && ($user->is_admin || $user->is_tenant_admin);
    }

    // Método que modifica la consulta para que los tenant_admin solo vean usuarios de su tenant
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Si es un tenant_admin, solo puede ver usuarios de su propio tenant
        $user = Auth::user();
        if ($user && $user->is_tenant_admin && !$user->is_admin) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query;
    }
}
