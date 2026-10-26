<?php

use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $filter = '';

    public ?int $respondingId = null;

    public string $response = '';

    public function startResponding(int $feedbackId): void
    {
        $this->guardAdmin();

        $feedback = Feedback::query()->findOrFail($feedbackId);

        $this->respondingId = $feedback->id;
        $this->response = (string) $feedback->response;
        $this->resetValidation();
    }

    public function cancelResponding(): void
    {
        $this->reset('respondingId', 'response');
        $this->resetValidation();
    }

    public function saveResponse(): void
    {
        $this->guardAdmin();

        if (! $this->respondingId) {
            $this->addError('response', __('Pilih masukan warga yang akan ditanggapi.'));

            return;
        }

        $validated = $this->validate([
            'response' => ['required', 'string', 'min:3', 'max:2000'],
        ], [], [
            'response' => __('Tanggapan'),
        ]);

        $feedback = Feedback::query()->findOrFail($this->respondingId);

        $feedback->update([
            'response' => $validated['response'],
            'responded_by' => Auth::id(),
            'responded_at' => now(),
        ]);

        $this->cancelResponding();
        $this->dispatch('notify', type: 'success', title: __('Tanggapan tersimpan'), message: __('Tanggapan untuk masukan warga berhasil disimpan.'));
    }

    public function with(): array
    {
        $this->guardAdmin();

        $query = Feedback::query()->with(['user', 'respondedBy'])->latest();

        if ($this->filter === 'pending') {
            $query->whereNull('responded_at');
        } elseif (array_key_exists($this->filter, Feedback::TYPE_LABELS)) {
            $query->where('type', $this->filter);
        }

        $counts = Feedback::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN responded_at IS NULL THEN 1 ELSE 0 END) as pending')
            ->first();

        $total = (int) ($counts->total ?? 0);

        return [
            'feedbacks' => $query->limit(100)->get(),
            'total' => $total,
            'pending' => (int) ($counts->pending ?? 0),
            'responded' => $total - (int) ($counts->pending ?? 0),
        ];
    }

    private function guardAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }
}; ?>

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total masukan') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $total }}</p>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-300">{{ __('Belum ditanggapi') }}</p>
            <p class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $pending }}</p>
        </div>

        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <p class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Sudah ditanggapi') }}</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $responded }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <label for="feedback-filter" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Saring masukan') }}</label>
                <select
                    id="feedback-filter"
                    wire:model.live="filter"
                    class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 sm:w-64 dark:border-gray-600"
                >
                    <option value="">{{ __('Semua masukan') }}</option>
                    <option value="pending">{{ __('Belum ditanggapi') }}</option>
                    @foreach (\App\Models\Feedback::TYPE_LABELS as $value => $label)
                        <option value="{{ $value }}">{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __(':count masukan ditampilkan', ['count' => $feedbacks->count()]) }}</p>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($feedbacks as $feedback)
            @php
                $typeStyle = match ($feedback->type) {
                    \App\Models\Feedback::TYPE_COMPLAINT => 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
                    \App\Models\Feedback::TYPE_APPRECIATION => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
                    \App\Models\Feedback::TYPE_SUGGESTION => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
                    default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400',
                };
            @endphp

            <article wire:key="feedback-{{ $feedback->id }}" class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    <span class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $typeStyle }}">{{ $feedback->type_label }}</span>

                    @if ($feedback->isResponded())
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                            {{ __('Sudah ditanggapi') }}
                        </span>
                    @else
                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                            {{ __('Menunggu tanggapan') }}
                        </span>
                    @endif

                    <span class="ml-auto text-xs text-gray-500 dark:text-gray-400">{{ $feedback->created_at->format('d M Y H:i') }}</span>
                </div>

                <div class="space-y-3 p-4">
                    <dl class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                        <div>
                            {{ __('Pengirim') }}
                            <span class="font-medium text-gray-700 dark:text-gray-300">
                                {{ $feedback->name ?? $feedback->user?->name ?? __('Anonim') }}
                            </span>
                        </div>
                        <div>
                            {{ __('Kontak') }}
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $feedback->contact ?? $feedback->user?->email ?? __('Tidak diisi') }}</span>
                        </div>
                        @if ($feedback->user)
                            <div>{{ __('Akun warga') }} <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('terhubung') }}</span></div>
                        @endif
                    </dl>

                    <p class="whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $feedback->message }}</p>

                    @if ($feedback->response)
                        <div class="rounded-md border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                {{ __('Tanggapan petugas') }}
                            </p>
                            <p class="mt-1 whitespace-pre-line text-sm text-emerald-900 dark:text-emerald-200">{{ $feedback->response }}</p>
                            <p class="mt-2 text-[11px] text-emerald-700 dark:text-emerald-300">
                                {{ $feedback->respondedBy?->name ?? __('Petugas') }}
                                · {{ $feedback->responded_at?->format('d M Y H:i') }}
                            </p>
                        </div>
                    @endif

                    @if ($respondingId === $feedback->id)
                        <form wire:submit="saveResponse" class="border-t border-gray-100 pt-3 dark:border-gray-800">
                            <label for="feedback-response-{{ $feedback->id }}" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Tanggapan untuk warga') }}
                            </label>
                            <textarea
                                id="feedback-response-{{ $feedback->id }}"
                                wire:model="response"
                                rows="3"
                                placeholder="{{ __('Tuliskan tanggapan, tindak lanjut, atau penjelasan untuk masukan ini...') }}"
                                class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('response') border-red-300 dark:border-red-500/40 @enderror"
                            ></textarea>
                            @error('response')
                                <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                            @enderror

                            <div class="mt-2 flex flex-wrap justify-end gap-2">
                                <button
                                    type="button"
                                    wire:click="cancelResponding"
                                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    {{ __('Batal') }}
                                </button>
                                <button
                                    type="submit"
                                    class="rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-60"
                                    wire:loading.attr="disabled"
                                    wire:target="saveResponse"
                                >
                                    <span wire:loading.remove wire:target="saveResponse">{{ __('Simpan tanggapan') }}</span>
                                    <span wire:loading wire:target="saveResponse">{{ __('Menyimpan...') }}</span>
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="flex justify-end">
                            <button
                                type="button"
                                wire:click="startResponding({{ $feedback->id }})"
                                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                {{ $feedback->isResponded() ? __('Ubah tanggapan') : __('Tanggapi') }}
                            </button>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-gray-200 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada masukan pada saringan ini.') }}</p>
            </div>
        @endforelse
    </div>
</div>
