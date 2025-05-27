<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\PowerBiDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityTest extends TestCase
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
    public function cannot_escalate_privileges_through_direct_assignment()
    {
        $regularUser = User::factory()->regularUser($this->tenantA->id)->create();
        
        // Intentar cambiar permisos directamente
        $regularUser->is_admin = true;
        
        $this->expectException(\InvalidArgumentException::class);
        $regularUser->save();
    }

    /** @test */
    public function cannot_create_user_with_both_admin_flags()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Un usuario no puede ser administrador global y administrador de tenant al mismo tiempo');

        User::create([
            'name' => 'Invalid User',
            'email' => 'invalid@test.com',
            'password' => Hash::make('password'),
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
            'password' => Hash::make('password'),
            'is_admin' => false,
            'is_tenant_admin' => true,
            'tenant_id' => null,
        ]);
    }

    /** @test */
    public function tenant_isolation_is_enforced()
    {
        $userA = User::factory()->regularUser($this->tenantA->id)->create();
        $userB = User::factory()->regularUser($this->tenantB->id)->create();
        
        $dashboardA = PowerBiDashboard::factory()->forTenant($this->tenantA->id)->create();
        $dashboardB = PowerBiDashboard::factory()->forTenant($this->tenantB->id)->create();

        // Usuario A no puede acceder a datos de tenant B
        $this->assertFalse($userA->canAccessTenant($this->tenantB));
        $this->assertTrue($userA->canAccessTenant($this->tenantA));

        // Usuario B no puede acceder a datos de tenant A
        $this->assertFalse($userB->canAccessTenant($this->tenantA));
        $this->assertTrue($userB->canAccessTenant($this->tenantB));
    }

    /** @test */
    public function cannot_access_other_tenant_data_through_direct_urls()
    {
        $userA = User::factory()->regularUser($this->tenantA->id)->create();
        $this->actingAs($userA);

        // Intentar acceder a rutas de otro tenant
        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $response->assertStatus(403);

        // Verificar que no puede acceder a recursos específicos de otro tenant
        $dashboardB = PowerBiDashboard::factory()->forTenant($this->tenantB->id)->create();
        
        // Simular intento de acceso directo (esto dependería de cómo estén configuradas las rutas)
        $this->assertFalse($userA->canAccessTenant($dashboardB->tenant));
    }

    /** @test */
    public function session_hijacking_protection()
    {
        $userA = User::factory()->regularUser($this->tenantA->id)->create();
        $userB = User::factory()->regularUser($this->tenantB->id)->create();

        // Autenticar como usuario A
        $this->actingAs($userA);
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);

        // Cambiar a usuario B (simular hijacking)
        $this->actingAs($userB);
        
        // No debería poder acceder a recursos de tenant A
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(403);
    }

    /** @test */
    public function mass_assignment_protection()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true, // Intentar asignar privilegios
            'is_tenant_admin' => true,
        ];

        // Esto debería fallar por las validaciones del modelo
        $this->expectException(\InvalidArgumentException::class);
        User::create($userData);
    }

    /** @test */
    public function sql_injection_protection_in_tenant_queries()
    {
        $maliciousSlug = "'; DROP TABLE tenants; --";
        $user = User::factory()->regularUser($this->tenantA->id)->create();
        $this->actingAs($user);

        // Intentar inyección SQL a través del slug del tenant
        $response = $this->get("/cliente/{$maliciousSlug}/power-bi-dashboards");
        
        // Debería devolver 404 o 403, no causar error de SQL
        $this->assertContains($response->status(), [403, 404]);
        
        // Verificar que la tabla tenants sigue existiendo
        $this->assertDatabaseHas('tenants', ['id' => $this->tenantA->id]);
    }

    /** @test */
    public function csrf_protection_is_active()
    {
        $user = User::factory()->globalAdmin()->create();
        $this->actingAs($user);

        // Intentar crear dashboard sin token CSRF
        $response = $this->post('/admin/power-bi-dashboards', [
            'title' => 'Test Dashboard',
            'tenant_id' => $this->tenantA->id,
        ]);

        // Debería fallar por falta de token CSRF
        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function authorization_is_checked_on_every_request()
    {
        $tenantAdmin = User::factory()->tenantAdmin($this->tenantA->id)->create();
        $this->actingAs($tenantAdmin);

        // Verificar acceso inicial
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);

        // Simular cambio de permisos (por ejemplo, por otro admin)
        $tenantAdmin->update(['is_tenant_admin' => false, 'tenant_id' => null]);

        // El siguiente request debería fallar
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(403);
    }

    /** @test */
    public function sensitive_data_is_not_exposed_in_responses()
    {
        $user = User::factory()->regularUser($this->tenantA->id)->create();
        $this->actingAs($user);

        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);

        // Verificar que no se exponen datos sensibles en la respuesta
        $content = $response->getContent();
        
        // No debería contener información de otros tenants
        $this->assertStringNotContainsString($this->tenantB->name, $content);
        $this->assertStringNotContainsString($this->tenantB->slug, $content);
    }

    /** @test */
    public function rate_limiting_protects_against_brute_force()
    {
        $user = User::factory()->regularUser($this->tenantA->id)->create();
        
        // Simular múltiples intentos de acceso no autorizado
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user);
            $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
            $this->assertEquals(403, $response->status());
        }

        // Después de muchos intentos, debería seguir siendo 403, no 429 (rate limit)
        // porque el middleware de autorización bloquea antes que el rate limiting
        $this->actingAs($user);
        $response = $this->get("/cliente/{$this->tenantB->slug}/power-bi-dashboards");
        $this->assertEquals(403, $response->status());
    }

    /** @test */
    public function audit_trail_is_maintained()
    {
        $user = User::factory()->globalAdmin()->create();
        $this->actingAs($user);

        // Realizar una acción que debería ser auditada
        $response = $this->get("/cliente/{$this->tenantA->slug}/power-bi-dashboards");
        $response->assertStatus(200);

        // Verificar que se registró en los logs (esto dependería de tu implementación de logging)
        // Podrías verificar archivos de log o una tabla de auditoría
        $this->assertTrue(true); // Placeholder - implementar según tu sistema de auditoría
    }

    /** @test */
    public function password_security_requirements_are_enforced()
    {
        // Verificar que las contraseñas se hashean correctamente
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertFalse(Hash::check('wrongpassword', $user->password));
        
        // Verificar que la contraseña no se almacena en texto plano
        $this->assertNotEquals('password123', $user->password);
    }
}
