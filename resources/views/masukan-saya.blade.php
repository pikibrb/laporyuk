<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-4">
        <div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('Kembali ke laporan saya') }}
            </a>

            <p class="mt-2 text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">{{ __('Suara warga') }}</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('Masukan Saya') }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Lihat kembali kritik, saran, dan apresiasi yang Anda kirim beserta tanggapan petugas.') }}
            </p>
        </div>

        <livewire:feedback-history />
    </div>
</x-app-layout>
