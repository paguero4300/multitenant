<?php

// app/Models/Tenant.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Filament\Models\Contracts\HasName;

class Tenant extends Model implements HasName
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_active'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * Obtiene los dashboards de Power BI asociados a este tenant
     *
     * @return BelongsToMany
     */
    public function powerBiDashboards(): BelongsToMany
    {
        return $this->belongsToMany(PowerBiDashboard::class, 'power_bi_dashboard_tenant', 'tenant_id', 'dashboard_id')
            ->withTimestamps();
    }
}