<?php

use App\Livewire\Actions\Logout;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::view('/', 'welcome')->name('home');

Route::view('/darurat', 'darurat')->name('darurat');

Route::view('/lapor', 'lapor')->name('lapor');

Route::view('/masukan', 'feedback')->name('feedback');

Route::get('/bahasa/{locale}', function (Request $request, string $locale) {
    abort_unless(array_key_exists($locale, config('app.supported_locales')), 404);

    $request->session()->put('locale', $locale);

    $target = parse_url((string) url()->previous(), PHP_URL_PATH) ?: '/';

    // Never bounce outside the portal or back to the switcher itself.
    if (! is_string($target) || ! str_starts_with($target, '/') || str_starts_with($target, '/bahasa/')) {
        $target = '/';
    }

    return redirect($target);
})->name('locale.switch');

Route::get('/laporan/{report}', function (Report $report) {
    return view('laporan-detail', ['report' => $report]);
})->name('laporan.show');

Route::get('/laporan/{report}/foto', function (Report $report) {
    abort_unless($report->photo_path, 404);

    $disk = Storage::disk('public');

    abort_unless($disk->exists($report->photo_path), 404);

    return $disk->response($report->photo_path);
})->name('laporan.foto');

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

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('masukan.')
    ->group(function () {
        Route::view('/masukan', 'masukan')->name('index');
    });

require __DIR__.'/auth.php';
