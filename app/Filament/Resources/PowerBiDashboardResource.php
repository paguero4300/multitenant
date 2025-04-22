<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PowerBiDashboardResource\Pages;
use App\Filament\Resources\PowerBiDashboardResource\RelationManagers;
use App\Models\PowerBiDashboard;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Dashboards Power BI';
    
    protected static ?string $modelLabel = 'Dashboard de Power BI';
    
    protected static ?string $pluralModelLabel = 'Dashboards de Power BI';
    
    protected static ?string $navigationGroup = 'Gestión BI';
    
    protected static ?int $navigationSort = 1;
    
    // Método para determinar si el recurso debe ser visible en la navegación
    public static function shouldRegisterNavigation(): bool
    {
        // Solo administradores generales y tenant_admin pueden ver este recurso
        $user = Auth::user();
        return $user && ($user->is_admin || $user->is_tenant_admin);
    }
    
    // Método para determinar si el usuario actual puede acceder al recurso
    public static function canAccess(): bool
    {
        // Solo administradores generales y tenant_admin pueden acceder a este recurso
        $user = Auth::user();
        return $user && ($user->is_admin || $user->is_tenant_admin);
    }
    
    // Método para controlar el acceso a la creación de dashboards
    public static function canCreate(): bool 
    {
        $user = Auth::user();
        
        // Si es administrador de tenant pero no administrador global,
        // indicar que debe ser redirigido al panel de tenant
        if ($user && $user->is_tenant_admin && !$user->is_admin) {
            // Registrar el intento para debugging
            Log::info('PowerBiDashboardResource - Tenant admin intentó crear dashboard en panel admin', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
            ]);
            
            // Mostrar una notificación en la siguiente carga de página
            \Filament\Notifications\Notification::make()
                ->title('Acceso al panel de tenant')
                ->body('Como administrador de organización, debes crear dashboards desde el panel de tu organización.')
                ->warning()
                ->persistent()
                ->send();
            
            // Simplemente devolver false para evitar acceso, sin intentar redireccionar
            // ya que estamos en un contexto donde el redirect no funciona como esperamos
            return false;
        }
        
        // Solo administradores globales pueden crear dashboards desde el panel de administración
        return $user && $user->is_admin;
    }
    
    // Método que modifica la consulta para que los tenant_admin solo vean dashboards de su tenant
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Registrar información para debug
        $user = Auth::user();
        $currentUrl = request()->url();
        $method = request()->method();
        
        Log::debug('PowerBiDashboardResource::getEloquentQuery - Acceso', [
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'is_admin' => $user && $user->is_admin ? 'true' : 'false',
            'is_tenant_admin' => $user && $user->is_tenant_admin ? 'true' : 'false',
            'tenant_id' => $user ? $user->tenant_id : null,
            'url' => $currentUrl,
            'method' => $method,
        ]);
        
        // Si es un tenant_admin, solo puede ver dashboards de su propio tenant
        if ($user && $user->is_tenant_admin && !$user->is_admin) {
            // Obtenemos el ID del tenant del usuario
            $tenantId = $user->tenant_id;
            
            // Registrar para debugging
            Log::info('PowerBiDashboardResource - Filtrando por tenant_id: ' . $tenantId);
            
            // Filtramos los dashboards que tienen relación con el tenant del usuario
            $query->whereHas('tenants', function ($query) use ($tenantId) {
                // Especificar claramente la tabla para evitar ambigüedad en la columna 'tenant_id'
                $query->where('power_bi_dashboard_tenant.tenant_id', $tenantId);
            });
        }
        
        return $query;
    }

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
                                            ->maxLength(255)
                                            ->autofocus()
                                            ->prefixIcon('heroicon-o-document')
                                            ->placeholder('Nombre del dashboard')
                                            ->columnSpan(7), // Proporción áurea
                                            
                                        Forms\Components\Select::make('category')
                                            ->label('Categoría')
                                            ->prefixIcon('heroicon-o-tag')
                                            ->columnSpan(5) // Proporción áurea
                                            ->options([
                                                'ventas' => 'Ventas',
                                                'finanzas' => 'Finanzas',
                                                'operaciones' => 'Operaciones',
                                                'marketing' => 'Marketing',
                                                'rrhh' => 'Recursos Humanos',
                                                'clientes' => 'Clientes',
                                                'general' => 'General',
                                                'otros' => 'Otros'
                                            ])
                                            ->searchable(),
                                            
                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripción')
                                            ->rows(3)
                                            ->placeholder('Describe el propósito y contenido del dashboard')
                                            ->columnSpan('full'),
                                ]),
                                
                                Section::make('Estado y Visualización')
                                    ->description('Configuración visual y de disponibilidad')
                                    ->icon('heroicon-o-eye')
                                    ->columns(12) // Proporción áurea interna
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Activo')
                                            ->helperText('Determina si el dashboard estará disponible para los usuarios')
                                            ->default(true)
                                            ->onIcon('heroicon-o-check')
                                            ->offIcon('heroicon-o-x-mark')
                                            ->inline(false)
                                            ->columnSpan(4),
                                            
                                        Forms\Components\FileUpload::make('thumbnail')
                                            ->label('Imagen de Miniatura')
                                            ->helperText('Imagen representativa del dashboard (recomendado: 16:9)')
                                            ->image()
                                            ->directory('dashboard-thumbnails')
                                            ->imagePreviewHeight('150')
                                            ->columnSpan(8), // Proporción áurea
                                    ]),
                            ]),
                            
                        Tab::make('Configuración de Power BI')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Section::make('Integración con Power BI')
                                    ->description('Configura los parámetros para conectar con Power BI')
                                    ->icon('heroicon-o-rectangle-stack')
                                    ->columns(12) // Proporción áurea interna
                                    ->schema([
                                        Forms\Components\TextInput::make('embed_url')
                                            ->label('URL de Incrustación')
                                            ->required()
                                            ->url()
                                            ->prefixIcon('heroicon-o-link')
                                            ->columnSpan('full'),
                                            
                                        Forms\Components\TextInput::make('report_id')
                                            ->label('ID del Reporte')
                                            ->placeholder('Ej: a1b2c3d4-e5f6-g7h8-i9j0-k1l2m3n4o5p6')
                                            ->prefixIcon('heroicon-o-identification')
                                            ->helperText('El ID único del reporte de Power BI')
                                            ->columnSpan(7) // Proporción áurea
                                            ->maxLength(255),
                                            
                                        Forms\Components\TextInput::make('embed_token')
                                            ->label('Token de Incrustación')
                                            ->password()
                                            ->revealable()
                                            ->prefixIcon('heroicon-o-key')
                                            ->helperText('Token de seguridad para la incrustación del dashboard')
                                            ->dehydrated(fn (?string $state) => filled($state))
                                            ->dehydrateStateUsing(fn (string $state) => $state)
                                            ->columnSpan(5) // Proporción áurea
                                            ->maxLength(255),
                                    ]),
                            ]),
                            
                        Tab::make('Permisos')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Section::make('Asignación de Permisos')
                                    ->description('Selecciona los tenants que pueden ver este dashboard')
                                    ->icon('heroicon-o-shield-check')
                                    ->columns(12) // Proporción áurea interna
                                    ->schema([
                                        Select::make('tenants')
                                            ->label('Organizaciones con Acceso')
                                            ->multiple()
                                            ->preload()
                                            ->relationship('tenants', 'name')
                                            ->searchable()
                                            // Para tenant_admin, deshabilitar el campo y establecer valor por defecto
                                            ->disabled(function () {
                                                $user = Auth::user();
                                                return $user && $user->is_tenant_admin && !$user->is_admin;
                                            })
                                            // Para tenant_admin, preseleccionar automáticamente su tenant
                                            ->default(function () {
                                                $user = Auth::user();
                                                if ($user && $user->is_tenant_admin && !$user->is_admin) {
                                                    return [$user->tenant_id];
                                                }
                                                return null;
                                            })
                                            ->helperText(function () {
                                                $user = Auth::user();
                                                if ($user && $user->is_tenant_admin && !$user->is_admin) {
                                                    return 'El dashboard estará disponible para tu organización';
                                                }
                                                return 'Selecciona las organizaciones que tendrán acceso a este dashboard';
                                            })
                                            ->placeholder('Selecciona una o más organizaciones')
                                            ->columnSpan('full'),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Aplicamos principios de Gestalt y proporción áurea al listado
        return $table
            // Principio de figura-fondo (filas alternadas para mejor legibilidad)
            ->striped()
            // Principio de continuidad (mantener flujo visual coherente)
            ->defaultSort('title', 'asc')
            // Proporción áurea para tamaño relativo de columnas
            ->columns([
                // Si hay imagen, mostrar la imagen; si no, mostrar un icono de acuerdo a la categoría
                Tables\Columns\IconColumn::make('has_thumbnail')
                    ->label('Vista previa')
                    ->boolean()
                    ->getStateUsing(function (PowerBiDashboard $record): bool {
                        // Verificamos si el dashboard tiene una imagen
                        return !empty($record->thumbnail);
                    })
                    // Cuando no hay imagen, mostrar un icono de chart-bar
                    ->falseIcon('heroicon-o-presentation-chart-bar')
                    // Cuando hay imagen, ocultar el icono y mostrar otro icono
                    ->trueIcon('heroicon-o-photo')
                    // Colores semánticos
                    ->falseColor('primary')
                    ->trueColor('success')
                    ->size('xl')
                    ->alignCenter(),
                
                // Información textual - ocupa espacio proporcional según importancia
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg')
                    // Principio de proximidad - agrupar la descripción con el título
                    ->description(fn (PowerBiDashboard $record): ?string => 
                        $record->description ? \Illuminate\Support\Str::limit($record->description, 80) : null),
                
                // Badge con diseño mejorado - proporciones áureas en tamaño y espaciado
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Categoría')
                    ->searchable()
                    // Colores con mejor armonía visual
                    ->colors([
                        'primary' => 'general',
                        'success' => 'ventas',
                        'warning' => 'finanzas',
                        'danger' => 'marketing',
                        'gray' => 'otros',
                        'info' => 'operaciones',
                        'secondary' => 'rrhh',
                        'tertiary' => 'clientes',
                    ])
                    // Íconos para mejorar reconocimiento visual (principio de similitud)
                    ->icons([
                        'heroicon-o-presentation-chart-bar' => 'general',
                        'heroicon-o-currency-dollar' => 'ventas',
                        'heroicon-o-banknotes' => 'finanzas',
                        'heroicon-o-megaphone' => 'marketing',
                        'heroicon-o-question-mark-circle' => 'otros',
                        'heroicon-o-cog' => 'operaciones',
                        'heroicon-o-users' => 'rrhh',
                        'heroicon-o-user-group' => 'clientes',
                    ])
                    ->iconPosition('before')
                    ->size('md'),
                
                // Estado con mejor diseño visual - ícono más descriptivo
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->size('lg')
                    ->alignCenter()
                    ->sortable(),
                
                // Contador de organizaciones con mejor formato visual
                Tables\Columns\TextColumn::make('tenants_count')
                    ->label('Organizaciones')
                    ->counts('tenants')
                    ->color('gray')
                    ->sortable()
                    ->icon('heroicon-o-building-office')
                    ->alignCenter()
                    ->badge(),
                
                // Fechas menos prominentes (principio de jerarquía visual)
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->color('gray')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->color('gray')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Principio de proximidad - filtros agrupados funcionalmente
            ->filters([
                // Filtros en proporciones áureas - mayor importancia visual
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoría')
                    ->multiple()
                    ->preload()
                    ->options([
                        'ventas' => 'Ventas',
                        'finanzas' => 'Finanzas',
                        'operaciones' => 'Operaciones',
                        'marketing' => 'Marketing',
                        'rrhh' => 'Recursos Humanos',
                        'clientes' => 'Clientes',
                        'general' => 'General',
                        'otros' => 'Otros'
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos los dashboards')
                    ->trueLabel('Dashboards activos')
                    ->falseLabel('Dashboards inactivos'),
                    
                // Nuevo filtro por organizaciones para un acceso más rápido
                Tables\Filters\SelectFilter::make('tenant')
                    ->label('Organización')
                    ->relationship('tenants', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver detalles')
                        ->icon('heroicon-o-document-text'),
                    Tables\Actions\EditAction::make()
                        ->label('Editar')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\Action::make('dashboard_preview')
                        ->label('Vista previa')
                        ->icon('heroicon-o-presentation-chart-bar')
                        ->color('info')
                        ->modalHeading(fn (PowerBiDashboard $record): string => "Dashboard: {$record->title}")
                        ->modalIcon('heroicon-o-presentation-chart-bar')
                        ->modalWidth('screen')
                        ->modalAlignment('center')
                        ->extraModalFooterActions([])
                        ->modalContent(function (PowerBiDashboard $record) {
                            // Usar directamente la URL de embed sin proxy
                            $embedUrl = $record->embed_url;
                            
                            // Registrar para estadísticas
                            $tenant = Filament::getTenant();
                            \Illuminate\Support\Facades\Log::info('Preview Dashboard en Modal (Admin)', [
                                'dashboard_id' => $record->id,
                                'tenant_id' => $tenant ? $tenant->id : null,
                                'direct_url' => true
                            ]);
                            
                            // Devolvemos el iframe con una barra de navegación clara
                            // Usamos HtmlString para que sea compatible con Htmlable
                            return new \Illuminate\Support\HtmlString(<<<HTML
                            <div class="h-[90vh] w-full overflow-hidden">
                                <iframe src="{$embedUrl}" frameborder="0" allowfullscreen class="w-full h-full"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    style="border: none; width: 100%; height: 100vh; max-height: calc(100vh - 5rem);" scrolling="no"></iframe>
                            </div>
                            HTML);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar')
                        ->extraModalFooterActions([]), 
                    Tables\Actions\DeleteAction::make()
                        ->label('Eliminar')
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ])
                ->tooltip('Acciones')
                ->iconButton()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        // Solo permitimos que el admin general elimine dashboards en masa
                        ->visible(fn () => Auth::user() && Auth::user()->is_admin),
                    Tables\Actions\BulkAction::make('activar')
                        ->label('Activar Seleccionados')
                        ->icon('heroicon-o-check')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            // Verificar permisos: un tenant_admin solo puede modificar dashboards de su tenant
                            $user = Auth::user();
                            
                            // Para cada dashboard, verificar si el usuario tiene permisos para modificarlo
                            $records->each(function (PowerBiDashboard $record) use ($user) {
                                $canModify = true;
                                
                                // Si es tenant_admin, verificar que el dashboard pertenece a su tenant
                                if ($user && $user->is_tenant_admin && !$user->is_admin) {
                                    $canModify = $record->tenants()->where('tenant_id', $user->tenant_id)->exists();
                                }
                                
                                // Solo actualizar si tiene permisos
                                if ($canModify) {
                                    $record->update(['is_active' => true]);
                                }
                            });
                            
                            Notification::make()
                                ->title('Dashboards Activados')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('desactivar')
                        ->label('Desactivar Seleccionados')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            // Verificar permisos: un tenant_admin solo puede modificar dashboards de su tenant
                            $user = Auth::user();
                            
                            // Para cada dashboard, verificar si el usuario tiene permisos para modificarlo
                            $records->each(function (PowerBiDashboard $record) use ($user) {
                                $canModify = true;
                                
                                // Si es tenant_admin, verificar que el dashboard pertenece a su tenant
                                if ($user && $user->is_tenant_admin && !$user->is_admin) {
                                    $canModify = $record->tenants()->where('tenant_id', $user->tenant_id)->exists();
                                }
                                
                                // Solo actualizar si tiene permisos
                                if ($canModify) {
                                    $record->update(['is_active' => false]);
                                }
                            });
                            
                            Notification::make()
                                ->title('Dashboards Desactivados')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->emptyStateIcon('heroicon-o-chart-bar')
            ->emptyStateHeading('No hay dashboards')
            ->emptyStateDescription('Crea tu primer dashboard de Power BI haciendo clic en el botón "Crear dashboard".');
    }
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\TenantsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPowerBiDashboards::route('/'),
            'create' => Pages\CreatePowerBiDashboard::route('/create'),
            'edit' => Pages\EditPowerBiDashboard::route('/{record}/edit'),
        ];
    }
}
