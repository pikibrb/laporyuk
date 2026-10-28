<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        return [
            'user' => Auth::user(),
        ];
    }
}; ?>

@php
    $linkClass = fn (bool $active) => 'flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors '.($active
        ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400'
        : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100');
@endphp

<aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col justify-between overflow-y-auto border-r border-gray-200 bg-white lg:flex dark:border-gray-700 dark:bg-gray-900">
    <div class="p-4">
        <x-brand class="px-1" />

        <nav class="mt-6 space-y-1">
            <a href="{{ route('home') }}" class="{{ $linkClass(request()->routeIs('home', 'laporan.show')) }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                <span>{{ __('Peta Warga') }}</span>
            </a>

            @unless ($user?->isAdmin())
                <a href="{{ route('lapor') }}" class="{{ $linkClass(request()->routeIs('lapor')) }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>{{ __('Buat Laporan') }}</span>
                </a>
            @endunless

            <a href="{{ route('dashboard') }}" class="{{ $linkClass(request()->routeIs('dashboard')) }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <span>{{ $user?->isAdmin() ? __('Pusat Kendali') : __('Laporan Saya') }}</span>
            </a>

            @unless ($user?->isAdmin())
                <a href="{{ route('feedback') }}" class="{{ $linkClass(request()->routeIs('feedback')) }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    <span>{{ __('Masukan & Saran') }}</span>
                </a>

                <a href="{{ route('masukan.saya') }}" class="{{ $linkClass(request()->routeIs('masukan.saya')) }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>{{ __('Masukan Saya') }}</span>
                </a>
            @endunless

            @if ($user?->isAdmin())
                <a href="{{ route('masukan.index') }}" class="{{ $linkClass(request()->routeIs('masukan.*')) }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <span>{{ __('Masukan Warga') }}</span>
                </a>

                <a href="{{ route('kategori.index') }}" class="{{ $linkClass(request()->routeIs('kategori.*')) }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>{{ __('Kelola Kategori') }}</span>
                </a>

                <a href="{{ route('hotline.index') }}" class="{{ $linkClass(request()->routeIs('hotline.*')) }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2.28a1 1 0 01.95.68l1.1 3.3a1 1 0 01-.27 1.06l-1.2 1.1a11 11 0 005.5 5.5l1.1-1.2a1 1 0 011.06-.27l3.3 1.1a1 1 0 01.68.95V19a2 2 0 01-2 2h-1A16 16 0 013 6V5z"/>
                    </svg>
                    <span>{{ __('Kelola Nomor Penting') }}</span>
                </a>
            @endif
        </nav>

        <div class="mt-6">
            <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Bantuan') }}</p>
            <a href="{{ route('darurat') }}" class="mt-2 flex items-start gap-3 rounded-md border border-gray-200 bg-gray-50 px-3 py-3 text-gray-700 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-700 text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2.28a1 1 0 01.95.68l1.1 3.3a1 1 0 01-.27 1.06l-1.2 1.1a11 11 0 005.5 5.5l1.1-1.2a1 1 0 011.06-.27l3.3 1.1a1 1 0 01.68.95V19a2 2 0 01-2 2h-1A16 16 0 013 6V5z"/>
                    </svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-bold">{{ __('Nomor Penting') }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Daftar nomor layanan warga') }}</span>
                </span>
            </a>
        </div>

        <div class="mt-6 flex items-center justify-between gap-2 rounded-md border border-gray-200 px-3 py-2 dark:border-gray-700">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Tema tampilan') }}</span>
            <x-theme-toggle compact />
        </div>

        <div class="mt-2 flex items-center justify-between gap-2 rounded-md border border-gray-200 px-3 py-2 dark:border-gray-700">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Bahasa') }}</span>
            <x-locale-switcher />
        </div>
    </div>

    <div class="border-t border-gray-200 p-4 dark:border-gray-700">
        @if ($user)
            <div class="mb-3 flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-200">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->isAdmin() ? __('Admin / Petugas') : __('Warga') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('profile') }}" class="rounded-md border border-gray-300 px-3 py-2 text-center text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                    {{ __('Profil') }}
                </a>

                <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form">
                    @csrf
                    <button type="submit" x-on:click.prevent="$dispatch('open-modal', 'confirm-logout-sidebar')" class="w-full rounded-md border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('Keluar') }}
                    </button>
                </form>
            </div>

            <x-confirm-modal
                name="confirm-logout-sidebar"
                variant="warning"
                :title="__('Keluar dari akun?')"
                :message="__('Anda perlu masuk kembali untuk memantau laporan dan masukan.')"
                :confirm-label="__('Keluar')"
                submit-form="sidebar-logout-form"
            />
        @else
            <a href="{{ route('login') }}" class="block rounded-md bg-blue-700 px-3 py-2 text-center text-sm font-medium text-white hover:bg-blue-800">
                {{ __('Masuk Petugas') }}
            </a>
            <a href="{{ route('register') }}" class="mt-2 block rounded-md border border-gray-300 px-3 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                {{ __('Daftar Warga') }}
            </a>
            <p class="mt-3 text-[11px] leading-relaxed text-gray-500 dark:text-gray-400">
                {{ __('Laporan dapat dikirim tanpa akun. Masuk untuk memantau status laporan Anda.') }}
            </p>
        @endif
    </div>
</aside>
