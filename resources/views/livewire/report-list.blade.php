<?php

use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public function with(): array
    {
        $user = Auth::user();

        $query = Report::query()->with(['category', 'user'])->latest();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return [
            'reports' => $query->paginate(8),
            'isAdmin' => $user->isAdmin(),
        ];
    }
}; ?>

<div wire:poll.30s>
    <div class="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
        <table class="min-w-full text-left text-sm text-gray-700 dark:text-gray-300">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 font-semibold">Laporan</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    @if ($isAdmin)
                        <th class="px-4 py-3 font-semibold">Pelapor</th>
                    @endif
                    <th class="px-4 py-3 font-semibold">Dikirim</th>
                    <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($reports as $report)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3">
                            <a href="{{ route('laporan.show', $report) }}" class="block max-w-xs truncate font-medium text-gray-900 hover:text-blue-700 dark:text-gray-100 dark:hover:text-blue-400">
                                {{ $report->title }}
                            </a>
                            <span class="mt-0.5 block max-w-xs truncate text-xs text-gray-500 dark:text-gray-400">{{ $report->category?->name ?? 'Tanpa kategori' }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <x-status-badge :status="$report->status" />
                        </td>
                        @if ($isAdmin)
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                {{ $report->user?->name ?? 'Anonim' }}
                            </td>
                        @endif
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ $report->createdAgo() }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('laporan.show', $report) }}" class="text-xs font-semibold text-blue-700 hover:underline dark:text-blue-400">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 5 : 4 }}" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            Belum ada laporan. <a href="{{ route('lapor') }}" class="font-medium text-blue-700 hover:underline dark:text-blue-400">Buat laporan pertama</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $reports->links() }}
    </div>
</div>
