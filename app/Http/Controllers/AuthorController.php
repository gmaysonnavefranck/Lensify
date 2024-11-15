<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Text;
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
            'authors' => Author::with('texts')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the form input
        $validated = $request->validate([
            'name' => 'required|string',
        ]);

        $chatController = new ChatController;
        $authorInfo = $chatController->analyzeAuthor($request)->getData();

        //dd($authorInfo->themes);

        // Create the text
        $newAuthor = Author::create([
            'name' => $validated['name'],
            'themes' => json_encode($authorInfo->themes),
            'genres' => json_encode($authorInfo->genres),
            'period' => $authorInfo->period,
            'key_facts' => json_encode($authorInfo->keyFacts),
            'born_year' => $authorInfo->bornYear,
            'death_year' => $authorInfo->deathYear,
            'famous_works' => json_encode($authorInfo->famousWorks),
            'nationality' => $authorInfo->nationality,
            'user_id' => Auth::user()->id,
        ]);

        return redirect()->route('authors.show', ['id' => $newAuthor->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        return Inertia::render('Authors/Author', ['author' => Author::findOrFail($request->route('id'))]);
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
