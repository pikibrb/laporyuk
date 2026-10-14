<?php

use Livewire\Volt\Component;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Support\Str;

new class extends Component {
    public function createEmergencyReport($categoryId, $latitude, $longitude)
    {
        $category = Category::findOrFail($categoryId);

        Report::create([
            'user_id' => Auth::check() ? Auth::id() : null,
            'category_id' => $category->id,
            'title' => 'SOS Darurat: ' . $category->name,
            'description' => 'Laporan darurat otomatis dari fitur SOS. Lokasi dikirim secara real-time.',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => 'pending',
        ]);
    }

    public function with(): array
    {
        $categories = Category::where('urgency_level', 'high')->get()->map(function ($cat) {
            $nameLower = strtolower($cat->name);
            
            if (Str::contains($nameLower, ['kebakaran'])) {
                $cat->color_class = 'bg-orange-600 hover:bg-orange-700 border-orange-300 text-white';
                $cat->icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path>';
            } elseif (Str::contains($nameLower, ['medis', 'kesehatan'])) {
                $cat->color_class = 'bg-emerald-600 hover:bg-emerald-700 border-emerald-300 text-white';
                $cat->icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>';
            } elseif (Str::contains($nameLower, ['kriminalitas', 'keamanan'])) {
                $cat->color_class = 'bg-blue-800 hover:bg-blue-900 border-blue-400 text-white';
                $cat->icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>';
            } elseif (Str::contains($nameLower, ['bencana', 'banjir', 'longsor'])) {
                $cat->color_class = 'bg-cyan-700 hover:bg-cyan-800 border-cyan-300 text-white';
                $cat->icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>';
            } else {
                $cat->color_class = 'bg-red-600 hover:bg-red-700 border-red-300 text-white';
                $cat->icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>';
            }
            return $cat;
        });

        return [
            'sosCategories' => $categories,
        ];
    }
}; ?>

<div x-data="sosHandler">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 px-4 sm:px-0">
        @foreach($sosCategories as $cat)
            <button type="button" @click="trigger({{ $cat->id }}, '{{ $cat->name }}')" class="p-8 rounded-2xl shadow-xl flex flex-col items-center justify-center transition-transform transform active:scale-95 border-4 {{ $cat->color_class }}">
                <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $cat->icon !!}
                </svg>
                <span class="text-xl font-bold text-center">{{ $cat->name }}</span>
            </button>
        @endforeach
    </div>
</div>

@script
<script>
    Alpine.data('sosHandler', () => ({
        trigger(categoryId, categoryName) {
            Swal.fire({
                title: 'Peringatan Darurat!',
                text: "Kirim laporan " + categoryName + " sekarang? Lokasi Anda akan otomatis terkirim.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Kirim Laporan Darurat',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (navigator.geolocation) {
                        Swal.fire({
                            title: 'Mengambil Lokasi...',
                            text: 'Mohon tetap di tempat, jangan tutup aplikasi.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        navigator.geolocation.getCurrentPosition(async (position) => {
                            await $wire.createEmergencyReport(categoryId, position.coords.latitude, position.coords.longitude);
                            Swal.fire({
                                title: 'Terkirim!',
                                text: 'Laporan darurat dan lokasi Anda telah dikirim ke petugas.',
                                icon: 'success',
                                confirmButtonColor: '#1d4ed8'
                            }).then(() => {
                                window.location.href = "{{ route('dashboard') }}";
                            });
                        }, (error) => {
                            Swal.fire('Gagal!', 'Tidak dapat mengakses GPS. Pastikan izin lokasi diberikan.', 'error');
                        }, {
                            enableHighAccuracy: true
                        });
                    } else {
                        Swal.fire('Gagal!', 'Perangkat tidak mendukung deteksi lokasi.', 'error');
                    }
                }
            });
        }
    }));
</script>
@endscript