<x-app-layout>
    @php
        $hotlines = [
            ['number' => '112', 'label' => 'Darurat nasional', 'description' => 'Satu nomor untuk semua keadaan darurat, gratis dan aktif 24 jam.'],
            ['number' => '110', 'label' => 'Kepolisian', 'description' => 'Tindak kriminalitas, gangguan keamanan, dan kecelakaan lalu lintas.'],
            ['number' => '113', 'label' => 'Pemadam kebakaran', 'description' => 'Kebakaran bangunan, lahan, serta penyelamatan terjebak.'],
            ['number' => '119', 'label' => 'Ambulans & medis', 'description' => 'Kedaruratan medis dan permintaan rujukan rumah sakit.'],
            ['number' => '115', 'label' => 'Basarnas (SAR)', 'description' => 'Pencarian dan pertolongan di darat, laut, maupun udara.'],
            ['number' => '123', 'label' => 'PLN', 'description' => 'Gangguan listrik, kabel jatuh, dan jaringan yang membahayakan.'],
        ];
    @endphp

    <div class="mx-auto max-w-4xl space-y-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">Referensi cepat</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">Nomor Penting Layanan Warga</h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-400">
                Daftar nomor layanan yang dapat Anda hubungi di luar portal ini. Untuk masalah lingkungan
                dan fasilitas publik, gunakan formulir laporan warga agar tercatat dan ditindaklanjuti petugas.
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Nomor layanan</h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ketuk nomor untuk langsung menelepon dari perangkat Anda.</p>

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($hotlines as $hotline)
                    <a
                        href="tel:{{ $hotline['number'] }}"
                        class="flex items-start gap-3 rounded-md border border-gray-200 p-3 transition-colors hover:border-blue-300 hover:bg-blue-50 dark:border-gray-700 dark:hover:border-blue-500/40 dark:hover:bg-blue-500/10"
                    >
                        <span class="flex h-10 w-12 shrink-0 items-center justify-center rounded-md bg-blue-50 text-sm font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                            {{ $hotline['number'] }}
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $hotline['label'] }}</span>
                            <span class="mt-0.5 block text-xs leading-relaxed text-gray-600 dark:text-gray-400">{{ $hotline['description'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            <p class="mt-3 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                Sebagian daerah memakai nomor berbeda untuk pemadam kebakaran atau ambulans. Simpan juga nomor
                layanan darurat kota Anda.
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 lg:col-span-2 dark:border-gray-700 dark:bg-gray-900">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Portal ini untuk laporan non-darurat</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Portal Lapor Yuk! memuat laporan lingkungan dan fasilitas publik, bukan keadaan darurat seperti
                    kebakaran, kecelakaan, atau kebutuhan medis.
                </p>

                <ul class="mt-3 space-y-3 text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                    <li class="flex gap-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[11px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">1</span>
                        Untuk keadaan darurat, segera hubungi nomor layanan di atas.
                    </li>
                    <li class="flex gap-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[11px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">2</span>
                        Untuk sampah menumpuk, pencemaran, jalan rusak, atau fasilitas umum yang terbengkalai, buat laporan warga.
                    </li>
                    <li class="flex gap-2">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[11px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">3</span>
                        Laporan warga dapat dikirim tanpa akun dan dipantau statusnya hingga selesai.
                    </li>
                </ul>

                <a href="{{ route('lapor') }}" class="mt-4 inline-block rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">
                    Buat laporan warga
                </a>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Kapan menggunakan portal</h2>
                <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                    Gunakan portal bila masalahnya berkaitan dengan lingkungan atau fasilitas publik dan tidak
                    membahayakan nyawa secara langsung.
                </p>
                <a href="{{ route('home') }}" class="mt-3 inline-block text-xs font-medium text-blue-700 hover:underline dark:text-blue-400">
                    Lihat peta laporan warga
                </a>
                <a href="{{ route('feedback') }}" class="mt-1 inline-block text-xs font-medium text-blue-700 hover:underline dark:text-blue-400">
                    Kirim masukan &amp; saran
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
