@props(['href' => null, 'compact' => false])

@php
    $href = $href ?? route('home');
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-700 text-white">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
    </span>

    @unless ($compact)
        <span class="min-w-0">
            <span class="block text-base font-bold leading-tight text-gray-900 dark:text-gray-100">Lapor Yuk!</span>
            <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ __('Peta Laporan Warga') }}</span>
        </span>
    @endunless
</a>
