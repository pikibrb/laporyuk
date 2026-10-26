<x-app-layout>
    <div class="mx-auto max-w-5xl">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('Kembali ke peta') }}
        </a>

        <div class="mt-3">
            <livewire:report-detail :report-uuid="$report->uuid" />
        </div>
    </div>
</x-app-layout>
