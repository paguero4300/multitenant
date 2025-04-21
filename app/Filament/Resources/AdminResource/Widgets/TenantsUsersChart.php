<?php

namespace App\Filament\Resources\AdminResource\Widgets;

use App\Models\User;
use App\Models\Tenant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TenantsUsersChart extends ChartWidget
{
    protected static ?string $heading = 'Distribución de Usuarios por Organización';
    
    protected int | string | array $columnSpan = 7; // Proporción áurea aprox.
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $tenants = Tenant::withCount('users')
            ->orderByDesc('users_count')
            ->take(7)
            ->get();
            
        return [
            'datasets' => [
                [
                    'label' => 'Usuarios',
                    'data' => $tenants->pluck('users_count')->toArray(),
                    'backgroundColor' => [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(201, 203, 207, 0.7)',
                    ],
                    'borderColor' => [
                        'rgb(54, 162, 235)',
                        'rgb(255, 99, 132)',
                        'rgb(255, 206, 86)',
                        'rgb(75, 192, 192)',
                        'rgb(153, 102, 255)',
                        'rgb(255, 159, 64)',
                        'rgb(201, 203, 207)',
                    ],
                ],
            ],
            'labels' => $tenants->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; // Cambiado a dona para mejor visualización de distribuciones
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right', // Coloca la leyenda a la derecha para equilibrio visual
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
