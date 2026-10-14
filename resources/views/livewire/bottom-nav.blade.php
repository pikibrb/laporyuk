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

<nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-300 flex justify-between items-center h-16 z-40 px-2 pb-safe shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
    
    <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center w-full h-full text-gray-500 hover:text-blue-700 {{ request()->routeIs('dashboard') ? 'text-blue-700' : '' }}">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
        <span class="text-[10px] mt-1 font-medium">Dashboard</span>
    </a>

    <button @click="modalOpen = true" class="flex flex-col items-center justify-center w-full h-full text-gray-500 hover:text-blue-700">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        <span class="text-[10px] mt-1 font-medium">Laporan Baru</span>
    </button>

    <a href="{{ route('sos') }}" class="flex flex-col items-center justify-center w-full h-full relative -top-4">
        <div class="bg-red-600 text-white rounded-full p-3 shadow-lg border-4 border-white flex items-center justify-center animate-pulse">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
        </div>
        <span class="text-[10px] mt-2 font-bold text-red-600">SOS</span>
    </a>

    @if($user && $user->role === 'admin')
    <a href="#" class="flex flex-col items-center justify-center w-full h-full text-gray-500 hover:text-blue-700">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        <span class="text-[10px] mt-1 font-medium">Admin</span>
    </a>
    @else
    <div class="w-full h-full"></div>
    @endif

   @if($user)
    <form method="POST" action="{{ route('logout') }}" class="w-full h-full flex">
        @csrf
        <button type="submit" class="flex flex-col items-center justify-center w-full h-full text-gray-500 hover:text-red-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <span class="text-[10px] mt-1 font-medium">Keluar</span>
        </button>
    </form>
    @else
    <a href="{{ route('login') }}" class="flex flex-col items-center justify-center w-full h-full text-gray-500 hover:text-blue-700">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
        <span class="text-[10px] mt-1 font-medium">Masuk</span>
    </a>
    @endif
</nav>