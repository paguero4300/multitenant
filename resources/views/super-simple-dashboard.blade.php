<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Súper Simple</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
        }
        #reportContainer {
            width: 100%;
            height: 100vh;
        }
    </style>
</head>
<body>
    <div id="reportContainer"></div>

    <!-- Importar biblioteca de Power BI -->
    <script src="https://cdn.jsdelivr.net/npm/powerbi-client@2.18.6/dist/powerbi.min.js"></script>
    
    <script>
        // Configuración simple del dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar que la biblioteca se haya cargado
            if (!window.powerbi) {
                console.error('La biblioteca de Power BI no se ha cargado correctamente');
                document.getElementById('reportContainer').innerHTML = 
                    '<div style="padding: 20px; text-align: center;">Error: No se pudo cargar la biblioteca de Power BI</div>';
                return;
            }
            
            // Crear instancia de Power BI
            var reportContainer = document.getElementById('reportContainer');
            var powerbi = window.powerbi;
            
            // Configuración simplificada del informe
            var config = {
                type: 'report',
                embedUrl: '{{ $embedUrl }}',
                settings: {
                    navContentPaneEnabled: true,
                    filterPaneEnabled: false
                }
            };
            
            // Renderizar el informe
            try {
                console.log('Iniciando carga del dashboard...');
                var report = powerbi.embed(reportContainer, config);
                
                // Log cuando el informe se carga
                report.on('loaded', function() {
                    console.log('El dashboard ha sido cargado correctamente');
                });
                
                // Log si hay errores
                report.on('error', function(event) {
                    console.error('Error al cargar el dashboard:', event.detail);
                });
            } catch (err) {
                console.error('Error al inicializar el dashboard:', err);
                document.getElementById('reportContainer').innerHTML = 
                    '<div style="padding: 20px; text-align: center;">Error: ' + err.message + '</div>';
            }
        });
    </script>
</body>
</html> 