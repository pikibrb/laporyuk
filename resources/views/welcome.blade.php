<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">Portal Pelaporan Warga</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">Peta Fasilitas Publik &amp; Masalah Lingkungan</h1>
                <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-400">
                    Pantau laporan warga di sekitar Anda secara real-time, dari pencemaran dan sampah hingga fasilitas publik yang terbengkalai.
                </p>
            </div>

            <div class="flex shrink-0 gap-2">
                <a href="{{ route('lapor') }}" class="inline-flex items-center gap-2 rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Laporan
                </a>
                <a href="{{ route('feedback') }}" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    Masukan
                </a>
                <a href="{{ route('darurat') }}" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    Nomor Penting
                </a>
            </div>
        </div>

        <livewire:report-stats />

        <livewire:report-map />

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Lapor dengan lokasi</p>
                <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">Tandai titik kejadian pada peta agar petugas langsung menemukan lokasinya.</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Pantau status</p>
                <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">Setiap laporan memiliki riwayat status dari menunggu, diproses, hingga selesai.</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Nomor penting</p>
                <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">Butuh layanan terkait? Buka daftar nomor penting untuk menghubungi instansi penangan masalah.</p>
            </div>
        </div>
    </div>
</x-app-layout>
