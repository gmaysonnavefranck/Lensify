<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\TextController;
use App\Http\Controllers\TextNoteController;
use App\Http\Controllers\VocabularyController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::middleware(['auth', 'verified'])->prefix('texts')->group(function () {
    Route::post('/', [TextController::class, 'store']);
    Route::get('/', [TextController::class, 'index'])->name('texts.index');
    Route::get('{id}', [TextController::class, 'show'])->name('texts.show');
    Route::patch('{text}', [TextController::class, 'update']);
    Route::delete('{text}', [TextController::class, 'destroy']);

    Route::post('{text}/assign-author', [TextController::class, 'assignAuthor']);
    Route::post('{text}/footnote', [TextController::class, 'associateFootnote']);
    Route::post('fetch-text', [TextController::class, 'getTextInformation']);
});

Route::middleware(['auth', 'verified'])->prefix('books')->group(function () {
    Route::get('/', [BookController::class, 'index'])->name('books.index');
    Route::post('/', [BookController::class, 'store']);
    Route::get('{book}', [BookController::class, 'show'])->name('books.show');
    Route::patch('{book}', [BookController::class, 'update']);
    Route::delete('{book}', [BookController::class, 'destroy']);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::patch('notes/{textNote}', [TextNoteController::class, 'update']);
    Route::delete('notes/{textNote}', [TextNoteController::class, 'destroy']);

    Route::get('vocabulary', [VocabularyController::class, 'index'])->name('vocabulary.index');
    Route::post('vocabulary', [VocabularyController::class, 'store']);
    Route::patch('vocabulary/{vocabularyWord}', [VocabularyController::class, 'update']);
    Route::delete('vocabulary/{vocabularyWord}', [VocabularyController::class, 'destroy']);

    Route::get('quotes', [QuoteController::class, 'index'])->name('quotes.index');
    Route::post('quotes', [QuoteController::class, 'store']);
    Route::delete('quotes/{quote}', [QuoteController::class, 'destroy']);
});

Route::middleware(['auth', 'verified'])->prefix('authors')->group(function () {
    Route::post('/', [AuthorController::class, 'store']);
    Route::get('/', [AuthorController::class, 'index'])->name('authors.index');
    Route::get('{id}', [AuthorController::class, 'show'])->name('authors.show');
    Route::patch('{author}', [AuthorController::class, 'update']);
    Route::delete('{author}', [AuthorController::class, 'destroy'])->name('authors.destroy');

    Route::post('{author}/assign-texts', [AuthorController::class, 'assignTexts']);
    Route::post('fetch-author', [AuthorController::class, 'getAuthorInformation']);
});

Route::middleware(['auth', 'verified'])->prefix('chat')->group(function () {
    Route::post('analyze-text', [ChatController::class, 'analyzeText']);
});

Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
