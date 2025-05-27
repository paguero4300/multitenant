<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a global administrator user.
     */
    public function globalAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'is_tenant_admin' => false,
            'tenant_id' => null,
        ]);
    }

    /**
     * Create a tenant administrator user.
     */
    public function tenantAdmin($tenantId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => false,
            'is_tenant_admin' => true,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Create a regular user with a tenant.
     */
    public function regularUser($tenantId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Create a user without tenant (orphan user).
     */
    public function withoutTenant(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => false,
            'is_tenant_admin' => false,
            'tenant_id' => null,
        ]);
    }

    /**
     * Assign a specific tenant to the user.
     */
    public function forTenant($tenantId): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }
}
