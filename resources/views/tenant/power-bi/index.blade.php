@php
    $title = "Dashboards disponibles";
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 style="font-weight: 600; font-size: 1.25rem; color: #1f2937;">
            {{ $title }}
        </h2>
    </x-slot>

    <div style="margin-bottom: 2rem;">
        <div style="background-color: white; border-radius: 0.375rem; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);">
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1.125rem; font-weight: 500; color: #111827; margin-bottom: 0.5rem;">Dashboards de Power BI</h3>
                    <p style="margin-top: 0.25rem; font-size: 0.875rem; color: #4b5563;">
                        A continuación se muestran los dashboards disponibles para {{ $tenant->name }}.
                    </p>
                </div>

                    @if($dashboards->isEmpty())
                        <div style="background-color: #f9fafb; border-radius: 0.375rem; padding: 1rem;">
                            <p style="text-align: center; color: #6b7280;">No hay dashboards disponibles en este momento.</p>
                        </div>
                    @else
                        <div style="display: grid; grid-template-columns: repeat(1, 1fr); gap: 1.5rem;">
                            @foreach($dashboards as $dashboard)
                                <div style="background-color: white; border: 1px solid #e5e7eb; border-radius: 0.375rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                                    <div style="height: 10rem; background-color: #e5e7eb; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                        @if($dashboard->thumbnail)
                                            <img src="{{ $dashboard->thumbnail }}" alt="{{ $dashboard->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <svg style="width: 6rem; height: 6rem; color: #9ca3af;" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M10 3H4a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1V4a1 1 0 00-1-1zM9 9H5V5h4v4zm11-6h-6a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1V4a1 1 0 00-1-1zm-1 6h-4V5h4v4zm-9 4H4a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1v-6a1 1 0 00-1-1zm-1 6H5v-4h4v4zm8-6c-2.206 0-4 1.794-4 4s1.794 4 4 4 4-1.794 4-4-1.794-4-4-4zm0 6c-1.103 0-2-.897-2-2s.897-2 2-2 2 .897 2 2-.897 2-2 2z"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    <div style="padding: 1rem;">
                                        <h5 style="margin-bottom: 0.5rem; font-size: 1.25rem; font-weight: 700; color: #111827;">{{ $dashboard->title }}</h5>
                                        <p style="margin-bottom: 0.75rem; font-size: 0.875rem; color: #4b5563; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $dashboard->description ?: 'Sin descripción' }}</p>
                                        
                                        @if($dashboard->category)
                                            <span style="background-color: #dbeafe; color: #1e40af; font-size: 0.75rem; font-weight: 600; padding: 0.125rem 0.625rem; border-radius: 9999px;">
                                                {{ $dashboard->category }}
                                            </span>
                                        @endif
                                        
                                        <div style="margin-top: 1rem;">
                                            <a href="{{ route('tenant.power-bi.show', ['tenant' => $tenant->slug, 'dashboard' => $dashboard->id]) }}" 
                                               style="display: inline-flex; width: 100%; justify-content: center; align-items: center; padding: 0.5rem 0.75rem; font-size: 0.875rem; font-weight: 500; text-align: center; color: white; background-color: #1d4ed8; border-radius: 0.375rem; text-decoration: none;">
                                                Ver dashboard
                                                <svg style="width: 0.875rem; height: 0.875rem; margin-left: 0.5rem;" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        @media (min-width: 768px) {
            div[style*="display: grid"] {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1024px) {
            div[style*="display: grid"] {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</x-app-layout>
