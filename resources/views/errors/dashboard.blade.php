<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error al cargar el dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 600px;
            width: 100%;
        }
        .error-icon {
            color: #f56565;
            font-size: 48px;
            margin-bottom: 20px;
            text-align: center;
        }
        h1 {
            color: #2d3748;
            font-size: 24px;
            margin-bottom: 16px;
            text-align: center;
        }
        .error-message {
            margin-bottom: 24px;
            padding: 16px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #e53e3e;
        }
        .error-code {
            display: inline-block;
            background-color: #e53e3e;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            margin-right: 8px;
            font-weight: bold;
        }
        .error-actions {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }
        .button {
            display: inline-block;
            background-color: #4299e1;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin: 0 8px;
            transition: background-color 0.2s;
        }
        .button:hover {
            background-color: #3182ce;
        }
        .technical-details {
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #718096;
        }
        @media (max-width: 640px) {
            .error-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="48" height="48">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        
        <h1>Error al cargar el dashboard</h1>
        
        <div class="error-message">
            <span class="error-code">403</span>
            {{ $message ?? 'No se pudo cargar el dashboard solicitado.' }}
        </div>
        
        <div class="error-actions">
            <a href="{{ url()->previous() }}" class="button">Volver</a>
            <a href="{{ route('filament.admin.pages.dashboard') }}" class="button">Ir al Panel</a>
        </div>
        
        @if(app()->environment('local', 'development'))
            <div class="technical-details">
                <h3>Detalles técnicos:</h3>
                <p>{{ $error->getMessage() }}</p>
                <p>Archivo: {{ $error->getFile() }}:{{ $error->getLine() }}</p>
            </div>
        @endif
    </div>
</body>
</html>
