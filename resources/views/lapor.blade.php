<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-4">
        <div>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke peta
            </a>

            <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">Buat Laporan Warga</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Tandai lokasi masalah lingkungan atau fasilitas publik di sekitar Anda. Laporan dapat dikirim secara anonim.
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-900">
            <livewire:report-form />
        </div>

        <div class="flex flex-col items-start justify-between gap-3 rounded-lg border border-gray-200 bg-white p-4 sm:flex-row sm:items-center dark:border-gray-700 dark:bg-gray-900">
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Keadaan darurat?</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">Portal ini bukan kanal darurat. Hubungi nomor layanan yang sesuai.</p>
            </div>
            <a href="{{ route('darurat') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                Lihat nomor penting
            </a>
        </div>
    </div>
</x-app-layout>
