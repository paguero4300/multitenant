<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Console\Command;

class SystemPermissionsAudit extends Command
{
    protected $signature = 'system:audit-permissions';
    protected $description = 'Audita el sistema de permisos y usuarios';

    public function handle()
    {
        $this->info('🔍 AUDITORÍA DEL SISTEMA DE PERMISOS');
        $this->info('=====================================');
        
        // 1. Verificar usuarios
        $this->auditUsers();
        
        // 2. Verificar tenants
        $this->auditTenants();
        
        // 3. Verificar relaciones
        $this->auditRelations();
        
        // 4. Verificar casos edge
        $this->auditEdgeCases();
        
        return 0;
    }
    
    private function auditUsers()
    {
        $this->info("\n📋 USUARIOS EN EL SISTEMA:");
        $this->info("---------------------------");
        
        $users = User::all();
        
        foreach ($users as $user) {
            $this->line(sprintf(
                "ID: %d | Email: %s | Nombre: %s | Admin: %s | TenantAdmin: %s | TenantID: %s",
                $user->id,
                $user->email,
                $user->name,
                $user->is_admin ? 'SÍ' : 'NO',
                $user->is_tenant_admin ? 'SÍ' : 'NO',
                $user->tenant_id ?? 'NULL'
            ));
        }
        
        $this->info("\nRESUMEN:");
        $this->info("- Total usuarios: " . $users->count());
        $this->info("- Administradores globales: " . $users->where('is_admin', true)->count());
        $this->info("- Administradores de tenant: " . $users->where('is_tenant_admin', true)->count());
        $this->info("- Usuarios sin tenant: " . $users->whereNull('tenant_id')->count());
    }
    
    private function auditTenants()
    {
        $this->info("\n🏢 TENANTS EN EL SISTEMA:");
        $this->info("-------------------------");
        
        $tenants = Tenant::all();
        
        foreach ($tenants as $tenant) {
            $userCount = $tenant->users()->count();
            $this->line(sprintf(
                "ID: %d | Nombre: %s | Slug: %s | Activo: %s | Usuarios: %d",
                $tenant->id,
                $tenant->name,
                $tenant->slug,
                $tenant->is_active ? 'SÍ' : 'NO',
                $userCount
            ));
        }
        
        $this->info("\nRESUMEN:");
        $this->info("- Total tenants: " . $tenants->count());
        $this->info("- Tenants activos: " . $tenants->where('is_active', true)->count());
    }
    
    private function auditRelations()
    {
        $this->info("\n🔗 RELACIONES TENANT-USUARIO:");
        $this->info("------------------------------");
        
        $users = User::with('tenant', 'additionalTenants')->get();
        
        foreach ($users as $user) {
            $additionalCount = $user->additionalTenants()->count();
            $tenantName = $user->tenant ? $user->tenant->name : 'Sin tenant';
            
            $this->line(sprintf(
                "Usuario: %s | Tenant principal: %s | Tenants adicionales: %d",
                $user->email,
                $tenantName,
                $additionalCount
            ));
        }
    }
    
    private function auditEdgeCases()
    {
        $this->info("\n⚠️  CASOS EDGE DETECTADOS:");
        $this->info("---------------------------");
        
        // Usuarios sin tenant pero no admin
        $usersWithoutTenant = User::whereNull('tenant_id')->where('is_admin', false)->get();
        if ($usersWithoutTenant->count() > 0) {
            $this->warn("❌ Usuarios sin tenant y sin permisos de admin:");
            foreach ($usersWithoutTenant as $user) {
                $this->warn("  - " . $user->email);
            }
        }
        
        // Admins de tenant sin tenant asignado
        $tenantAdminsWithoutTenant = User::whereNull('tenant_id')->where('is_tenant_admin', true)->get();
        if ($tenantAdminsWithoutTenant->count() > 0) {
            $this->warn("❌ Administradores de tenant sin tenant asignado:");
            foreach ($tenantAdminsWithoutTenant as $user) {
                $this->warn("  - " . $user->email);
            }
        }
        
        // Tenants sin usuarios
        $tenantsWithoutUsers = Tenant::doesntHave('users')->get();
        if ($tenantsWithoutUsers->count() > 0) {
            $this->warn("⚠️  Tenants sin usuarios asignados:");
            foreach ($tenantsWithoutUsers as $tenant) {
                $this->warn("  - " . $tenant->name . " (ID: " . $tenant->id . ")");
            }
        }
        
        if ($usersWithoutTenant->count() == 0 && $tenantAdminsWithoutTenant->count() == 0) {
            $this->info("✅ No se detectaron casos edge problemáticos");
        }
    }
}
