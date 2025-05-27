<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class FixAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:fix-admin {user_id=1 : ID del usuario a convertir en administrador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convierte un usuario en administrador global y corrige su configuración';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Usuario con ID {$userId} no encontrado.");
            return 1;
        }
        
        $this->info("Usuario encontrado: {$user->name} ({$user->email})");
        $this->info("Estado actual:");
        $this->info("- is_admin: " . ($user->is_admin ? 'true' : 'false'));
        $this->info("- is_tenant_admin: " . ($user->is_tenant_admin ? 'true' : 'false'));
        $this->info("- tenant_id: " . ($user->tenant_id ?? 'NULL'));
        
        if ($this->confirm('¿Convertir este usuario en administrador global?')) {
            $user->update([
                'is_admin' => true,
                'is_tenant_admin' => false,
                'tenant_id' => null, // Los admins globales no necesitan tenant
            ]);
            
            $this->info("✅ Usuario actualizado correctamente:");
            $this->info("- is_admin: true");
            $this->info("- is_tenant_admin: false");
            $this->info("- tenant_id: NULL");
            
            $this->info("El usuario {$user->email} ahora puede acceder al panel de administración.");
        } else {
            $this->info("Operación cancelada.");
        }
        
        return 0;
    }
}
