<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-4">
        <div>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali ke peta
            </a>

            <p class="mt-2 text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">Suara warga</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">Masukan &amp; Saran</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Bantu kami memperbaiki portal ini. Sampaikan saran, kritik, atau apresiasi Anda — pengiriman dapat dilakukan
                tanpa akun.
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-900">
            <livewire:feedback-form />
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Bisa anonim</p>
                <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">Kirim tanpa akun. Nama dan kontak bersifat opsional.</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Bukan kanal laporan</p>
                <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                    Untuk masalah lingkungan atau fasilitas publik,
                    <a href="{{ route('lapor') }}" class="font-medium text-blue-700 hover:underline dark:text-blue-400">buat laporan warga</a>.
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Kami baca semua</p>
                <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">Masukan dipakai untuk menyempurnakan layanan portal.</p>
            </div>
        </div>
    </div>
</x-app-layout>
