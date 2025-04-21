{{-- Vista para mostrar el dashboard de Power BI en un modal --}}
<div class="space-y-4">
    {{-- Mostrar descripción y metadatos del dashboard --}}
    <div class="p-4 bg-gray-50 rounded-lg">
        @if($record->description)
            <div class="text-gray-600 text-sm mb-2">
                {{ $record->description }}
            </div>
        @endif

        <div class="flex flex-wrap gap-2 mt-3">
            {{-- Categoría --}}
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                {{ ucfirst($record->category) }}
            </span>

            {{-- Estado --}}
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $record->is_active ? 'bg-success-100 text-success-800' : 'bg-danger-100 text-danger-800' }}">
                {{ $record->is_active ? 'Activo' : 'Inactivo' }}
            </span>
            
            {{-- Organizaciones con acceso --}}
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                {{ $record->tenants()->count() }} organizaciones
            </span>
        </div>
    </div>

    {{-- Iframe con el dashboard --}}
    <div class="bg-white rounded-lg overflow-hidden shadow" style="height: 70vh; width: 100%;">
        <iframe 
            src="{{ route('admin.power-bi.preview', $record) }}" 
            class="w-full h-full border-0" 
            allow="fullscreen" 
            style="border-radius: 0.5rem;"
        ></iframe>
    </div>
</div>
