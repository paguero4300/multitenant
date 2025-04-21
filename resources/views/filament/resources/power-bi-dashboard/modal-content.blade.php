<div class="flex flex-col bg-gray-50 dark:bg-gray-900" style="height: calc(90vh - 100px); overflow: hidden;">
    @if(!isset($hideDetails) || !$hideDetails)
    <div class="p-4 bg-white dark:bg-gray-800 shadow-sm rounded-t-lg border-b dark:border-gray-700">
        <div class="flex items-center space-x-3">
            <div class="p-2 bg-primary-100 dark:bg-primary-950 rounded-lg">
                 <x-heroicon-o-presentation-chart-bar class="w-6 h-6 text-primary-600 dark:text-primary-400" />
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $record->title }}</h3>
                @if($record->description)
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ Str::limit($record->description, 80) }}</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="flex-1 p-1 bg-gray-50 dark:bg-gray-900 overflow-hidden">
        <div class="h-full w-full bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden border dark:border-gray-700">
            @php
                use Illuminate\Support\Facades\Crypt;
                use Illuminate\Support\Str;
                use Filament\Facades\Filament;
                use Illuminate\Support\Facades\Log;
                
                // Generar el token y la URL del proxy aquí mismo
                $proxyUrl = '';
                try {
                    // Obtener el tenant actual del contexto de Filament
                    $tenant = Filament::getTenant();
                    $controller = app(\App\Http\Controllers\PowerBiController::class);
                    
                    // Crear el payload del token manualmente (sin acceder a métodos protegidos)
                    // Los mismos datos que estarían en generateProxyToken o generateAdminProxyToken
                    $payload = [
                        'dashboard_id' => $record->id,
                        'embed_url' => $record->embed_url,
                        'expires' => now()->addHour()->timestamp,
                        'nonce' => Str::random(16),
                    ];
                    
                    // Si no hay tenant, estamos en el contexto de administrador, agregar is_admin: true
                    if (!$tenant) {
                        $payload['is_admin'] = true;
                    }
                    
                    $token = Crypt::encrypt($payload);
                    
                    if ($tenant) {
                        // Usar la ruta de tenant
                        $proxyUrl = route('tenant.power-bi.proxy', ['tenant' => $tenant, 'token' => $token]);
                    } else {
                        // Fallback a ruta de admin
                        $proxyUrl = route('admin.power-bi.proxy', ['token' => $token]);
                    }
                } catch (\Exception $e) {
                    // Manejar el error si la generación del token/ruta falla
                    $proxyUrl = '#error';
                    Log::error('Error al generar proxy URL en modal-content view: ' . $e->getMessage());
                }
            @endphp

            @if($proxyUrl !== '#error')
                <iframe 
                    src="{{ $proxyUrl }}" 
                    frameborder="0" 
                    allowfullscreen 
                    class="w-full h-full"
                    allow="accelerometer; autoplay; clipboard-write; clipboard-read; encrypted-media; gyroscope; picture-in-picture; web-share" {{-- Permisos más completos --}}
                    style="border: none; width: 100%; height: 100%;"
                    scrolling="no"
                ></iframe>
            @else
                <div class="p-4 text-red-700 bg-red-100 border border-red-300 rounded-md h-full flex items-center justify-center">
                    <strong>Error:</strong> No se pudo generar la URL segura para la vista previa.
                </div>
            @endif
        </div>
    </div>
</div>
