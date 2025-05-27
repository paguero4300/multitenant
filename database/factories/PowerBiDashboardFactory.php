<?php

namespace Database\Factories;

use App\Models\PowerBiDashboard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PowerBiDashboard>
 */
class PowerBiDashboardFactory extends Factory
{
    protected $model = PowerBiDashboard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'embed_url' => 'https://app.powerbi.com/reportEmbed?reportId=' . $this->faker->uuid(),
            'report_id' => $this->faker->uuid(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the dashboard is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a dashboard for a specific tenant.
     */
    public function forTenant($tenantId): static
    {
        return $this->afterCreating(function (PowerBiDashboard $dashboard) use ($tenantId) {
            $dashboard->tenants()->attach($tenantId);
        });
    }

    /**
     * Create a dashboard without description.
     */
    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => null,
        ]);
    }

    /**
     * Create a dashboard without embed URL.
     */
    public function withoutEmbedUrl(): static
    {
        return $this->state(fn (array $attributes) => [
            'embed_url' => null,
        ]);
    }

    /**
     * Create a dashboard with specific title.
     */
    public function withTitle(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    /**
     * Create a dashboard with test data.
     */
    public function testDashboard(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Test Dashboard - ' . $this->faker->word(),
            'description' => 'Dashboard de prueba para testing automatizado',
            'embed_url' => 'https://app.powerbi.com/reportEmbed?reportId=test-' . $this->faker->uuid(),
            'report_id' => 'test-' . $this->faker->uuid(),
            'workspace_id' => 'test-workspace-' . $this->faker->uuid(),
        ]);
    }
}
