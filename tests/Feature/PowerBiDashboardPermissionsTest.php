<?php

namespace Tests\Feature;

use App\Models\PowerBiDashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PowerBiDashboardPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecuta el seeder para tener datos de prueba
        $this->seed();
    }

    /** @test */
    public function admin_can_view_all_dashboards_in_admin_panel()
    {
        // Login con admin global
        $this->actingAs(User::where('email', 'admin@test.com')->first());
        
        // Accede a la lista de dashboards en el panel admin
        $response = $this->get('/admin/power-bi-dashboards');
        
        // Debe poder ver la página
        $response->assertStatus(200);
        
        // Todos los dashboards deben estar disponibles
        $dashboardCount = PowerBiDashboard::count();
        $this->assertTrue($dashboardCount >= 3, 'Debe haber al menos 3 dashboards en el sistema');
    }

    /** @test */
    public function tenant_admin_can_create_dashboard_for_their_tenant()
    {
        $tenant = Tenant::where('slug', 'org-uno')->first();
        $tenantAdmin = User::where('email', 'admin-org1@test.com')->first();
        
        // Login con admin de tenant
        $this->actingAs($tenantAdmin);
        
        // Obtiene el CSRF token para poder hacer la solicitud POST
        $response = $this->get('/cliente/' . $tenant->slug . '/power-bi-dashboards/create');
        $response->assertStatus(200);
        
        // Cuenta dashboards antes de crear uno nuevo
        $dashboardsBefore = PowerBiDashboard::whereHas('tenants', function ($q) use ($tenant) {
            $q->where('tenants.id', $tenant->id);
        })->count();
        
        // Crea un nuevo dashboard (simulamos la solicitud AJAX de Filament)
        $response = $this->post('/cliente/' . $tenant->slug . '/power-bi-dashboards', [
            'title' => 'Dashboard de Prueba',
            'description' => 'Descripción del dashboard',
            'category' => 'ventas',
            'workspace_id' => 'ws-test123',
            'report_id' => 'rep-test123',
            'embed_url' => 'https://app.powerbi.com/reportEmbed?reportId=test123',
            'is_active' => true,
            'is_public' => true,
        ]);
        
        // Verifica que haya un dashboard más asociado al tenant
        $dashboardsAfter = PowerBiDashboard::whereHas('tenants', function ($q) use ($tenant) {
            $q->where('tenants.id', $tenant->id);
        })->count();
        
        $this->assertEquals($dashboardsBefore + 1, $dashboardsAfter, 'Debe haber un dashboard más después de crear');
    }

    /** @test */
    public function normal_user_cannot_create_dashboard()
    {
        $tenant = Tenant::where('slug', 'org-uno')->first();
        $normalUser = User::where('email', 'user-org1@test.com')->first();
        
        // Login con usuario normal
        $this->actingAs($normalUser);
        
        // Intenta acceder a la página de creación
        $response = $this->get('/cliente/' . $tenant->slug . '/power-bi-dashboards/create');
        
        // Debe ser rechazado (403) o redirigido (302)
        $this->assertTrue(
            $response->status() == 403 || $response->status() == 302,
            'Usuario normal no debería poder acceder a la creación'
        );
    }

    /** @test */
    public function tenant_admin_can_edit_dashboard_in_their_tenant()
    {
        $tenant = Tenant::where('slug', 'org-uno')->first();
        $tenantAdmin = User::where('email', 'admin-org1@test.com')->first();
        
        // Login con admin de tenant
        $this->actingAs($tenantAdmin);
        
        // Obtiene un dashboard del tenant
        $dashboard = PowerBiDashboard::whereHas('tenants', function ($q) use ($tenant) {
            $q->where('tenants.id', $tenant->id);
        })->first();
        
        // Intenta acceder a la página de edición
        $response = $this->get('/cliente/' . $tenant->slug . '/power-bi-dashboards/' . $dashboard->id . '/edit');
        
        // Debe tener acceso
        $response->assertStatus(200);
        
        // Actualiza el dashboard (simulamos la solicitud AJAX de Filament)
        $newTitle = 'Título Actualizado ' . rand(1000, 9999);
        $response = $this->patch('/cliente/' . $tenant->slug . '/power-bi-dashboards/' . $dashboard->id, [
            'title' => $newTitle,
            'description' => $dashboard->description,
            'category' => $dashboard->category,
            'workspace_id' => $dashboard->workspace_id,
            'report_id' => $dashboard->report_id,
            'embed_url' => $dashboard->embed_url,
            'is_active' => $dashboard->is_active,
            'is_public' => $dashboard->is_public,
        ]);
        
        // Verifica que el dashboard se actualizó
        $updatedDashboard = PowerBiDashboard::find($dashboard->id);
        $this->assertEquals($newTitle, $updatedDashboard->title, 'El título debe haberse actualizado');
    }

    /** @test */
    public function admin_can_delete_any_dashboard()
    {
        // Login con admin global
        $this->actingAs(User::where('email', 'admin@test.com')->first());
        
        // Cuenta dashboards antes de eliminar
        $dashboardsBefore = PowerBiDashboard::count();
        
        // Obtiene un dashboard cualquiera para eliminar
        $dashboard = PowerBiDashboard::first();
        
        // Elimina el dashboard (simulamos la solicitud AJAX de Filament)
        $response = $this->delete('/admin/power-bi-dashboards/' . $dashboard->id);
        
        // Verifica que haya un dashboard menos
        $dashboardsAfter = PowerBiDashboard::count();
        $this->assertEquals($dashboardsBefore - 1, $dashboardsAfter, 'Debe haber un dashboard menos después de eliminar');
    }

    /** @test */
    public function normal_user_cannot_delete_dashboard()
    {
        $tenant = Tenant::where('slug', 'org-uno')->first();
        $normalUser = User::where('email', 'user-org1@test.com')->first();
        
        // Login con usuario normal
        $this->actingAs($normalUser);
        
        // Obtiene un dashboard del tenant
        $dashboard = PowerBiDashboard::whereHas('tenants', function ($q) use ($tenant) {
            $q->where('tenants.id', $tenant->id);
        })->first();
        
        // Intenta eliminar el dashboard (simulamos la solicitud AJAX de Filament)
        $response = $this->delete('/cliente/' . $tenant->slug . '/power-bi-dashboards/' . $dashboard->id);
        
        // Debe ser rechazado (403) o redirigido (302)
        $this->assertTrue(
            $response->status() == 403 || $response->status() == 302,
            'Usuario normal no debería poder eliminar un dashboard'
        );
        
        // Verifica que el dashboard sigue existiendo
        $this->assertNotNull(PowerBiDashboard::find($dashboard->id), 'El dashboard debe seguir existiendo');
    }

    /** @test */
    public function normal_user_can_view_dashboard_in_their_tenant()
    {
        $tenant = Tenant::where('slug', 'org-uno')->first();
        $normalUser = User::where('email', 'user-org1@test.com')->first();
        
        // Login con usuario normal
        $this->actingAs($normalUser);
        
        // Accede a la lista de dashboards
        $response = $this->get('/cliente/' . $tenant->slug . '/power-bi-dashboards');
        
        // Debe poder ver la página
        $response->assertStatus(200);
        
        // Obtiene un dashboard del tenant
        $dashboard = PowerBiDashboard::whereHas('tenants', function ($q) use ($tenant) {
            $q->where('tenants.id', $tenant->id);
        })->first();
        
        // Intenta acceder a la preview del dashboard
        $response = $this->get('/cliente/' . $tenant->slug . '/power-bi/dashboard/' . $dashboard->id);
        
        // Debe poder acceder
        $this->assertTrue(
            $response->status() == 200 || $response->status() == 302,
            'Usuario normal debería poder ver un dashboard'
        );
    }
}
