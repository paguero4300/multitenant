<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista previa: {{ $dashboard->title }}</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .header {
            background-color: #f8f9fa;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }
        .title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
        }
        .back-button {
            background-color: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .dashboard-container {
            width: 100%;
            height: calc(100vh - 50px);
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $dashboard->title }}</div>
        <a href="{{ route('filament.admin.resources.power-bi-dashboards.index') }}" class="back-button">Volver</a>
    </div>
    <div class="dashboard-container">
        <iframe src="{{ $embedUrl }}" frameborder="0" allowfullscreen="true"></iframe>
    </div>
</body>
</html>
