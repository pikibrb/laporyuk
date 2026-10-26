@props(['compact' => false])

@php
    $classes = 'inline-flex h-9 items-center justify-center gap-2 rounded-md border border-gray-300 bg-white text-gray-600 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900 '.($compact ? 'w-9 px-0' : 'px-2.5');
@endphp

<button
    type="button"
    x-data="{
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);

            try {
                localStorage.setItem('theme', this.dark ? 'dark' : 'light');
            } catch (error) {}
        },
    }"
    @click="toggle()"
    :title="dark ? '{{ __('Ganti ke tema terang') }}' : '{{ __('Ganti ke tema gelap') }}'"
    :aria-label="dark ? '{{ __('Ganti ke tema terang') }}' : '{{ __('Ganti ke tema gelap') }}'"
    :aria-pressed="dark"
    {{ $attributes->merge(['class' => $classes]) }}
>
    <svg x-show="!dark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
    </svg>

    <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
    </svg>

    @unless ($compact)
        <span class="text-xs font-medium" x-text="dark ? '{{ __('Terang') }}' : '{{ __('Gelap') }}'"></span>
    @endunless
</button>
