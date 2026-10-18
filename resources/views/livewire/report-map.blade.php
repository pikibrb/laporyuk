<?php

use App\Models\Category;
use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';

    public string $status = '';

    public string $category = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'category'], true)) {
            $this->dispatchMarkers();
        }
    }

    public function refresh(): void
    {
        $this->dispatchMarkers();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'category');
        $this->dispatchMarkers();
    }

    public function with(): array
    {
        $reports = $this->reports();

        return [
            'markers' => $reports->map->toMapMarker()->values()->all(),
            'reports' => $reports->take(6),
            'total' => $reports->count(),
            'categories' => Category::query()->orderBy('name')->get(),
        ];
    }

    /**
     * @return Collection<int, Report>
     */
    private function reports(): Collection
    {
        return Report::query()
            ->with('category')
            ->filter([
                'search' => $this->search !== '' ? $this->search : null,
                'status' => $this->status !== '' ? $this->status : null,
                'category' => $this->category !== '' ? $this->category : null,
            ])
            ->latest()
            ->limit(50)
            ->get();
    }

    private function dispatchMarkers(): void
    {
        $this->dispatch('report-map-updated', markers: $this->reports()->map->toMapMarker()->values()->all());
    }
}; ?>

<div wire:poll.30s="refresh" class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4 dark:border-gray-700 dark:bg-gray-900">
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative sm:col-span-2">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400 dark:text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                    </svg>
                </span>
                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Cari judul atau deskripsi laporan..."
                    class="w-full rounded-md border-gray-300 pl-9 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600"
                >
            </div>

            <select wire:model.live="status" class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600">
                <option value="">Semua status</option>
                @foreach (\App\Models\Report::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <select wire:model.live="category" class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600">
                <option value="">Semua kategori</option>
                @foreach ($categories as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span class="inline-flex items-center gap-1.5">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                {{ $total }} laporan ditampilkan · pembaruan otomatis tiap 30 detik
            </span>
            <button type="button" wire:click="resetFilters" class="font-medium text-blue-700 hover:underline dark:text-blue-400">
                Reset filter
            </button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="isolate relative overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div id="warga-map" wire:ignore class="h-[380px] w-full sm:h-[460px]"></div>

                <div class="pointer-events-none absolute bottom-2 left-2 z-[500] space-y-1.5 rounded-md border border-gray-200 bg-white/95 px-3 py-2 text-[11px] text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-900/95 dark:text-gray-400">
                    <p class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span> Menunggu</p>
                    <p class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-blue-700"></span> Diproses</p>
                    <p class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-600"></span> Selesai</p>
                </div>

                <button
                    type="button"
                    onclick="window.wargaMapLocate && window.wargaMapLocate()"
                    class="absolute right-2 top-2 z-[500] rounded-md border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    Lokasi saya
                </button>

                @if ($total === 0)
                    <div class="absolute inset-0 z-[450] flex items-center justify-center bg-white/75 p-6 text-center dark:bg-gray-900/75">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Tidak ada laporan yang cocok</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Coba ubah kata kunci atau reset filter.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex flex-col rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Laporan terbaru</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $reports->count() }} dari {{ $total }}</span>
            </div>

            <ul class="flex-1 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($reports as $report)
                    <li class="px-4 py-3">
                        <div class="flex items-start gap-2">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $report->markerColor() }}"></span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('laporan.show', $report) }}" class="block truncate text-sm font-medium text-gray-900 hover:text-blue-700 dark:text-gray-100 dark:hover:text-blue-400">
                                    {{ $report->title }}
                                </a>
                                <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ $report->category?->name }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <x-status-badge :status="$report->status" />
                                    <span class="text-[11px] text-gray-400 dark:text-gray-500">{{ $report->createdAgo() }}</span>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        Belum ada laporan pada filter ini.
                    </li>
                @endforelse
            </ul>

            <div class="border-t border-gray-100 p-3 dark:border-gray-800">
                <a href="{{ route('lapor') }}" class="block rounded-md bg-blue-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-blue-800">
                    Buat laporan baru
                </a>
            </div>
        </div>
    </div>
</div>

@script
<script>
    (() => {
        const container = document.getElementById('warga-map');

        if (!container || container.dataset.initialized === '1') {
            return;
        }

        container.dataset.initialized = '1';

        const map = L.map(container, { scrollWheelZoom: false }).setView([3.5952, 98.6722], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        const markerLayer = L.layerGroup().addTo(map);
        let hasFitted = false;
        let userMarker = null;

        const popupFor = (marker) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'space-y-1 text-sm';

            const title = document.createElement('a');
            title.href = marker.url;
            title.textContent = marker.title;
            title.className = 'block font-semibold text-blue-700 hover:underline dark:text-blue-400';
            wrapper.appendChild(title);

            const category = document.createElement('p');
            category.textContent = marker.category;
            category.className = 'text-xs text-gray-500 dark:text-gray-400';
            wrapper.appendChild(category);

            const meta = document.createElement('p');
            meta.textContent = marker.status_label + ' · ' + marker.created_ago;
            meta.className = 'text-xs font-medium text-gray-700 dark:text-gray-300';
            wrapper.appendChild(meta);

            return wrapper;
        };

        const draw = (markers) => {
            markerLayer.clearLayers();
            const points = [];

            (markers || []).forEach((marker) => {
                const point = [marker.latitude, marker.longitude];
                points.push(point);

                L.circleMarker(point, {
                    radius: 7,
                    color: '#ffffff',
                    weight: 2,
                    fillColor: marker.color,
                    fillOpacity: 0.95,
                }).addTo(markerLayer).bindPopup(popupFor(marker));
            });

            if (!hasFitted && points.length > 0) {
                map.fitBounds(points, { padding: [32, 32], maxZoom: 14 });
                hasFitted = true;
            }
        };

        draw(@js($markers));

        Livewire.on('report-map-updated', (payload) => {
            const markers = payload?.markers ?? payload?.[0]?.markers ?? [];
            draw(markers);
        });

        window.wargaMapLocate = () => {
            if (!navigator.geolocation) {
                window.alert('Perangkat tidak mendukung deteksi lokasi.');
                return;
            }

            navigator.geolocation.getCurrentPosition((position) => {
                const point = [position.coords.latitude, position.coords.longitude];

                if (userMarker) {
                    userMarker.setLatLng(point);
                } else {
                    userMarker = L.circleMarker(point, {
                        radius: 8,
                        color: '#ffffff',
                        weight: 2,
                        fillColor: '#1d4ed8',
                        fillOpacity: 1,
                    }).addTo(map).bindPopup('Lokasi Anda');
                }

                map.setView(point, 15);
                userMarker.openPopup();
            }, () => {
                window.alert('Tidak dapat mengakses lokasi. Pastikan izin lokasi sudah diberikan.');
            }, { enableHighAccuracy: true });
        };

        window.addEventListener('resize', () => map.invalidateSize());
    })();
</script>
@endscript
