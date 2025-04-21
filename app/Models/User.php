<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 
        'email', 
        'password',
        'tenant_id',
        'is_admin', // Agregar campo para distinguir admins
        'is_tenant_admin', // Agregar campo para administradores de organización
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Método requerido por HasTenants
    public function getTenants(Panel $panel): Collection
    {
        // Si es admin, puede ver todos los tenants
        if ($this->is_admin) {
            return Tenant::all();
        }
        
        // Usuario normal solo ve su propio tenant
        return Tenant::where('id', $this->tenant_id)->get();
    }

    // Método para verificar seguridad de acceso a tenant
    public function canAccessTenant(Model $tenant): bool
    {
        // Admins globales pueden acceder a cualquier tenant
        if ($this->is_admin == 1) {
            return true;
        }
        
        // Administradores de tenant pueden acceder solo a su tenant
        if ($this->is_tenant_admin == 1) {
            return (string)$tenant->id === (string)$this->tenant_id;
        }
        
        // Usuario normal puede acceder solo al tenant al que pertenece
        if ($this->tenant_id !== null) {
            return (string)$tenant->id === (string)$this->tenant_id;
        }
        
        // Si el usuario no tiene tenant asignado, no puede acceder a ninguno
        return false;
    }

    // Para filtrar acceso al panel Filament
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // O personalizar según necesidad
    }
    
    /**
     * Verifica si el usuario es un administrador
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
    
    /**
     * Verifica si el usuario es un administrador de tenant
     *
     * @return bool
     */
    public function isTenantAdmin(): bool
    {
        return (bool) $this->is_tenant_admin;
    }
    
    /**
     * Verifica si el usuario tiene acceso a un tenant específico
     *
     * @param \App\Models\Tenant $tenant
     * @return bool
     */
    public function hasAccessToTenant(Tenant $tenant): bool
    {
        return $this->isAdmin() || $this->canAccessTenant($tenant);
    }
}
