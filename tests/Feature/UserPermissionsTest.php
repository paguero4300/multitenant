<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear tenants de prueba
        $this->tenantA = Tenant::factory()->withNameAndSlug('Company A', 'company-a')->create();
        $this->tenantB = Tenant::factory()->withNameAndSlug('Company B', 'company-b')->create();
    }

    /** @test */
    public function global_admin_user_has_correct_permissions()
    {
        $globalAdmin = User::factory()->globalAdmin()->create();

        // Verificar propiedades
        $this->assertTrue($globalAdmin->is_admin);
        $this->assertFalse($globalAdmin->is_tenant_admin);
        $this->assertNull($globalAdmin->tenant_id);

        // Verificar métodos de permisos
        $this->assertTrue($globalAdmin->isAdmin());
        $this->assertFalse($globalAdmin->isTenantAdmin());

        // Verificar acceso a todos los tenants
        $this->assertTrue($globalAdmin->canAccessTenant($this->tenantA));
        $this->assertTrue($globalAdmin->canAccessTenant($this->tenantB));

        // Verificar getTenants devuelve todos los tenants
        $accessibleTenants = $globalAdmin->getTenants(null);
        $this->assertCount(2, $accessibleTenants);
    }

    /** @test */
    public function tenant_admin_user_has_correct_permissions()
    {
        $tenantAdmin = User::factory()->tenantAdmin($this->tenantA->id)->create();

        // Verificar propiedades
        $this->assertFalse($tenantAdmin->is_admin);
        $this->assertTrue($tenantAdmin->is_tenant_admin);
        $this->assertEquals($this->tenantA->id, $tenantAdmin->tenant_id);

        // Verificar métodos de permisos
        $this->assertFalse($tenantAdmin->isAdmin());
        $this->assertTrue($tenantAdmin->isTenantAdmin());

        // Verificar acceso solo a su tenant
        $this->assertTrue($tenantAdmin->canAccessTenant($this->tenantA));
        $this->assertFalse($tenantAdmin->canAccessTenant($this->tenantB));

        // Verificar getTenants devuelve solo su tenant
        $accessibleTenants = $tenantAdmin->getTenants(null);
        $this->assertCount(1, $accessibleTenants);
        $this->assertEquals($this->tenantA->id, $accessibleTenants->first()->id);
    }

    /** @test */
    public function regular_user_has_correct_permissions()
    {
        $regularUser = User::factory()->regularUser($this->tenantA->id)->create();

        // Verificar propiedades
        $this->assertFalse($regularUser->is_admin);
        $this->assertFalse($regularUser->is_tenant_admin);
        $this->assertEquals($this->tenantA->id, $regularUser->tenant_id);

        // Verificar métodos de permisos
        $this->assertFalse($regularUser->isAdmin());
        $this->assertFalse($regularUser->isTenantAdmin());

        // Verificar acceso solo a su tenant
        $this->assertTrue($regularUser->canAccessTenant($this->tenantA));
        $this->assertFalse($regularUser->canAccessTenant($this->tenantB));

        // Verificar getTenants devuelve solo su tenant
        $accessibleTenants = $regularUser->getTenants(null);
        $this->assertCount(1, $accessibleTenants);
        $this->assertEquals($this->tenantA->id, $accessibleTenants->first()->id);
    }

    /** @test */
    public function user_without_tenant_has_no_access()
    {
        $orphanUser = User::factory()->withoutTenant()->create();

        // Verificar propiedades
        $this->assertFalse($orphanUser->is_admin);
        $this->assertFalse($orphanUser->is_tenant_admin);
        $this->assertNull($orphanUser->tenant_id);

        // Verificar métodos de permisos
        $this->assertFalse($orphanUser->isAdmin());
        $this->assertFalse($orphanUser->isTenantAdmin());

        // Verificar sin acceso a ningún tenant
        $this->assertFalse($orphanUser->canAccessTenant($this->tenantA));
        $this->assertFalse($orphanUser->canAccessTenant($this->tenantB));

        // Verificar getTenants devuelve colección vacía
        $accessibleTenants = $orphanUser->getTenants(null);
        $this->assertCount(0, $accessibleTenants);
    }

    /** @test */
    public function user_with_additional_tenants_has_correct_access()
    {
        $multiTenantUser = User::factory()->regularUser($this->tenantA->id)->create();

        // Asignar acceso adicional al tenant B
        $multiTenantUser->additionalTenants()->attach($this->tenantB->id);

        // Verificar acceso a ambos tenants
        $this->assertTrue($multiTenantUser->canAccessTenant($this->tenantA));
        $this->assertTrue($multiTenantUser->canAccessTenant($this->tenantB));

        // Verificar getTenants devuelve ambos tenants
        $accessibleTenants = $multiTenantUser->getTenants(null);
        $this->assertCount(2, $accessibleTenants);
    }

    /** @test */
    public function cannot_create_user_with_inconsistent_permissions()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Un usuario no puede ser administrador global y administrador de tenant al mismo tiempo');

        User::create([
            'name' => 'Invalid User',
            'email' => 'invalid@test.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
            'is_tenant_admin' => true,
        ]);
    }

    /** @test */
    public function cannot_create_tenant_admin_without_tenant()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Un administrador de tenant debe tener un tenant_id asignado');

        User::create([
            'name' => 'Invalid Tenant Admin',
            'email' => 'invalid.admin@test.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_tenant_admin' => true,
            'tenant_id' => null,
        ]);
    }

    /** @test */
    public function panel_access_logic_works_correctly()
    {
        $globalAdmin = User::factory()->globalAdmin()->create();
        $tenantAdmin = User::factory()->tenantAdmin($this->tenantA->id)->create();
        $regularUser = User::factory()->regularUser($this->tenantA->id)->create();
        $orphanUser = User::factory()->withoutTenant()->create();

        // Verificar lógica de acceso al panel admin (solo admins globales)
        $this->assertTrue($globalAdmin->is_admin);
        $this->assertFalse($tenantAdmin->is_admin);
        $this->assertFalse($regularUser->is_admin);
        $this->assertFalse($orphanUser->is_admin);

        // Verificar lógica de acceso al panel tenant (admins globales, admins de tenant, usuarios con tenant)
        $this->assertTrue($globalAdmin->is_admin || $globalAdmin->is_tenant_admin || $globalAdmin->tenant_id !== null);
        $this->assertTrue($tenantAdmin->is_admin || $tenantAdmin->is_tenant_admin || $tenantAdmin->tenant_id !== null);
        $this->assertTrue($regularUser->is_admin || $regularUser->is_tenant_admin || $regularUser->tenant_id !== null);
        $this->assertFalse($orphanUser->is_admin || $orphanUser->is_tenant_admin || $orphanUser->tenant_id !== null);
    }
}
