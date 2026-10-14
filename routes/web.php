<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    // Websites routes
    Route::resource('websites', WebsiteController::class);

    Route::patch(
        '/websites/{website}/toggle',
        [WebsiteController::class, 'toggle']
    )->name('websites.toggle');

    Route::post(
        '/websites/{website}/check',
        [WebsiteController::class, 'checkNow']
    )
        ->middleware('throttle:6,1')
        ->name('websites.check');

});

require __DIR__.'/auth.php';
