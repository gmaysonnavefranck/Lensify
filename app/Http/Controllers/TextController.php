<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Text;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TextController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Texts/Texts', [
            'texts' => Text::query()
                ->where('user_id', Auth::user()->id)
                ->where('translation_for', null)
                ->whereNull('book_id')
                ->when($request->input('search'), function ($query, $search) {
                    $query->where('title', 'like', "%{$search}%");
                })
                ->when($request->input('author'), function ($query, $author) {
                    $query->whereHas('author', function ($q) use ($author) {
                        $q->where('authors.id', $author);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(9)
                ->through(function ($text) {
                    return [
                        'id' => $text->id,
                        'title' => $text->title,
                        'year' => $text->year,
                        'author' => $text->author ? ['id' => $text->author->id, 'name' => $text->author->name] : null,
                        'text_notes' => count($text->textNotes),
                        'type' => $text->type,
                        'language' => $text->language,
                        'content' => mb_substr($text->content, 0, 120),
                    ];
                }),
            'authors' => Author::where('user_id', Auth::user()->id)->get()->map(function ($author) {
                return ['id' => $author->id, 'name' => $author->name];
            }),
            'filters' => $request->only(['search', 'author']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the form input
        $validated = $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'author_name' => 'nullable|string|max:255',
        ]);

        $newText = Text::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'year' => $request->input('year'),
            'type' => $request->input('type'),
            'language' => $request->input('language'),
            'author_id' => Author::idFor($validated['author_name'] ?? null),
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('texts.show', ['id' => $newText->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $text = Text::with(['author', 'textNotes', 'highlights'])
            ->with(['translations:id,translation_for,language'])
            ->where('user_id', Auth::id())
            ->findOrFail($request->route('id'));
        $text->updateQuietly(['last_read_at' => now()]);

        return Inertia::render('Texts/Text', [
            'text' => $text,
            'book' => $this->chapterNav($text),
            'glossary' => $text->book_id ? $text->book->glossaryEntries : [],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Text $text)
    {
        abort_unless($text->user_id === Auth::id(), 404);

        // Content isn't editable: footnotes are anchored to character positions in it
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'year' => 'nullable|string|max:20',
            'type' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:50',
        ]);

        $text->update([
            'title' => $validated['title'],
            'year' => $validated['year'] ?? null,
            'type' => $validated['type'] ?? null,
            'language' => $validated['language'] ?? null,
            'author_id' => Author::idFor($validated['author_name'] ?? null),
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Text $text)
    {
        abort_unless($text->user_id === Auth::id(), 404);

        // Footnotes have no cascading foreign key; quotes and vocabulary keep their entries, unlinked
        $text->textNotes()->delete();
        $text->delete();

        return $text->book_id ? redirect()->route('books.show', $text->book_id) : redirect()->route('texts.index');
    }

    // Remembers where the reader stopped, as a character offset (pages change with text size)
    public function saveProgress(Request $request, Text $text)
    {
        abort_unless($text->user_id === Auth::id(), 404);

        $validated = $request->validate(['offset' => 'required|integer|min:0']);
        $text->updateQuietly(['read_offset' => min($validated['offset'], mb_strlen($text->content))]);

        return response()->noContent();
    }

    public function getTextInformation(Request $request)
    {
        $chatController = new ChatController;

        return $chatController->findTextInformation($request);
    }

    public function assignAuthor(Request $request, Text $text)
    {
        // Ensure the text belongs to the currently authenticated user
        if ($text->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate the request to ensure author_id is an integer and exists in the database
        $validated = $request->validate([
            'author_id' => 'required|integer|exists:authors,id',
        ]);

        // Fetch the author using first() to retrieve a single instance
        $author = Author::where('id', $validated['author_id'])->first();

        // Check if the author exists (good practice, although it's validated already)
        if (! $author) {
            return response()->json(['error' => 'Author not found'], 404);
        }

        // Associate the text with the author and save it
        $text->author()->associate($author);
        $text->save();

        return response()->json(['text' => Text::with('author')->with('textNotes')->findOrFail($text->id)]);
    }

    public function associateFootnote(Request $request, Text $text)
    {
        // Ensure the text belongs to the authenticated user
        if ($text->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate request
        $validated = $request->validate([
            'note' => 'required|string',
            'start_position' => 'required|integer|min:0',
            'end_position' => 'required|integer|min:0|gte:start_position',
            'is_ai_generated' => 'required|boolean',
            'text_content' => 'required|string',
        ]);

        // Create the footnote
        return response()->json(['note' => $text->textNotes()->create($validated)], 201);
    }

    // Where a chapter sits in its book, for the breadcrumb and previous/next links
    private function chapterNav(Text $text): ?array
    {
        if (! $text->book_id) {
            return null;
        }

        $book = $text->book;
        $book->update(['last_read_text_id' => $text->id]);
        $siblings = $book->chapters();

        return [
            'id' => $book->id,
            'title' => $book->title,
            'position' => (clone $siblings)->where('position', '<=', $text->position)->count(),
            'total' => (clone $siblings)->count(),
            'prev' => (clone $siblings)->where('position', '<', $text->position)->reorder('position', 'desc')->first(['id', 'title']),
            'next' => (clone $siblings)->where('position', '>', $text->position)->first(['id', 'title']),
        ];
    }
}
