<?php

namespace App\Filament\TenantPages;

use App\Models\Dashboard as DashboardModel;
use Filament\Pages\Page;
use Filament\Facades\Filament;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.tenant-pages.dashboard';
    
    protected static ?int $navigationSort = -2;
    
    public static function getNavigationLabel(): string
    {
        return 'Panel principal';
    }
    
    public function getHeading(): string
    {
        $tenant = Filament::getTenant();
        return "Bienvenido a {$tenant->name}";
    }
}
