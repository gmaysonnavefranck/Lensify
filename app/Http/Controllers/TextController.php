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
    public function index()
    {
        return Inertia::render('Texts/Texts', ['texts' => Text::with('author')->get(), 'authors' => Author::all()]);
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
            'author_id' => 'nullable|exists:authors,id',
        ]);

        // Create the text
        $newText = Text::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'author_id' => $validated['author_id'],
            'user_id' => \Illuminate\Support\Facades\Auth::user()->id,
        ]);

        return redirect()->route('texts.show', ['id' => $newText->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        return Inertia::render('Texts/Text', ['text' => Text::with('author')->with('textNotes')->findOrFail($request->route('id'))]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Text $text)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Text $text)
    {
        //
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
        if (!$author) {
            return response()->json(['error' => 'Author not found'], 404);
        }
    
        // Associate the text with the author and save it
        $text->author()->associate($author);
        $text->save();
    
        return response()->json(['text' => Text::with('author')->with('textNotes')->findOrFail($text->id)]);
    }
}
