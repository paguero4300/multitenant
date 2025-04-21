<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Elegir qué seeder ejecutar según el entorno
        if (app()->environment('testing')) {
            // Para pruebas automatizadas, usar el seeder completo
            $this->call(PowerBiTestSeeder::class);
        } else {
            // Para desarrollo local, usar seeders básicos
            $this->call(AdminUserSeeder::class);
            
            // Crear tenant de prueba
            $tenant = Tenant::create([
                'name' => 'InmGenio',
                'slug' => 'inmgenio',
                'is_active' => true,
            ]);
            
            // Crear un usuario asociado al tenant
            User::factory()->create([
                'name' => 'Usuario de tenant',
                'email' => 'tenant@example.com',
                'tenant_id' => $tenant->id,
                'is_admin' => false,
            ]);
        }
    }
}
