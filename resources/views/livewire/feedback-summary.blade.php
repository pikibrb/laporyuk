<?php

use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        return [
            'pending' => Feedback::query()->whereNull('responded_at')->count(),
            'latest' => Feedback::query()->latest()->limit(3)->get(),
        ];
    }
}; ?>

<div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
    <div class="flex items-start justify-between gap-2">
        <div>
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Masukan warga') }}</h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Saran, kritik, dan apresiasi dari pengunjung portal.') }}</p>
        </div>

        <span class="shrink-0 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
            {{ __(':count baru', ['count' => $pending]) }}
        </span>
    </div>

    <ul class="mt-3 space-y-2">
        @forelse ($latest as $feedback)
            <li class="rounded-md border border-gray-100 px-3 py-2 dark:border-gray-800">
                <p class="flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $feedback->type_label }}</span>
                    · {{ $feedback->created_at->diffForHumans() }}
                    @if ($feedback->isResponded())
                        · <span class="text-emerald-700 dark:text-emerald-400">{{ __('ditanggapi') }}</span>
                    @endif
                </p>
                <p class="mt-0.5 line-clamp-2 text-xs text-gray-600 dark:text-gray-400">{{ $feedback->message }}</p>
            </li>
        @empty
            <li class="text-xs text-gray-500 dark:text-gray-400">{{ __('Belum ada masukan masuk.') }}</li>
        @endforelse
    </ul>

    <a
        href="{{ route('masukan.index') }}"
        class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800"
    >
        {{ __('Buka kotak masukan') }}
    </a>
</div>
