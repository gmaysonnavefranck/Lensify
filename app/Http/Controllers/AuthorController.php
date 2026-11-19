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
    public function index(Request $request)
    {
        return Inertia::render('Authors/Authors', [
            'authors' => Author::query()
                ->where('user_id', Auth::user()->id)
                ->when($request->input('search'), function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->when($request->input('genre'), function ($query, $genre) {
                    $query->whereHas('genres', function ($q) use ($genre) {
                        $q->where('genres.id', $genre);
                    });
                })
                ->when($request->input('period'), function ($query, $period) {
                    $query->whereHas('literaryPeriods', function ($q) use ($period) {
                        $q->where('literary_periods.id', $period);
                    });
                })
                ->with('texts')
                ->orderBy('created_at', 'desc')
                ->paginate(9)
                ->through(fn ($author) => $this->present($author)),
            'genres' => Genre::where('user_id', Auth::user()->id)->get()->map(function ($genre) {
                return ['id' => $genre->id, 'name' => $genre->name];
            }),
            'periods' => LiteraryPeriod::where('user_id', Auth::user()->id)->get()->map(function ($period) {
                return ['id' => $period->id, 'name' => $period->name];
            }),
            'filters' => $request->only(['search', 'genre', 'period']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->save(new Author(['user_id' => Auth::id()]), $request);

        return redirect()->route('authors.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $author = Author::where('user_id', Auth::id())->findOrFail($request->route('id'));

        return Inertia::render('Authors/Author', ['author' => $this->present($author)]);
    }

    public function update(Request $request, Author $author)
    {
        abort_unless($author->user_id === Auth::id(), 404);

        $this->save($author, $request);

        return back();
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

        // Their texts and books stay, unlinked (the foreign keys set author_id to null)
        $author->delete();

        return redirect()->route('authors.index');
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

    // Shared by create and edit: validates the form, saves the author and syncs genres, themes and period
    private function save(Author $author, Request $request): void
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'genres' => 'array',
            'genres.*' => 'string|max:64',
            'themes' => 'array',
            'themes.*' => 'string|max:64',
            'period' => 'nullable|string|max:64',
            'key_facts' => 'array',
            'key_facts.*' => 'string',
            'born_year' => 'nullable|string|max:10',
            'death_year' => 'nullable|string|max:10',
            'famous_works' => 'array',
            'famous_works.*' => 'string',
            'nationality' => 'nullable|string|max:30',
        ]);

        $author->fill([
            'name' => $validated['name'],
            'key_facts' => json_encode($validated['key_facts'] ?? []),
            'born_year' => $validated['born_year'] ?? null,
            'death_year' => $validated['death_year'] ?? null,
            'famous_works' => json_encode($validated['famous_works'] ?? []),
            'nationality' => $validated['nationality'] ?? null,
        ])->save();

        $owned = fn (string $model, array $names) => collect($names)
            ->map(fn ($name) => $model::firstOrCreate(['name' => $name, 'user_id' => Auth::id()])->id);

        $author->genres()->sync($owned(Genre::class, $validated['genres'] ?? []));
        $author->themes()->sync($owned(Theme::class, $validated['themes'] ?? []));
        $author->literaryPeriods()->sync($owned(LiteraryPeriod::class, array_filter([$validated['period'] ?? null])));
    }

    private function present(Author $author): array
    {
        return [
            'id' => $author->id,
            'name' => $author->name,
            'key_facts' => json_decode($author->key_facts ?? '[]'),
            'born_year' => $author->born_year,
            'death_year' => $author->death_year,
            'famous_works' => json_decode($author->famous_works ?? '[]'),
            'nationality' => $author->nationality,
            'genres' => $author->genres->map->only(['id', 'name']),
            'themes' => $author->themes->map->only(['id', 'name']),
            'literary_periods' => $author->literaryPeriods->map->only(['id', 'name']),
            'texts' => $author->texts->map->only(['id', 'title', 'year', 'type']),
        ];
    }
}
