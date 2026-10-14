<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div x-data="{ modalOpen: false }" class="min-h-screen flex overflow-hidden">
            
            <aside class="hidden lg:flex flex-col w-64 bg-white border-r border-gray-300 min-h-screen justify-between z-20">
                <div class="p-4">
                    <div class="flex items-center space-x-3 pb-6 border-b border-gray-200">
                        <div class="w-10 h-10 rounded bg-blue-700 flex items-center justify-center text-white font-bold text-lg">LY</div>
                        <div>
                            <h1 class="font-bold text-gray-800 text-base leading-none">Lapor Yuk!</h1>
                            <span class="text-xs text-gray-500">Sistem Pelaporan</span>
                        </div>
                    </div>

                    <nav class="mt-6 space-y-1">
                        <a href="{{ route('sos') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-md text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-colors mb-4">
                            <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span>Pusat SOS Darurat</span>
                        </a>
                        
                        <a href="{{ url('/') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-md text-sm font-medium bg-blue-50 text-blue-700 border-l-4 border-blue-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Buat Laporan Baru</span>
                        </a>
                    </nav>
                </div>

                <div class="p-4 border-t border-gray-200">
                    <a href="{{ route('login') }}" class="w-full flex justify-center py-2 px-4 border border-blue-700 rounded-md shadow-sm text-sm font-medium text-blue-700 hover:bg-blue-50 transition-colors mb-2">
                        Masuk Admin/Petugas
                    </a>
                </div>
            </aside>

            <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto pb-16 lg:pb-0 relative z-10">
                <header class="bg-white border-b border-gray-300 flex items-center justify-between px-4 sm:px-6 lg:px-8 h-16 lg:hidden">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded bg-blue-700 flex items-center justify-center text-white font-bold text-sm">LY</div>
                        <h1 class="font-bold text-gray-800 text-lg leading-none">Lapor Yuk!</h1>
                    </div>
                </header>

                <main class="flex-1 p-4 sm:p-6">
                    <div class="max-w-4xl mx-auto">
                        
                        <div class="block md:hidden mb-4">
                            <button @click="modalOpen = true" class="w-full py-3 px-4 bg-blue-700 text-white font-medium rounded-md shadow flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <span>Buat Laporan Baru</span>
                            </button>
                        </div>

                        <!-- Form langsung tampil di layar yang lebih lebar dari 768px (Desktop/Tablet) -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-300 hidden md:block">
                            <div class="p-6 text-gray-900">
                                <div class="mb-6 pb-2 border-b border-gray-200">
                                    <h3 class="text-xl font-medium text-gray-800">Sampaikan Laporan Anda</h3>
                                    <p class="text-sm text-gray-500 mt-1">Laporan akan dikirim secara anonim. Jika ini darurat, silakan gunakan menu SOS Darurat.</p>
                                </div>
                                <livewire:report-form />
                            </div>
                        </div>

                    </div>
                </main>
            </div>

            <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-300 flex justify-between items-center h-16 z-40 px-2 pb-safe shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
                <a href="{{ url('/') }}" class="flex flex-col items-center justify-center w-full h-full text-blue-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span class="text-[10px] mt-1 font-medium">Beranda</span>
                </a>

                <button @click="modalOpen = true" class="flex flex-col items-center justify-center w-full h-full text-gray-500 hover:text-blue-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="text-[10px] mt-1 font-medium">Lapor</span>
                </button>

                <a href="{{ route('sos') }}" class="flex flex-col items-center justify-center w-full h-full relative -top-4">
                    <div class="bg-red-600 text-white rounded-full p-3 shadow-lg border-4 border-white flex items-center justify-center animate-pulse">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <span class="text-[10px] mt-2 font-bold text-red-600">SOS</span>
                </a>

                <div class="w-full h-full"></div>

                <a href="{{ route('login') }}" class="flex flex-col items-center justify-center w-full h-full text-gray-500 hover:text-blue-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    <span class="text-[10px] mt-1 font-medium">Masuk</span>
                </a>
            </nav>

            <!-- Menggunakan style display:none agar render lebih stabil dibanding x-cloak -->
            <div x-show="modalOpen" style="display: none;" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                <div @click.away="modalOpen = false" class="bg-white w-full sm:w-11/12 max-w-lg max-h-[90vh] overflow-y-auto rounded-t-xl sm:rounded-lg shadow-xl p-5 mt-auto sm:mt-0">
                    <div class="flex justify-between items-center border-b pb-2 mb-4 sticky top-0 bg-white z-10">
                        <h3 class="text-lg font-medium text-gray-800">Buat Laporan Baru</h3>
                        <button @click="modalOpen = false" class="text-gray-500 hover:text-red-600 bg-gray-100 rounded-full p-1 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <livewire:report-form />
                </div>
            </div>

        </div>
        
        @livewireScripts
    </body>
</html>