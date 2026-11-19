<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Text;
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
            'glossary' => $book->glossaryEntries,
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

        // Chapters, their footnotes, highlights and the glossary go with it; quotes and vocabulary stay, unlinked
        $book->delete();

        return redirect()->route('books.index');
    }

    // Every occurrence of a phrase across the book's chapters, in reading order, 10 per page
    public function search(Request $request, Book $book)
    {
        abort_unless($book->user_id === Auth::id(), 404);

        $validated = $request->validate(['q' => 'required|string|min:2|max:100', 'page' => 'integer|min:1']);
        $query = trim($validated['q']);
        $perPage = 10;
        $skip = (($validated['page'] ?? 1) - 1) * $perPage;
        $length = mb_strlen($query);
        $hits = [];
        $seen = 0;

        // The database narrows it to chapters containing the phrase, then each one is scanned for every occurrence.
        // ponytail: scans chapter text in PHP, fine for books; use a fulltext index if libraries get huge
        $chapters = $book->chapters()->where('content', 'like', '%'.addcslashes($query, '%_\\').'%')
            ->select('id', 'title', 'position', 'content')->lazy(20);

        foreach ($chapters as $chapter) {
            $offset = 0;
            while (($at = mb_stripos($chapter->content, $query, $offset)) !== false) {
                if ($seen++ >= $skip) {
                    $hits[] = $this->hit($chapter, $at, $length);
                }
                // One past the page is enough to know there's a next page
                if (count($hits) > $perPage) {
                    break 2;
                }
                $offset = $at + $length;
            }
        }

        return response()->json(['results' => array_slice($hits, 0, $perPage), 'hasMore' => count($hits) > $perPage]);
    }

    private function hit(Text $chapter, int $at, int $length): array
    {
        $before = mb_substr($chapter->content, max(0, $at - 90), min($at, 90));
        $after = mb_substr($chapter->content, $at + $length, 90);
        $cutAfter = $at + $length + 90 < mb_strlen($chapter->content);

        return [
            'text_id' => $chapter->id,
            'chapter' => $chapter->title,
            'position' => $chapter->position,
            'offset' => $at,
            'length' => $length,
            // Cut to whole words and flatten line breaks so each snippet reads as one line
            'before' => ($at > 90 ? '…' : '').preg_replace('/\s+/u', ' ', $at > 90 ? preg_replace('/^\S*\s/u', '', $before) : $before),
            'match' => mb_substr($chapter->content, $at, $length),
            'after' => preg_replace('/\s+/u', ' ', $cutAfter ? preg_replace('/\s\S*$/u', '', $after) : $after).($cutAfter ? '…' : ''),
        ];
    }
}
