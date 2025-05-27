<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Console\Command;

class TestPermissions extends Command
{
    protected $signature = 'test:permissions';
    protected $description = 'Prueba automáticamente el sistema de permisos';

    public function handle()
    {
        $this->info('🧪 PRUEBAS AUTOMÁTICAS DE PERMISOS');
        $this->info('==================================');

        $this->testAdminGlobalPermissions();
        $this->testTenantAdminPermissions();
        $this->testRegularUserPermissions();
        $this->testEdgeCases();
        $this->testSecurityVulnerabilities();

        return 0;
    }

    private function testAdminGlobalPermissions()
    {
        $this->info("\n🔑 PRUEBA 1: PERMISOS DE ADMINISTRADOR GLOBAL");
        $this->info("==============================================");

        $admin = User::where('email', 'paguero@digito.pe')->first();

        if (!$admin) {
            $this->error("❌ No se encontró el administrador global");
            return;
        }

        $this->info("Usuario: {$admin->email}");
        $this->info("is_admin: " . ($admin->is_admin ? 'true' : 'false'));
        $this->info("is_tenant_admin: " . ($admin->is_tenant_admin ? 'true' : 'false'));
        $this->info("tenant_id: " . ($admin->tenant_id ?? 'NULL'));

        // Verificar métodos de permisos
        $this->info("\nVerificando métodos de permisos:");
        $this->info("- isAdmin(): " . ($admin->isAdmin() ? '✅ true' : '❌ false'));
        $this->info("- isTenantAdmin(): " . ($admin->isTenantAdmin() ? '⚠️ true' : '✅ false'));

        // Verificar acceso a todos los tenants
        $allTenants = Tenant::all();

        // Para testing, verificamos la lógica directamente sin usar getTenants
        if ($admin->is_admin) {
            $accessibleTenants = Tenant::all();
        } else {
            $accessibleTenants = collect([$admin->tenant])->filter();
            if ($admin->additionalTenants) {
                $accessibleTenants = $accessibleTenants->merge($admin->additionalTenants);
            }
        }

        $this->info("- Tenants totales: " . $allTenants->count());
        $this->info("- Tenants accesibles: " . $accessibleTenants->count());

        if ($allTenants->count() === $accessibleTenants->count()) {
            $this->info("✅ Admin global puede acceder a todos los tenants");
        } else {
            $this->error("❌ Admin global no puede acceder a todos los tenants");
        }

        // Verificar canAccessTenant para cada tenant
        foreach ($allTenants as $tenant) {
            $canAccess = $admin->canAccessTenant($tenant);
            $this->info("- Acceso a '{$tenant->name}': " . ($canAccess ? '✅' : '❌'));
        }
    }

    private function testTenantAdminPermissions()
    {
        $this->info("\n🏢 PRUEBA 2: PERMISOS DE ADMINISTRADOR DE TENANT");
        $this->info("================================================");

        $tenantAdmin = User::where('email', 'admin.tenant@test.com')->first();

        if (!$tenantAdmin) {
            $this->warn("⚠️ Usuario admin.tenant@test.com no encontrado. Ejecuta 'php artisan test:create-users' primero");
            return;
        }

        $this->info("Usuario: {$tenantAdmin->email}");
        $this->info("is_admin: " . ($tenantAdmin->is_admin ? 'true' : 'false'));
        $this->info("is_tenant_admin: " . ($tenantAdmin->is_tenant_admin ? 'true' : 'false'));
        $this->info("tenant_id: " . ($tenantAdmin->tenant_id ?? 'NULL'));

        // Verificar métodos de permisos
        $this->info("\nVerificando métodos de permisos:");
        $this->info("- isAdmin(): " . ($tenantAdmin->isAdmin() ? '❌ true' : '✅ false'));
        $this->info("- isTenantAdmin(): " . ($tenantAdmin->isTenantAdmin() ? '✅ true' : '❌ false'));

        // Verificar acceso solo a su tenant
        $allTenants = Tenant::all();

        // Para testing, verificamos la lógica directamente
        if ($tenantAdmin->is_admin) {
            $accessibleTenants = Tenant::all();
        } else {
            $accessibleTenants = collect([$tenantAdmin->tenant])->filter();
            if ($tenantAdmin->additionalTenants) {
                $accessibleTenants = $accessibleTenants->merge($tenantAdmin->additionalTenants);
            }
        }

        $this->info("- Tenants totales: " . $allTenants->count());
        $this->info("- Tenants accesibles: " . $accessibleTenants->count());

        // Verificar que solo puede acceder a su tenant
        $ownTenant = $tenantAdmin->tenant;
        if ($ownTenant) {
            $canAccessOwn = $tenantAdmin->canAccessTenant($ownTenant);
            $this->info("- Acceso a su tenant '{$ownTenant->name}': " . ($canAccessOwn ? '✅' : '❌'));
        }

        // Verificar que NO puede acceder a otros tenants
        $otherTenants = $allTenants->where('id', '!=', $tenantAdmin->tenant_id);
        foreach ($otherTenants as $tenant) {
            $canAccess = $tenantAdmin->canAccessTenant($tenant);
            $this->info("- Acceso a '{$tenant->name}' (otro tenant): " . ($canAccess ? '❌ SÍ (PROBLEMA)' : '✅ NO'));
        }
    }

    private function testRegularUserPermissions()
    {
        $this->info("\n👤 PRUEBA 3: PERMISOS DE USUARIO REGULAR");
        $this->info("========================================");

        $regularUser = User::where('email', 'usuario.regular@test.com')->first();

        if (!$regularUser) {
            $this->warn("⚠️ Usuario usuario.regular@test.com no encontrado. Ejecuta 'php artisan test:create-users' primero");
            return;
        }

        $this->info("Usuario: {$regularUser->email}");
        $this->info("is_admin: " . ($regularUser->is_admin ? 'true' : 'false'));
        $this->info("is_tenant_admin: " . ($regularUser->is_tenant_admin ? 'true' : 'false'));
        $this->info("tenant_id: " . ($regularUser->tenant_id ?? 'NULL'));

        // Verificar métodos de permisos
        $this->info("\nVerificando métodos de permisos:");
        $this->info("- isAdmin(): " . ($regularUser->isAdmin() ? '❌ true' : '✅ false'));
        $this->info("- isTenantAdmin(): " . ($regularUser->isTenantAdmin() ? '❌ true' : '✅ false'));

        // Verificar acceso solo a su tenant
        $ownTenant = $regularUser->tenant;
        if ($ownTenant) {
            $canAccessOwn = $regularUser->canAccessTenant($ownTenant);
            $this->info("- Acceso a su tenant '{$ownTenant->name}': " . ($canAccessOwn ? '✅' : '❌'));
        }

        // Verificar que NO puede acceder a otros tenants
        $allTenants = Tenant::all();
        $otherTenants = $allTenants->where('id', '!=', $regularUser->tenant_id);
        foreach ($otherTenants as $tenant) {
            $canAccess = $regularUser->canAccessTenant($tenant);
            $this->info("- Acceso a '{$tenant->name}' (otro tenant): " . ($canAccess ? '❌ SÍ (PROBLEMA)' : '✅ NO'));
        }
    }

    private function testEdgeCases()
    {
        $this->info("\n⚠️  PRUEBA 4: CASOS EDGE");
        $this->info("========================");

        // Usuario sin tenant
        $userWithoutTenant = User::where('email', 'sin.tenant@test.com')->first();

        if ($userWithoutTenant) {
            $this->info("Usuario sin tenant: {$userWithoutTenant->email}");
            $this->info("tenant_id: " . ($userWithoutTenant->tenant_id ?? 'NULL'));

            $allTenants = Tenant::all();
            foreach ($allTenants as $tenant) {
                $canAccess = $userWithoutTenant->canAccessTenant($tenant);
                $this->info("- Acceso a '{$tenant->name}': " . ($canAccess ? '❌ SÍ (PROBLEMA)' : '✅ NO'));
            }
        }
    }

    private function testSecurityVulnerabilities()
    {
        $this->info("\n🔒 PRUEBA 5: VULNERABILIDADES DE SEGURIDAD");
        $this->info("==========================================");

        // Verificar que no hay usuarios con permisos inconsistentes
        $inconsistentUsers = User::where('is_admin', true)
                                ->where('is_tenant_admin', true)
                                ->get();

        if ($inconsistentUsers->count() > 0) {
            $this->error("❌ Usuarios con permisos inconsistentes (admin Y tenant_admin):");
            foreach ($inconsistentUsers as $user) {
                $this->error("  - {$user->email}");
            }
        } else {
            $this->info("✅ No hay usuarios con permisos inconsistentes");
        }

        // Verificar usuarios tenant_admin sin tenant
        $orphanTenantAdmins = User::where('is_tenant_admin', true)
                                 ->whereNull('tenant_id')
                                 ->get();

        if ($orphanTenantAdmins->count() > 0) {
            $this->error("❌ Administradores de tenant sin tenant asignado:");
            foreach ($orphanTenantAdmins as $user) {
                $this->error("  - {$user->email}");
            }
        } else {
            $this->info("✅ Todos los administradores de tenant tienen tenant asignado");
        }
    }
}
