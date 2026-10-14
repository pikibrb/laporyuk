<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div x-data="{ modalOpen: false }" class="min-h-screen flex overflow-hidden">
            
            <livewire:sidebar />

            <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto pb-16 lg:pb-0">
                <header class="bg-white border-b border-gray-300 flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16 lg:hidden">
                    @if (isset($header))
                        {{ $header }}
                    @endif
                </header>

                <main class="flex-1 p-4 sm:p-6">
                    {{ $slot }}
                </main>
            </div>

            <livewire:bottom-nav />
            
        </div>
    </body>
</html>