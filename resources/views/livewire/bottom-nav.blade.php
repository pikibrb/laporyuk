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
    $itemClass = fn (bool $active) => 'flex h-full w-full flex-col items-center justify-center gap-1 text-[10px] font-medium transition-colors '.($active ? 'text-blue-700 dark:text-blue-400' : 'text-gray-500 hover:text-blue-700 dark:text-gray-400 dark:hover:text-blue-400');
@endphp

<nav class="fixed bottom-0 left-0 right-0 z-40 flex h-16 items-center border-t border-gray-200 bg-white px-1 pb-safe shadow-[0_-2px_10px_rgba(0,0,0,0.05)] lg:hidden dark:border-gray-700 dark:bg-gray-900">
    <a href="{{ route('home') }}" class="{{ $itemClass(request()->routeIs('home', 'laporan.show')) }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
        </svg>
        <span>Peta</span>
    </a>

    <a href="{{ route('lapor') }}" class="{{ $itemClass(request()->routeIs('lapor')) }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <span>Lapor</span>
    </a>

    <a href="{{ route('feedback') }}" class="{{ $itemClass(request()->routeIs('feedback')) }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
        </svg>
        <span>Masukan</span>
    </a>

    <a href="{{ route('darurat') }}" class="{{ $itemClass(request()->routeIs('darurat')) }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h2.28a1 1 0 01.95.68l1.1 3.3a1 1 0 01-.27 1.06l-1.2 1.1a11 11 0 005.5 5.5l1.1-1.2a1 1 0 011.06-.27l3.3 1.1a1 1 0 01.68.95V19a2 2 0 01-2 2h-1A16 16 0 013 6V5z"/>
        </svg>
        <span>Bantuan</span>
    </a>

    <a href="{{ route('dashboard') }}" class="{{ $itemClass(request()->routeIs('dashboard')) }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span>Laporan</span>
    </a>

    @if ($user)
        <form method="POST" action="{{ route('logout') }}" class="h-full w-full">
            @csrf
            <button type="submit" class="{{ $itemClass(false) }}">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Keluar</span>
            </button>
        </form>
    @else
        <a href="{{ route('login') }}" class="{{ $itemClass(request()->routeIs('login', 'register')) }}">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            <span>Masuk</span>
        </a>
    @endif
</nav>
