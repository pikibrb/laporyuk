@props(['initial' => []])

@php
    $notifications = collect($initial)
        ->map(fn ($item) => is_array($item) ? $item : ['message' => $item])
        ->map(function (array $item) {
            $type = $item['type'] ?? 'info';

            return [
                'type' => in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info',
                'title' => $item['title'] ?? null,
                'message' => (string) ($item['message'] ?? ''),
            ];
        })
        ->values()
        ->all();

    $toastStyles = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100',
        'error' => 'border-red-200 bg-red-50 text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-100',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100',
        'info' => 'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-100',
    ];

    $iconStyles = [
        'success' => 'bg-emerald-600 text-white',
        'error' => 'bg-red-600 text-white',
        'warning' => 'bg-amber-500 text-white',
        'info' => 'bg-blue-700 text-white',
    ];
@endphp

<div
    x-data="{
        toasts: [],
        styles: @js($toastStyles),
        icons: @js($iconStyles),
        push(detail) {
            const payload = detail ?? {};
            const allowed = ['success', 'error', 'warning', 'info'];
            const toast = {
                id: 'toast-' + Date.now() + '-' + Math.floor(Math.random() * 10000),
                type: allowed.includes(payload.type) ? payload.type : 'info',
                title: payload.title ?? null,
                message: payload.message ?? '',
                visible: false,
            };

            this.toasts.push(toast);
            window.setTimeout(() => {
                const item = this.toasts.find((entry) => entry.id === toast.id);
                if (item) {
                    item.visible = true;
                }
            }, 20);

            window.setTimeout(() => this.dismiss(toast.id), payload.duration ?? 5000);
        },
        dismiss(id) {
            const item = this.toasts.find((entry) => entry.id === id);
            if (item) {
                item.visible = false;
            }

            window.setTimeout(() => {
                this.toasts = this.toasts.filter((entry) => entry.id !== id);
            }, 250);
        },
    }"
    x-init="(@js($notifications)).forEach((notification, index) => window.setTimeout(() => push(notification), index * 150))"
    x-on:notify.window="push($event.detail)"
    class="pointer-events-none fixed inset-x-4 top-4 z-[100] flex flex-col items-end gap-2 sm:inset-x-auto sm:right-4 sm:w-full sm:max-w-sm"
    role="status"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-6 opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-6 opacity-0"
            class="pointer-events-auto w-full overflow-hidden rounded-lg border shadow-lg"
            :class="styles[toast.type]"
        >
            <div class="flex items-start gap-3 p-3.5">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="icons[toast.type]">
                    <svg x-show="toast.type === 'success'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>

                    <svg x-show="toast.type === 'error'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>

                    <svg x-show="toast.type === 'warning'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>

                    <svg x-show="toast.type === 'info'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>

                <div class="min-w-0 flex-1 pt-0.5">
                    <p x-show="toast.title" class="text-sm font-semibold" x-text="toast.title"></p>
                    <p class="text-sm leading-snug" x-text="toast.message"></p>
                </div>

                <button
                    type="button"
                    x-on:click="dismiss(toast.id)"
                    class="-mr-1 -mt-1 rounded-md p-1 opacity-70 transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-current"
                >
                    <span class="sr-only">{{ __('Tutup notifikasi') }}</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>
