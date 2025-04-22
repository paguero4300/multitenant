<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $dashboard->title }} | {{ $tenant->name }}</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    
    <!-- Estilos -->
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        
        .dashboard-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #f9fafb;
        }
        
        /* Header siguiendo proporción áurea */
        .header {
            height: 60px;
            background-color: {{ $dashboard->theme_color ?? '#3B82F6' }};
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 1.618rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .header-title {
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .header-subtitle {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            background-color: rgba(255,255,255,0.15);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: rgba(255,255,255,0.25);
        }
        
        .btn svg {
            width: 1rem;
            height: 1rem;
        }
        
        /* Contenido principal */
        .content {
            flex: 1;
            position: relative;
        }
        
        iframe {
            position: absolute;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* Mensaje de error */
        .error-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f9fafb;
        }
        
        .error-message {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #b91c1c;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 500px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="header">
            <div class="header-left">
                <div class="header-title">{{ $dashboard->title }}</div>
                <div class="header-subtitle">{{ $tenant->name }}</div>
            </div>
            <div class="header-actions">
                <a href="{{ route('tenant.dashboard', ['tenant' => $tenant->slug]) }}" class="btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver
                </a>
                <button class="btn" onclick="document.documentElement.requestFullscreen()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5" />
                    </svg>
                    Pantalla completa
                </button>
            </div>
        </header>
        
        <main class="content">
            @if(isset($dashboard->embed_url))
                <iframe 
                    src="{{ $dashboard->embed_url }}" 
                    frameborder="0" 
                    allowfullscreen 
                    allow="accelerometer; autoplay; clipboard-write; clipboard-read; encrypted-media; gyroscope; picture-in-picture; web-share"
                    scrolling="no"
                ></iframe>
            @else
                <div class="error-container">
                    <div class="error-message">
                        <strong>Error:</strong> No se pudo cargar el dashboard. Por favor, intente nuevamente más tarde.
                    </div>
                </div>
            @endif
        </main>
    </div>
    
    <script>
        // Permitir salir del modo pantalla completa con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.fullscreenElement) {
                document.exitFullscreen();
            }
        });
    </script>
</body>
</html>
