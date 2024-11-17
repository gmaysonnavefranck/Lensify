<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Genre;
use App\Models\LiteraryPeriod;
use App\Models\Text;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Authors/Authors', [
            'authors' => Author::with('texts')
                ->where('user_id', Auth::user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate(9),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the form input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'genres' => 'array',
            'genres.*' => 'string|max:64',
            'themes' => 'array',
            'themes.*' => 'string|max:64',
            'period' => 'string|max:64',
            'key_facts' => 'array',
            'key_facts.*' => 'string',
            'born_year' => 'string|max:10',
            'death_year' => 'string|max:10',
            'famous_works' => 'array',
            'famous_works.*' => 'string',
            'nationality' => 'string|max:30',
        ]);

        // Create the text
        $newAuthor = Author::create([
            'name' => $validated['name'],
            'key_facts' => json_encode($validated['key_facts']),
            'born_year' => $validated['born_year'],
            'death_year' => $validated['death_year'],
            'famous_works' => json_encode($validated['famous_works']),
            'nationality' => $validated['nationality'],
            'user_id' => Auth::user()->id,
        ]);

        $newGenres = [];
        foreach ($validated['genres'] as $genre) {
            $newGenres[] = Genre::firstOrCreate(['name' => $genre, 'user_id' => Auth::user()->id])->id;
        }

        $newAuthor->genres()->sync($newGenres);

        $newThemes = [];
        foreach ($validated['themes'] as $theme) {
            $newThemes[] = Theme::firstOrCreate(['name' => $theme, 'user_id' => Auth::user()->id])->id;
        }

        $newAuthor->themes()->sync($newThemes);

        $newLiteraryPeriod = LiteraryPeriod::firstOrCreate(['name' => $validated['period'], 'user_id' => Auth::user()->id])->id;

        $newAuthor->literaryPeriods()->sync($newLiteraryPeriod);

        return redirect()->route('authors.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        return Inertia::render('Authors/Author', ['author' => Author::with('genres')->findOrFail($request->route('id'))]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Author $author)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Author $author)
    {
        // Ensure the user is authorized to delete this author
        if ($author->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Set the author_id to null for all texts associated with this author
        Text::where('author_id', $author->id)->update(['author_id' => null]);

        // Now delete the author
        $author->delete();

        return Inertia::render(route('authors.index'));
    }

    public function getAuthorInformation(Request $request)
    {
        $chatController = new ChatController;

        return $chatController->findAuthorInformation($request);
    }

    public function assignTexts(Request $request, Author $author)
    {
        // Ensure the author belongs to the currently authenticated user
        if ($author->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validate the request to ensure text_ids is an array of integers
        $validated = $request->validate([
            'text_ids' => 'required|array',
            'text_ids.*' => 'exists:texts,id', // Ensure each ID exists in the texts table
        ]);

        // Fetch the texts and link them to the author
        $texts = Text::whereIn('id', $validated['text_ids'])->get();

        foreach ($texts as $text) {
            $text->author()->associate($author);
            $text->save();
        }

        return response()->json([
            'message' => 'Texts successfully linked to the author.',
            'author' => $author->load('texts'), // Load texts for confirmation
        ]);
    }
}
