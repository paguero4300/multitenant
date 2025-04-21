<?php

namespace Database\Seeders;

use App\Models\PowerBiDashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PowerBiTestSeeder extends Seeder
{
    /**
     * Ejecuta los seeders para pruebas completas del sistema.
     * Crea usuarios, tenants y dashboards de diferentes tipos.
     */
    public function run(): void
    {
        $this->command->info('Creando datos de prueba para el sistema de dashboards Power BI...');
        
        // Limpiar tablas antes de insertar nuevos datos
        $this->command->info('Limpiando tablas existentes...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        PowerBiDashboard::truncate();
        // Solo eliminamos relaciones, no los tenants/usuarios existentes
        DB::table('power_bi_dashboard_tenant')->truncate();
        // Si queremos empezar desde cero, descomenta estas líneas:
        // User::where('email', 'like', '%@test.com')->delete();
        // Tenant::where('slug', 'like', 'org-%')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->command->info('Tablas limpiadas correctamente.');

        // 1. Crear administrador global (acceso a todo el sistema)
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
            'name' => 'Admin Global',
            'password' => Hash::make('password'),
            'is_admin' => 1,
            'is_tenant_admin' => 0,
            'tenant_id' => null,
            ]
        );
        $this->command->info('Usuario administrador global creado: ' . $adminUser->email);

        // 2. Crear tenants de prueba
        $tenant1 = Tenant::updateOrCreate(
            ['slug' => 'org-uno'],
            [
            'name' => 'Organización Uno',
            'is_active' => true,
            ]
        );

        $tenant2 = Tenant::updateOrCreate(
            ['slug' => 'org-dos'],
            [
            'name' => 'Organización Dos',
            'is_active' => true,
            ]
        );
        
        $this->command->info('Tenants creados: ' . $tenant1->name . ', ' . $tenant2->name);

        // 3. Crear administradores de organizaciones
        $tenant1Admin = User::updateOrCreate(
            ['email' => 'admin-org1@test.com'],
            [
            'name' => 'Admin Organización Uno',
            'password' => Hash::make('password'),
            'is_admin' => 0,
            'is_tenant_admin' => 1,
            'tenant_id' => $tenant1->id,
            ]
        );

        $tenant2Admin = User::updateOrCreate(
            ['email' => 'admin-org2@test.com'],
            [
            'name' => 'Admin Organización Dos',
            'password' => Hash::make('password'),
            'is_admin' => 0,
            'is_tenant_admin' => 1,
            'tenant_id' => $tenant2->id,
            ]
        );
        
        $this->command->info('Usuarios administradores de organización creados.');

        // 4. Crear usuarios normales para cada tenant
        $tenant1User = User::updateOrCreate(
            ['email' => 'user-org1@test.com'],
            [
            'name' => 'Usuario Organización Uno',
            'password' => Hash::make('password'),
            'is_admin' => 0,
            'is_tenant_admin' => 0,
            'tenant_id' => $tenant1->id,
            ]
        );

        $tenant2User = User::updateOrCreate(
            ['email' => 'user-org2@test.com'],
            [
            'name' => 'Usuario Organización Dos',
            'password' => Hash::make('password'),
            'is_admin' => 0,
            'is_tenant_admin' => 0,
            'tenant_id' => $tenant2->id,
            ]
        );
        
        $this->command->info('Usuarios normales creados.');

        // 5. Crear dashboards para las organizaciones
        // Dashboards para Tenant 1
        $dashboard1_1 = PowerBiDashboard::create([
            'title' => 'Dashboard Ventas Tenant 1',
            'description' => 'Información de ventas para la Organización Uno',
            'category' => 'ventas',
            'report_id' => 'rep-'.Str::random(8),
            'embed_url' => 'https://app.powerbi.com/reportEmbed?reportId='.Str::random(10),
            'embed_token' => Str::random(32),
            'is_active' => true,
            'thumbnail' => null,
        ]);

        $dashboard1_2 = PowerBiDashboard::create([
            'title' => 'Dashboard Finanzas Tenant 1',
            'description' => 'Información financiera para la Organización Uno',
            'category' => 'finanzas',
            'report_id' => 'rep-'.Str::random(8),
            'embed_url' => 'https://app.powerbi.com/reportEmbed?reportId='.Str::random(10),
            'embed_token' => Str::random(32),
            'is_active' => true,
            'thumbnail' => null,
        ]);
        
        // Dashboards para Tenant 2
        $dashboard2_1 = PowerBiDashboard::create([
            'title' => 'Dashboard Ventas Tenant 2',
            'description' => 'Información de ventas para la Organización Dos',
            'category' => 'ventas',
            'report_id' => 'rep-'.Str::random(8),
            'embed_url' => 'https://app.powerbi.com/reportEmbed?reportId='.Str::random(10),
            'embed_token' => Str::random(32),
            'is_active' => true,
            'thumbnail' => null,
        ]);
        
        $this->command->info('Dashboards creados para los tenants.');

        // 6. Asociar dashboards a tenants
        $dashboard1_1->tenants()->attach($tenant1->id);
        $dashboard1_2->tenants()->attach($tenant1->id);
        $dashboard2_1->tenants()->attach($tenant2->id);
        
        $this->command->info('Dashboards asociados a sus respectivos tenants.');
        
        $this->command->info('Seeder completado correctamente! Usuarios creados para pruebas:');
        $this->command->info('- Admin global: admin@test.com / password');
        $this->command->info('- Admin Org1: admin-org1@test.com / password');
        $this->command->info('- Admin Org2: admin-org2@test.com / password');
        $this->command->info('- Usuario Org1: user-org1@test.com / password');
        $this->command->info('- Usuario Org2: user-org2@test.com / password');
    }
}
