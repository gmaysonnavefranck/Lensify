<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TextController;
use App\Http\Controllers\ChatController;
use App\Models\Author;
use App\Models\Text;
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

    Route::get('/', function () {
        return Inertia::render('Texts/Texts', ['texts' => Text::with('author')->get(), 'authors' => Author::all()]);
    });

    Route::get('{id}', function ($id) {
        return Inertia::render('Texts/Text', ['text' => Text::with('author')->findOrFail($id)]);
    })->name('texts.show');

});

Route::middleware(['auth', 'verified'])->prefix('authors')->group(function () {
    Route::post('/', [AuthorController::class, 'store']);

    Route::get('/', function () {
        return Inertia::render('Authors/Authors', [
            'authors' => Author::with('texts')->get(),
        ]);
    });

    Route::get('{id}', function ($id) {
        return Inertia::render('Authors/Author', ['author' => Author::findOrFail($id)]);
    })->name('authors.show');
});


Route::prefix('chat')->group(function () {
    Route::post('/analyze-text', [ChatController::class, 'analyzeText']);
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
