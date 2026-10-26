@props([
    'name',
    'title' => 'Konfirmasi',
    'message' => null,
    'confirmLabel' => 'Ya, lanjutkan',
    'cancelLabel' => 'Batal',
    'variant' => 'danger',
    'show' => false,
    'action' => null,
    'focusable' => false,
])

@php
    $variants = [
        'danger' => [
            'icon' => 'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-300',
            'button' => 'bg-red-600 text-white hover:bg-red-500 focus:ring-red-500',
        ],
        'warning' => [
            'icon' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300',
            'button' => 'bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-500',
        ],
        'primary' => [
            'icon' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
            'button' => 'bg-blue-700 text-white hover:bg-blue-800 focus:ring-blue-500',
        ],
    ];

    $style = $variants[$variant] ?? $variants['danger'];
@endphp

<div
    x-data="{ show: @js((bool) $show) }"
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
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[95] overflow-y-auto px-4 py-6 sm:px-0"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirm-modal-{{ $name }}-title"
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
            class="relative w-full max-w-md transform overflow-hidden rounded-xl border border-gray-200 bg-white p-5 text-left shadow-xl transition-all sm:p-6 dark:border-gray-700 dark:bg-gray-900"
        >
            @if ($action)
                <form wire:submit="{{ $action }}">
            @endif

            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $style['icon'] }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </span>

                <div class="min-w-0 flex-1">
                    <h2 id="confirm-modal-{{ $name }}-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        {{ __($title) }}
                    </h2>

                    @if ($message)
                        <p class="mt-1 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ __($message) }}</p>
                    @endif

                    {{ $slot }}
                </div>
            </div>

            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    x-on:click="show = false"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900"
                >
                    {{ __($cancelLabel) }}
                </button>

                @isset($confirm)
                    {{ $confirm }}
                @elseif ($action)
                    <button type="submit" class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-900 {{ $style['button'] }}">
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
