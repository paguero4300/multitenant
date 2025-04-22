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
    </style>
</head>
<body>
    <!-- Redirección directa sin iframe -->
    <script type="text/javascript">
        // Simplemente redirigir al usuario directamente a la URL del dashboard
        window.location.href = "{{ $dashboard->embed_url }}";
    </script>
    
    <!-- Fallback por si JavaScript está desactivado -->
    <p style="text-align: center; padding: 20px;">
        Redirigiendo al dashboard...
        <br><br>
        Si no eres redirigido automáticamente, <a href="{{ $dashboard->embed_url }}">haz clic aquí</a>.
    </p>
</body>
</html> 