@php
    $title = "Vista previa: {$dashboard->title}";
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 style="font-weight: 600; font-size: 1.25rem; color: #1f2937;">
            {{ $title }}
        </h2>
    </x-slot>

    <div style="padding-top: 3rem; padding-bottom: 3rem;">
        <div style="margin-bottom: 1.5rem;">
            <div style="background-color: white; border-radius: 0.375rem; padding: 1rem 2rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div>
                        <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827;">{{ $dashboard->title }}</h3>
                        <p style="font-size: 0.875rem; color: #4b5563;">{{ $dashboard->description }}</p>
                    </div>
                    <div>
                        <a href="{{ route('filament.admin.resources.power-bi-dashboards.index') }}" style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; background-color: #1f2937; border-radius: 0.375rem; font-weight: 600; font-size: 0.75rem; color: white; text-transform: uppercase; text-decoration: none;">
                            Volver
                        </a>
                    </div>
                </div>

                <div style="background-color: #f3f4f6; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem;">
                    <h4 style="font-weight: 600; margin-bottom: 0.5rem; color: #111827;">Información del Dashboard</h4>
                    <dl style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                        <div>
                            <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">URL de incrustación</dt>
                            <dd style="margin-top: 0.25rem; font-size: 0.875rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <a href="{{ $dashboard->embed_url }}" target="_blank" style="color: #2563eb; text-decoration: underline;">{{ $dashboard->embed_url }}</a>
                            </dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">ID de reporte</dt>
                            <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $dashboard->report_id ?: 'No especificado' }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Categoría</dt>
                            <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">{{ $dashboard->category ?: 'Sin categoría' }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.875rem; font-weight: 500; color: #6b7280;">Estado</dt>
                            <dd style="margin-top: 0.25rem; font-size: 0.875rem; color: #111827;">
                                <span style="padding: 0 0.5rem; display: inline-flex; font-size: 0.75rem; line-height: 1.25rem; font-weight: 600; border-radius: 9999px; {{ $dashboard->is_active ? 'background-color: #d1fae5; color: #065f46;' : 'background-color: #fee2e2; color: #991b1b;' }}">
                                    {{ $dashboard->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.375rem; overflow: hidden; height: 75vh;">
                    <iframe src="{{ $embedUrl }}" frameborder="0" allowfullscreen="true" style="width: 100%; height: 100%;"></iframe>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        @media (min-width: 768px) {
            dl[style*="display: grid"] {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</x-app-layout>
