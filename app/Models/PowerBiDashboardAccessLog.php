<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PowerBiDashboardAccessLog extends Model
{
    use HasFactory;
    
    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dashboard_id',
        'tenant_id',
        'user_id',
        'access_ip',
        'accessed_at',
    ];
    
    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'accessed_at' => 'datetime',
    ];
    
    /**
     * Obtiene el dashboard al que se accedió.
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(PowerBiDashboard::class, 'dashboard_id');
    }
    
    /**
     * Obtiene el tenant que accedió al dashboard.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    
    /**
     * Obtiene el usuario que accedió al dashboard.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
