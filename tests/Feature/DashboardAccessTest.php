<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\PowerBiDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $tenantA;
    protected $tenantB;
    protected $globalAdmin;
    protected $tenantAdminA;
    protected $tenantAdminB;
    protected $regularUserA;
    protected $regularUserB;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear tenants
        $this->tenantA = Tenant::factory()->withNameAndSlug('Company A', 'company-a')->create();
        $this->tenantB = Tenant::factory()->withNameAndSlug('Company B', 'company-b')->create();

        // Crear usuarios
        $this->globalAdmin = User::factory()->globalAdmin()->create(['email' => 'global@test.com']);
        $this->tenantAdminA = User::factory()->tenantAdmin($this->tenantA->id)->create(['email' => 'admin.a@test.com']);
        $this->tenantAdminB = User::factory()->tenantAdmin($this->tenantB->id)->create(['email' => 'admin.b@test.com']);
        $this->regularUserA = User::factory()->regularUser($this->tenantA->id)->create(['email' => 'user.a@test.com']);
        $this->regularUserB = User::factory()->regularUser($this->tenantB->id)->create(['email' => 'user.b@test.com']);

        // Crear dashboards
        $this->dashboardA1 = PowerBiDashboard::factory()->forTenant($this->tenantA->id)->create(['title' => 'Dashboard A1']);
        $this->dashboardA2 = PowerBiDashboard::factory()->forTenant($this->tenantA->id)->create(['title' => 'Dashboard A2']);
        $this->dashboardB1 = PowerBiDashboard::factory()->forTenant($this->tenantB->id)->create(['title' => 'Dashboard B1']);
    }

    /** @test */
    public function global_admin_can_access_all_dashboards()
    {
        $this->actingAs($this->globalAdmin);

        // Verificar acceso a dashboards de tenant A
        $response = $this->get("/admin/power-bi-dashboards");
        $response->assertStatus(200);

        // Verificar que puede ver dashboards de todos los tenants
        $allDashboards = PowerBiDashboard::all();
        $this->assertCount(3, $allDashboards);
        
        // El admin global debería poder acceder a cualquier dashboard
        foreach ($allDashboards as $dashboard) {
            $this->assertTrue($this->globalAdmin->canAccessTenant($dashboard->tenant));
        }
    }

    /** @test */
    public function tenant_admin_can_only_access_own_tenant_dashboards()
    {
        $this->actingAs($this->tenantAdminA);

        // Verificar acceso a su tenant
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);

        // Verificar que NO puede acceder a dashboards de otro tenant
        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $response->assertStatus(403); // O redirección según middleware

        // Verificar lógica de acceso
        $this->assertTrue($this->tenantAdminA->canAccessTenant($this->tenantA));
        $this->assertFalse($this->tenantAdminA->canAccessTenant($this->tenantB));
    }

    /** @test */
    public function regular_user_can_only_access_own_tenant_dashboards()
    {
        $this->actingAs($this->regularUserA);

        // Verificar acceso a su tenant
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);

        // Verificar que NO puede acceder a dashboards de otro tenant
        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $response->assertStatus(403); // O redirección según middleware

        // Verificar lógica de acceso
        $this->assertTrue($this->regularUserA->canAccessTenant($this->tenantA));
        $this->assertFalse($this->regularUserA->canAccessTenant($this->tenantB));
    }

    /** @test */
    public function user_without_tenant_cannot_access_any_dashboards()
    {
        $orphanUser = User::factory()->withoutTenant()->create();
        $this->actingAs($orphanUser);

        // No debería poder acceder a ningún tenant
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(403);

        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $response->assertStatus(403);

        // Verificar lógica de acceso
        $this->assertFalse($orphanUser->canAccessTenant($this->tenantA));
        $this->assertFalse($orphanUser->canAccessTenant($this->tenantB));
    }

    /** @test */
    public function dashboard_creation_respects_tenant_permissions()
    {
        // Admin global puede crear dashboards para cualquier tenant
        $this->actingAs($this->globalAdmin);
        $response = $this->post('/admin/power-bi-dashboards', [
            'title' => 'New Dashboard for A',
            'description' => 'Test dashboard',
            'tenant_id' => $this->tenantA->id,
            'embed_url' => 'https://test.com/embed',
            'report_id' => 'test-report-id',
            'workspace_id' => 'test-workspace-id',
        ]);
        $response->assertStatus(302); // Redirección después de crear

        // Tenant admin solo puede crear para su tenant
        $this->actingAs($this->tenantAdminA);
        
        // Debería poder crear para su tenant
        $dashboardsCountBefore = PowerBiDashboard::where('tenant_id', $this->tenantA->id)->count();
        
        // Verificar que el tenant admin tiene acceso a su tenant
        $this->assertTrue($this->tenantAdminA->canAccessTenant($this->tenantA));
        
        // Verificar que NO tiene acceso a otro tenant
        $this->assertFalse($this->tenantAdminA->canAccessTenant($this->tenantB));
    }

    /** @test */
    public function dashboard_filtering_works_correctly()
    {
        // Verificar que los dashboards se filtran correctamente por tenant
        $dashboardsA = PowerBiDashboard::where('tenant_id', $this->tenantA->id)->get();
        $dashboardsB = PowerBiDashboard::where('tenant_id', $this->tenantB->id)->get();

        $this->assertCount(2, $dashboardsA);
        $this->assertCount(1, $dashboardsB);

        // Verificar que cada dashboard pertenece al tenant correcto
        foreach ($dashboardsA as $dashboard) {
            $this->assertEquals($this->tenantA->id, $dashboard->tenant_id);
        }

        foreach ($dashboardsB as $dashboard) {
            $this->assertEquals($this->tenantB->id, $dashboard->tenant_id);
        }
    }

    /** @test */
    public function dashboard_with_null_fields_handled_correctly()
    {
        // Crear dashboard sin descripción
        $dashboardWithoutDesc = PowerBiDashboard::factory()
            ->forTenant($this->tenantA->id)
            ->withoutDescription()
            ->create();

        // Crear dashboard sin URL de embed
        $dashboardWithoutUrl = PowerBiDashboard::factory()
            ->forTenant($this->tenantA->id)
            ->withoutEmbedUrl()
            ->create();

        $this->assertNull($dashboardWithoutDesc->description);
        $this->assertNull($dashboardWithoutUrl->embed_url);

        // Verificar que no causan errores al acceder
        $this->actingAs($this->regularUserA);
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);
    }

    /** @test */
    public function unauthorized_dashboard_access_is_blocked()
    {
        $this->actingAs($this->regularUserA);

        // Intentar acceder directamente a un dashboard de otro tenant
        $dashboardB = $this->dashboardB1;
        
        // Verificar que el usuario no puede acceder al tenant del dashboard
        $this->assertFalse($this->regularUserA->canAccessTenant($dashboardB->tenant));
        
        // Intentar acceder a la ruta del otro tenant debería fallar
        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $response->assertStatus(403);
    }

    /** @test */
    public function multi_tenant_user_can_access_multiple_tenants()
    {
        $multiTenantUser = User::factory()->regularUser($this->tenantA->id)->create();
        $multiTenantUser->additionalTenants()->attach($this->tenantB->id);

        $this->actingAs($multiTenantUser);

        // Debería poder acceder a ambos tenants
        $this->assertTrue($multiTenantUser->canAccessTenant($this->tenantA));
        $this->assertTrue($multiTenantUser->canAccessTenant($this->tenantB));

        // Verificar acceso a ambas rutas
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);

        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $response->assertStatus(200);
    }
}
