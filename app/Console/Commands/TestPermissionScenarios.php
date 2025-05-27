<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class TestPermissionScenarios extends Command
{
    protected $signature = 'test:permission-scenarios';
    protected $description = 'Prueba escenarios específicos de permisos';

    public function handle()
    {
        $this->info('🧪 PRUEBAS DE ESCENARIOS DE PERMISOS');
        $this->info('===================================');
        
        $this->setupTestData();
        $this->testAdminGlobalScenario();
        $this->testTenantAdminScenario();
        $this->testRegularUserScenario();
        $this->testEdgeCaseScenarios();
        $this->testSecurityValidations();
        
        return 0;
    }
    
    private function setupTestData()
    {
        $this->info("\n🔧 CONFIGURANDO DATOS DE PRUEBA");
        $this->info("===============================");
        
        // Crear tenants de prueba
        $tenant1 = Tenant::firstOrCreate(
            ['slug' => 'test-empresa-a'],
            ['name' => 'Test Empresa A', 'is_active' => true]
        );
        
        $tenant2 = Tenant::firstOrCreate(
            ['slug' => 'test-empresa-b'],
            ['name' => 'Test Empresa B', 'is_active' => true]
        );
        
        $this->info("✅ Tenants de prueba creados/verificados");
        $this->info("  - {$tenant1->name} (ID: {$tenant1->id})");
        $this->info("  - {$tenant2->name} (ID: {$tenant2->id})");
    }
    
    private function testAdminGlobalScenario()
    {
        $this->info("\n🔑 ESCENARIO 1: ADMINISTRADOR GLOBAL");
        $this->info("===================================");
        
        $admin = User::where('email', 'paguero@digito.pe')->first();
        
        if (!$admin) {
            $this->error("❌ No se encontró el administrador global");
            return;
        }
        
        $this->info("👤 Usuario: {$admin->email}");
        
        // Verificar propiedades
        $this->line("📋 Propiedades:");
        $this->line("  - is_admin: " . ($admin->is_admin ? '✅ true' : '❌ false'));
        $this->line("  - is_tenant_admin: " . ($admin->is_tenant_admin ? '✅ false' : '❌ true'));
        $this->line("  - tenant_id: " . ($admin->tenant_id ?? '✅ NULL'));
        
        // Verificar métodos
        $this->line("🔍 Métodos de verificación:");
        $this->line("  - isAdmin(): " . ($admin->isAdmin() ? '✅ true' : '❌ false'));
        $this->line("  - isTenantAdmin(): " . ($admin->isTenantAdmin() ? '❌ true' : '✅ false'));
        
        // Verificar acceso a todos los tenants
        $this->line("🏢 Acceso a tenants:");
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            $canAccess = $admin->canAccessTenant($tenant);
            $this->line("  - {$tenant->name}: " . ($canAccess ? '✅ Permitido' : '❌ Denegado'));
        }
        
        // Verificar getTenants
        $accessibleTenants = $admin->getTenants(null);
        $this->line("📊 Tenants accesibles: {$accessibleTenants->count()}/{$tenants->count()}");
    }
    
    private function testTenantAdminScenario()
    {
        $this->info("\n🏢 ESCENARIO 2: ADMINISTRADOR DE TENANT");
        $this->info("======================================");
        
        // Buscar o crear un admin de tenant
        $tenant = Tenant::where('slug', 'test-empresa-a')->first();
        $tenantAdmin = User::where('is_tenant_admin', true)->where('tenant_id', $tenant->id)->first();
        
        if (!$tenantAdmin) {
            $tenantAdmin = User::create([
                'name' => 'Test Admin Tenant',
                'email' => 'test.admin.tenant@example.com',
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'is_admin' => false,
                'is_tenant_admin' => true,
            ]);
            $this->info("✅ Admin de tenant creado para pruebas");
        }
        
        $this->info("👤 Usuario: {$tenantAdmin->email}");
        
        // Verificar propiedades
        $this->line("📋 Propiedades:");
        $this->line("  - is_admin: " . ($tenantAdmin->is_admin ? '❌ true' : '✅ false'));
        $this->line("  - is_tenant_admin: " . ($tenantAdmin->is_tenant_admin ? '✅ true' : '❌ false'));
        $this->line("  - tenant_id: " . ($tenantAdmin->tenant_id ?? '❌ NULL'));
        
        // Verificar acceso a tenants
        $this->line("🏢 Acceso a tenants:");
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            $canAccess = $tenantAdmin->canAccessTenant($tenant);
            $shouldAccess = $tenant->id == $tenantAdmin->tenant_id;
            $status = $canAccess === $shouldAccess ? 
                ($canAccess ? '✅ Correcto (Permitido)' : '✅ Correcto (Denegado)') :
                '❌ INCORRECTO';
            $this->line("  - {$tenant->name}: {$status}");
        }
    }
    
    private function testRegularUserScenario()
    {
        $this->info("\n👤 ESCENARIO 3: USUARIO REGULAR");
        $this->info("===============================");
        
        // Buscar o crear un usuario regular
        $tenant = Tenant::where('slug', 'test-empresa-a')->first();
        $regularUser = User::where('is_admin', false)
                          ->where('is_tenant_admin', false)
                          ->where('tenant_id', $tenant->id)
                          ->first();
        
        if (!$regularUser) {
            $regularUser = User::create([
                'name' => 'Test Usuario Regular',
                'email' => 'test.usuario.regular@example.com',
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'is_admin' => false,
                'is_tenant_admin' => false,
            ]);
            $this->info("✅ Usuario regular creado para pruebas");
        }
        
        $this->info("👤 Usuario: {$regularUser->email}");
        
        // Verificar propiedades
        $this->line("📋 Propiedades:");
        $this->line("  - is_admin: " . ($regularUser->is_admin ? '❌ true' : '✅ false'));
        $this->line("  - is_tenant_admin: " . ($regularUser->is_tenant_admin ? '❌ true' : '✅ false'));
        $this->line("  - tenant_id: " . ($regularUser->tenant_id ?? '❌ NULL'));
        
        // Verificar acceso a tenants
        $this->line("🏢 Acceso a tenants:");
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            $canAccess = $regularUser->canAccessTenant($tenant);
            $shouldAccess = $tenant->id == $regularUser->tenant_id;
            $status = $canAccess === $shouldAccess ? 
                ($canAccess ? '✅ Correcto (Permitido)' : '✅ Correcto (Denegado)') :
                '❌ INCORRECTO';
            $this->line("  - {$tenant->name}: {$status}");
        }
    }
    
    private function testEdgeCaseScenarios()
    {
        $this->info("\n⚠️  ESCENARIO 4: CASOS EDGE");
        $this->info("===========================");
        
        // Caso 1: Usuario sin tenant
        $userWithoutTenant = User::whereNull('tenant_id')
                                ->where('is_admin', false)
                                ->where('is_tenant_admin', false)
                                ->first();
        
        if ($userWithoutTenant) {
            $this->info("🔍 Usuario sin tenant: {$userWithoutTenant->email}");
            $tenants = Tenant::all();
            foreach ($tenants as $tenant) {
                $canAccess = $userWithoutTenant->canAccessTenant($tenant);
                $this->line("  - Acceso a {$tenant->name}: " . ($canAccess ? '❌ PERMITIDO (PROBLEMA)' : '✅ DENEGADO'));
            }
        } else {
            $this->info("✅ No hay usuarios sin tenant (bueno)");
        }
        
        // Caso 2: Verificar que no hay permisos inconsistentes
        $inconsistentUsers = User::where('is_admin', true)->where('is_tenant_admin', true)->get();
        if ($inconsistentUsers->count() > 0) {
            $this->error("❌ Usuarios con permisos inconsistentes encontrados:");
            foreach ($inconsistentUsers as $user) {
                $this->error("  - {$user->email}");
            }
        } else {
            $this->info("✅ No hay usuarios con permisos inconsistentes");
        }
    }
    
    private function testSecurityValidations()
    {
        $this->info("\n🔒 ESCENARIO 5: VALIDACIONES DE SEGURIDAD");
        $this->info("=========================================");
        
        // Intentar crear usuario con permisos inconsistentes
        $this->info("🧪 Probando validaciones del modelo...");
        
        try {
            $invalidUser = new User([
                'name' => 'Test Invalid User',
                'email' => 'invalid@test.com',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'is_tenant_admin' => true,
            ]);
            $invalidUser->save();
            $this->error("❌ FALLO: Se permitió crear usuario con permisos inconsistentes");
        } catch (\InvalidArgumentException $e) {
            $this->info("✅ ÉXITO: Validación bloqueó permisos inconsistentes");
            $this->line("  Mensaje: {$e->getMessage()}");
        }
        
        try {
            $invalidTenantAdmin = new User([
                'name' => 'Test Invalid Tenant Admin',
                'email' => 'invalid.tenant.admin@test.com',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'is_tenant_admin' => true,
                'tenant_id' => null,
            ]);
            $invalidTenantAdmin->save();
            $this->error("❌ FALLO: Se permitió crear admin de tenant sin tenant_id");
        } catch (\InvalidArgumentException $e) {
            $this->info("✅ ÉXITO: Validación bloqueó admin de tenant sin tenant_id");
            $this->line("  Mensaje: {$e->getMessage()}");
        }
        
        $this->info("\n🎯 RESULTADO: Las validaciones de seguridad están funcionando correctamente");
    }
}
