<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUsers extends Command
{
    protected $signature = 'test:create-users';
    protected $description = 'Crea usuarios de prueba para verificar permisos';

    public function handle()
    {
        $this->info('🔧 CREANDO USUARIOS DE PRUEBA');
        $this->info('=============================');
        
        // Crear tenants de prueba si no existen
        $tenant1 = Tenant::firstOrCreate(
            ['slug' => 'empresa-a'],
            ['name' => 'Empresa A', 'is_active' => true]
        );
        
        $tenant2 = Tenant::firstOrCreate(
            ['slug' => 'empresa-b'],
            ['name' => 'Empresa B', 'is_active' => true]
        );
        
        $this->info("✅ Tenants creados/verificados:");
        $this->info("  - {$tenant1->name} (ID: {$tenant1->id})");
        $this->info("  - {$tenant2->name} (ID: {$tenant2->id})");
        
        // Crear usuarios de prueba
        $password = Hash::make('password123');
        
        // 1. Usuario regular con tenant
        $regularUser = User::firstOrCreate(
            ['email' => 'usuario.regular@test.com'],
            [
                'name' => 'Usuario Regular',
                'password' => $password,
                'tenant_id' => $tenant1->id,
                'is_admin' => false,
                'is_tenant_admin' => false,
            ]
        );
        
        // 2. Administrador de tenant
        $tenantAdmin = User::firstOrCreate(
            ['email' => 'admin.tenant@test.com'],
            [
                'name' => 'Admin de Tenant',
                'password' => $password,
                'tenant_id' => $tenant1->id,
                'is_admin' => false,
                'is_tenant_admin' => true,
            ]
        );
        
        // 3. Usuario sin tenant (caso edge)
        $userWithoutTenant = User::firstOrCreate(
            ['email' => 'sin.tenant@test.com'],
            [
                'name' => 'Usuario Sin Tenant',
                'password' => $password,
                'tenant_id' => null,
                'is_admin' => false,
                'is_tenant_admin' => false,
            ]
        );
        
        // 4. Segundo admin de tenant para empresa B
        $tenantAdmin2 = User::firstOrCreate(
            ['email' => 'admin.tenant2@test.com'],
            [
                'name' => 'Admin de Tenant B',
                'password' => $password,
                'tenant_id' => $tenant2->id,
                'is_admin' => false,
                'is_tenant_admin' => true,
            ]
        );
        
        $this->info("\n✅ USUARIOS DE PRUEBA CREADOS:");
        $this->info("------------------------------");
        $this->info("1. Usuario Regular:");
        $this->info("   Email: usuario.regular@test.com");
        $this->info("   Password: password123");
        $this->info("   Tenant: {$tenant1->name}");
        $this->info("   Permisos: Usuario normal");
        
        $this->info("\n2. Admin de Tenant A:");
        $this->info("   Email: admin.tenant@test.com");
        $this->info("   Password: password123");
        $this->info("   Tenant: {$tenant1->name}");
        $this->info("   Permisos: Administrador de organización");
        
        $this->info("\n3. Admin de Tenant B:");
        $this->info("   Email: admin.tenant2@test.com");
        $this->info("   Password: password123");
        $this->info("   Tenant: {$tenant2->name}");
        $this->info("   Permisos: Administrador de organización");
        
        $this->info("\n4. Usuario Sin Tenant:");
        $this->info("   Email: sin.tenant@test.com");
        $this->info("   Password: password123");
        $this->info("   Tenant: Ninguno");
        $this->info("   Permisos: Sin permisos (caso edge)");
        
        $this->info("\n5. Admin Global (ya existente):");
        $this->info("   Email: paguero@digito.pe");
        $this->info("   Permisos: Administrador global");
        
        $this->info("\n🎯 PRUEBAS RECOMENDADAS:");
        $this->info("========================");
        $this->info("1. Acceder con admin global al panel de administración");
        $this->info("2. Intentar acceder con usuario regular al panel admin (debe ser redirigido)");
        $this->info("3. Acceder con admin de tenant al panel de su organización");
        $this->info("4. Verificar que admin de tenant A no puede ver datos de tenant B");
        $this->info("5. Probar usuario sin tenant (debe mostrar error)");
        
        return 0;
    }
}
