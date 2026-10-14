<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div class="hidden md:block md:col-span-1">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-300">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium mb-4 text-gray-800 border-b pb-2">Buat Laporan Baru</h3>
                        <livewire:report-form />
                    </div>
                </div>
            </div>

            <div x-show="modalOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 md:hidden" style="display: none;">
                <div @click.away="modalOpen = false" class="bg-white w-11/12 max-h-[90vh] overflow-y-auto rounded-lg shadow-xl p-5">
                    <div class="flex justify-between items-center border-b pb-2 mb-4">
                        <h3 class="text-lg font-medium text-gray-800">Buat Laporan Baru</h3>
                        <button @click="modalOpen = false" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <livewire:report-form />
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-300">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h3 class="text-lg font-medium text-gray-800">Riwayat Laporan</h3>
                        </div>
                        <livewire:report-list />
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>