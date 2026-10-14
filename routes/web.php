<?php

use Illuminate\Support\Facades\Route;


Route::view('/', 'welcome');
Route::view('/sos', 'sos')->name('sos');
Route::view('/lapor', 'lapor')->name('lapor');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
    
require __DIR__.'/auth.php';
