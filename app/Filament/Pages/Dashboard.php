<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // Personalizar la página de dashboard
    protected static ?string $title = 'Panel de Control';
    
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $navigationLabel = 'Panel Principal';
    
    protected static ?int $navigationSort = -2; // Para que aparezca al principio
}
