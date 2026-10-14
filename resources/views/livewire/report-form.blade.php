<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Report;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithFileUploads;

    public string $mapId;
    public $title = '';
    public $description = '';
    public $category_id = '';
    public $photo;
    public $latitude = '';
    public $longitude = '';

    public function mount()
    {
        $this->mapId = 'map-' . uniqid();
    }

    public function submit()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'photo' => 'nullable|image|max:2048',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $photoPath = $this->photo ? $this->photo->store('reports', 'public') : null;

        Report::create([
            'user_id' => Auth::check() ? Auth::id() : null,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'description' => $this->description,
            'photo_path' => $photoPath,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => 'pending',
        ]);

        $this->reset(['title', 'description', 'category_id', 'photo', 'latitude', 'longitude']);
        session()->flash('message', 'Laporan berhasil dikirim secara anonim dan sedang menunggu peninjauan.');
        $this->dispatch('report-submitted');
    }

    public function with(): array
    {
        return [
            'categories' => Category::where('urgency_level', 'normal')->get(),
        ];
    }
}; ?>

<div>
    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded text-sm">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="submit" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Judul Laporan</label>
            <input type="text" wire:model="title" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
            <select wire:model="category_id" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                <option value="">Pilih Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Detail</label>
            <textarea wire:model="description" required rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Bukti (Opsional)</label>
            <input type="file" wire:model="photo" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
            <div wire:loading wire:target="photo" class="text-xs text-blue-600 mt-1">Mengunggah file...</div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tandai Lokasi Kejadian</label>
            <div wire:ignore id="{{ $mapId }}" class="h-64 w-full border border-gray-300 rounded-md mb-2 z-0"></div>
            <div class="flex space-x-2">
                <input type="text" wire:model="latitude" readonly placeholder="Latitude" class="w-1/2 bg-gray-50 border-gray-300 text-gray-500 rounded-md text-xs cursor-not-allowed">
                <input type="text" wire:model="longitude" readonly placeholder="Longitude" class="w-1/2 bg-gray-50 border-gray-300 text-gray-500 rounded-md text-xs cursor-not-allowed">
            </div>
            @error('latitude') <span class="text-red-500 text-xs">Lokasi wajib dipilih dari peta.</span> @enderror
        </div>

        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            Kirim Laporan
        </button>
    </form>

    <script>
        document.addEventListener('livewire:initialized', () => {
            const mapContainerId = '{{ $mapId }}';
            const mapElement = document.getElementById(mapContainerId);
            
            if (!mapElement) return;

            const map = L.map(mapContainerId).setView([3.5952, 98.6722], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            let marker;

            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                if (marker) {
                    marker.setLatLng(e.latlng);
                } else {
                    marker = L.marker(e.latlng).addTo(map);
                }

                @this.set('latitude', lat);
                @this.set('longitude', lng);
            });

            Livewire.on('report-submitted', () => {
                if (marker) {
                    map.removeLayer(marker);
                    marker = null;
                }
                map.setView([3.5952, 98.6722], 13);
            });
        });
    </script>
</div>