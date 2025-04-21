<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant;
use App\Models\PowerBiDashboard;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestTenantAccessSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para crear datos de prueba que cubran todos los escenarios de acceso a tenants.
     *
     * @return void
     */
    public function run()
    {
        // Limpiar datos existentes para evitar duplicados
        $this->cleanData();
        
        // 1. Crear tenants de prueba
        $tenants = $this->createTenants();
        
        // 2. Crear usuarios con diferentes roles
        $this->createUsers($tenants);
        
        // 3. Crear dashboards para cada tenant
        $this->createDashboards($tenants);
        
        $this->command->info('Datos de prueba creados exitosamente. A continuación se muestran los usuarios para pruebas:');
        $this->showTestAccounts();
    }
    
    /**
     * Elimina datos existentes para evitar duplicados
     */
    private function cleanData()
    {
        // Solo eliminar los registros creados por este seeder
        // Puedes ajustar según tus necesidades
        User::where('email', 'like', '%@test.com')->delete();
        Tenant::where('name', 'like', 'Test Tenant%')->delete();
        PowerBiDashboard::where('title', 'like', 'Test Dashboard%')->delete();
    }
    
    /**
     * Crea tenants de prueba
     */
    private function createTenants()
    {
        $tenants = [];
        
        // Tenant 1 - Activo y con dashboards
        $tenants[] = Tenant::create([
            'name' => 'Test Tenant Active',
            'slug' => 'test-tenant-active',
            'is_active' => true,
        ]);
        
        // Tenant 2 - Activo y con dashboards
        $tenants[] = Tenant::create([
            'name' => 'Test Tenant Secondary',
            'slug' => 'test-tenant-secondary',
            'is_active' => true,
        ]);
        
        // Tenant 3 - Inactivo
        $tenants[] = Tenant::create([
            'name' => 'Test Tenant Inactive',
            'slug' => 'test-tenant-inactive',
            'is_active' => false,
        ]);
        
        return $tenants;
    }
    
    /**
     * Crea usuarios con diferentes roles
     */
    private function createUsers($tenants)
    {
        // Password común para facilitar pruebas
        $password = Hash::make('password');
        
        // 1. Administrador global (acceso a todos los tenants)
        User::create([
            'name' => 'Global Admin',
            'email' => 'admin@test.com',
            'password' => $password,
            'is_admin' => true,
            'is_tenant_admin' => false,
            'tenant_id' => null,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        
        // 2. Administrador del primer tenant
        User::create([
            'name' => 'Tenant 1 Admin',
            'email' => 'tenant1_admin@test.com',
            'password' => $password,
            'is_admin' => false,
            'is_tenant_admin' => true,
            'tenant_id' => $tenants[0]->id,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        
        // 3. Administrador del segundo tenant
        User::create([
            'name' => 'Tenant 2 Admin',
            'email' => 'tenant2_admin@test.com',
            'password' => $password,
            'is_admin' => false,
            'is_tenant_admin' => true,
            'tenant_id' => $tenants[1]->id,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        
        // 4. Usuario regular del primer tenant
        User::create([
            'name' => 'Tenant 1 User',
            'email' => 'tenant1_user@test.com',
            'password' => $password,
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => $tenants[0]->id,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        
        // 5. Usuario regular del segundo tenant
        User::create([
            'name' => 'Tenant 2 User',
            'email' => 'tenant2_user@test.com',
            'password' => $password,
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => $tenants[1]->id,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        
        // 6. Usuario del tenant inactivo
        User::create([
            'name' => 'Inactive Tenant User',
            'email' => 'inactive_tenant_user@test.com',
            'password' => $password,
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => $tenants[2]->id,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        
        // 7. Usuario sin tenant asignado
        User::create([
            'name' => 'No Tenant User',
            'email' => 'no_tenant_user@test.com',
            'password' => $password,
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => null,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
    }
    
    /**
     * Crea dashboards para cada tenant
     */
    private function createDashboards($tenants)
    {
        // Dashboards para el primer tenant
        PowerBiDashboard::create([
            'title' => 'Test Dashboard 1',
            'description' => 'Dashboard de prueba para el tenant 1',
            'embed_url' => 'https://app.powerbi.com/reportEmbed?reportId=test1',
            'is_active' => true,
            'order' => 1,
        ])->tenants()->attach($tenants[0]->id);
        
        PowerBiDashboard::create([
            'title' => 'Test Dashboard 2',
            'description' => 'Dashboard de prueba adicional para el tenant 1',
            'embed_url' => 'https://app.powerbi.com/reportEmbed?reportId=test2',
            'is_active' => true,
            'order' => 2,
        ])->tenants()->attach($tenants[0]->id);
        
        // Dashboard para el segundo tenant
        PowerBiDashboard::create([
            'title' => 'Test Dashboard 3',
            'description' => 'Dashboard de prueba para el tenant 2',
            'embed_url' => 'https://app.powerbi.com/reportEmbed?reportId=test3',
            'is_active' => true,
            'order' => 1,
        ])->tenants()->attach($tenants[1]->id);
        
        // Dashboard compartido entre tenant 1 y 2
        $sharedDashboard = PowerBiDashboard::create([
            'title' => 'Test Dashboard Shared',
            'description' => 'Dashboard compartido entre tenant 1 y 2',
            'embed_url' => 'https://app.powerbi.com/reportEmbed?reportId=testShared',
            'is_active' => true,
            'order' => 3,
        ]);
        
        $sharedDashboard->tenants()->attach([$tenants[0]->id, $tenants[1]->id]);
        
        // Dashboard inactivo para tenant 1
        PowerBiDashboard::create([
            'title' => 'Test Dashboard Inactive',
            'description' => 'Dashboard inactivo para el tenant 1',
            'embed_url' => 'https://app.powerbi.com/reportEmbed?reportId=testInactive',
            'is_active' => false,
            'order' => 4,
        ])->tenants()->attach($tenants[0]->id);
    }
    
    /**
     * Muestra la información de las cuentas creadas
     */
    private function showTestAccounts()
    {
        $this->command->info('1. Global Admin: admin@test.com / password');
        $this->command->info('2. Tenant 1 Admin: tenant1_admin@test.com / password');
        $this->command->info('3. Tenant 2 Admin: tenant2_admin@test.com / password');
        $this->command->info('4. Tenant 1 User: tenant1_user@test.com / password');
        $this->command->info('5. Tenant 2 User: tenant2_user@test.com / password');
        $this->command->info('6. Inactive Tenant User: inactive_tenant_user@test.com / password');
        $this->command->info('7. No Tenant User: no_tenant_user@test.com / password');
    }
}
