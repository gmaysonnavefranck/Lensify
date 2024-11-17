<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    public function index(string $search)
    {
        return Theme::where('name', 'like', '%' . $search . '%')
            ->where('user_id', Auth::id())
            ->get();
    }
}
