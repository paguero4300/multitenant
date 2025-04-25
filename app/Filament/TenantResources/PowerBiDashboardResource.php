<?php

namespace App\Filament\TenantResources;

use App\Filament\TenantResources\PowerBiDashboardResource\Pages;
use App\Models\PowerBiDashboard;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class PowerBiDashboardResource extends Resource
{
    protected static ?string $model = PowerBiDashboard::class;

    // Define la relación con el tenant (esto es lo clave para la multi-tenancy)
    // Usamos la relación inversa definida en el modelo Tenant
    protected static ?string $tenantRelationshipName = 'powerBiDashboards';

    // Define explícitamente qué relación usar para ownership check
    // Debe ser la relación que va desde PowerBiDashboard hacia los tenants
    protected static ?string $tenantOwnershipRelationshipName = 'tenants';

    // Verifica si el usuario actual puede crear dashboards
    public static function canCreate(): bool
    {
        // Permitir tanto a administradores generales como de organización
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Administradores generales siempre pueden crear
        if ($user->is_admin === 1) {
            return true;
        }

        // Administradores de organización pueden crear dentro de su tenant
        if ($user->is_tenant_admin === 1) {
            return true;
        }

        // Usuarios normales no pueden crear (sin mostrar notificación auto)
        return false;
    }

    // Ajustes de navegación y etiquetas
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Dashboards Power BI';
    protected static ?string $modelLabel = 'Dashboard de Power BI';
    protected static ?string $pluralModelLabel = 'Dashboards de Power BI';
    protected static ?int $navigationSort = 2;

    // Definición del formulario de creación/edición siguiendo principios de UI/UX
    public static function form(Form $form): Form
    {
        return $form
            ->columns(12) // Proporción áurea base para toda la interfaz
            ->schema([
                Tabs::make('Dashboards')
                    ->tabs([
                        Tab::make('Información General')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Datos Principales')
                                    ->description('Información básica del dashboard')
                                    ->icon('heroicon-o-document-text')
                                    ->columns(12) // Proporción áurea interna
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->label('Título')
                                            ->required()
                                            ->minLength(3)
                                            ->maxLength(100)
                                            ->columnSpan(7), // Proporción áurea 7:5

                                        Forms\Components\Select::make('category')
                                            ->label('Categoría')
                                            ->options([
                                                'ventas' => 'Ventas',
                                                'finanzas' => 'Finanzas',
                                                'operaciones' => 'Operaciones',
                                                'marketing' => 'Marketing',
                                                'rrhh' => 'Recursos Humanos',
                                                'clientes' => 'Clientes',
                                                'general' => 'General',
                                                'otros' => 'Otros',
                                            ])
                                            ->required()
                                            ->searchable()
                                            ->columnSpan(5), // Proporción áurea 7:5

                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripción')
                                            ->placeholder('Describe el propósito y contenido de este dashboard')
                                            ->required()
                                            ->columnSpan(12),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Activo')
                                            ->helperText('Los dashboards inactivos no serán visibles para los usuarios')
                                            ->default(true)
                                            ->columnSpan(6),

                                        Forms\Components\Toggle::make('is_public')
                                            ->label('Público dentro del tenant')
                                            ->helperText('Si está activo, todos los usuarios del tenant podrán verlo')
                                            ->default(false)
                                            ->columnSpan(6),
                                    ]),
                            ]),

                        Tab::make('Integración Power BI')
                            ->icon('heroicon-o-link')
                            ->schema([
                                Section::make('Integración con Power BI')
                                    ->description('Configura los parámetros para conectar con Power BI')
                                    ->icon('heroicon-o-rectangle-stack')
                                    ->columns(12) // Proporción áurea interna
                                    ->schema([
                                        Forms\Components\TextInput::make('embed_url')
                                            ->label('URL de Incrustación')
                                            ->helperText('URL completa del informe de Power BI')
                                            ->required()
                                            ->url()
                                            ->columnSpan(12),

                                        Forms\Components\TextInput::make('report_id')
                                            ->label('ID del Informe')
                                            ->helperText('ID único del informe en Power BI')
                                            ->required()
                                            ->columnSpan(7), // Proporción áurea 7:5

                                        Forms\Components\TextInput::make('embed_token')
                                            ->label('Token')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Opcional - Token para acceso a Power BI')
                                            ->columnSpan(5), // Proporción áurea 7:5
                                    ]),
                            ]),
                    ])
                    ->columnSpan(12)
            ]);
    }

    // No necesitamos navigationGroup en el panel de Tenant, ya que el contexto
    // es más específico y hay menos recursos en la navegación
    // protected static ?string $navigationGroup = 'Gestión BI';

    public static function table(Table $table): Table
    {
        // En el contexto del tenant, solo mostraremos los dashboards que pertenecen al tenant actual
        // La relación ya está definida por $tenantRelationshipName arriba

        return $table
            ->columns([
                // Mostrar la imagen de miniatura real o un icono por defecto
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('Vista previa')
                    ->defaultImageUrl(function (PowerBiDashboard $record): string {
                        // Cuando no hay imagen, mostrar un icono basado en la categoría
                        $categoryIcons = [
                            'ventas' => 'https://api.iconify.design/heroicons/currency-dollar.svg?color=%2322c55e',
                            'finanzas' => 'https://api.iconify.design/heroicons/banknotes.svg?color=%23ef4444',
                            'operaciones' => 'https://api.iconify.design/heroicons/cog-6-tooth.svg?color=%23f59e0b',
                            'marketing' => 'https://api.iconify.design/heroicons/megaphone.svg?color=%238b5cf6',
                            'rrhh' => 'https://api.iconify.design/heroicons/user-group.svg?color=%233b82f6',
                            'clientes' => 'https://api.iconify.design/heroicons/users.svg?color=%230ea5e9',
                            'general' => 'https://api.iconify.design/heroicons/chart-bar.svg?color=%236366f1',
                            'otros' => 'https://api.iconify.design/heroicons/document.svg?color=%236b7280'
                        ];

                        return $categoryIcons[$record->category] ?? $categoryIcons['general'];
                    })
                    ->circular()
                    ->size(60)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (PowerBiDashboard $record): string => Str::limit($record->description, 100))
                    ->wrap(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ventas' => 'Ventas',
                        'finanzas' => 'Finanzas',
                        'operaciones' => 'Operaciones',
                        'marketing' => 'Marketing',
                        'rrhh' => 'Recursos Humanos',
                        'clientes' => 'Clientes',
                        'general' => 'General',
                        'otros' => 'Otros',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'general',
                        'success' => 'ventas',
                        'info' => 'clientes',
                        'warning' => 'operaciones',
                        'danger' => 'finanzas',
                        'gray' => 'otros',
                        'violet' => 'marketing',
                        'blue' => 'rrhh',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Público')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->alignCenter(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoría')
                    ->options([
                        'ventas' => 'Ventas',
                        'finanzas' => 'Finanzas',
                        'operaciones' => 'Operaciones',
                        'marketing' => 'Marketing',
                        'rrhh' => 'Recursos Humanos',
                        'clientes' => 'Clientes',
                        'general' => 'General',
                        'otros' => 'Otros',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos los dashboards')
                    ->trueLabel('Dashboards activos')
                    ->falseLabel('Dashboards inactivos'),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Visibilidad')
                    ->placeholder('Todos los dashboards')
                    ->trueLabel('Dashboards públicos')
                    ->falseLabel('Dashboards privados'),
            ])
            ->actions([
                // Acción modal personalizada sólo con el iframe del dashboard
                Tables\Actions\Action::make('dashboard_preview')
                    ->label('Vista previa')
                    ->icon('heroicon-o-presentation-chart-bar')
                    ->color('info')
                    ->modalHeading(fn (PowerBiDashboard $record): string => "Dashboard: {$record->title}")
                    ->modalIcon('heroicon-o-presentation-chart-bar')
                    ->modalWidth('screen')
                    ->modalAlignment('center')
                    ->slideOver(false)
                    ->extraModalFooterActions([])
                    ->modalContent(function (PowerBiDashboard $record) {
                        // Usar directamente la URL de embed sin proxy
                        $embedUrl = $record->embed_url;

                        // Registrar para estadísticas
                        $tenant = Filament::getTenant();
                        \Illuminate\Support\Facades\Log::info('Preview Dashboard en Modal (Tenant)', [
                            'dashboard_id' => $record->id,
                            'tenant_id' => $tenant ? $tenant->id : null,
                            'direct_url' => true
                        ]);

                        // Devolvemos el iframe con una barra de navegación clara
                        // Usamos HtmlString para que sea compatible con Htmlable
                        return new \Illuminate\Support\HtmlString(<<<HTML
                        <div class="h-[90vh] w-full overflow-hidden bg-white">
                            <iframe
                                src="{$embedUrl}"
                                frameborder="0"
                                allowfullscreen="true"
                                class="w-full h-full"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                                style="border: none; width: 100%; height: 100%; min-height: 80vh; background-color: white;"
                                scrolling="auto">
                            </iframe>
                        </div>
                        HTML);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([])
            ])
            ->emptyStateIcon('heroicon-o-chart-bar')
            ->emptyStateHeading('No hay dashboards asignados')
            ->emptyStateDescription('Este cliente no tiene dashboards de Power BI asignados.');
    }

    // No necesitamos relaciones en el contexto del tenant
    // ya que accedemos directamente a los dashboards asociados
    public static function getRelations(): array
    {
        return [];
    }

    // Especificamos las páginas que utilizará este recurso,
    // importante usar las del namespace correcto
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPowerBiDashboards::route('/'),
            'create' => Pages\CreatePowerBiDashboard::route('/create'),
            'edit' => Pages\EditPowerBiDashboard::route('/{record}/edit'),
        ];
    }

    // Verificar si el usuario puede editar un dashboard
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // Permitir tanto a administradores generales como de organización
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Administradores generales siempre pueden editar
        if ($user->is_admin === 1) {
            return true;
        }

        // Administradores de organización pueden editar dentro de su tenant
        if ($user->is_tenant_admin === 1) {
            return true;
        }

        // Usuarios normales no pueden editar (sin mostrar notificación auto)
        return false;
    }

    // Verificar si el usuario puede eliminar un dashboard
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // Permitir tanto a administradores generales como de organización
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Administradores generales siempre pueden eliminar
        if ($user->is_admin === 1) {
            return true;
        }

        // Administradores de organización pueden eliminar dentro de su tenant
        if ($user->is_tenant_admin === 1) {
            return true;
        }

        // Usuarios normales no pueden eliminar (sin mostrar notificación auto)
        return false;
    }
}
