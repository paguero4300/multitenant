<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>Prueba Dashboard Directo</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        iframe {
            width: 100%;
            height: 100vh;
            border: none;
        }
    </style>
    <script>
        // Polyfill simple para ClipboardItem si no existe
        if (typeof ClipboardItem === 'undefined') {
            window.ClipboardItem = function(data) {
                this.data = data;
            };
        }
    </script>
</head>
<body>
    <!-- Dashboard directo sin proxy -->
    <iframe 
        src="{{ $embedUrl }}" 
        frameborder="0" 
        allowfullscreen="true"
        allow="clipboard-read; clipboard-write; accelerometer; autoplay; camera; geolocation; gyroscope; microphone; encrypted-media; picture-in-picture"
    ></iframe>
</body>
</html> 