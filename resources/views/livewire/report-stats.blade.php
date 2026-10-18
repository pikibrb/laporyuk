<?php

use App\Models\Report;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        $counts = Report::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts[Report::STATUS_PENDING] ?? 0),
            'processing' => (int) ($counts[Report::STATUS_PROCESSING] ?? 0),
            'resolved' => (int) ($counts[Report::STATUS_RESOLVED] ?? 0),
        ];
    }
}; ?>

<div wire:poll.30s class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Laporan masuk</p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $total }}</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Menunggu
        </p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $pending }}</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
            <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span> Diproses
        </p>
        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $processing }}</p>
    </div>

    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
        <p class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span> Selesai
        </p>
        <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $resolved }}</p>
    </div>
</div>
