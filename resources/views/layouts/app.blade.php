<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
<<<<<<< HEAD
        <meta name="description" content="Portal pelaporan warga berbasis peta untuk pemantauan masalah lingkungan dan fasilitas publik secara real-time.">

        <title>{{ config('app.name', 'Lapor Yuk!') }}</title>

        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('theme');
                    var dark = stored ? stored === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    document.documentElement.classList.toggle('dark', dark);
                } catch (error) {}
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body class="bg-gray-100 font-sans text-gray-800 antialiased dark:bg-gray-950 dark:text-gray-200">
        <div class="min-h-screen lg:flex">
            <livewire:sidebar />

            <div class="flex min-w-0 flex-1 flex-col pb-20 lg:h-screen lg:overflow-y-auto lg:pb-0">
                <header class="sticky top-0 z-30 border-b border-gray-200 bg-white lg:hidden dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex h-16 items-center justify-between gap-3 px-4">
                        <x-brand />

                        <div class="flex items-center gap-2">
                            <x-theme-toggle compact />

                            <a href="{{ route('darurat') }}" class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2.28a1 1 0 01.95.68l1.1 3.3a1 1 0 01-.27 1.06l-1.2 1.1a11 11 0 005.5 5.5l1.1-1.2a1 1 0 011.06-.27l3.3 1.1a1 1 0 01.68.95V19a2 2 0 01-2 2h-1A16 16 0 013 6V5z"/>
                                </svg>
                                Nomor Penting
                            </a>
                        </div>
                    </div>

                    @isset($header)
                        <div class="border-t border-gray-100 px-4 py-2 dark:border-gray-800">
                            {{ $header }}
                        </div>
                    @endisset
=======

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
>>>>>>> 150e879 (beta release)
                </header>
            @endif

<<<<<<< HEAD
                <main class="flex-1 px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
                    {{ $slot }}
                </main>

                <footer class="hidden border-t border-gray-200 px-8 py-4 text-xs text-gray-500 lg:block dark:border-gray-700 dark:text-gray-400">
                    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2">
                        <p>Lapor Yuk! — portal pelaporan warga untuk masalah lingkungan dan fasilitas publik.</p>
                        <p class="inline-flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                            Peta diperbarui otomatis
                        </p>
                    </div>
                </footer>
            </div>

            <livewire:bottom-nav />
=======
            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
>>>>>>> 150e879 (beta release)
        </div>

        <x-toast-stack :initial="session('notify')" />

        @livewireScripts
    </body>
</html>
