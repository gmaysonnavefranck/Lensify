<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Text;
use App\Models\TextNote;
use App\Support\BookParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use RuntimeException;

class BookController extends Controller
{
    public function index()
    {
        return Inertia::render('Books/Books', [
            'books' => Book::where('user_id', Auth::id())
                ->with(['author:id,name', 'lastRead:id,title'])
                ->withCount('chapters')
                ->latest('updated_at')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['file' => 'required|file|max:51200|extensions:epub,txt']);
        $file = $request->file('file');

        try {
            $parsed = BookParser::parse($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }
        if (! $parsed['chapters']) {
            throw ValidationException::withMessages(['file' => "We couldn't find any text in this file."]);
        }

        $book = DB::transaction(function () use ($parsed, $file) {
            $book = Book::create([
                'title' => mb_substr($parsed['title'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 0, 255),
                'language' => $parsed['language'],
                'author_id' => Author::idFor($parsed['author']),
                'user_id' => Auth::id(),
            ]);

            $now = now();
            $rows = collect($parsed['chapters'])->map(fn ($chapter, $i) => [
                'title' => $chapter['title'],
                'content' => $chapter['content'],
                'position' => $i + 1,
                'book_id' => $book->id,
                'author_id' => $book->author_id,
                'language' => $book->language,
                'user_id' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            // Chunked so a long book stays under the database's packet size
            $rows->chunk(25)->each(fn ($chunk) => Text::insert($chunk->values()->all()));

            return $book;
        });

        return redirect()->route('books.show', $book);
    }

    public function show(Book $book)
    {
        abort_unless($book->user_id === Auth::id(), 404);

        return Inertia::render('Books/Book', [
            'book' => $book->load('author:id,name'),
            'chapters' => $book->chapters()->withCount('textNotes')->get(['id', 'title', 'position', 'book_id']),
        ]);
    }

    public function update(Request $request, Book $book)
    {
        abort_unless($book->user_id === Auth::id(), 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
        ]);

        $authorId = Author::idFor($validated['author_name'] ?? null);
        $book->update(['title' => $validated['title'], 'author_id' => $authorId]);
        $book->chapters()->update(['author_id' => $authorId]);

        return back();
    }

    public function destroy(Book $book)
    {
        abort_unless($book->user_id === Auth::id(), 404);

        // Footnotes have no cascading foreign key; quotes and vocabulary keep their entries, unlinked
        TextNote::whereIn('text_id', $book->chapters()->select('id'))->delete();
        $book->chapters()->delete();
        $book->delete();

        return redirect()->route('books.index');
    }
}
