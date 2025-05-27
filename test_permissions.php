<?php

// Script para probar permisos manualmente
require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Application;

// Inicializar Laravel
$app = new Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 AUDITORÍA MANUAL DEL SISTEMA DE PERMISOS\n";
echo "==========================================\n\n";

// 1. Verificar usuarios
echo "📋 USUARIOS EN EL SISTEMA:\n";
echo "---------------------------\n";

$users = User::all();

foreach ($users as $user) {
    printf(
        "ID: %d | Email: %s | Nombre: %s | Admin: %s | TenantAdmin: %s | TenantID: %s\n",
        $user->id,
        $user->email,
        $user->name,
        $user->is_admin ? 'SÍ' : 'NO',
        $user->is_tenant_admin ? 'SÍ' : 'NO',
        $user->tenant_id ?? 'NULL'
    );
}

echo "\nRESUMEN:\n";
echo "- Total usuarios: " . $users->count() . "\n";
echo "- Administradores globales: " . $users->where('is_admin', true)->count() . "\n";
echo "- Administradores de tenant: " . $users->where('is_tenant_admin', true)->count() . "\n";
echo "- Usuarios sin tenant: " . $users->whereNull('tenant_id')->count() . "\n";

// 2. Verificar tenants
echo "\n🏢 TENANTS EN EL SISTEMA:\n";
echo "-------------------------\n";

$tenants = Tenant::all();

foreach ($tenants as $tenant) {
    $userCount = $tenant->users()->count();
    printf(
        "ID: %d | Nombre: %s | Slug: %s | Activo: %s | Usuarios: %d\n",
        $tenant->id,
        $tenant->name,
        $tenant->slug,
        $tenant->is_active ? 'SÍ' : 'NO',
        $userCount
    );
}

echo "\nRESUMEN:\n";
echo "- Total tenants: " . $tenants->count() . "\n";
echo "- Tenants activos: " . $tenants->where('is_active', true)->count() . "\n";

// 3. Verificar casos edge
echo "\n⚠️  CASOS EDGE DETECTADOS:\n";
echo "---------------------------\n";

// Usuarios sin tenant pero no admin
$usersWithoutTenant = User::whereNull('tenant_id')->where('is_admin', false)->get();
if ($usersWithoutTenant->count() > 0) {
    echo "❌ Usuarios sin tenant y sin permisos de admin:\n";
    foreach ($usersWithoutTenant as $user) {
        echo "  - " . $user->email . "\n";
    }
} else {
    echo "✅ No hay usuarios sin tenant y sin permisos\n";
}

// Admins de tenant sin tenant asignado
$tenantAdminsWithoutTenant = User::whereNull('tenant_id')->where('is_tenant_admin', true)->get();
if ($tenantAdminsWithoutTenant->count() > 0) {
    echo "❌ Administradores de tenant sin tenant asignado:\n";
    foreach ($tenantAdminsWithoutTenant as $user) {
        echo "  - " . $user->email . "\n";
    }
} else {
    echo "✅ Todos los admins de tenant tienen tenant asignado\n";
}

// 4. Probar permisos del admin global
echo "\n🔑 PRUEBA DE PERMISOS - ADMIN GLOBAL:\n";
echo "====================================\n";

$admin = User::where('email', 'paguero@digito.pe')->first();

if ($admin) {
    echo "Usuario: {$admin->email}\n";
    echo "is_admin: " . ($admin->is_admin ? 'true' : 'false') . "\n";
    echo "isAdmin(): " . ($admin->isAdmin() ? 'true' : 'false') . "\n";
    
    // Verificar acceso a todos los tenants
    foreach ($tenants as $tenant) {
        $canAccess = $admin->canAccessTenant($tenant);
        echo "- Acceso a '{$tenant->name}': " . ($canAccess ? '✅' : '❌') . "\n";
    }
} else {
    echo "❌ No se encontró el administrador global\n";
}

echo "\n✅ AUDITORÍA COMPLETADA\n";
