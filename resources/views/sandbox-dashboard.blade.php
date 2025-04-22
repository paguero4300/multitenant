<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sandbox Local</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            background: #f5f7fa;
            color: #333;
            font-family: sans-serif;
        }
        .container {
            padding: 20px;
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .dashboard-frame {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background: white;
            overflow: hidden;
            height: 85vh;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .url-info {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $dashboard->title }}</h1>
        
        <div class="dashboard-frame">
            <!-- Usando URL local para evitar problemas CORS/HTTPS -->
            <iframe 
                src="{{ route('api.dashboard.embed', ['dashboard' => $dashboard->id]) }}" 
                scrolling="no"
                allowfullscreen
            ></iframe>
        </div>
        
        <div class="url-info">
            URL original: {{ $dashboard->embed_url }}
        </div>
    </div>
</body>
</html> 