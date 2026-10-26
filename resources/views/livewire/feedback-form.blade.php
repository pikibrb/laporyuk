<?php

use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $type = Feedback::TYPE_SUGGESTION;

    public string $message = '';

    public string $name = '';

    public string $contact = '';

    public bool $submitted = false;

    public function submit(): void
    {
        $rateLimitKey = 'feedback-submit:'.(Auth::id() ?? request()->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $this->addError('message', __('Terlalu banyak masukan dikirim. Coba lagi dalam :seconds detik.', ['seconds' => RateLimiter::availableIn($rateLimitKey)]));

            return;
        }

        $validated = $this->validate([
            'type' => ['required', Rule::in(array_keys(Feedback::TYPE_LABELS))],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'name' => ['nullable', 'string', 'max:100'],
            'contact' => ['nullable', 'string', 'max:150'],
        ], [], [
            'type' => __('Jenis masukan'),
            'message' => __('Isi masukan'),
            'name' => __('Nama'),
            'contact' => __('Kontak'),
        ]);

        Feedback::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'message' => $validated['message'],
            'name' => ($validated['name'] ?? null) ?: null,
            'contact' => ($validated['contact'] ?? null) ?: null,
        ]);

        RateLimiter::hit($rateLimitKey, 600);

        $this->reset(['message', 'name', 'contact']);
        $this->type = Feedback::TYPE_SUGGESTION;
        $this->resetValidation();
        $this->submitted = true;
        $this->dispatch('notify', type: 'success', message: __('Masukan Anda terkirim. Terima kasih!'));
    }

    public function resetForm(): void
    {
        $this->reset(['type', 'message', 'name', 'contact', 'submitted']);
        $this->type = Feedback::TYPE_SUGGESTION;
        $this->resetValidation();
    }
}; ?>

<div>
    @if ($submitted)
        <div class="rounded-md border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">{{ __('Terima kasih atas masukan Anda') }}</p>
                    <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                        {{ __('Masukan Anda sudah kami terima dan akan ditinjau oleh tim pengelola portal.') }}
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <button type="button" wire:click="resetForm" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    {{ __('Kirim masukan lain') }}
                </button>
            </div>
        </div>
    @else
        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
                <p class="font-medium">{{ __('Masukan belum bisa dikirim.') }}</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form wire:submit="submit" class="space-y-4">
            <div>
                <label for="feedback-type" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Jenis masukan') }}</label>
                <select
                    id="feedback-type"
                    wire:model="type"
                    class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 @error('type') border-red-300 @enderror"
                >
                    @foreach (\App\Models\Feedback::TYPE_LABELS as $value => $label)
                        <option value="{{ $value }}">{{ __($label) }}</option>
                    @endforeach
                </select>
                @error('type')
                    <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="feedback-message" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Isi masukan') }}</label>
                <textarea
                    id="feedback-message"
                    wire:model="message"
                    rows="5"
                    placeholder="{{ __('Tuliskan saran, kritik, atau apresiasi Anda untuk portal ini...') }}"
                    class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 @error('message') border-red-300 @enderror"
                ></textarea>
                @error('message')
                    <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="feedback-name" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Nama') }} <span class="font-normal text-gray-500 dark:text-gray-400">{{ __('(opsional)') }}</span>
                    </label>
                    <input
                        id="feedback-name"
                        type="text"
                        wire:model="name"
                        placeholder="{{ __('Kosongkan untuk anonim') }}"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 @error('name') border-red-300 @enderror"
                    >
                    @error('name')
                        <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="feedback-contact" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Kontak') }} <span class="font-normal text-gray-500 dark:text-gray-400">{{ __('(opsional)') }}</span>
                    </label>
                    <input
                        id="feedback-contact"
                        type="text"
                        wire:model="contact"
                        placeholder="{{ __('Email atau nomor telepon') }}"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 @error('contact') border-red-300 @enderror"
                    >
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Diisi hanya bila Anda bersedia dihubungi.') }}</p>
                    @error('contact')
                        <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="flex flex-col gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ Auth::check() ? __('Masukan tercatat atas nama akun Anda.') : __('Masukan dikirim secara anonim tanpa akun.') }}
                </p>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-60" wire:loading.attr="disabled" wire:target="submit">
                    <span wire:loading.remove wire:target="submit">{{ __('Kirim masukan') }}</span>
                    <span wire:loading wire:target="submit">{{ __('Mengirim...') }}</span>
                </button>
            </div>
        </form>
    @endif
</div>
