<?php

namespace App\Http\Controllers;

use App\Models\LiteraryPeriod;
use Illuminate\Support\Facades\Auth;

class LiteraryPeriodController extends Controller
{
    public function index(string $search)
    {
        return LiteraryPeriod::where('name', 'like', '%' . $search . '%')
            ->where('user_id', Auth::id())
            ->get();
    }
}
