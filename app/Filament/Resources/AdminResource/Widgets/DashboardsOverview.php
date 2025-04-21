<?php

namespace App\Filament\Resources\AdminResource\Widgets;

use App\Models\PowerBiDashboard;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DashboardsOverview extends BaseWidget
{
    protected static ?string $heading = 'Dashboards Destacados';
    
    protected int | string | array $columnSpan = 5; // Proporción áurea aproximada

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PowerBiDashboard::query()
                    ->where('is_active', true)
                    ->withCount('tenants')
                    ->orderByDesc('tenants_count')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('')
                    ->size(40)
                    ->circular(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Dashboard')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(30),
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Categoría')
                    ->colors([
                        'primary' => 'general',
                        'success' => 'ventas',
                        'warning' => 'finanzas',
                        'danger' => 'marketing',
                        'gray' => 'otros',
                        'info' => 'operaciones',
                        'secondary' => 'rrhh',
                        'tertiary' => 'clientes',
                    ]),
                Tables\Columns\TextColumn::make('tenants_count')
                    ->label('# Org.')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->url(fn (PowerBiDashboard $record) => route('admin.power-bi.preview', $record))
                    ->icon('heroicon-m-eye')
                    ->openUrlInNewTab(),
            ]);
    }
}
