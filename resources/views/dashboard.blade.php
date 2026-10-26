<x-app-layout>
<<<<<<< HEAD
    @php
        $user = auth()->user();
    @endphp

    <div class="mx-auto max-w-7xl space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">
                    {{ $user->isAdmin() ? 'Pusat Kendali Petugas' : 'Laporan Saya' }}
                </p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $user->isAdmin() ? 'Pemantauan Laporan Warga' : 'Riwayat Laporan Anda' }}
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $user->isAdmin()
                        ? 'Pantau laporan masuk, perbarui status, dan koordinasikan penanganan masalah lingkungan dan fasilitas publik.'
                        : 'Pantau status laporan yang Anda kirim ke petugas.' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    Lihat peta
                </a>
                <a href="{{ route('lapor') }}" class="inline-flex items-center gap-2 rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">
                    Buat laporan
                </a>
            </div>
        </div>

        <livewire:report-stats />

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                            {{ $user->isAdmin() ? 'Semua laporan warga' : 'Laporan terbaru' }}
                        </h2>
                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                            Live
                        </span>
                    </div>

                    <div class="p-4">
                        <livewire:report-list />
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Aksi cepat</h2>
                    <div class="mt-3 space-y-2">
                        <a href="{{ route('lapor') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                            Laporkan masalah lingkungan &amp; fasilitas
                        </a>
                        <a href="{{ route('feedback') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                            Kirim masukan &amp; saran
                        </a>
                        <a href="{{ route('darurat') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                            Lihat nomor penting
                        </a>
                        <a href="{{ route('home') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                            Buka peta warga
                        </a>

                        @if ($user->isAdmin())
                            <a href="{{ route('kategori.index') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                Kelola kategori
                            </a>
                        @endif
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Alur status laporan</h2>
                    <ul class="mt-3 space-y-2 text-xs text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2"><x-status-badge status="pending" /> menunggu verifikasi petugas.</li>
                        <li class="flex items-center gap-2"><x-status-badge status="processing" /> sedang ditangani di lapangan.</li>
                        <li class="flex items-center gap-2"><x-status-badge status="resolved" /> perbaikan atau penanganan selesai.</li>
                    </ul>
                    @if ($user->isAdmin())
                        <p class="mt-3 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            Buka detail laporan untuk memperbarui status dan menambahkan catatan petugas.
                        </p>
                    @endif
                </div>
            </div>
=======
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{ __("You're logged in!") }}
                </div>
            </div>
>>>>>>> 150e879 (beta release)
        </div>
    </div>
</x-app-layout>
