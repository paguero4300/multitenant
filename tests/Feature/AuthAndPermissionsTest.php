<?php

namespace Tests\Feature;

use App\Models\PowerBiDashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthAndPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecuta el seeder para tener datos de prueba
        $this->seed();
    }

    /** @test */
    public function admin_can_login_to_admin_panel()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();
    }

    /** @test */
    public function tenant_admin_cannot_login_to_admin_panel()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin-org1@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();
        
        // Intenta acceder al panel admin después de login
        $response = $this->get('/admin');
        
        // Debe ser redirigido
        $response->assertStatus(302);
    }

    /** @test */
    public function tenant_user_can_login_to_tenant_panel()
    {
        $tenant = Tenant::where('slug', 'org-uno')->first();
        
        $response = $this->post('/login', [
            'email' => 'user-org1@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        
        // Intenta acceder al panel de tenant
        $response = $this->get('/cliente/' . $tenant->slug);
        
        // Debe tener acceso
        $response->assertStatus(200);
    }

    /** @test */
    public function tenant_user_cannot_access_different_tenant()
    {
        $tenant1 = Tenant::where('slug', 'org-uno')->first();
        $tenant2 = Tenant::where('slug', 'org-dos')->first();
        
        // Login con usuario del tenant 1
        $this->post('/login', [
            'email' => 'user-org1@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        
        // Intenta acceder al panel del tenant 2
        $response = $this->get('/cliente/' . $tenant2->slug);
        
        // Debe ser rechazado
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_any_tenant()
    {
        $tenant1 = Tenant::where('slug', 'org-uno')->first();
        $tenant2 = Tenant::where('slug', 'org-dos')->first();
        
        // Login con admin global
        $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        
        // Intenta acceder al panel del tenant 1
        $response = $this->get('/cliente/' . $tenant1->slug);
        $response->assertStatus(200);
        
        // Intenta acceder al panel del tenant 2
        $response = $this->get('/cliente/' . $tenant2->slug);
        $response->assertStatus(200);
    }
}
