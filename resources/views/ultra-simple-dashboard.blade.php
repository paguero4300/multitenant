<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Dashboard Ultra Simple</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        .dashboard-container {
            width: 100%;
            height: 100vh;
        }
    </style>
    <script>
        // Implementar polyfill completo para ClipboardItem antes de que se cargue el iframe
        if (typeof ClipboardItem === 'undefined') {
            window.ClipboardItem = function(data) {
                this.data = data;
                this.types = Object.keys(data);
                this.getType = function(type) {
                    return Promise.resolve(this.data[type]);
                };
            };
            
            // Si Clipboard API tampoco está definida, simulemos sus métodos básicos
            if (navigator.clipboard && !navigator.clipboard.write) {
                navigator.clipboard.write = function() {
                    return Promise.resolve();
                };
            }
            
            if (navigator.clipboard && !navigator.clipboard.read) {
                navigator.clipboard.read = function() {
                    return Promise.resolve([]);
                };
            }
            
            console.log('Polyfill de ClipboardItem aplicado');
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <iframe 
            src="{{ $embedUrl }}" 
            width="100%" 
            height="100%" 
            frameborder="0" 
            allowfullscreen="true"
            sandbox="allow-scripts allow-same-origin allow-popups allow-forms allow-downloads"
            allow="clipboard-read; clipboard-write; fullscreen"
            style="border: none;"
        ></iframe>
    </div>
    
    <script>
        // Capturar y manejar errores relacionados con el iframe
        window.addEventListener('error', function(e) {
            console.log('Error capturado:', e.message);
            // Prevenir que el error se propague
            e.preventDefault();
            return true;
        }, true);
    </script>
</body>
</html> 