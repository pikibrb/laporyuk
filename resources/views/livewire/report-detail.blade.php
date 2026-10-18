<?php

use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public int $reportId;

    public string $status = '';

    public string $notes = '';

    public string $comment = '';

    public function mount(int $reportId): void
    {
        $report = Report::query()->findOrFail($reportId);

        $this->reportId = $report->id;
        $this->status = $report->status;
    }

    public function addComment(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login');

            return;
        }

        $validated = $this->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        Report::query()->findOrFail($this->reportId)->comments()->create([
            'user_id' => Auth::id(),
            'body' => $validated['comment'],
        ]);

        $this->reset('comment');
        $this->resetValidation('comment');
        $this->dispatch('notify', type: 'success', message: 'Tanggapan Anda terkirim.');
    }

    public function updateStatus(): void
    {
        $user = Auth::user();

        if (! $user?->isAdmin()) {
            $this->addError('status', 'Hanya petugas yang dapat memperbarui status laporan.');
            $this->dispatch('notify', type: 'error', message: 'Hanya petugas yang dapat memperbarui status laporan.');

            return;
        }

        $validated = $this->validate([
            'status' => ['required', Rule::in(array_keys(Report::STATUS_LABELS))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $report = Report::query()->findOrFail($this->reportId);
        $report->update(['status' => $validated['status']]);

        $report->statusHistories()->create([
            'user_id' => $user->id,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?: 'Status diperbarui oleh '.$user->name.'.',
        ]);

        $this->reset('notes');
        $this->status = $validated['status'];
        $this->dispatch('notify', type: 'success', title: 'Status diperbarui', message: 'Status laporan berhasil diperbarui.');
    }

    public function with(): array
    {
        $report = Report::query()
            ->with([
                'category',
                'user',
                'comments' => fn ($query) => $query->with('user')->latest(),
                'statusHistories' => fn ($query) => $query->with('user')->latest(),
            ])
            ->findOrFail($this->reportId);

        return [
            'report' => $report,
            'isAdmin' => Auth::user()?->isAdmin() ?? false,
            'viewer' => Auth::user(),
        ];
    }
}; ?>

<div class="space-y-4">
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        {{ $report->category?->name ?? 'Tanpa kategori' }}
                    </span>
                    <x-status-badge :status="$report->status" />
                </div>

                <h1 class="mt-3 text-xl font-bold text-gray-900 sm:text-2xl dark:text-gray-100">{{ $report->title }}</h1>

                <dl class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <div>Laporan <span class="font-medium text-gray-700 dark:text-gray-300">#{{ $report->id }}</span></div>
                    <div>Dikirim <span class="font-medium text-gray-700 dark:text-gray-300">{{ $report->created_at->format('d M Y H:i') }}</span></div>
                    <div>Pelapor <span class="font-medium text-gray-700 dark:text-gray-300">{{ $report->user?->name ?? 'Anonim' }}</span></div>
                    @if ($isAdmin && $report->phone)
                        <div>Kontak <span class="font-medium text-gray-700 dark:text-gray-300">{{ $report->phone }}</span></div>
                    @endif
                </dl>

                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $report->description }}</p>

                @if ($report->photo_url)
                    <div class="mt-4">
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Foto bukti</p>
                        <a href="{{ $report->photo_url }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                            <img src="{{ $report->photo_url }}" alt="Foto laporan {{ $report->title }}" class="max-h-80 w-full object-cover">
                        </a>
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <span>Koordinat: {{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}</span>
                    <a
                        href="https://www.openstreetmap.org/?mlat={{ $report->latitude }}&mlon={{ $report->longitude }}#map=17/{{ $report->latitude }}/{{ $report->longitude }}"
                        target="_blank"
                        rel="noopener"
                        class="font-medium text-blue-700 hover:underline dark:text-blue-400"
                    >
                        Buka di OpenStreetMap
                    </a>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Tanggapan warga</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $report->comments->count() }} tanggapan</span>
                </div>

                <div class="space-y-4 p-4">
                    @forelse ($report->comments as $comment)
                        <div class="flex gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ strtoupper(substr($comment->user?->name ?? 'W', 0, 1)) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $comment->user?->name ?? 'Warga' }}</span>
                                    · {{ $comment->created_at->format('d M Y H:i') }}
                                </p>
                                <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $comment->body }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada tanggapan. Berikan informasi tambahan bila Anda mengetahui kondisi di lokasi.</p>
                    @endforelse

                    @if ($viewer)
                        <form wire:submit="addComment" class="border-t border-gray-100 pt-4 dark:border-gray-800">
                            <label for="comment" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tulis tanggapan</label>
                            <textarea
                                id="comment"
                                wire:model="comment"
                                rows="3"
                                placeholder="Tambahkan informasi atau konfirmasi kondisi di lokasi..."
                                class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('comment') border-red-300 dark:border-red-500/40 @enderror"
                            ></textarea>
                            @error('comment')
                                <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                            @enderror
                            <div class="mt-2 flex justify-end">
                                <button type="submit" class="rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800" wire:loading.attr="disabled" wire:target="addComment">
                                    <span wire:loading.remove wire:target="addComment">Kirim tanggapan</span>
                                    <span wire:loading wire:target="addComment">Mengirim...</span>
                                </button>
                            </div>
                        </form>
                    @else
                        <p class="border-t border-gray-100 pt-4 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            <a href="{{ route('login') }}" class="font-medium text-blue-700 hover:underline dark:text-blue-400">Masuk</a>
                            untuk menanggapi laporan ini.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="isolate overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div wire:ignore id="report-location-map" class="h-56 w-full"></div>
                <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Lokasi kejadian</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $report->category?->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Riwayat status</h2>

                <ol class="mt-3 space-y-3">
                    @foreach ($report->statusHistories as $history)
                        @php
                            $dot = match ($history->status) {
                                \App\Models\Report::STATUS_PROCESSING => 'bg-blue-600',
                                \App\Models\Report::STATUS_RESOLVED => 'bg-emerald-600',
                                \App\Models\Report::STATUS_REJECTED => 'bg-gray-400',
                                default => 'bg-amber-500',
                            };
                        @endphp
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $dot }}"></span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ \App\Models\Report::STATUS_LABELS[$history->status] ?? ucfirst($history->status) }}</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ $history->created_at->format('d M Y H:i') }} · {{ $history->user?->name ?? 'Sistem' }}
                                </p>
                                @if ($history->notes)
                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $history->notes }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>

                @if ($isAdmin)
                    <form wire:submit="updateStatus" class="mt-4 space-y-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Perbarui status</p>

                        <select wire:model="status" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600">
                            @foreach (\App\Models\Report::STATUS_LABELS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror

                        <textarea
                            wire:model="notes"
                            rows="2"
                            placeholder="Catatan petugas untuk warga (opsional)"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600"
                        ></textarea>
                        @error('notes')
                            <span class="block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror

                        <button type="submit" class="w-full rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800" wire:loading.attr="disabled" wire:target="updateStatus">
                            <span wire:loading.remove wire:target="updateStatus">Simpan status</span>
                            <span wire:loading wire:target="updateStatus">Menyimpan...</span>
                        </button>
                    </form>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Butuh nomor layanan?</p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Lihat daftar nomor penting untuk keperluan di luar laporan lingkungan dan fasilitas publik.</p>
                <a href="{{ route('darurat') }}" class="mt-3 inline-block rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                    Lihat nomor penting
                </a>
            </div>
        </div>
    </div>
</div>

@script
<script>
    (() => {
        const container = document.getElementById('report-location-map');

        if (!container || container.dataset.initialized === '1') {
            return;
        }

        container.dataset.initialized = '1';

        const point = [@js($report->latitude), @js($report->longitude)];
        const map = L.map(container, { scrollWheelZoom: false }).setView(point, 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        const color = @js($report->markerColor());

        L.circleMarker(point, {
            radius: 9,
            color: '#ffffff',
            weight: 2,
            fillColor: color,
            fillOpacity: 0.95,
        }).addTo(map).bindPopup(@js($report->title));

        window.addEventListener('resize', () => map.invalidateSize());
    })();
</script>
@endscript
