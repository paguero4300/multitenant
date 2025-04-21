@php
    $title = $dashboard->title;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-weight: 600; font-size: 1.25rem; color: #1f2937;">
                {{ $title }}
            </h2>
            <a href="{{ route('tenant.power-bi.index', ['tenant' => $tenant->slug]) }}" 
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; background-color: #1f2937; border-radius: 0.375rem; font-weight: 600; font-size: 0.75rem; color: white; text-transform: uppercase; text-decoration: none;">
                Volver a dashboards
            </a>
        </div>
    </x-slot>

    <div style="padding-top: 1.5rem; padding-bottom: 1.5rem;">
        <div style="background-color: white; border-radius: 0.375rem; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); margin-bottom: 1.5rem;">
            <div style="padding: 1rem 1.5rem;">
                <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <h1 style="font-size: 1.25rem; font-weight: 700; color: #111827;">{{ $dashboard->title }}</h1>
                        @if($dashboard->description)
                            <p style="margin-top: 0.25rem; font-size: 0.875rem; color: #4b5563;">{{ $dashboard->description }}</p>
                        @endif
                    </div>
                    
                    <div>
                        <p style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">URL de incrustación:</p>
                        <p style="margin-top: 0.25rem; font-size: 0.875rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <a href="{{ $embedUrl }}" target="_blank" style="color: #2563eb; text-decoration: underline;">{{ $embedUrl }}</a>
                        </p>
                    </div>
                    
                    @if($dashboard->category)
                        <div>
                            <span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 600; padding: 0.125rem 0.625rem; border-radius: 9999px;">
                                {{ $dashboard->category }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div style="background-color: white; border-radius: 0.375rem; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); height: 80vh;">
            <iframe 
                src="{{ $embedUrl }}" 
                frameborder="0" 
                allowfullscreen="true" 
                style="width: 100%; height: 100%;"
            ></iframe>
        </div>
    </div>
    
    <style>
        @media (min-width: 768px) {
            div[style*="display: flex; flex-direction: column"] {
                flex-direction: row !important;
                justify-content: space-between;
                align-items: center;
            }
        }
    </style>
    
    @push('scripts')
    <script>
        // Cualquier código JavaScript adicional que se necesite para manejar la visualización del dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard cargado: {{ $dashboard->title }}');
        });
    </script>
    @endpush
</x-app-layout>
