<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Support\Facades\Auth;

class GenreController extends Controller
{
    public function index(string $search)
    {
        return Genre::where('name', 'like', '%' . $search . '%')
            ->where('user_id', Auth::id())
            ->get();
    }
}
