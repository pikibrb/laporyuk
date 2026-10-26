<?php

use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        $feedbacks = Feedback::query()
            ->where('user_id', Auth::id() ?? 0)
            ->with('respondedBy:id,name')
            ->latest()
            ->get();

        return [
            'feedbacks' => $feedbacks,
            'total' => $feedbacks->count(),
            'responded' => $feedbacks->whereNotNull('responded_at')->count(),
        ];
    }
}; ?>

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Total masukan') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $total }}</p>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-300">{{ __('Menunggu tanggapan') }}</p>
            <p class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-300">{{ $total - $responded }}</p>
        </div>

        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <p class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Sudah ditanggapi') }}</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $responded }}</p>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($feedbacks as $feedback)
            <article wire:key="my-feedback-{{ $feedback->id }}" class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">{{ $feedback->type_label }}</span>

                    @if ($feedback->isResponded())
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                            {{ __('Sudah ditanggapi') }}
                        </span>
                    @else
                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                            {{ __('Menunggu tanggapan') }}
                        </span>
                    @endif

                    <span class="ml-auto text-xs text-gray-500 dark:text-gray-400">{{ $feedback->created_at->format('d M Y H:i') }}</span>
                </div>

                <div class="space-y-3 p-4">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $feedback->message }}</p>

                    @if ($feedback->response)
                        <div class="rounded-md border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Tanggapan petugas') }}</p>
                            <p class="mt-1 whitespace-pre-line text-sm text-emerald-900 dark:text-emerald-200">{{ $feedback->response }}</p>
                            <p class="mt-2 text-[11px] text-emerald-700 dark:text-emerald-300">
                                {{ $feedback->respondedBy?->name ?? __('Petugas') }}
                                · {{ $feedback->responded_at?->format('d M Y H:i') }}
                            </p>
                        </div>
                    @else
                        <p class="rounded-md border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            {{ __('Masukan Anda sedang menunggu tanggapan petugas.') }}
                        </p>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-gray-200 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Anda belum pernah mengirim masukan dari akun ini.') }}</p>
                <a href="{{ route('feedback') }}" class="mt-3 inline-block rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">
                    {{ __('Kirim masukan & saran') }}
                </a>
            </div>
        @endforelse
    </div>

    <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
        {{ __('Masukan yang dikirim tanpa masuk akun tetap tercatat, tetapi tidak dapat ditampilkan di halaman ini karena tidak terhubung dengan akun Anda.') }}
    </p>
</div>
