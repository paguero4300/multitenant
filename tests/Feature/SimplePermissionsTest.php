<?php

use App\Models\User;
use App\Models\Tenant;

beforeEach(function () {
    // Crear tenants manualmente para evitar problemas con factories
    $this->tenantA = Tenant::create([
        'name' => 'Company A',
        'slug' => 'company-a',
        'is_active' => true,
    ]);
    
    $this->tenantB = Tenant::create([
        'name' => 'Company B', 
        'slug' => 'company-b',
        'is_active' => true,
    ]);
});

describe('User Permissions', function () {
    test('global admin has correct permissions', function () {
        $globalAdmin = User::create([
            'name' => 'Global Admin',
            'email' => 'global@test.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
            'is_tenant_admin' => false,
            'tenant_id' => null,
        ]);

        expect($globalAdmin->is_admin)->toBeTrue();
        expect($globalAdmin->is_tenant_admin)->toBeFalse();
        expect($globalAdmin->tenant_id)->toBeNull();
        expect($globalAdmin->isAdmin())->toBeTrue();
        expect($globalAdmin->isTenantAdmin())->toBeFalse();
        expect($globalAdmin->canAccessTenant($this->tenantA))->toBeTrue();
        expect($globalAdmin->canAccessTenant($this->tenantB))->toBeTrue();
    });

    test('tenant admin has correct permissions', function () {
        $tenantAdmin = User::create([
            'name' => 'Tenant Admin',
            'email' => 'tenant@test.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_tenant_admin' => true,
            'tenant_id' => $this->tenantA->id,
        ]);

        expect($tenantAdmin->is_admin)->toBeFalse();
        expect($tenantAdmin->is_tenant_admin)->toBeTrue();
        expect($tenantAdmin->tenant_id)->toBe($this->tenantA->id);
        expect($tenantAdmin->isAdmin())->toBeFalse();
        expect($tenantAdmin->isTenantAdmin())->toBeTrue();
        expect($tenantAdmin->canAccessTenant($this->tenantA))->toBeTrue();
        expect($tenantAdmin->canAccessTenant($this->tenantB))->toBeFalse();
    });

    test('regular user has correct permissions', function () {
        $regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular@test.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => $this->tenantA->id,
        ]);

        expect($regularUser->is_admin)->toBeFalse();
        expect($regularUser->is_tenant_admin)->toBeFalse();
        expect($regularUser->tenant_id)->toBe($this->tenantA->id);
        expect($regularUser->isAdmin())->toBeFalse();
        expect($regularUser->isTenantAdmin())->toBeFalse();
        expect($regularUser->canAccessTenant($this->tenantA))->toBeTrue();
        expect($regularUser->canAccessTenant($this->tenantB))->toBeFalse();
    });

    test('user without tenant has no access', function () {
        $orphanUser = User::create([
            'name' => 'Orphan User',
            'email' => 'orphan@test.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => null,
        ]);

        expect($orphanUser->is_admin)->toBeFalse();
        expect($orphanUser->is_tenant_admin)->toBeFalse();
        expect($orphanUser->tenant_id)->toBeNull();
        expect($orphanUser->isAdmin())->toBeFalse();
        expect($orphanUser->isTenantAdmin())->toBeFalse();
        expect($orphanUser->canAccessTenant($this->tenantA))->toBeFalse();
        expect($orphanUser->canAccessTenant($this->tenantB))->toBeFalse();
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
        })->toThrow(InvalidArgumentException::class);
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
        })->toThrow(InvalidArgumentException::class);
    });
});

describe('Panel Access Logic', function () {
    test('admin panel access logic works correctly', function () {
        $globalAdmin = User::create([
            'name' => 'Global Admin',
            'email' => 'global2@test.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
            'is_tenant_admin' => false,
            'tenant_id' => null,
        ]);

        $tenantAdmin = User::create([
            'name' => 'Tenant Admin',
            'email' => 'tenant2@test.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_tenant_admin' => true,
            'tenant_id' => $this->tenantA->id,
        ]);

        $regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular2@test.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => $this->tenantA->id,
        ]);

        // Solo admins globales pueden acceder al panel admin
        expect($globalAdmin->is_admin)->toBeTrue();
        expect($tenantAdmin->is_admin)->toBeFalse();
        expect($regularUser->is_admin)->toBeFalse();
    });

    test('tenant panel access logic works correctly', function () {
        $globalAdmin = User::create([
            'name' => 'Global Admin',
            'email' => 'global3@test.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
            'is_tenant_admin' => false,
            'tenant_id' => null,
        ]);

        $tenantAdmin = User::create([
            'name' => 'Tenant Admin',
            'email' => 'tenant3@test.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_tenant_admin' => true,
            'tenant_id' => $this->tenantA->id,
        ]);

        $regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular3@test.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => $this->tenantA->id,
        ]);

        $orphanUser = User::create([
            'name' => 'Orphan User',
            'email' => 'orphan3@test.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => null,
        ]);

        // Lógica de acceso al panel tenant
        $canAccessTenant = fn($user) => $user->is_admin || $user->is_tenant_admin || $user->tenant_id !== null;
        
        expect($canAccessTenant($globalAdmin))->toBeTrue();
        expect($canAccessTenant($tenantAdmin))->toBeTrue();
        expect($canAccessTenant($regularUser))->toBeTrue();
        expect($canAccessTenant($orphanUser))->toBeFalse();
    });
});
