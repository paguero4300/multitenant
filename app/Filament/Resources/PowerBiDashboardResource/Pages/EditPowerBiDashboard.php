<?php

namespace App\Filament\Resources\PowerBiDashboardResource\Pages;

use App\Filament\Resources\PowerBiDashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPowerBiDashboard extends EditRecord
{
    protected static string $resource = PowerBiDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
