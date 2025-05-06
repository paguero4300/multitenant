<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Tenants';
    protected static ?string $modelLabel = 'Tenant';
    protected static ?string $pluralModelLabel = 'Tenants';
    protected static bool $isScopedToTenant = false; // No aplicar tenant a este recurso

    // Método para determinar si el recurso debe ser visible en la navegación
    public static function shouldRegisterNavigation(): bool
    {
        // Solo administradores generales pueden ver este recurso
        return Auth::user() && Auth::user()->is_admin;
    }

    // Método para determinar si el usuario actual puede acceder al recurso
    public static function canAccess(): bool
    {
        // Solo administradores generales pueden acceder a este recurso
        return Auth::user() && Auth::user()->is_admin;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(12) // Usamos 12 columnas para la proporción áurea
            ->schema([
                // Sección de información principal (Principio de proximidad)
                Forms\Components\Section::make('Información del tenant')
                    ->description('Datos principales del tenant')
                    ->icon('heroicon-o-building-office')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Nombre del tenant')
                            ->helperText('Nombre completo del tenant')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-building-office')
                            ->live(onBlur: true)
                            ->columnSpan(7) // Proporción áurea
                            ->afterStateUpdated(fn (string $state, callable $set) =>
                                $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('Identificador URL')
                            ->placeholder('identificador-url')
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Identificador único para la URL. Se genera automáticamente desde el nombre.')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->columnSpan(5) // Proporción áurea
                            ->maxLength(255),
                    ])
                    ->columnSpan(8), // Proporción áurea para secciones

                // Sección de configuración (Principio de similitud)
                Forms\Components\Section::make('Configuración')
                    ->description('Ajustes del tenant en la plataforma')
                    ->icon('heroicon-o-cog')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->helperText('Determina si este tenant está activo en el sistema')
                            ->default(true),
                    ])
                    ->columnSpan(4), // Proporción áurea para secciones
            ]);
    }


    public static function table(Table $table): Table
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST);

        return $table
            ->striped() // Mejora la legibilidad (Principio de figura-fondo)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Identificador')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                // Columna mejorada para acceder directamente al panel del tenant
                Tables\Columns\TextColumn::make('tenant_url')
                    ->label('Panel del tenant')
                    ->getStateUsing(function ($record) use ($domain) {
                        return "Acceder al panel"; // Texto del enlace
                    })
                    ->description(function ($record) use ($domain) {
                        return "cliente/{$record->slug}"; // Mantenemos la URL original
                    })
                    ->copyable() // Permite copiar la URL al portapapeles
                    ->copyMessage('URL copiada al portapapeles')
                    ->color('primary')
                    ->url(function ($record) {
                        // URL absoluta que incluye protocolo y dominio
                        return url("/cliente/{$record->slug}");
                    })
                    ->openUrlInNewTab(), // Abre la URL en una nueva pestaña
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtros
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Ver detalles')
                        ->icon('heroicon-o-eye'),
                    Tables\Actions\EditAction::make()->label('Editar')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\Action::make('access')
                        ->label('Acceder')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('success')
                        ->url(fn (Tenant $record) => url("/cliente/{$record->slug}"))
                        ->openUrlInNewTab(),
                    Tables\Actions\DeleteAction::make()->label('Eliminar')
                        ->icon('heroicon-o-trash'),
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
            ->emptyStateIcon('heroicon-o-building-office')
            ->emptyStateHeading('No hay tenants')
            ->emptyStateDescription('Crea tu primer tenant haciendo clic en el botón "Crear tenant".');
    }




    public static function getRelations(): array
    {
        return [
            // Relaciones
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    // Textos personalizados para el recurso
    public static function getNavigationGroup(): ?string
    {
        return 'Administración';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
