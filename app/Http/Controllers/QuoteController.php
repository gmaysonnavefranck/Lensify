<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Quotes/Quotes', [
            'quotes' => Quote::query()
                ->where('user_id', Auth::id())
                ->when($request->input('search'), fn ($query, $search) => $query->where('content', 'like', "%{$search}%"))
                ->with('text:id,title,author_id', 'text.author:id,name')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'text_id' => ['nullable', Rule::exists('texts', 'id')->where('user_id', Auth::id())],
        ]);

        $quote = Quote::firstOrCreate(
            ['user_id' => Auth::id(), 'text_id' => $validated['text_id'] ?? null, 'content' => trim($validated['content'])],
        );

        return response()->json(['quote' => $quote, 'alreadySaved' => ! $quote->wasRecentlyCreated]);
    }

    public function destroy(Quote $quote)
    {
        abort_unless($quote->user_id === Auth::id(), 404);

        $quote->delete();

        return back();
    }
}
