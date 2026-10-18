<?php

use App\Livewire\Actions\Logout;
use App\Models\Report;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('/darurat', 'darurat')->name('darurat');

Route::view('/lapor', 'lapor')->name('lapor');

Route::view('/masukan', 'feedback')->name('feedback');

Route::get('/laporan/{report}', function (Report $report) {
    return view('laporan-detail', ['report' => $report]);
})->name('laporan.show');

Route::post('/logout', function (Logout $logout) {
    $logout();

    return redirect()->route('home');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/profile', 'profile')->name('profile');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('kategori.')
    ->group(function () {
        Route::view('/kategori', 'kategori')->name('index');
    });

require __DIR__.'/auth.php';
