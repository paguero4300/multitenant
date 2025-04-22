<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $dashboard->title }}</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        #dashboardFrame {
            width: 100%;
            height: 100vh;
            border: none;
        }
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            z-index: 100;
            flex-direction: column;
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-bottom: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
        <div>Cargando dashboard...</div>
    </div>
    
    <iframe id="dashboardFrame" src="about:blank" allowfullscreen="true"></iframe>
    
    <script>
        // Esperar a que el documento se cargue
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener referencia al iframe
            const iframe = document.getElementById('dashboardFrame');
            const loading = document.getElementById('loading');
            
            // URL del dashboard
            const dashboardUrl = "{{ $dashboard->embed_url }}";
            
            // Cargar el dashboard
            iframe.src = dashboardUrl;
            
            // Escuchar cuando se haya cargado el iframe
            iframe.onload = function() {
                // Ocultar la pantalla de carga
                loading.style.display = 'none';
                console.log('Dashboard cargado correctamente');
            };
            
            // Por si hay un error al cargar
            iframe.onerror = function() {
                console.error('Error al cargar el dashboard');
                loading.innerHTML = '<div>Error al cargar el dashboard. <a href="' + dashboardUrl + '" target="_blank">Abrir en nueva ventana</a></div>';
            };
        });
    </script>
</body>
</html> 