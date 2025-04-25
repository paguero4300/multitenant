<x-filament-panels::page>
@php
    $currentDate = \Carbon\Carbon::now()->translatedFormat('l, j \\de F \\de Y');
@endphp

<!-- Header con fecha y estado -->
<div class="border-b pb-4 mb-6 border-gray-200 dark:border-gray-700 flex justify-between items-center">
    <div class="flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500 dark:text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $currentDate }}</span>
    </div>
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100 rounded-full px-3 py-1 text-xs font-medium">
        <div class="flex items-center">
            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
            <span>Sistema activo</span>
        </div>
    </div>
</div>

<!-- Contenido Principal Simple -->
<div class="max-w-4xl mx-auto px-4 py-6 text-center">
    <div class="flex justify-center mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-primary-500 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
        </svg>
    </div>
    
    <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-4">Inteligencia de Negocios</h3>
    
    <p class="text-gray-600 dark:text-gray-300 text-base leading-relaxed mb-8 max-w-2xl mx-auto">
        Aquí podrás revisar todos los reportes de inteligencia de negocios (BI) de la empresa, 
        mantenerte informado con datos relevantes y tomar decisiones estratégicas con mayor seguridad. 
        Esperamos que esta herramienta sea de gran utilidad para tu trabajo diario.
    </p>
    
    <p class="text-gray-600 dark:text-gray-300 mb-6">
        Si tienes alguna duda o necesitas asistencia, no dudes en contactar a nuestro equipo de soporte. 
        <span class="text-primary-600 dark:text-primary-400">¡Gracias por confiar en nosotros!</span>
    </p>
    
    <div class="flex justify-center space-x-6 text-gray-400 dark:text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
    </div>
</div>

</x-filament-panels::page>