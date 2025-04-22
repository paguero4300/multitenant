<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error de Dashboard</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            color: #333;
            background: #f7f8fb;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .error-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 500px;
            text-align: center;
        }
        h1 {
            color: #e53e3e;
            font-size: 24px;
            margin-bottom: 16px;
        }
        p {
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .icon {
            margin-bottom: 20px;
            color: #e53e3e;
            font-size: 48px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon">⚠️</div>
        <h1>Error al cargar el dashboard</h1>
        <p>{{ $message ?? 'Ha ocurrido un error al intentar cargar el dashboard. Por favor, intente nuevamente más tarde.' }}</p>
    </div>
</body>
</html>
