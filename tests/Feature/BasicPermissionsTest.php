<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

class BasicPermissionsTest extends TestCase
{
    /** @test */
    public function admin_has_access_to_admin_panel()
    {
        // Encuentra el admin global que ya creamos con el seeder
        $admin = User::where('email', 'admin@test.com')->first();
        $this->actingAs($admin);
        
        $response = $this->get('/admin');
        // Debería poder acceder
        $response->assertStatus(200);
    }

    /** @test */
    public function tenant_admin_is_redirected_from_admin_panel()
    {
        // Encuentra el admin de tenant que ya creamos con el seeder
        $tenantAdmin = User::where('email', 'admin-org1@test.com')->first();
        $this->actingAs($tenantAdmin);
        
        $response = $this->get('/admin');
        // Debería ser redirigido
        $response->assertStatus(302);
    }

    /** @test */
    public function normal_user_is_redirected_from_admin_panel()
    {
        // Encuentra el usuario normal que ya creamos con el seeder
        $user = User::where('email', 'user-org1@test.com')->first();
        $this->actingAs($user);
        
        $response = $this->get('/admin');
        // Debería ser redirigido
        $response->assertStatus(302);
    }

    /** @test */
    public function tenant_user_can_access_own_tenant()
    {
        // Encuentra el usuario y su tenant
        $user = User::where('email', 'user-org1@test.com')->first();
        $tenant = Tenant::find($user->tenant_id);
        
        $this->actingAs($user);
        
        // Primero verificamos que el middleware de tenant permita el acceso
        $response = $this->get('/cliente/' . $tenant->slug);
        // Puede ser 200 o 302 (redirección) pero no debe ser 403 (prohibido)
        $this->assertNotEquals(403, $response->getStatusCode());
        
        // Verificamos el acceso a una vista específica del tenant
        $response = $this->get('/cliente/' . $tenant->slug . '/power-bi/dashboards');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function tenant_user_cannot_access_different_tenant()
    {
        // Encuentra al usuario del tenant 1
        $user = User::where('email', 'user-org1@test.com')->first();
        
        // Encuentra el tenant 2
        $otherTenant = Tenant::where('slug', 'org-dos')->first();
        
        $this->actingAs($user);
        
        // En el contexto de Filament, intentar acceder a otro tenant puede resultar en
        // un 404 (no encontrado) en lugar de 403 (prohibido) dependiendo de cómo esté configurado
        $response = $this->get('/cliente/' . $otherTenant->slug);
        
        // Lo importante es verificar que NO pueda acceder (código 40x)
        $this->assertTrue(in_array($response->getStatusCode(), [403, 404]));
    }

    /** @test */
    public function admin_can_access_any_tenant()
    {
        // Encuentra al admin global
        $admin = User::where('email', 'admin@test.com')->first();
        
        // Encuentra los dos tenants
        $tenant1 = Tenant::where('slug', 'org-uno')->first();
        $tenant2 = Tenant::where('slug', 'org-dos')->first();
        
        $this->actingAs($admin);
        
        // Para cada tenant, verificamos que no se produzca un error de acceso
        // Primero tenant 1
        $response1 = $this->get('/cliente/' . $tenant1->slug . '/power-bi/dashboards');
        $this->assertNotEquals(403, $response1->getStatusCode(), 'El admin no puede acceder al tenant 1');
        
        // Luego tenant 2
        $response2 = $this->get('/cliente/' . $tenant2->slug . '/power-bi/dashboards');
        $this->assertNotEquals(403, $response2->getStatusCode(), 'El admin no puede acceder al tenant 2');
    }
}
