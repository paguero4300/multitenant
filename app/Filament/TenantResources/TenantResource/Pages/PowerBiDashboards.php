<?php

namespace App\Filament\TenantResources\TenantResource\Pages;

use App\Filament\TenantResources\TenantResource;
use Filament\Resources\Pages\Page;

class PowerBiDashboards extends Page
{
    protected static string $resource = TenantResource::class;

    protected static string $view = 'filament.tenant-resources.tenant-resource.pages.power-bi-dashboards';
}
