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
            'author_id' => $this->authorIdFor($validated['author_name'] ?? null),
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('texts.show', ['id' => $newText->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        return Inertia::render('Texts/Text', [
            'text' => Text::with(['author', 'textNotes'])
                ->with(['translations:id,translation_for,language'])
                ->where('user_id', Auth::id())
                ->findOrFail($request->route('id')),
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
            'author_id' => $this->authorIdFor($validated['author_name'] ?? null),
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

        return redirect()->route('texts.index');
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
        $text->textNotes()->create($validated);

        return response()->json(['text' => Text::with('author')->with('textNotes')->findOrFail($text->id)]);
    }

    // Reuse the user's author with this name, or create it
    private function authorIdFor(?string $name): ?int
    {
        $name = trim($name ?? '');
        if ($name === '') {
            return null;
        }

        return (Author::where('user_id', Auth::id())->where('name', $name)->first()
            ?? Author::create(['name' => $name, 'user_id' => Auth::id()]))->id;
    }
}
