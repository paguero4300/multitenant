<?php

use App\Models\User;
use App\Models\Tenant;

beforeEach(function () {
    // Crear tenants de prueba
    $this->tenantA = Tenant::factory()->withNameAndSlug('Company A', 'company-a')->create();
    $this->tenantB = Tenant::factory()->withNameAndSlug('Company B', 'company-b')->create();
});

describe('Global Admin User', function () {
    test('has correct permissions', function () {
        $globalAdmin = User::factory()->globalAdmin()->create();

        // Verificar propiedades
        expect($globalAdmin->is_admin)->toBeTrue();
        expect($globalAdmin->is_tenant_admin)->toBeFalse();
        expect($globalAdmin->tenant_id)->toBeNull();

        // Verificar métodos de permisos
        expect($globalAdmin->isAdmin())->toBeTrue();
        expect($globalAdmin->isTenantAdmin())->toBeFalse();

        // Verificar acceso a todos los tenants
        expect($globalAdmin->canAccessTenant($this->tenantA))->toBeTrue();
        expect($globalAdmin->canAccessTenant($this->tenantB))->toBeTrue();
    });
});

describe('Tenant Admin User', function () {
    test('has correct permissions', function () {
        $tenantAdmin = User::factory()->tenantAdmin($this->tenantA->id)->create();

        // Verificar propiedades
        expect($tenantAdmin->is_admin)->toBeFalse();
        expect($tenantAdmin->is_tenant_admin)->toBeTrue();
        expect($tenantAdmin->tenant_id)->toBe($this->tenantA->id);

        // Verificar métodos de permisos
        expect($tenantAdmin->isAdmin())->toBeFalse();
        expect($tenantAdmin->isTenantAdmin())->toBeTrue();

        // Verificar acceso solo a su tenant
        expect($tenantAdmin->canAccessTenant($this->tenantA))->toBeTrue();
        expect($tenantAdmin->canAccessTenant($this->tenantB))->toBeFalse();
    });
});

describe('Regular User', function () {
    test('has correct permissions', function () {
        $regularUser = User::factory()->regularUser($this->tenantA->id)->create();

        // Verificar propiedades
        expect($regularUser->is_admin)->toBeFalse();
        expect($regularUser->is_tenant_admin)->toBeFalse();
        expect($regularUser->tenant_id)->toBe($this->tenantA->id);

        // Verificar métodos de permisos
        expect($regularUser->isAdmin())->toBeFalse();
        expect($regularUser->isTenantAdmin())->toBeFalse();

        // Verificar acceso solo a su tenant
        expect($regularUser->canAccessTenant($this->tenantA))->toBeTrue();
        expect($regularUser->canAccessTenant($this->tenantB))->toBeFalse();
    });
});

describe('User Without Tenant', function () {
    test('has no access', function () {
        $orphanUser = User::factory()->withoutTenant()->create();

        // Verificar propiedades
        expect($orphanUser->is_admin)->toBeFalse();
        expect($orphanUser->is_tenant_admin)->toBeFalse();
        expect($orphanUser->tenant_id)->toBeNull();

        // Verificar métodos de permisos
        expect($orphanUser->isAdmin())->toBeFalse();
        expect($orphanUser->isTenantAdmin())->toBeFalse();

        // Verificar sin acceso a ningún tenant
        expect($orphanUser->canAccessTenant($this->tenantA))->toBeFalse();
        expect($orphanUser->canAccessTenant($this->tenantB))->toBeFalse();
    });
});

describe('Multi-Tenant User', function () {
    test('has correct access to multiple tenants', function () {
        $multiTenantUser = User::factory()->regularUser($this->tenantA->id)->create();
        
        // Asignar acceso adicional al tenant B
        $multiTenantUser->additionalTenants()->attach($this->tenantB->id);

        // Verificar acceso a ambos tenants
        expect($multiTenantUser->canAccessTenant($this->tenantA))->toBeTrue();
        expect($multiTenantUser->canAccessTenant($this->tenantB))->toBeTrue();
    });
});

describe('User Validation', function () {
    test('cannot create user with inconsistent permissions', function () {
        expect(function () {
            User::create([
                'name' => 'Invalid User',
                'email' => 'invalid@test.com',
                'password' => bcrypt('password'),
                'is_admin' => true,
                'is_tenant_admin' => true,
            ]);
        })->toThrow(InvalidArgumentException::class, 'Un usuario no puede ser administrador global y administrador de tenant al mismo tiempo');
    });

    test('cannot create tenant admin without tenant', function () {
        expect(function () {
            User::create([
                'name' => 'Invalid Tenant Admin',
                'email' => 'invalid.admin@test.com',
                'password' => bcrypt('password'),
                'is_admin' => false,
                'is_tenant_admin' => true,
                'tenant_id' => null,
            ]);
        })->toThrow(InvalidArgumentException::class, 'Un administrador de tenant debe tener un tenant_id asignado');
    });
});

describe('Panel Access Logic', function () {
    test('works correctly for different user types', function () {
        $globalAdmin = User::factory()->globalAdmin()->create();
        $tenantAdmin = User::factory()->tenantAdmin($this->tenantA->id)->create();
        $regularUser = User::factory()->regularUser($this->tenantA->id)->create();
        $orphanUser = User::factory()->withoutTenant()->create();

        // Verificar lógica de acceso al panel admin (solo admins globales)
        expect($globalAdmin->is_admin)->toBeTrue();
        expect($tenantAdmin->is_admin)->toBeFalse();
        expect($regularUser->is_admin)->toBeFalse();
        expect($orphanUser->is_admin)->toBeFalse();

        // Verificar lógica de acceso al panel tenant
        $canAccessTenant = fn($user) => $user->is_admin || $user->is_tenant_admin || $user->tenant_id !== null;
        
        expect($canAccessTenant($globalAdmin))->toBeTrue();
        expect($canAccessTenant($tenantAdmin))->toBeTrue();
        expect($canAccessTenant($regularUser))->toBeTrue();
        expect($canAccessTenant($orphanUser))->toBeFalse();
    });
});
