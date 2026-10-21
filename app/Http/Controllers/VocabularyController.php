<?php

namespace App\Http\Controllers;

use App\Models\Text;
use App\Models\VocabularyWord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VocabularyController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Vocabulary/Vocabulary', [
            'words' => VocabularyWord::query()
                ->where('user_id', Auth::id())
                ->when($request->input('search'), function ($query, $search) {
                    $query->where(fn ($q) => $q->where('word', 'like', "%{$search}%")->orWhere('definition', 'like', "%{$search}%"));
                })
                ->with('text:id,title')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'word' => 'required|string|max:100',
            'context' => 'nullable|string|max:1000',
            'text_id' => ['nullable', Rule::exists('texts', 'id')->where('user_id', Auth::id())],
        ]);

        $word = mb_strtolower(trim($validated['word'], " \t\n\r\0\x0B.,;:!?\"'“”‘’()[]—-"));

        $existing = VocabularyWord::where('user_id', Auth::id())->where('word', $word)->first();
        if ($existing) {
            return response()->json(['word' => $existing, 'alreadySaved' => true]);
        }

        // A failed AI call still saves the word; the definition can be written later
        try {
            $definition = (new ChatController)->defineWord($word, $validated['context'] ?? null);
        } catch (HttpException) {
            $definition = null;
        }

        return response()->json(['word' => VocabularyWord::create([
            'user_id' => Auth::id(),
            'text_id' => $validated['text_id'] ?? null,
            'word' => $word,
            'definition' => $definition,
            'context' => $validated['context'] ?? null,
        ]), 'alreadySaved' => false]);
    }

    public function update(Request $request, VocabularyWord $vocabularyWord)
    {
        abort_unless($vocabularyWord->user_id === Auth::id(), 404);

        $vocabularyWord->update($request->validate(['definition' => 'nullable|string|max:2000']));

        return back();
    }

    public function destroy(VocabularyWord $vocabularyWord)
    {
        abort_unless($vocabularyWord->user_id === Auth::id(), 404);

        $vocabularyWord->delete();

        return back();
    }
}
