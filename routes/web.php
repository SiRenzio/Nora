<?php

use App\Http\Controllers\LibraryEntryController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::patch('library/{libraryEntry}/progress', [LibraryEntryController::class, 'updateProgress'])
        ->name('library.progress.update');
    Route::resource('library', LibraryEntryController::class)
        ->parameters(['library' => 'libraryEntry'])
        ->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
