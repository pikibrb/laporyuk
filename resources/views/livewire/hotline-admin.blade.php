<?php

use App\Models\Hotline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $number = '';

    public string $label = '';

    public string $description = '';

    public int $sortOrder = 0;

    public bool $isActive = true;

    public ?int $editingId = null;

    public ?int $confirmingDeleteId = null;

    public ?string $confirmingDeleteLabel = null;

    public function save(): void
    {
        $this->guardAdmin();

        $validated = $this->validate([
            'number' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{2,30}$/', Rule::unique('hotlines', 'number')->ignore($this->editingId)],
            'label' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:999'],
            'isActive' => ['boolean'],
        ], [], [
            'number' => __('Nomor'),
            'label' => __('Nama layanan'),
            'description' => __('Keterangan'),
            'sortOrder' => __('Urutan'),
            'isActive' => __('Status aktif'),
        ]);

        $attributes = [
            'number' => trim($validated['number']),
            'label' => $validated['label'],
            'description' => ($validated['description'] ?? null) ?: null,
            'sort_order' => $validated['sortOrder'],
            'is_active' => $validated['isActive'],
        ];

        if ($this->editingId) {
            Hotline::query()->findOrFail($this->editingId)->update($attributes);

            $this->resetForm();
            $this->dispatch('notify', type: 'success', message: __('Nomor penting diperbarui.'));

            return;
        }

        Hotline::query()->create($attributes);

        $this->resetForm();
        $this->dispatch('notify', type: 'success', message: __('Nomor penting ditambahkan.'));
    }

    public function startEditing(int $hotlineId): void
    {
        $this->guardAdmin();

        $hotline = Hotline::query()->findOrFail($hotlineId);

        $this->editingId = $hotline->id;
        $this->number = $hotline->number;
        $this->label = $hotline->label;
        $this->description = (string) $hotline->description;
        $this->sortOrder = $hotline->sort_order;
        $this->isActive = $hotline->is_active;
        $this->resetValidation();
    }

    public function cancelEditing(): void
    {
        $this->resetForm();
    }

    public function toggleActive(int $hotlineId): void
    {
        $this->guardAdmin();

        $hotline = Hotline::query()->findOrFail($hotlineId);
        $hotline->update(['is_active' => ! $hotline->is_active]);

        $this->dispatch('notify', type: 'success', message: $hotline->is_active
            ? __('Nomor penting diaktifkan.')
            : __('Nomor penting dinonaktifkan.'));
    }

    public function confirmDelete(int $hotlineId): void
    {
        $this->guardAdmin();

        $hotline = Hotline::query()->findOrFail($hotlineId);

        $this->confirmingDeleteId = $hotline->id;
        $this->confirmingDeleteLabel = $hotline->label;
        $this->dispatch('open-modal', 'delete-hotline');
    }

    public function deleteHotline(int $hotlineId): void
    {
        $this->guardAdmin();

        Hotline::query()->findOrFail($hotlineId)->delete();

        if ($this->editingId === $hotlineId) {
            $this->resetForm();
        }

        $this->reset('confirmingDeleteId', 'confirmingDeleteLabel');
        $this->dispatch('close-modal', 'delete-hotline');
        $this->dispatch('notify', type: 'success', message: __('Nomor penting dihapus.'));
    }

    public function with(): array
    {
        $hotlines = Hotline::query()->ordered()->get();

        return [
            'hotlines' => $hotlines,
            'activeCount' => $hotlines->where('is_active', true)->count(),
        ];
    }

    private function resetForm(): void
    {
        $this->reset('number', 'label', 'description', 'sortOrder', 'isActive', 'editingId');
        $this->resetValidation();
    }

    private function guardAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }
}; ?>

<div>
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-2">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                    {{ $editingId ? __('Ubah nomor penting') : __('Tambah nomor penting') }}
                </h2>
                <span class="shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                    {{ __(':count aktif', ['count' => $activeCount]) }}
                </span>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Nomor di sini tampil otomatis pada halaman Nomor Penting tanpa mengubah kode program.') }}
            </p>

            <form wire:submit="save" class="mt-4 space-y-3">
                <div>
                    <label for="hotline-number" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Nomor') }}</label>
                    <input
                        id="hotline-number"
                        type="text"
                        inputmode="tel"
                        wire:model="number"
                        placeholder="{{ __('Contoh: 112') }}"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('number') border-red-300 dark:border-red-500/40 @enderror"
                    >
                    @error('number')
                        <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="hotline-label" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Nama layanan') }}</label>
                    <input
                        id="hotline-label"
                        type="text"
                        wire:model="label"
                        placeholder="{{ __('Contoh: Pemadam kebakaran') }}"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('label') border-red-300 dark:border-red-500/40 @enderror"
                    >
                    @error('label')
                        <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="hotline-description" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Keterangan') }}</label>
                    <textarea
                        id="hotline-description"
                        wire:model="description"
                        rows="2"
                        placeholder="{{ __('Kapan nomor ini digunakan?') }}"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('description') border-red-300 dark:border-red-500/40 @enderror"
                    ></textarea>
                    @error('description')
                        <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="hotline-sort" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Urutan') }}</label>
                        <input
                            id="hotline-sort"
                            type="number"
                            min="0"
                            max="999"
                            wire:model="sortOrder"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('sortOrder') border-red-300 dark:border-red-500/40 @enderror"
                        >
                        @error('sortOrder')
                            <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-end pb-2">
                        <label for="hotline-active" class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input id="hotline-active" type="checkbox" wire:model="isActive" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900">
                            {{ __('Aktif') }}
                        </label>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? __('Simpan perubahan') : __('Tambah nomor') }}</span>
                        <span wire:loading wire:target="save">{{ __('Menyimpan...') }}</span>
                    </button>
                    @if ($editingId)
                        <button type="button" wire:click="cancelEditing" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                            {{ __('Batal') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <div class="space-y-3 lg:col-span-2">
            @forelse ($hotlines as $hotline)
                <article wire:key="hotline-{{ $hotline->id }}" class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex flex-wrap items-start gap-3">
                        <span class="flex h-10 w-14 shrink-0 items-center justify-center rounded-md bg-blue-50 text-sm font-bold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                            {{ $hotline->number }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $hotline->label }}
                                @if ($hotline->is_active)
                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ __('Aktif') }}</span>
                                @else
                                    <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">{{ __('Nonaktif') }}</span>
                                @endif
                            </p>
                            @if ($hotline->description)
                                <p class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400">{{ $hotline->description }}</p>
                            @endif
                            <p class="mt-1 text-[11px] text-gray-400 dark:text-gray-500">{{ __('Urutan :order', ['order' => $hotline->sort_order]) }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" wire:click="toggleActive({{ $hotline->id }})" class="text-xs font-semibold text-gray-600 hover:underline dark:text-gray-300">
                                {{ $hotline->is_active ? __('Nonaktifkan') : __('Aktifkan') }}
                            </button>
                            <button type="button" wire:click="startEditing({{ $hotline->id }})" class="text-xs font-semibold text-blue-700 hover:underline dark:text-blue-400">
                                {{ __('Ubah') }}
                            </button>
                            <button type="button" wire:click="confirmDelete({{ $hotline->id }})" class="text-xs font-semibold text-red-700 hover:underline dark:text-red-300">
                                {{ __('Hapus') }}
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-gray-200 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Belum ada nomor penting. Tambahkan melalui formulir di samping.') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    <x-confirm-modal
        name="delete-hotline"
        variant="danger"
        :title="__('Hapus nomor penting ini?')"
        :message="__('Nomor :label akan dihapus permanen dari halaman Nomor Penting.', ['label' => $confirmingDeleteLabel ?? ''])"
        :confirm-label="__('Hapus nomor')"
    >
        @if ($confirmingDeleteId)
            <x-slot:confirm>
                <x-danger-button
                    type="button"
                    wire:click="deleteHotline({{ $confirmingDeleteId }})"
                    wire:loading.attr="disabled"
                    wire:target="deleteHotline"
                >
                    <span wire:loading.remove wire:target="deleteHotline">{{ __('Hapus nomor') }}</span>
                    <span wire:loading wire:target="deleteHotline">{{ __('Menghapus...') }}</span>
                </x-danger-button>
            </x-slot:confirm>
        @endif
    </x-confirm-modal>
</div>
