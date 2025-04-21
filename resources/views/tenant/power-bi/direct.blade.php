<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $dashboard->title }}</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        
        iframe {
            display: block;
            width: 100vw;
            height: 100vh;
            border: none;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <iframe 
        src="{{ $embedUrl }}" 
        frameborder="0" 
        allowfullscreen="true"
        allow="accelerometer; autoplay; clipboard-write; clipboard-read; encrypted-media; gyroscope; picture-in-picture; web-share"
    ></iframe>
</body>
</html>
