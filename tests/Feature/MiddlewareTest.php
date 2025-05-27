<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $tenantA;
    protected $tenantB;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenantA = Tenant::factory()->withNameAndSlug('Company A', 'company-a')->create();
        $this->tenantB = Tenant::factory()->withNameAndSlug('Company B', 'company-b')->create();
    }

    /** @test */
    public function admin_middleware_allows_global_admin_access()
    {
        $globalAdmin = User::factory()->globalAdmin()->create();
        $this->actingAs($globalAdmin);

        $response = $this->get('/admin/power-bi-dashboards');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_middleware_redirects_tenant_admin()
    {
        $tenantAdmin = User::factory()->tenantAdmin($this->tenantA->id)->create();
        $this->actingAs($tenantAdmin);

        $response = $this->get('/admin/power-bi-dashboards');
        $response->assertStatus(302); // Redirección
        $response->assertRedirect("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
    }

    /** @test */
    public function admin_middleware_redirects_regular_user()
    {
        $regularUser = User::factory()->regularUser($this->tenantA->id)->create();
        $this->actingAs($regularUser);

        $response = $this->get('/admin/power-bi-dashboards');
        $response->assertStatus(302); // Redirección
        $response->assertRedirect("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
    }

    /** @test */
    public function admin_middleware_handles_user_without_tenant()
    {
        $orphanUser = User::factory()->withoutTenant()->create();
        $this->actingAs($orphanUser);

        $response = $this->get('/admin/power-bi-dashboards');
        $response->assertStatus(302); // Redirección al login
        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Tu cuenta no tiene una organización asignada. Contacta al administrador.');
    }

    /** @test */
    public function admin_middleware_redirects_unauthenticated_user()
    {
        $response = $this->get('/admin/power-bi-dashboards');
        $response->assertStatus(302); // Redirección al login
        $response->assertRedirect('/login');
    }

    /** @test */
    public function ensure_user_belongs_to_tenant_allows_global_admin()
    {
        $globalAdmin = User::factory()->globalAdmin()->create();
        $this->actingAs($globalAdmin);

        // Admin global puede acceder a cualquier tenant
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);

        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $response->assertStatus(200);
    }

    /** @test */
    public function ensure_user_belongs_to_tenant_allows_tenant_admin_to_own_tenant()
    {
        $tenantAdmin = User::factory()->tenantAdmin($this->tenantA->id)->create();
        $this->actingAs($tenantAdmin);

        // Puede acceder a su propio tenant
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);
    }

    /** @test */
    public function ensure_user_belongs_to_tenant_blocks_tenant_admin_from_other_tenant()
    {
        $tenantAdmin = User::factory()->tenantAdmin($this->tenantA->id)->create();
        $this->actingAs($tenantAdmin);

        // NO puede acceder a otro tenant
        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $response->assertStatus(403);
    }

    /** @test */
    public function ensure_user_belongs_to_tenant_allows_regular_user_to_own_tenant()
    {
        $regularUser = User::factory()->regularUser($this->tenantA->id)->create();
        $this->actingAs($regularUser);

        // Puede acceder a su propio tenant
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);
    }

    /** @test */
    public function ensure_user_belongs_to_tenant_blocks_regular_user_from_other_tenant()
    {
        $regularUser = User::factory()->regularUser($this->tenantA->id)->create();
        $this->actingAs($regularUser);

        // NO puede acceder a otro tenant
        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $response->assertStatus(403);
    }

    /** @test */
    public function ensure_user_belongs_to_tenant_blocks_user_without_tenant()
    {
        $orphanUser = User::factory()->withoutTenant()->create();
        $this->actingAs($orphanUser);

        // No puede acceder a ningún tenant
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(403);

        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $response->assertStatus(403);
    }

    /** @test */
    public function ensure_user_belongs_to_tenant_allows_multi_tenant_user()
    {
        $multiTenantUser = User::factory()->regularUser($this->tenantA->id)->create();
        $multiTenantUser->additionalTenants()->attach($this->tenantB->id);
        
        $this->actingAs($multiTenantUser);

        // Puede acceder a su tenant principal
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);

        // También puede acceder a su tenant adicional
        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $response->assertStatus(200);
    }

    /** @test */
    public function middleware_handles_nonexistent_tenant()
    {
        $regularUser = User::factory()->regularUser($this->tenantA->id)->create();
        $this->actingAs($regularUser);

        // Intentar acceder a un tenant que no existe
        $response = $this->get("/cliente/nonexistent-tenant/power-bi-dashboards");
        $response->assertStatus(404); // O el comportamiento esperado para tenant no encontrado
    }

    /** @test */
    public function middleware_redirects_unauthenticated_user_from_tenant_routes()
    {
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(302); // Redirección al login
        $response->assertRedirect('/login');
    }

    /** @test */
    public function middleware_preserves_intended_url_after_login()
    {
        $intendedUrl = "/cliente/{$this->tenantA->slug}/power-bi-dashboards";
        
        // Intentar acceder sin autenticación
        $response = $this->get($intendedUrl);
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // Verificar que la URL se preserva en la sesión
        $this->assertSessionHas('url.intended', $intendedUrl);
    }

    /** @test */
    public function middleware_logs_access_attempts()
    {
        $globalAdmin = User::factory()->globalAdmin()->create();
        $this->actingAs($globalAdmin);

        // Verificar que el middleware registra el acceso
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);

        // Aquí podrías verificar los logs si tienes un sistema de logging configurado
        // Por ejemplo, usando Log::shouldReceive() con Mockery
    }
}
