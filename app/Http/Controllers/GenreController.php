<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GenreController extends Controller
{
    public function store(Request $request)
    {
        // Validate the form input
        $validated = $request->validate([
            'name' => 'required|string',
        ]);

        $newGenre = Genre::create(['name' => $validated['name'], 'user_id' => Auth::user()->id]);

        return $newGenre->id;
    }

    public function list(string $search)
    {
        return Genre::where('name', 'like', '%' . $search . '%')->get();
    }
}
