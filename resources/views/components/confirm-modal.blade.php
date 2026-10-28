@props([
    'name',
    'title' => 'Konfirmasi',
    'message' => null,
    'confirmLabel' => 'Ya, lanjutkan',
    'cancelLabel' => 'Batal',
    'variant' => 'danger',
    'show' => false,
    'action' => null,
    'submitForm' => null,
    'focusable' => false,
])

@php
    $variants = [
        'danger' => [
            'accent' => 'from-red-500 via-rose-500 to-rose-600',
            'icon' => 'bg-red-50 text-red-600 ring-red-100 dark:bg-red-500/15 dark:text-red-300 dark:ring-red-500/25',
            'button' => 'bg-red-600 text-white shadow-sm shadow-red-600/25 hover:bg-red-500 focus-visible:ring-red-500',
        ],
        'warning' => [
            'accent' => 'from-amber-400 via-amber-500 to-orange-500',
            'icon' => 'bg-amber-50 text-amber-600 ring-amber-100 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/25',
            'button' => 'bg-amber-500 text-white shadow-sm shadow-amber-500/25 hover:bg-amber-600 focus-visible:ring-amber-500',
        ],
        'primary' => [
            'accent' => 'from-blue-500 via-blue-600 to-indigo-600',
            'icon' => 'bg-blue-50 text-blue-700 ring-blue-100 dark:bg-blue-500/15 dark:text-blue-300 dark:ring-blue-500/25',
            'button' => 'bg-blue-700 text-white shadow-sm shadow-blue-700/25 hover:bg-blue-800 focus-visible:ring-blue-500',
        ],
    ];

    $style = $variants[$variant] ?? $variants['danger'];
@endphp

<div
    x-data="{
        show: @js((bool) $show),
        modalName: @js($name),
        isTarget(detail) {
            const name = Array.isArray(detail) ? detail[0] : (detail && typeof detail === 'object' ? detail.name : detail);

            return name === this.modalName;
        },
        open(detail) {
            if (this.isTarget(detail)) {
                this.show = true;
            }
        },
        close(detail) {
            if (this.isTarget(detail)) {
                this.show = false;
            }
        },
    }"
    x-init="$watch('show', (value) => {
        document.body.classList.toggle('overflow-y-hidden', value);

        @if ($focusable)
            if (value) {
                setTimeout(() => {
                    const target = $el.querySelector('input:not([type=hidden]), textarea, select, [data-autofocus]');
                    if (target) target.focus();
                }, 100);
            }
        @endif
    })"
    x-on:open-modal.window="open($event.detail)"
    x-on:close-modal.window="close($event.detail)"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[95] overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirm-modal-{{ $name }}-title"
    @if ($message) aria-describedby="confirm-modal-{{ $name }}-message" @endif
>
    <div
        x-show="show"
        x-on:click="show = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
    ></div>

    <div class="flex min-h-full items-center justify-center">
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-md transform overflow-hidden rounded-2xl border border-gray-200 bg-white text-left shadow-2xl ring-1 ring-black/5 transition-all dark:border-gray-700 dark:bg-gray-900 dark:ring-white/10"
        >
            <div class="h-1.5 w-full bg-gradient-to-r {{ $style['accent'] }}"></div>

            <button
                type="button"
                x-on:click="show = false"
                class="absolute right-3 top-4 rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-gray-500 dark:hover:bg-gray-800 dark:hover:text-gray-300"
            >
                <span class="sr-only">{{ __('Tutup') }}</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="p-5 sm:p-6">
                @if ($action)
                    <form wire:submit="{{ $action }}">
                @endif

                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-1 ring-inset {{ $style['icon'] }}">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </span>

                    <div class="min-w-0 flex-1 pr-7">
                        <h2 id="confirm-modal-{{ $name }}-title" class="text-base font-semibold tracking-tight text-gray-900 sm:text-lg dark:text-gray-100">
                            {{ __($title) }}
                        </h2>

                        @if ($message)
                            <p id="confirm-modal-{{ $name }}-message" class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ __($message) }}</p>
                        @endif

                        {{ $slot }}
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        x-on:click="show = false"
                        class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus-visible:ring-offset-gray-900"
                    >
                        {{ __($cancelLabel) }}
                    </button>

                    @isset($confirm)
                        {{ $confirm }}
                    @elseif ($action)
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto dark:focus-visible:ring-offset-gray-900 {{ $style['button'] }}"
                        >
                            {{ __($confirmLabel) }}
                        </button>
                    @elseif ($submitForm)
                        <button
                            type="button"
                            x-on:click="document.getElementById('{{ $submitForm }}')?.requestSubmit(); show = false"
                            class="inline-flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto dark:focus-visible:ring-offset-gray-900 {{ $style['button'] }}"
                        >
                            {{ __($confirmLabel) }}
                        </button>
                    @endif
                </div>

                @if ($action)
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
