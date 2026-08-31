<?php

use App\Http\Controllers\LibraryEntryController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('library', LibraryEntryController::class)
        ->parameters(['library' => 'libraryEntry'])
        ->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
