<?php

use App\Livewire\Actions\Logout;
use App\Models\Hotline;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::view('/', 'welcome')->name('home');

Route::get('/darurat', function () {
    return view('darurat', [
        'hotlines' => Hotline::query()->active()->ordered()->get(),
    ]);
})->name('darurat');

Route::view('/lapor', 'lapor')->name('lapor');

Route::view('/masukan', 'feedback')->name('feedback');

Route::get('/bahasa/{locale}', function (Request $request, string $locale) {
    abort_unless(array_key_exists($locale, config('app.supported_locales')), 404);

    $request->session()->put('locale', $locale);

    $target = parse_url((string) url()->previous(), PHP_URL_PATH) ?: '/';

    if (! is_string($target) || ! str_starts_with($target, '/') || str_starts_with($target, '/bahasa/')) {
        $target = '/';
    }

    return redirect($target);
})->name('locale.switch');

Route::get('/laporan/peta', function (Request $request) {
    $status = $request->string('status')->toString();
    $category = $request->string('category')->toString();
    $search = trim($request->string('search')->toString());

    abort_unless($status === '' || array_key_exists($status, Report::STATUS_LABELS), 422);
    abort_unless($category === '' || ctype_digit($category), 422);

    $reports = Report::query()
        ->with('category:id,name')
        ->filter([
            'search' => $search !== '' ? mb_substr($search, 0, 100) : null,
            'status' => $status !== '' ? $status : null,
            'category' => $category !== '' ? $category : null,
        ])
        ->latest()
        ->limit(50)
        ->get();

    $markers = $reports->map->toMapMarker()->values()->all();

    $response = response()->json([
        'markers' => $markers,
        'total' => count($markers),
    ]);

    $response->setEtag(md5((string) $response->getContent()));
    $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');

    if ($response->isNotModified($request)) {
        return $response->setNotModified();
    }

    return $response;
})->name('laporan.peta');

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
    Route::view('/masukan-saya', 'masukan-saya')->name('masukan.saya');
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

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('hotline.')
    ->group(function () {
        Route::view('/nomor-penting', 'hotline')->name('index');
    });

require __DIR__.'/auth.php';
