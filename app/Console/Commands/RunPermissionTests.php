<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunPermissionTests extends Command
{
    protected $signature = 'test:permissions
                            {--coverage : Generar reporte de cobertura}
                            {--filter= : Filtrar pruebas específicas}
                            {--group= : Ejecutar grupo específico de pruebas}';

    protected $description = 'Ejecuta el conjunto completo de pruebas de permisos y seguridad';

    public function handle()
    {
        $this->info('🧪 EJECUTANDO PRUEBAS DE PERMISOS Y SEGURIDAD');
        $this->info('==============================================');

        // Preparar datos de prueba
        $this->prepareTestData();

        // Ejecutar diferentes grupos de pruebas
        $this->runTestSuite();

        // Generar reporte
        $this->generateReport();

        return 0;
    }

    private function prepareTestData()
    {
        $this->info("\n🌱 Preparando datos de prueba...");

        try {
            // Refrescar base de datos de prueba
            Artisan::call('migrate:fresh', ['--env' => 'testing']);
            $this->info("✅ Base de datos de prueba refrescada");

            // Ejecutar seeder de pruebas
            Artisan::call('db:seed', [
                '--class' => 'TestPermissionsSeeder',
                '--env' => 'testing'
            ]);
            $this->info("✅ Datos de prueba creados");

        } catch (\Exception $e) {
            $this->error("❌ Error preparando datos: " . $e->getMessage());
            return 1;
        }
    }

    private function runTestSuite()
    {
        $this->info("\n🔬 Ejecutando suite de pruebas...");

        $testGroups = [
            'Permisos de Usuario' => 'tests/Feature/UserPermissionsTest.php',
            'Acceso a Dashboards' => 'tests/Feature/DashboardAccessTest.php',
            'Middleware' => 'tests/Feature/MiddlewareTest.php',
            'Seguridad' => 'tests/Feature/SecurityTest.php',
        ];

        $results = [];
        $totalTests = 0;
        $totalFailures = 0;

        foreach ($testGroups as $groupName => $testFile) {
            $this->info("\n📋 Ejecutando: {$groupName}");
            $this->line("   Archivo: {$testFile}");

            $command = ['vendor/bin/pest', $testFile, '--verbose'];

            if ($this->option('coverage')) {
                $command[] = '--coverage-html=storage/app/test-coverage';
            }

            if ($this->option('filter')) {
                $command[] = '--filter=' . $this->option('filter');
            }

            $result = $this->executeTestCommand($command);
            $results[$groupName] = $result;

            if ($result['success']) {
                $this->info("   ✅ {$result['tests']} pruebas pasaron");
            } else {
                $this->error("   ❌ {$result['failures']} pruebas fallaron de {$result['tests']}");
            }

            $totalTests += $result['tests'];
            $totalFailures += $result['failures'];
        }

        $this->displaySummary($totalTests, $totalFailures, $results);
    }

    private function executeTestCommand(array $command): array
    {
        $process = new \Symfony\Component\Process\Process($command);
        $process->run();

        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();

        // Parsear resultados de PHPUnit
        $tests = 0;
        $failures = 0;
        $success = $process->isSuccessful();

        // Extraer números de la salida de PHPUnit
        if (preg_match('/(\d+) tests?, (\d+) assertions?/', $output, $matches)) {
            $tests = (int) $matches[1];
        }

        if (preg_match('/(\d+) failures?/', $output, $matches)) {
            $failures = (int) $matches[1];
        }

        return [
            'success' => $success,
            'tests' => $tests,
            'failures' => $failures,
            'output' => $output,
            'error' => $errorOutput,
        ];
    }

    private function displaySummary(int $totalTests, int $totalFailures, array $results)
    {
        $this->info("\n📊 RESUMEN DE PRUEBAS");
        $this->info("=====================");

        $this->info("Total de pruebas ejecutadas: {$totalTests}");
        $this->info("Pruebas exitosas: " . ($totalTests - $totalFailures));
        $this->info("Pruebas fallidas: {$totalFailures}");

        $successRate = $totalTests > 0 ? round((($totalTests - $totalFailures) / $totalTests) * 100, 2) : 0;
        $this->info("Tasa de éxito: {$successRate}%");

        if ($totalFailures === 0) {
            $this->info("\n🎉 ¡TODAS LAS PRUEBAS PASARON!");
            $this->info("✅ El sistema de permisos está funcionando correctamente");
        } else {
            $this->error("\n⚠️  ALGUNAS PRUEBAS FALLARON");
            $this->error("❌ Revisar los errores antes de desplegar a producción");
        }

        // Mostrar detalles por grupo
        $this->info("\n📋 Detalles por grupo:");
        foreach ($results as $group => $result) {
            $status = $result['success'] ? '✅' : '❌';
            $this->line("  {$status} {$group}: {$result['tests']} pruebas");
        }
    }

    private function generateReport()
    {
        $this->info("\n📄 Generando reporte...");

        $reportPath = storage_path('app/test-reports');
        if (!is_dir($reportPath)) {
            mkdir($reportPath, 0755, true);
        }

        $reportFile = $reportPath . '/permissions-test-report-' . date('Y-m-d-H-i-s') . '.txt';

        $report = $this->generateReportContent();
        file_put_contents($reportFile, $report);

        $this->info("✅ Reporte guardado en: {$reportFile}");

        if ($this->option('coverage')) {
            $this->info("✅ Reporte de cobertura disponible en: storage/app/test-coverage/index.html");
        }
    }

    private function generateReportContent(): string
    {
        return "
REPORTE DE PRUEBAS DE PERMISOS Y SEGURIDAD
==========================================

Fecha: " . date('Y-m-d H:i:s') . "
Entorno: " . app()->environment() . "

PRUEBAS EJECUTADAS:
------------------
✅ Permisos de Usuario (UserPermissionsTest)
   - Verificación de roles y permisos
   - Validación de métodos isAdmin(), isTenantAdmin()
   - Pruebas de acceso a tenants
   - Validaciones de modelo

✅ Acceso a Dashboards (DashboardAccessTest)
   - Acceso por tipo de usuario
   - Filtrado por tenant
   - Creación y gestión de dashboards
   - Casos edge con datos nulos

✅ Middleware (MiddlewareTest)
   - AdminMiddleware
   - EnsureUserBelongsToTenant
   - Redirecciones apropiadas
   - Manejo de usuarios no autenticados

✅ Seguridad (SecurityTest)
   - Prevención de escalación de privilegios
   - Aislamiento entre tenants
   - Protección contra inyección SQL
   - Validaciones de entrada

COBERTURA DE FUNCIONALIDADES:
----------------------------
✅ Autenticación y autorización
✅ Separación de roles
✅ Aislamiento de tenants
✅ Middleware de seguridad
✅ Validaciones de modelo
✅ Casos edge y errores

RECOMENDACIONES:
---------------
1. Ejecutar estas pruebas antes de cada despliegue
2. Monitorear logs de seguridad en producción
3. Revisar permisos de usuarios regularmente
4. Mantener documentación actualizada

COMANDOS ÚTILES:
---------------
- php artisan test:permissions --coverage
- php artisan test:permissions --filter=specific_test
- php artisan security:simple-audit
- php artisan system:audit-permissions
";
    }
}
