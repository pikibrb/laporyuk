<?php

use App\Models\Category;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public string $mapId;

    public string $title = '';

    public string $description = '';

    public string $phone = '';

    public string $category_id = '';

    public $photo;

    public string $latitude = '';

    public string $longitude = '';

    public ?string $submittedReportUuid = null;

    public function mount(): void
    {
        $this->mapId = 'report-map-'.uniqid();
    }

    public function submit(): void
    {
        $rateLimitKey = 'report-submit:'.(Auth::id() ?? request()->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $this->addError('title', __('Terlalu banyak laporan dikirim dari perangkat ini. Coba lagi dalam :seconds detik.', ['seconds' => RateLimiter::availableIn($rateLimitKey)]));

            return;
        }

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+\-\s().]+$/'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $photoPath = $this->photo?->store('reports', 'public');

        $report = Report::create([
            'user_id' => Auth::id(),
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'phone' => ($validated['phone'] ?? null) ?: null,
            'photo_path' => $photoPath,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => Report::STATUS_PENDING,
        ]);

        RateLimiter::hit($rateLimitKey, 600);

        $this->submittedReportUuid = $report->uuid;
        $this->reset(['title', 'description', 'phone', 'category_id', 'photo', 'latitude', 'longitude']);
        $this->resetValidation();
        $this->dispatch('report-submitted');
        $this->dispatch('notify', type: 'success', title: __('Laporan terkirim'), message: __('Laporan Anda berhasil dikirim dan menunggu verifikasi petugas.'));
    }

    public function removePhoto(): void
    {
        $this->reset('photo');
        $this->resetValidation('photo');
    }

    public function resetForm(): void
    {
        $this->reset(['title', 'description', 'phone', 'category_id', 'photo', 'latitude', 'longitude', 'submittedReportUuid']);
        $this->resetValidation();
        $this->dispatch('report-submitted');
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(),
        ];
    }
}; ?>

<div>
    @if ($submittedReportUuid)
        <div class="rounded-md border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">{{ __('Laporan berhasil dikirim') }}</p>
                    <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-200">
                        {{ __('Nomor laporan') }} <span class="font-semibold">#{{ strtoupper(substr($submittedReportUuid, 0, 8)) }}</span>.
                        {{ Auth::check()
                            ? __('Petugas akan memverifikasi laporan Anda dan memperbarui statusnya secara berkala.')
                            : __('Petugas akan memverifikasi laporan anonim ini dan memperbarui statusnya secara berkala.') }}
                    </p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('laporan.show', $submittedReportUuid) }}" class="rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">
                    {{ __('Lihat detail laporan') }}
                </a>
                <button type="button" wire:click="resetForm" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                    {{ __('Buat laporan lain') }}
                </button>
            </div>
        </div>
    @else
        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                <p class="font-medium">{{ __('Laporan belum bisa dikirim.') }}</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form wire:submit="submit" class="space-y-4">
            <div>
                <label for="title" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Judul laporan') }}</label>
                <input
                    id="title"
                    type="text"
                    wire:model="title"
                    placeholder="{{ __('Contoh: Lampu jalan mati di simpang Jalan Gatot Subroto') }}"
                    class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('title') border-red-300 dark:border-red-500/40 @enderror"
                >
                @error('title')
                    <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="category_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Kategori') }}</label>
                    <select
                        id="category_id"
                        wire:model="category_id"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('category_id') border-red-300 dark:border-red-500/40 @enderror"
                    >
                        <option value="">{{ __('Pilih kategori') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="photo" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Foto bukti') }} <span class="font-normal text-gray-500 dark:text-gray-400">{{ __('(opsional)') }}</span></label>
                    <input
                        id="photo"
                        type="file"
                        wire:model="photo"
                        accept="image/*"
                        class="w-full text-sm text-gray-500 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:text-gray-400 dark:file:bg-gray-700 dark:file:text-gray-300 dark:hover:file:bg-gray-600"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Format JPG, PNG, atau WEBP. Ukuran maksimal 2 MB.') }}</p>

                    <div wire:loading wire:target="photo" class="mt-2 flex items-center gap-2 rounded-md border border-blue-200 bg-blue-50 p-2 text-xs text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ __('Mengunggah foto...') }}
                    </div>

                    @error('photo')
                        <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror

                    @if ($photo && $photo->isPreviewable())
                        <div wire:loading.remove wire:target="photo" class="mt-3 flex items-start gap-3 rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                            <a href="{{ $photo->temporaryUrl() }}" target="_blank" rel="noopener" class="block shrink-0 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                                <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau foto laporan" class="h-20 w-20 object-cover">
                            </a>

                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Pratinjau foto') }}</p>
                                <p class="mt-0.5 truncate text-sm font-medium text-gray-800 dark:text-gray-200">{{ $photo->getClientOriginalName() }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($photo->getSize() / 1024, 0) }} KB ·
                                    <a href="{{ $photo->temporaryUrl() }}" target="_blank" rel="noopener" class="font-medium text-blue-700 hover:underline dark:text-blue-400">{{ __('Lihat ukuran penuh') }}</a>
                                </p>

                                <button type="button" wire:click="removePhoto" class="mt-2 inline-flex items-center gap-1 rounded-md border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    {{ __('Hapus foto') }}
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <label for="description" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Deskripsi detail') }}</label>
                <textarea
                    id="description"
                    wire:model="description"
                    rows="4"
                    placeholder="{{ __('Jelaskan kondisi, dampak, dan sejak kapan masalah ini terjadi.') }}"
                    class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('description') border-red-300 dark:border-red-500/40 @enderror"
                ></textarea>
                @error('description')
                    <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="phone" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Nomor telepon/WhatsApp') }} <span class="font-normal text-gray-500 dark:text-gray-400">{{ __('(opsional)') }}</span>
                </label>
                <input
                    id="phone"
                    type="tel"
                    inputmode="tel"
                    wire:model="phone"
                    placeholder="{{ __('Contoh: 0812 3456 7890') }}"
                    class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('phone') border-red-300 dark:border-red-500/40 @enderror"
                >
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Dipakai petugas untuk menghubungi Anda bila perlu klarifikasi lokasi atau kondisi.') }}</p>
                @error('phone')
                    <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between gap-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tandai lokasi kejadian') }}</label>
                    <button
                        type="button"
                        onclick="window.reportFormLocate && window.reportFormLocate()"
                        class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        {{ __('Gunakan lokasi saya') }}
                    </button>
                </div>

                <div wire:ignore id="{{ $mapId }}" class="isolate h-64 w-full rounded-md border border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-800"></div>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Klik peta untuk menaruh pin di titik kejadian.') }}</p>

                <div class="mt-2 grid grid-cols-2 gap-2">
                    <input type="text" wire:model="latitude" readonly placeholder="Latitude" class="w-full cursor-not-allowed rounded-md border-gray-200 bg-gray-50 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    <input type="text" wire:model="longitude" readonly placeholder="Longitude" class="w-full cursor-not-allowed rounded-md border-gray-200 bg-gray-50 text-xs text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                </div>

                @error('latitude')
                    <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ __('Lokasi wajib dipilih dari peta.') }}</span>
                @enderror
            </div>

            <div class="flex flex-col gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ Auth::check() ? __('Laporan tercatat atas nama akun Anda.') : __('Laporan dikirim secara anonim tanpa akun.') }}
                </p>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-60" wire:loading.attr="disabled" wire:target="submit,photo">
                    <span wire:loading.remove wire:target="submit">{{ __('Kirim laporan') }}</span>
                    <span wire:loading wire:target="submit">{{ __('Mengirim...') }}</span>
                </button>
            </div>
        </form>
    @endif
</div>

@script
<script>
    (() => {
        const mapId = @js($mapId);
        const container = document.getElementById(mapId);

        if (!container || container.dataset.initialized === '1') {
            return;
        }

        container.dataset.initialized = '1';

        const defaultCenter = [3.5952, 98.6722];
        const map = L.map(mapId).setView(defaultCenter, 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        let marker = null;

        const placeMarker = (point, zoom = null) => {
            if (marker) {
                marker.setLatLng(point);
            } else {
                marker = L.marker(point, { draggable: true }).addTo(map);

                marker.on('dragend', () => {
                    const position = marker.getLatLng();
                    $wire.set('latitude', position.lat.toFixed(8));
                    $wire.set('longitude', position.lng.toFixed(8));
                });
            }

            if (zoom) {
                map.setView(point, zoom);
            }

            $wire.set('latitude', point[0].toFixed(8));
            $wire.set('longitude', point[1].toFixed(8));
        };

        map.on('click', (event) => {
            placeMarker([event.latlng.lat, event.latlng.lng]);
        });

        window.reportFormLocate = () => {
            if (!navigator.geolocation) {
                window.alert(@js(__('Perangkat tidak mendukung deteksi lokasi.')));
                return;
            }

            navigator.geolocation.getCurrentPosition((position) => {
                placeMarker([position.coords.latitude, position.coords.longitude], 16);
            }, () => {
                window.alert(@js(__('Tidak dapat mengakses lokasi. Pastikan izin lokasi sudah diberikan.')));
            }, { enableHighAccuracy: true });
        };

        Livewire.on('report-submitted', () => {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }

            map.setView(defaultCenter, 13);
        });

        window.addEventListener('resize', () => map.invalidateSize());
    })();
</script>
@endscript
