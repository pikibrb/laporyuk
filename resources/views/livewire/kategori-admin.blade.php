<?php

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';

    public ?int $editingId = null;

    public string $editName = '';

    public ?int $confirmingDeleteId = null;

    public ?string $confirmingDeleteName = null;

    public function createCategory(): void
    {
        $this->guardAdmin();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')],
        ], [], [
            'name' => 'Nama kategori',
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
        ]);

        $this->reset('name');
        $this->resetValidation();
        $this->dispatch('notify', type: 'success', message: 'Kategori baru ditambahkan.');
    }

    public function startEditing(int $categoryId): void
    {
        $this->guardAdmin();

        $category = Category::query()->findOrFail($categoryId);

        $this->editingId = $category->id;
        $this->editName = $category->name;
        $this->resetValidation();
    }

    public function cancelEditing(): void
    {
        $this->reset('editingId', 'editName');
        $this->resetValidation();
    }

    public function updateCategory(): void
    {
        $this->guardAdmin();

        $category = Category::query()->findOrFail($this->editingId ?? 0);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($category->id)],
        ], [], [
            'editName' => 'Nama kategori',
        ]);

        $category->update([
            'name' => $validated['editName'],
            'slug' => $this->uniqueSlug($validated['editName'], $category->id),
        ]);

        $this->cancelEditing();
        $this->dispatch('notify', type: 'success', message: 'Kategori diperbarui.');
    }

    public function confirmDelete(int $categoryId): void
    {
        $this->guardAdmin();

        $category = Category::query()->findOrFail($categoryId);

        $this->confirmingDeleteId = $category->id;
        $this->confirmingDeleteName = $category->name;
        $this->dispatch('open-modal', 'delete-category');
    }

    public function deleteCategory(int $categoryId): void
    {
        $this->guardAdmin();

        $category = Category::query()->withCount('reports')->findOrFail($categoryId);

        if ($category->reports_count > 0) {
            $this->closeDeleteConfirmation();

            $this->dispatch('notify', type: 'error', title: 'Tidak dapat dihapus', message: 'Kategori masih dipakai oleh '.$category->reports_count.' laporan dan tidak dapat dihapus.');

            return;
        }

        $category->delete();

        $this->cancelEditing();
        $this->closeDeleteConfirmation();

        $this->dispatch('notify', type: 'success', message: 'Kategori dihapus.');
    }

    private function closeDeleteConfirmation(): void
    {
        $this->reset('confirmingDeleteId', 'confirmingDeleteName');
        $this->dispatch('close-modal', 'delete-category');
    }

    public function with(): array
    {
        return [
            'categories' => Category::query()->withCount('reports')->orderBy('name')->get(),
        ];
    }

    private function guardAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'kategori';
        $slug = $base;
        $suffix = 1;

        while (Category::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}; ?>

<div>
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Tambah kategori</h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Kategori dipakai untuk mengelompokkan laporan lingkungan dan fasilitas publik warga.
            </p>

            <form wire:submit="createCategory" class="mt-4 space-y-3">
                <div>
                    <label for="category-name" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama kategori</label>
                    <input
                        id="category-name"
                        type="text"
                        wire:model="name"
                        placeholder="Contoh: Pencemaran Sungai &amp; Air"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('name') border-red-300 dark:border-red-500/40 @enderror"
                    >
                    @error('name')
                        <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="w-full rounded-md bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800" wire:loading.attr="disabled" wire:target="createCategory">
                    <span wire:loading.remove wire:target="createCategory">Simpan kategori</span>
                    <span wire:loading wire:target="createCategory">Menyimpan...</span>
                </button>
            </form>
        </div>

        <div class="lg:col-span-2">
            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <table class="min-w-full text-left text-sm text-gray-700 dark:text-gray-300">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Kategori</th>
                            <th class="px-4 py-3 font-semibold">Laporan</th>
                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($categories as $category)
                            @if ($editingId === $category->id)
                                <tr class="bg-blue-50/40 dark:bg-blue-500/10">
                                    <td class="px-4 py-3">
                                        <input
                                            type="text"
                                            wire:model="editName"
                                            class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 @error('editName') border-red-300 dark:border-red-500/40 @enderror"
                                        >
                                        @error('editName')
                                            <span class="mt-1 block text-xs text-red-600 dark:text-red-400">{{ $message }}</span>
                                        @enderror
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $category->reports_count }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <button type="button" wire:click="updateCategory" class="text-xs font-semibold text-blue-700 hover:underline dark:text-blue-400">Simpan</button>
                                        <button type="button" wire:click="cancelEditing" class="ml-3 text-xs font-semibold text-gray-500 hover:underline dark:text-gray-400">Batal</button>
                                    </td>
                                </tr>
                            @else
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $category->name }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">/{{ $category->slug }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-600 dark:text-gray-400">{{ $category->reports_count }} laporan</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <button type="button" wire:click="startEditing({{ $category->id }})" class="text-xs font-semibold text-blue-700 hover:underline dark:text-blue-400">
                                            Ubah
                                        </button>
                                        <button type="button" wire:click="confirmDelete({{ $category->id }})" class="ml-3 text-xs font-semibold text-red-700 hover:underline dark:text-red-300">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada kategori. Tambahkan melalui formulir di samping.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Kategori hanya dapat dihapus bila belum dipakai oleh laporan mana pun.
            </p>
        </div>
    </div>

    <x-confirm-modal
        name="delete-category"
        variant="danger"
        title="Hapus kategori ini?"
        :message="'Kategori '.($confirmingDeleteName ?? '').' akan dihapus permanen. Kategori hanya dapat dihapus bila belum dipakai laporan.'"
        confirm-label="Hapus kategori"
    >
        @if ($confirmingDeleteId)
            <x-slot:confirm>
                <x-danger-button
                    type="button"
                    wire:click="deleteCategory({{ $confirmingDeleteId }})"
                    wire:loading.attr="disabled"
                    wire:target="deleteCategory"
                >
                    <span wire:loading.remove wire:target="deleteCategory">Hapus kategori</span>
                    <span wire:loading wire:target="deleteCategory">Menghapus...</span>
                </x-danger-button>
            </x-slot:confirm>
        @endif
    </x-confirm-modal>
</div>
