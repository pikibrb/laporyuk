@props(['status'])

@php
    $styles = [
        \App\Models\Report::STATUS_PENDING => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
        \App\Models\Report::STATUS_PROCESSING => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-300',
        \App\Models\Report::STATUS_RESOLVED => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
        \App\Models\Report::STATUS_REJECTED => 'border-gray-200 bg-gray-100 text-gray-600 dark:border-gray-600 dark:bg-gray-700/40 dark:text-gray-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs font-medium '.($styles[$status] ?? $styles[\App\Models\Report::STATUS_PENDING])]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>
    {{ \App\Models\Report::STATUS_LABELS[$status] ?? ucfirst($status) }}
</span>
