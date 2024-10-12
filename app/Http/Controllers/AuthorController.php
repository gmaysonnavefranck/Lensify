<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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

        $chatController = new ChatController();
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
            'user_id' => \Illuminate\Support\Facades\Auth::user()->id,
        ]);

        return redirect()->route('authors.show', ['id' => $newAuthor->id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Author $author)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Author $author)
    {
        //
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
        //
    }
}
