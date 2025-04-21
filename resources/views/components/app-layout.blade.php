<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Power BI Dashboards') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Estilos básicos para la demostración -->
    <style>
        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }
        nav {
            background-color: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
        }
        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-weight: 600;
            font-size: 1.25rem;
            color: #111827;
            text-decoration: none;
        }
        .page-header {
            background-color: white;
            padding: 1rem 0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="min-h-screen">
        <nav>
            <div class="container nav-content">
                <a href="/" class="logo">
                    {{ config('app.name', 'Power BI Dashboards') }}
                </a>
            </div>
        </nav>

        <!-- Page Heading -->
        @if (isset($header))
            <header class="page-header">
                <div class="container">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main class="container">
            {{ $slot }}
        </main>
    </div>
    
    @stack('scripts')
</body>
</html>
