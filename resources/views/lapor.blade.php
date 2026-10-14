<x-guest-layout>
    <div class="max-w-2xl mx-auto py-8 px-4">
        <div class="mb-8 text-center">
            <h2 class="text-2xl font-bold text-gray-800">Lapor Yuk!</h2>
            <p class="text-gray-500 text-sm">Sampaikan laporan Anda secara anonim</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <livewire:report-form />
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('sos') }}" class="text-red-600 hover:underline text-sm font-semibold">Keadaan Darurat? Klik di sini untuk SOS</a>
        </div>
    </div>
</x-guest-layout>