<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminRouteAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecuta el seeder para tener datos de prueba
        $this->seed();
    }

    /** @test */
    public function admin_global_can_access_admin_routes()
    {
        $admin = User::where('email', 'admin@test.com')->first();
        
        $this->actingAs($admin);
        
        // Verifica acceso a varias rutas del panel admin
        $response = $this->get('/admin');
        $response->assertStatus(200);
        
        $response = $this->get('/admin/power-bi-dashboards');
        $response->assertStatus(200);
        
        $response = $this->get('/admin/tenants');
        $response->assertStatus(200);
        
        $response = $this->get('/admin/users');
        $response->assertStatus(200);
    }

    /** @test */
    public function tenant_admin_is_redirected_from_admin_routes()
    {
        $tenantAdmin = User::where('email', 'admin-org1@test.com')->first();
        $tenant = Tenant::where('id', $tenantAdmin->tenant_id)->first();
        
        $this->actingAs($tenantAdmin);
        
        // Intenta acceder al panel admin
        $response = $this->get('/admin');
        
        // Debe ser redirigido
        $response->assertStatus(302);
        $response->assertRedirect('/cliente/' . $tenant->slug);
        
        // Verifica que el mensaje de error esté en la sesión
        $response->assertSessionHas('error', 'Como administrador de organización, solo puedes acceder al panel de tu organización');
    }

    /** @test */
    public function normal_user_is_redirected_from_admin_routes()
    {
        $user = User::where('email', 'user-org1@test.com')->first();
        $tenant = Tenant::where('id', $user->tenant_id)->first();
        
        $this->actingAs($user);
        
        // Intenta acceder al panel admin
        $response = $this->get('/admin');
        
        // Debe ser redirigido
        $response->assertStatus(302);
        $response->assertRedirect('/cliente/' . $tenant->slug);
        
        // Verifica que el mensaje de error esté en la sesión
        $response->assertSessionHas('error', 'No tienes permisos para acceder al panel de administrador');
    }

    /** @test */
    public function dashboard_operations_respect_user_roles()
    {
        // 1. Admin global (puede hacer todo)
        $admin = User::where('email', 'admin@test.com')->first();
        $this->actingAs($admin);
        
        // Debe poder acceder a la creación
        $response = $this->get('/admin/power-bi-dashboards/create');
        $response->assertStatus(200);
        
        // 2. Admin de tenant (puede crear/editar en su tenant)
        $tenantAdmin = User::where('email', 'admin-org1@test.com')->first();
        $tenant = Tenant::where('id', $tenantAdmin->tenant_id)->first();
        $this->actingAs($tenantAdmin);
        
        // Debe poder acceder a la creación en su tenant
        $response = $this->get('/cliente/' . $tenant->slug . '/power-bi-dashboards/create');
        $response->assertStatus(200);
        
        // 3. Usuario normal (no puede crear/editar)
        $user = User::where('email', 'user-org1@test.com')->first();
        $tenant = Tenant::where('id', $user->tenant_id)->first();
        $this->actingAs($user);
        
        // No debe poder acceder a la creación
        $response = $this->get('/cliente/' . $tenant->slug . '/power-bi-dashboards/create');
        
        // Debe ser rechazado (403) o redirigido (302)
        $this->assertTrue(
            $response->status() == 403 || $response->status() == 302,
            'Usuario normal no debería poder acceder a la creación'
        );
    }
}
