<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-4">
        <div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('Kembali ke pusat kendali') }}
            </a>

            <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('Kelola Kategori') }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Atur kategori untuk mengelompokkan laporan masalah lingkungan dan fasilitas publik warga.') }}
            </p>
        </div>

        <livewire:kategori-admin />
    </div>
</x-app-layout>
