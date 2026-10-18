<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        session()->flash('notify', ['type' => 'success', 'message' => 'Akun Anda telah dihapus.']);

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
            Hapus Akun
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Setelah akun dihapus, seluruh data akun akan hilang secara permanen. Laporan yang sudah dikirim tetap tersimpan sebagai laporan anonim.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus Akun</x-danger-button>

    <x-confirm-modal
        name="confirm-user-deletion"
        :show="$errors->isNotEmpty()"
        focusable
        action="deleteUser"
        variant="danger"
        title="Yakin ingin menghapus akun?"
        message="Laporan yang sudah dikirim tetap tersimpan sebagai laporan anonim, namun seluruh data akun akan hilang secara permanen."
        confirm-label="Hapus Akun"
    >
        <div class="mt-4">
            <x-input-label for="password" value="Kata sandi" class="sr-only" />

            <x-text-input
                wire:model="password"
                id="password"
                name="password"
                type="password"
                class="mt-1 block w-full"
                placeholder="Kata sandi"
            />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
    </x-confirm-modal>
</section>
