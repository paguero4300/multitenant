<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Tenant;
use App\Models\PowerBiDashboard;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        $activeTenantsCount = Tenant::where('is_active', true)->count();
        $inactiveTenantsCount = Tenant::where('is_active', false)->count();
        
        return [
            Stat::make('Total de Organizaciones', Tenant::count())
                ->description($activeTenantsCount . ' activas, ' . $inactiveTenantsCount . ' inactivas')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary')
                ->chart([7, 6, 9, 8, 10, 12, $activeTenantsCount + $inactiveTenantsCount]),
                
            Stat::make('Total de Usuarios', User::count())
                ->description(User::where('is_admin', true)->count() . ' administradores, ' . 
                             User::where('is_tenant_admin', true)->count() . ' admins de organización')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([8, 10, 12, 14, 16, 18, User::count()]),
                
            Stat::make('Dashboards Power BI', PowerBiDashboard::count())
                ->description(PowerBiDashboard::where('is_active', true)->count() . ' activos')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning')
                ->chart([3, 5, 7, 6, 8, 9, PowerBiDashboard::count()]),
        ];
    }
}
