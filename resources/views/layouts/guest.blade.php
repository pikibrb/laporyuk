<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ __('Portal pelaporan warga berbasis peta untuk pemantauan masalah lingkungan dan fasilitas publik.') }}">

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

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-gray-100 font-sans text-gray-800 antialiased dark:bg-gray-950 dark:text-gray-200">
        <div class="flex min-h-screen flex-col">
            <header class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                    <x-brand />

                    <div class="flex items-center gap-4">
                        <a href="{{ route('home') }}" class="text-sm font-medium text-gray-600 hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-400">{{ __('Peta Warga') }}</a>
                        <a href="{{ route('feedback') }}" class="hidden text-sm font-medium text-gray-600 hover:text-blue-700 sm:inline dark:text-gray-400 dark:hover:text-blue-400">{{ __('Masukan') }}</a>
                        <a href="{{ route('darurat') }}" class="text-sm font-medium text-gray-600 hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-400">
                            {{ __('Nomor Penting') }}
                        </a>

                        <x-locale-switcher />

                        <x-theme-toggle compact />
                    </div>
                </div>
            </header>

            <main class="flex flex-1 items-center justify-center px-4 py-10">
                <div class="w-full max-w-md">
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        {{ $slot }}
                    </div>

                    <p class="mt-4 text-center text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Laporan warga dapat dikirim tanpa akun melalui') }}
                        <a href="{{ route('lapor') }}" class="font-medium text-blue-700 hover:underline dark:text-blue-400">{{ __('formulir laporan') }}</a>.
                    </p>
                </div>
            </main>
        </div>

        <x-toast-stack :initial="session('notify')" />

        @livewireScripts
    </body>
</html>
