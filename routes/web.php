<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TextController;
use App\Http\Controllers\TextNoteController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->prefix('texts')->group(function () {
    Route::post('/', [TextController::class, 'store']);
    Route::get('/', [TextController::class, 'index']);
    Route::get('{id}', [TextController::class, 'show'])->name('texts.show');

    Route::post('{text}/assign-author', [TextController::class, 'assignAuthor']);
    Route::post('{text}/footnote', [TextController::class, 'associateFootnote']);
    Route::post('fetch-text', [TextController::class, 'getTextInformation']);
});

Route::middleware(['auth', 'verified'])->prefix('authors')->group(function () {
    Route::post('/', [AuthorController::class, 'store']);
    Route::get('/', [AuthorController::class, 'index'])->name('authors.index');
    Route::get('{id}', [AuthorController::class, 'show'])->name('authors.show');
    Route::delete('{author}', [AuthorController::class, 'destroy'])->name('authors.destroy');

    Route::post('{author}/assign-texts', [AuthorController::class, 'assignTexts']);
    Route::post('fetch-author', [AuthorController::class, 'getAuthorInformation']);
});

Route::prefix('chat')->group(function () {
    Route::post('analyze-text', [ChatController::class, 'analyzeText']);
});

Route::get('quotes', function () {
    return Inertia::render('Quotes/Quotes');
});

Route::get('books', function () {
    return Inertia::render('Books/Books');
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
