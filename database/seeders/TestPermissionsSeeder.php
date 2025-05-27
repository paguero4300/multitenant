<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\PowerBiDashboard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Creando datos de prueba para sistema de permisos...');

        // Crear tenants de prueba
        $tenantA = Tenant::factory()->withNameAndSlug('Test Company A', 'test-company-a')->create();
        $tenantB = Tenant::factory()->withNameAndSlug('Test Company B', 'test-company-b')->create();
        $tenantC = Tenant::factory()->withNameAndSlug('Test Company C', 'test-company-c')->create();

        $this->command->info("✅ Tenants creados: {$tenantA->name}, {$tenantB->name}, {$tenantC->name}");

        // Crear usuarios de prueba
        $this->createTestUsers($tenantA, $tenantB, $tenantC);

        // Crear dashboards de prueba
        $this->createTestDashboards($tenantA, $tenantB, $tenantC);

        $this->command->info('🎉 Datos de prueba creados exitosamente!');
    }

    private function createTestUsers($tenantA, $tenantB, $tenantC): void
    {
        // 1. Administrador Global
        $globalAdmin = User::factory()->globalAdmin()->create([
            'name' => 'Test Global Admin',
            'email' => 'test.global.admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 2. Administradores de Tenant
        $tenantAdminA = User::factory()->tenantAdmin($tenantA->id)->create([
            'name' => 'Test Tenant Admin A',
            'email' => 'test.tenant.admin.a@example.com',
            'password' => Hash::make('password123'),
        ]);

        $tenantAdminB = User::factory()->tenantAdmin($tenantB->id)->create([
            'name' => 'Test Tenant Admin B',
            'email' => 'test.tenant.admin.b@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 3. Usuarios Regulares
        $regularUserA1 = User::factory()->regularUser($tenantA->id)->create([
            'name' => 'Test Regular User A1',
            'email' => 'test.regular.a1@example.com',
            'password' => Hash::make('password123'),
        ]);

        $regularUserA2 = User::factory()->regularUser($tenantA->id)->create([
            'name' => 'Test Regular User A2',
            'email' => 'test.regular.a2@example.com',
            'password' => Hash::make('password123'),
        ]);

        $regularUserB1 = User::factory()->regularUser($tenantB->id)->create([
            'name' => 'Test Regular User B1',
            'email' => 'test.regular.b1@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 4. Usuario sin tenant (caso edge)
        $orphanUser = User::factory()->withoutTenant()->create([
            'name' => 'Test Orphan User',
            'email' => 'test.orphan@example.com',
            'password' => Hash::make('password123'),
        ]);

        // 5. Usuario con acceso a múltiples tenants
        $multiTenantUser = User::factory()->regularUser($tenantA->id)->create([
            'name' => 'Test Multi-Tenant User',
            'email' => 'test.multi.tenant@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Asignar acceso adicional al usuario multi-tenant
        $multiTenantUser->additionalTenants()->attach([$tenantB->id, $tenantC->id]);

        $this->command->info('✅ Usuarios de prueba creados:');
        $this->command->info("  - Global Admin: {$globalAdmin->email}");
        $this->command->info("  - Tenant Admin A: {$tenantAdminA->email}");
        $this->command->info("  - Tenant Admin B: {$tenantAdminB->email}");
        $this->command->info("  - Regular Users: {$regularUserA1->email}, {$regularUserA2->email}, {$regularUserB1->email}");
        $this->command->info("  - Orphan User: {$orphanUser->email}");
        $this->command->info("  - Multi-Tenant User: {$multiTenantUser->email}");
    }

    private function createTestDashboards($tenantA, $tenantB, $tenantC): void
    {
        // Dashboards para Tenant A
        PowerBiDashboard::factory()->count(3)->forTenant($tenantA->id)->testDashboard()->create();
        PowerBiDashboard::factory()->forTenant($tenantA->id)->withTitle('Dashboard A - Sin Descripción')->withoutDescription()->create();
        PowerBiDashboard::factory()->forTenant($tenantA->id)->withTitle('Dashboard A - Sin URL')->withoutEmbedUrl()->create();

        // Dashboards para Tenant B
        PowerBiDashboard::factory()->count(2)->forTenant($tenantB->id)->testDashboard()->create();
        PowerBiDashboard::factory()->forTenant($tenantB->id)->withTitle('Dashboard B - Inactivo')->inactive()->create();

        // Dashboards para Tenant C
        PowerBiDashboard::factory()->count(1)->forTenant($tenantC->id)->testDashboard()->create();

        $this->command->info('✅ Dashboards de prueba creados:');
        $this->command->info("  - Tenant A: 5 dashboards");
        $this->command->info("  - Tenant B: 3 dashboards");
        $this->command->info("  - Tenant C: 1 dashboard");
    }
}
