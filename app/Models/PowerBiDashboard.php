<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PowerBiDashboard extends Model
{
    use HasFactory;
    
    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'embed_url',
        'embed_token',
        'report_id',
        'description',
        'category',
        'thumbnail',
        'is_active',
    ];
    
    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * Obtiene los tenants asociados a este dashboard.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'power_bi_dashboard_tenant', 'dashboard_id', 'tenant_id')
            ->withTimestamps();
    }
    
    /**
     * Alias para la relación tenants() pero en singular.
     * Necesario para Filament y su sistema de multi-tenancy.
     */
    public function tenant()
    {
        return $this->tenants();
    }
    
    /**
     * Obtiene los registros de acceso a este dashboard.
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(PowerBiDashboardAccessLog::class, 'dashboard_id');
    }
}
