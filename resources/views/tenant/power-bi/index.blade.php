<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboards disponibles - {{ $tenant->name }}</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f9fafb;
        }
        
        .container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .title {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .dashboard-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .card-image {
            height: 150px;
            background-color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card-content {
            padding: 15px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #111827;
        }
        
        .card-description {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .card-category {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 9999px;
            margin-bottom: 15px;
        }
        
        .view-button {
            display: block;
            flex: 1;
            background-color: #1d4ed8;
            color: white;
            text-align: center;
            padding: 8px 0;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        
        .view-external {
            display: block;
            flex: 1;
            background-color: #047857;
            color: white;
            text-align: center;
            padding: 8px 0;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Dashboards disponibles para {{ $tenant->name }}</h1>
        </div>
        
        @if($dashboards->isEmpty())
            <div class="empty-state">
                <p>No hay dashboards disponibles en este momento.</p>
            </div>
        @else
            <div class="dashboard-grid">
                @foreach($dashboards as $dashboard)
                    <div class="dashboard-card">
                        <div class="card-image">
                            @if($dashboard->thumbnail)
                                <img src="{{ $dashboard->thumbnail }}" alt="{{ $dashboard->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="#9ca3af">
                                    <path d="M10 3H4a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1V4a1 1 0 00-1-1zM9 9H5V5h4v4zm11-6h-6a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1V4a1 1 0 00-1-1zm-1 6h-4V5h4v4zm-9 4H4a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1v-6a1 1 0 00-1-1zm-1 6H5v-4h4v4zm8-6c-2.206 0-4 1.794-4 4s1.794 4 4 4 4-1.794 4-4-1.794-4-4-4zm0 6c-1.103 0-2-.897-2-2s.897-2 2-2 2 .897 2 2-.897 2-2 2z"></path>
                                </svg>
                            @endif
                        </div>
                        <div class="card-content">
                            <h2 class="card-title">{{ $dashboard->title }}</h2>
                            <p class="card-description">{{ $dashboard->description ?: 'Sin descripción' }}</p>
                            
                            @if($dashboard->category)
                                <div class="card-category">{{ $dashboard->category }}</div>
                            @endif
                            
                            <div class="dashboard-actions">
                                <a href="{{ route('tenant.power-bi.show', ['tenant' => $tenant->slug, 'dashboard' => $dashboard->id]) }}" class="view-button">
                                    Ver en App
                                </a>
                                <a href="{{ $dashboard->embed_url }}" class="view-external" target="_blank">
                                    Ver externo
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>
