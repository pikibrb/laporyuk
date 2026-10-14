<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public function with(): array
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $reports = Report::with(['user', 'category'])->latest()->paginate(10);
        } else {
            $reports = Report::with(['category'])->where('user_id', $user->id)->latest()->paginate(10);
        }

        return [
            'reports' => $reports,
            'role' => $user->role,
        ];
    }
}; ?>

<div>
    <div class="overflow-x-auto bg-white border border-gray-300 rounded-md">
        <table class="min-w-full text-left text-sm text-gray-700">
            <thead class="bg-gray-100 text-gray-800 font-semibold border-b border-gray-300">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3">Judul Laporan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($reports as $report)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $report->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $report->category->name ?? '-' }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $report->title }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($report->status === 'pending')
                                <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800 border border-yellow-200 text-xs">Menunggu</span>
                            @elseif($report->status === 'processing')
                                <span class="px-2 py-1 rounded bg-blue-100 text-blue-800 border border-blue-200 text-xs">Diproses</span>
                            @elseif($report->status === 'resolved')
                                <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs">Selesai</span>
                            @else
                                <span class="px-2 py-1 rounded bg-red-100 text-red-800 border border-red-200 text-xs">Ditolak</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <a href="#" class="text-blue-700 hover:text-blue-900 text-xs font-semibold underline">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            Belum ada riwayat laporan.
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