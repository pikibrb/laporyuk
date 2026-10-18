<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-400">Akun Warga</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">Profil Saya</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Perbarui data akun dan keamanan untuk memantau laporan Anda.</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-900">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-900">
            <livewire:profile.update-password-form />
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-900">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</x-app-layout>
