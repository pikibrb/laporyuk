<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public function with(): array
    {
        return [
            'user' => Auth::user(),
        ];
    }
}; ?>

<aside class="hidden lg:flex flex-col w-64 bg-white border-r border-gray-300 min-h-screen justify-between">
    <div class="p-4">
        <div class="flex items-center space-x-3 pb-6 border-b border-gray-200">
            <div class="w-10 h-10 rounded bg-blue-700 flex items-center justify-center text-white font-bold text-lg">LY</div>
            <div>
                <h1 class="font-bold text-gray-800 text-base leading-none">Lapor Yuk!</h1>
                <span class="text-xs text-gray-500">Sistem Pelaporan</span>
            </div>
        </div>

        <nav class="mt-6 space-y-1">
            <a href="{{ route('sos') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-md text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-colors mb-4">
                <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>Pusat SOS Darurat</span>
            </a>

            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 border-l-4 border-blue-700' : 'text-gray-700 hover:bg-gray-100' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span>Dashboard</span>
            </a>

            <a href="#" class="flex items-center space-x-3 px-3 py-2.5 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Laporan Saya</span>
            </a>

            @if($user && $user->role === 'admin')
                <div class="pt-4 pb-1"><span class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Menu Admin</span></div>
                <a href="#" class="flex items-center space-x-3 px-3 py-2.5 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span>Kelola Kategori</span>
                </a>
            @endif
        </nav>
    </div>

    <div class="p-4 border-t border-gray-200">
        @if($user)
            <div class="flex items-center justify-between">
                <div class="truncate">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500 capitalize">{{ $user->role }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2 text-gray-500 hover:text-red-700 hover:bg-gray-100 rounded-md transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}" class="w-full flex justify-center py-2 px-4 border border-blue-700 rounded-md shadow-sm text-sm font-medium text-blue-700 hover:bg-blue-50 transition-colors mb-2">
                Masuk Admin/Petugas
            </a>
        @endif
    </div>
</aside>