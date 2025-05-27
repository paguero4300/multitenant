<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Console\Command;

class SimplePermissionTest extends Command
{
    protected $signature = 'test:simple-permissions';
    protected $description = 'Prueba simple de permisos sin dependencias complejas';

    public function handle()
    {
        $this->info('🧪 PRUEBAS SIMPLES DE PERMISOS');
        $this->info('==============================');
        
        $this->testBasicPermissions();
        $this->testTenantAccess();
        $this->testUserTypes();
        $this->generateSummary();
        
        return 0;
    }
    
    private function testBasicPermissions()
    {
        $this->info("\n🔑 PRUEBA 1: PERMISOS BÁSICOS");
        $this->info("=============================");
        
        // Buscar admin global
        $admin = User::where('email', 'paguero@digito.pe')->first();
        
        if (!$admin) {
            $this->error("❌ No se encontró el administrador global");
            return;
        }
        
        $this->info("👤 Usuario: {$admin->email}");
        $this->info("📋 Propiedades:");
        $this->info("  - is_admin: " . ($admin->is_admin ? '✅ true' : '❌ false'));
        $this->info("  - is_tenant_admin: " . ($admin->is_tenant_admin ? '❌ true (problema)' : '✅ false'));
        $this->info("  - tenant_id: " . ($admin->tenant_id ?? '✅ NULL'));
        
        $this->info("🔍 Métodos de verificación:");
        $this->info("  - isAdmin(): " . ($admin->isAdmin() ? '✅ true' : '❌ false'));
        $this->info("  - isTenantAdmin(): " . ($admin->isTenantAdmin() ? '❌ true (problema)' : '✅ false'));
        
        // Verificar acceso a tenants
        $tenants = Tenant::all();
        $this->info("🏢 Acceso a tenants ({$tenants->count()} total):");
        
        foreach ($tenants as $tenant) {
            $canAccess = $admin->canAccessTenant($tenant);
            $this->info("  - {$tenant->name}: " . ($canAccess ? '✅ Permitido' : '❌ Denegado'));
        }
    }
    
    private function testTenantAccess()
    {
        $this->info("\n🏢 PRUEBA 2: ACCESO POR TENANT");
        $this->info("==============================");
        
        $tenants = Tenant::all();
        
        if ($tenants->count() === 0) {
            $this->warning("⚠️  No hay tenants en el sistema");
            return;
        }
        
        // Buscar usuarios de diferentes tipos
        $tenantAdmins = User::where('is_tenant_admin', true)->get();
        $regularUsers = User::where('is_admin', false)->where('is_tenant_admin', false)->whereNotNull('tenant_id')->get();
        
        $this->info("👥 Administradores de tenant encontrados: {$tenantAdmins->count()}");
        foreach ($tenantAdmins as $admin) {
            $this->testUserTenantAccess($admin, 'Admin de Tenant');
        }
        
        $this->info("\n👤 Usuarios regulares encontrados: {$regularUsers->count()}");
        foreach ($regularUsers->take(3) as $user) { // Solo los primeros 3 para no saturar
            $this->testUserTenantAccess($user, 'Usuario Regular');
        }
    }
    
    private function testUserTenantAccess(User $user, string $userType)
    {
        $this->info("\n  📧 {$user->email} ({$userType}):");
        $this->info("    - Tenant principal: " . ($user->tenant ? $user->tenant->name : 'Ninguno'));
        
        $tenants = Tenant::all();
        $accessCount = 0;
        
        foreach ($tenants as $tenant) {
            $canAccess = $user->canAccessTenant($tenant);
            if ($canAccess) {
                $accessCount++;
                $this->info("    - ✅ Acceso a: {$tenant->name}");
            }
        }
        
        $this->info("    - Total accesos: {$accessCount}/{$tenants->count()}");
        
        // Verificar que el acceso es apropiado
        if ($user->is_admin) {
            $expectedAccess = $tenants->count();
        } elseif ($user->tenant_id) {
            $expectedAccess = 1 + $user->additionalTenants()->count();
        } else {
            $expectedAccess = 0;
        }
        
        if ($accessCount === $expectedAccess) {
            $this->info("    - ✅ Acceso correcto");
        } else {
            $this->error("    - ❌ Acceso incorrecto (esperado: {$expectedAccess}, actual: {$accessCount})");
        }
    }
    
    private function testUserTypes()
    {
        $this->info("\n👥 PRUEBA 3: TIPOS DE USUARIO");
        $this->info("=============================");
        
        $users = User::all();
        
        $globalAdmins = $users->where('is_admin', true);
        $tenantAdmins = $users->where('is_tenant_admin', true);
        $regularUsers = $users->where('is_admin', false)->where('is_tenant_admin', false);
        $orphanUsers = $users->whereNull('tenant_id')->where('is_admin', false);
        
        $this->info("📊 Distribución de usuarios:");
        $this->info("  - Total: {$users->count()}");
        $this->info("  - Administradores globales: {$globalAdmins->count()}");
        $this->info("  - Administradores de tenant: {$tenantAdmins->count()}");
        $this->info("  - Usuarios regulares: {$regularUsers->count()}");
        $this->info("  - Usuarios sin tenant: {$orphanUsers->count()}");
        
        // Verificar problemas
        $problems = [];
        
        // Usuarios con permisos inconsistentes
        $inconsistent = $users->where('is_admin', true)->where('is_tenant_admin', true);
        if ($inconsistent->count() > 0) {
            $problems[] = "❌ {$inconsistent->count()} usuarios con permisos inconsistentes";
        }
        
        // Admins de tenant sin tenant
        $orphanAdmins = $users->where('is_tenant_admin', true)->whereNull('tenant_id');
        if ($orphanAdmins->count() > 0) {
            $problems[] = "❌ {$orphanAdmins->count()} admins de tenant sin tenant asignado";
        }
        
        if (empty($problems)) {
            $this->info("✅ No se encontraron problemas de configuración");
        } else {
            $this->error("⚠️  Problemas encontrados:");
            foreach ($problems as $problem) {
                $this->error("  {$problem}");
            }
        }
    }
    
    private function generateSummary()
    {
        $this->info("\n📊 RESUMEN FINAL");
        $this->info("================");
        
        $users = User::all();
        $tenants = Tenant::all();
        
        $this->info("📈 Estadísticas del sistema:");
        $this->info("  - Usuarios totales: {$users->count()}");
        $this->info("  - Tenants totales: {$tenants->count()}");
        
        // Verificar admin global
        $globalAdmin = User::where('email', 'paguero@digito.pe')->first();
        if ($globalAdmin && $globalAdmin->isAdmin()) {
            $this->info("✅ Administrador global configurado correctamente");
        } else {
            $this->error("❌ Problema con administrador global");
        }
        
        // Verificar que hay tenants
        if ($tenants->count() > 0) {
            $this->info("✅ Sistema multi-tenant configurado");
        } else {
            $this->warning("⚠️  No hay tenants configurados");
        }
        
        $this->info("\n🎯 Estado general del sistema:");
        
        $issues = 0;
        
        // Verificar problemas comunes
        if (User::where('is_admin', true)->where('is_tenant_admin', true)->count() > 0) {
            $this->error("❌ Usuarios con permisos inconsistentes");
            $issues++;
        }
        
        if (User::where('is_tenant_admin', true)->whereNull('tenant_id')->count() > 0) {
            $this->error("❌ Admins de tenant sin tenant");
            $issues++;
        }
        
        if ($issues === 0) {
            $this->info("🎉 ¡Sistema de permisos funcionando correctamente!");
        } else {
            $this->error("⚠️  Se encontraron {$issues} problemas que requieren atención");
        }
        
        $this->info("\n💡 Para pruebas más detalladas, ejecuta:");
        $this->info("   vendor\\bin\\phpunit.bat tests\\Feature\\UserPermissionsTest.php");
    }
}
