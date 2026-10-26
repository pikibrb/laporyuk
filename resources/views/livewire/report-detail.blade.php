<?php

use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $reportUuid;

    public string $status = '';

    public string $notes = '';

    public string $comment = '';

    public ?int $replyToId = null;

    public string $reply = '';

    public function mount(string $reportUuid): void
    {
        $report = $this->resolveReport();

        $this->reportUuid = $report->uuid;
        $this->status = $report->status;
    }

    public function addComment(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login');

            return;
        }

        if ($this->isRateLimited()) {
            return;
        }

        $validated = $this->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $this->resolveReport()->comments()->create([
            'user_id' => Auth::id(),
            'body' => $validated['comment'],
        ]);

        RateLimiter::hit($this->rateLimitKey(), 600);

        $this->reset('comment');
        $this->resetValidation('comment');
        $this->dispatch('notify', type: 'success', message: __('Tanggapan Anda terkirim.'));
    }

    public function startReply(int $commentId): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login');

            return;
        }

        $comment = $this->resolveReport()->comments()->whereKey($commentId)->firstOrFail();

        $this->replyToId = $comment->parent_id ?? $comment->id;
        $this->reply = '';
        $this->resetValidation('reply');
    }

    public function cancelReply(): void
    {
        $this->reset('replyToId', 'reply');
        $this->resetValidation('reply');
    }

    public function addReply(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login');

            return;
        }

        if ($this->isRateLimited('reply')) {
            return;
        }

        $validated = $this->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ], [], [
            'reply' => __('Balasan'),
        ]);

        if (! $this->replyToId) {
            $this->addError('reply', __('Pilih tanggapan warga yang akan dibalas.'));

            return;
        }

        $report = $this->resolveReport();

        $parent = $report->comments()->whereKey($this->replyToId)->first();

        if (! $parent) {
            $this->addError('reply', __('Tanggapan yang dibalas tidak ditemukan pada laporan ini.'));

            return;
        }

        $report->comments()->create([
            'user_id' => Auth::id(),
            'parent_id' => $parent->parent_id ?? $parent->id,
            'body' => $validated['reply'],
        ]);

        RateLimiter::hit($this->rateLimitKey(), 600);

        $this->cancelReply();
        $this->dispatch('notify', type: 'success', message: __('Balasan Anda terkirim.'));
    }

    public function updateStatus(): void
    {
        $user = Auth::user();

        if (! $user?->isAdmin()) {
            $this->addError('status', __('Hanya petugas yang dapat memperbarui status laporan.'));
            $this->dispatch('notify', type: 'error', message: __('Hanya petugas yang dapat memperbarui status laporan.'));

            return;
        }

        $validated = $this->validate([
            'status' => ['required', Rule::in(array_keys(Report::STATUS_LABELS))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $report = $this->resolveReport();
        $report->update(['status' => $validated['status']]);

        $report->statusHistories()->create([
            'user_id' => $user->id,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?: __('Status diperbarui oleh :name.', ['name' => $user->name]),
        ]);

        $this->reset('notes');
        $this->status = $validated['status'];
        $this->dispatch('notify', type: 'success', title: __('Status diperbarui'), message: __('Status laporan berhasil diperbarui.'));
    }

    public function with(): array
    {
        $report = Report::query()
            ->with([
                'category',
                'user',
                'comments' => fn ($query) => $query->whereNull('parent_id')->with(['user', 'replies.user'])->latest(),
                'statusHistories' => fn ($query) => $query->with('user')->latest(),
            ])
            ->where('uuid', $this->reportUuid)
            ->firstOrFail();

        $viewer = Auth::user();

        return [
            'report' => $report,
            'isAdmin' => $viewer?->isAdmin() ?? false,
            'viewer' => $viewer,
            'canViewReporter' => $viewer !== null && ($viewer->isAdmin() || $report->user_id === $viewer->id),
        ];
    }

    private function rateLimitKey(): string
    {
        return 'report-comment:'.Auth::id();
    }

    private function isRateLimited(string $field = 'comment'): bool
    {
        if (RateLimiter::tooManyAttempts($this->rateLimitKey(), 5)) {
            $this->addError($field, __('Terlalu banyak tanggapan dikirim. Coba lagi dalam :seconds detik.', ['seconds' => RateLimiter::availableIn($this->rateLimitKey())]));

            return true;
        }

        return false;
    }

    private function resolveReport(): Report
    {
        return Report::query()->where('uuid', $this->reportUuid)->firstOrFail();
    }
}; ?>

<div class="space-y-4">
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        {{ $report->category?->name ?? __('Tanpa kategori') }}
                    </span>
                    <x-status-badge :status="$report->status" />
                </div>

                <h1 class="mt-3 text-xl font-bold text-gray-900 sm:text-2xl dark:text-gray-100">{{ $report->title }}</h1>

                <dl class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <div>{{ __('Laporan') }} <span class="font-medium text-gray-700 dark:text-gray-300">#{{ $report->reference() }}</span></div>
                    <div>{{ __('Dikirim') }} <span class="font-medium text-gray-700 dark:text-gray-300">{{ $report->created_at->format('d M Y H:i') }}</span></div>
                    @if ($canViewReporter)
                        <div>{{ __('Pelapor') }} <span class="font-medium text-gray-700 dark:text-gray-300">{{ $report->user?->name ?? __('Anonim') }}</span></div>
                    @endif
                    @if ($isAdmin && $report->phone)
                        <div>{{ __('Kontak') }} <span class="font-medium text-gray-700 dark:text-gray-300">{{ $report->phone }}</span></div>
                    @endif
                </dl>

                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $report->description }}</p>

                @if ($report->photo_url)
                    <div class="mt-4" x-data="{ previewOpen: false }">
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Foto bukti') }}</p>

                        <button
                            type="button"
                            x-on:click="previewOpen = true"
                            class="group relative block w-full overflow-hidden rounded-md border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700"
                            aria-label="{{ __('Pratinjau foto bukti laporan') }}"
                        >
                            <img
                                src="{{ $report->photo_url }}"
                                alt="{{ __('Foto laporan :title', ['title' => $report->title]) }}"
                                class="max-h-80 w-full object-cover transition duration-200 group-hover:opacity-90"
                            >
                            <span class="absolute bottom-2 right-2 inline-flex items-center gap-1 rounded-md bg-gray-900/70 px-2 py-1 text-[11px] font-medium text-white">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V6a2 2 0 012-2h2m8 0h2a2 2 0 012 2v2m0 8v2a2 2 0 01-2 2h-2m-8 0H6a2 2 0 01-2-2v-2"/>
                                </svg>
                                {{ __('Pratinjau foto bukti') }}
                            </span>
                        </button>

                        <a
                            href="{{ $report->photo_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-2 inline-block text-xs font-medium text-blue-700 hover:underline dark:text-blue-400"
                        >
                            {{ __('Buka foto di tab baru') }}
                        </a>

                        <div
                            x-show="previewOpen"
                            x-cloak
                            x-on:keydown.escape.window="previewOpen = false"
                            x-transition.opacity.duration.200ms
                            class="fixed inset-0 z-[90] flex items-center justify-center bg-gray-950/85 p-4"
                            role="dialog"
                            aria-modal="true"
                            aria-label="{{ __('Pratinjau foto bukti') }}"
                            style="display: none;"
                        >
                            <div class="absolute inset-0" x-on:click="previewOpen = false" aria-hidden="true"></div>

                            <div class="relative z-10 w-full max-w-4xl">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <p class="truncate text-xs font-medium text-gray-200">{{ $report->title }}</p>
                                    <button
                                        type="button"
                                        x-on:click="previewOpen = false"
                                        class="inline-flex items-center gap-1 rounded-md border border-white/30 bg-white/10 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white"
                                    >
                                        {{ __('Tutup') }}
                                    </button>
                                </div>

                                <img
                                    src="{{ $report->photo_url }}"
                                    alt="{{ __('Foto laporan :title ukuran penuh', ['title' => $report->title]) }}"
                                    class="mx-auto max-h-[78vh] w-auto max-w-full rounded-lg object-contain shadow-2xl"
                                >
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <span>{{ __('Koordinat:') }} {{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}</span>
                    <a
                        href="https://www.openstreetmap.org/?mlat={{ $report->latitude }}&mlon={{ $report->longitude }}#map=17/{{ $report->latitude }}/{{ $report->longitude }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-blue-700 hover:underline dark:text-blue-400"
                    >
                        {{ __('Buka di OpenStreetMap') }}
                    </a>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Tanggapan warga') }}</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __(':count tanggapan', ['count' => $report->comments->count()]) }}</span>
                </div>

                <div class="space-y-4 p-4">
                    @forelse ($report->comments as $comment)
                        <article wire:key="comment-{{ $comment->id }}" class="space-y-2">
                            <div class="flex gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                    {{ strtoupper(substr($comment->user?->name ?? 'W', 0, 1)) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $comment->user?->name ?? __('Warga') }}</span>
                                        @if ($comment->user?->isAdmin())
                                            <span class="rounded-full border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-[10px] font-semibold text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300">{{ __('Petugas') }}</span>
                                        @endif
                                        · {{ $comment->created_at->format('d M Y H:i') }}
                                    </p>
                                    <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $comment->body }}</p>
                                </div>
                            </div>

                            @if ($comment->replies->isNotEmpty())
                                <div class="ml-11 space-y-3 border-l border-gray-100 pl-3 dark:border-gray-800">
                                    @foreach ($comment->replies as $child)
                                        <div wire:key="comment-{{ $child->id }}" class="flex gap-2">
                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-100 text-[10px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                {{ strtoupper(substr($child->user?->name ?? 'W', 0, 1)) }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $child->user?->name ?? __('Warga') }}</span>
                                                    @if ($child->user?->isAdmin())
                                                        <span class="rounded-full border border-blue-200 bg-blue-50 px-1.5 py-0.5 text-[10px] font-semibold text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300">{{ __('Petugas') }}</span>
                                                    @endif
                                                    · {{ $child->created_at->format('d M Y H:i') }}
                                                </p>
                                                <p class="mt-0.5 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $child->body }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($viewer || $comment->replies->isNotEmpty())
                                <div class="ml-11 flex items-center gap-3">
                                    @if ($viewer)
                                        <button type="button" wire:click="startReply({{ $comment->id }})" class="text-xs font-semibold text-blue-700 hover:underline dark:text-blue-400">
                                            {{ __('Balas') }}
                                        </button>
                                    @endif
                                    @if ($comment->replies->isNotEmpty())
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ __(':count balasan', ['count' => $comment->replies->count()]) }}</span>
                                    @endif
                                </div>
                            @endif

                            @if ($replyToId === $comment->id)
                                <form wire:submit="addReply" class="ml-11 rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                    <label for="reply-{{ $comment->id }}" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Balas tanggapan ini') }}</label>
                                    <textarea
                                        id="reply-{{ $comment->id }}"
                                        wire:model="reply"
                                        rows="2"
                                        placeholder="{{ __('Tulis balasan untuk tanggapan ini...') }}"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('reply') border-red-300 dark:border-red-500/40 @enderror"
                                    ></textarea>
                                    @error('reply')
                                        <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                                    @enderror
                                    <div class="mt-2 flex justify-end gap-2">
                                        <button type="button" wire:click="cancelReply" class="rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                            {{ __('Batal') }}
                                        </button>
                                        <button type="submit" class="rounded-md bg-blue-700 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-blue-800 disabled:opacity-60" wire:loading.attr="disabled" wire:target="addReply">
                                            <span wire:loading.remove wire:target="addReply">{{ __('Kirim balasan') }}</span>
                                            <span wire:loading wire:target="addReply">{{ __('Mengirim...') }}</span>
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada tanggapan. Berikan informasi tambahan bila Anda mengetahui kondisi di lokasi.') }}</p>
                    @endforelse

                    @if ($viewer)
                        <form wire:submit="addComment" class="border-t border-gray-100 pt-4 dark:border-gray-800">
                            <label for="comment" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tulis tanggapan') }}</label>
                            <textarea
                                id="comment"
                                wire:model="comment"
                                rows="3"
                                placeholder="{{ __('Tambahkan informasi atau konfirmasi kondisi di lokasi...') }}"
                                class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('comment') border-red-300 dark:border-red-500/40 @enderror"
                            ></textarea>
                            @error('comment')
                                <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                            @enderror
                            <div class="mt-2 flex justify-end">
                                <button type="submit" class="rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800" wire:loading.attr="disabled" wire:target="addComment">
                                    <span wire:loading.remove wire:target="addComment">{{ __('Kirim tanggapan') }}</span>
                                    <span wire:loading wire:target="addComment">{{ __('Mengirim...') }}</span>
                                </button>
                            </div>
                        </form>
                    @else
                        <p class="border-t border-gray-100 pt-4 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            <a href="{{ route('login') }}" class="font-medium text-blue-700 hover:underline dark:text-blue-400">{{ __('Masuk') }}</a>
                            {{ __('untuk menanggapi laporan ini.') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="isolate overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div wire:ignore id="report-location-map" class="h-56 w-full"></div>
                <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Lokasi kejadian') }}</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $report->category?->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}</p>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Riwayat status') }}</h2>

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
                                    {{ $history->created_at->format('d M Y H:i') }} · {{ $history->user?->isAdmin() ? $history->user->name : __('Sistem') }}
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
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Perbarui status') }}</p>

                        <select wire:model="status" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600">
                            @foreach (\App\Models\Report::STATUS_LABELS as $value => $label)
                                <option value="{{ $value }}">{{ __($label) }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <span class="block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror

                        <textarea
                            wire:model="notes"
                            rows="2"
                            placeholder="{{ __('Catatan petugas untuk warga (opsional)') }}"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600"
                        ></textarea>
                        @error('notes')
                            <span class="block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror

                        <button type="submit" class="w-full rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800" wire:loading.attr="disabled" wire:target="updateStatus">
                            <span wire:loading.remove wire:target="updateStatus">{{ __('Simpan status') }}</span>
                            <span wire:loading wire:target="updateStatus">{{ __('Menyimpan...') }}</span>
                        </button>
                    </form>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Butuh nomor layanan?') }}</p>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ __('Lihat daftar nomor penting untuk keperluan di luar laporan lingkungan dan fasilitas publik.') }}</p>
                <a href="{{ route('darurat') }}" class="mt-3 inline-block rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                    {{ __('Lihat nomor penting') }}
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
