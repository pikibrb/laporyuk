<x-app-layout>
    @php
        $user = auth()->user();
    @endphp

    <div class="mx-auto max-w-7xl space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">
                    {{ $user->isAdmin() ? __('Pusat Kendali Petugas') : __('Laporan Saya') }}
                </p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $user->isAdmin() ? __('Pemantauan Laporan Warga') : __('Riwayat Laporan Anda') }}
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $user->isAdmin()
                        ? __('Pantau laporan masuk, perbarui status, dan koordinasikan penanganan masalah lingkungan dan fasilitas publik.')
                        : __('Pantau status laporan yang Anda kirim ke petugas.') }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    {{ __('Lihat peta') }}
                </a>
                @if ($user->isAdmin())
                    <a href="{{ route('masukan.index') }}" class="inline-flex items-center gap-2 rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">
                        {{ __('Kotak masukan') }}
                    </a>
                @else
                    <a href="{{ route('lapor') }}" class="inline-flex items-center gap-2 rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">
                        {{ __('Buat laporan') }}
                    </a>
                @endif
            </div>
        </div>

        <livewire:report-stats :scope="$user->isAdmin() ? 'all' : 'mine'" />

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                            {{ $user->isAdmin() ? __('Semua laporan warga') : __('Laporan saya terbaru') }}
                        </h2>
                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                            {{ __('Live') }}
                        </span>
                    </div>

                    <div class="p-4">
                        <livewire:report-list />
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                @if ($user->isAdmin())
                    <livewire:feedback-summary />
                @endif

                @unless ($user->isAdmin())
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Masukan & saran saya') }}</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Pantau kritik dan saran yang Anda kirim beserta tanggapan petugas.') }}</p>
                        <a href="{{ route('masukan.saya') }}" class="mt-3 inline-flex w-full items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                            {{ __('Lihat masukan saya') }}
                        </a>
                    </div>
                @endunless

                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Aksi cepat') }}</h2>
                    <div class="mt-3 space-y-2">
                        @unless ($user->isAdmin())
                            <a href="{{ route('lapor') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                {{ __('Laporkan masalah lingkungan & fasilitas') }}
                            </a>
                        @endunless
                        @unless ($user->isAdmin())
                            <a href="{{ route('feedback') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                {{ __('Kirim masukan & saran') }}
                            </a>
                        @endunless
                        @unless ($user->isAdmin())
                            <a href="{{ route('masukan.saya') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                {{ __('Lihat tanggapan masukan saya') }}
                            </a>
                        @endunless
                        <a href="{{ route('darurat') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                            {{ __('Lihat nomor penting') }}
                        </a>
                        <a href="{{ route('home') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                            {{ __('Buka peta warga') }}
                        </a>

                        @if ($user->isAdmin())
                            <a href="{{ route('masukan.index') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                {{ __('Tinjau masukan & saran warga') }}
                            </a>
                            <a href="{{ route('kategori.index') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                {{ __('Kelola kategori') }}
                            </a>
                            <a href="{{ route('hotline.index') }}" class="block rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                                {{ __('Kelola nomor penting') }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Alur status laporan') }}</h2>
                    <ul class="mt-3 space-y-2 text-xs text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2"><x-status-badge status="pending" /> {{ __('menunggu verifikasi petugas.') }}</li>
                        <li class="flex items-center gap-2"><x-status-badge status="processing" /> {{ __('sedang ditangani di lapangan.') }}</li>
                        <li class="flex items-center gap-2"><x-status-badge status="resolved" /> {{ __('perbaikan atau penanganan selesai.') }}</li>
                    </ul>
                    @if ($user->isAdmin())
                        <p class="mt-3 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            {{ __('Buka detail laporan untuk memperbarui status dan menambahkan catatan petugas.') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
