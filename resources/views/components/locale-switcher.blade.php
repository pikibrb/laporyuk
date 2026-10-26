@props(['label' => true])

<div
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5 rounded-md border border-gray-300 bg-white p-0.5 dark:border-gray-600 dark:bg-gray-800']) }}
    role="group"
    aria-label="{{ __('Pilih bahasa') }}"
>
    @foreach (config('app.supported_locales') as $locale => $name)
        <a
            href="{{ route('locale.switch', $locale) }}"
            @if (app()->getLocale() === $locale) aria-current="true" @endif
            title="{{ $name }}"
            class="rounded px-2 py-1 text-xs font-semibold transition-colors {{ app()->getLocale() === $locale ? 'bg-blue-700 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}"
        >
            {{ strtoupper($locale) }}
        </a>
    @endforeach
</div>
