<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\GlossaryEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GlossaryController extends Controller
{
    public function store(Request $request, Book $book)
    {
        abort_unless($book->user_id === Auth::id(), 404);

        // "Add as alias of an existing entry" instead of creating a new one
        if ($request->filled('alias_of')) {
            $validated = $request->validate([
                'alias_of' => ['integer', Rule::exists('glossary_entries', 'id')->where('book_id', $book->id)],
                'name' => 'required|string|max:255',
            ]);
            $entry = GlossaryEntry::find($validated['alias_of']);
            $alias = trim($validated['name']);
            if ($alias !== $entry->name) {
                $entry->update(['aliases' => array_values(array_unique([...$entry->aliases ?? [], $alias]))]);
            }

            return back();
        }

        $book->glossaryEntries()->create([...$this->validated($request, $book), 'user_id' => Auth::id()]);

        return back();
    }

    public function update(Request $request, GlossaryEntry $entry)
    {
        abort_unless($entry->user_id === Auth::id(), 404);

        $entry->update($this->validated($request, $entry->book, $entry));

        return back();
    }

    public function destroy(GlossaryEntry $entry)
    {
        abort_unless($entry->user_id === Auth::id(), 404);

        $entry->delete();

        return back();
    }

    private function validated(Request $request, Book $book, ?GlossaryEntry $entry = null): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(GlossaryEntry::TYPES)],
            'name' => ['required', 'string', 'max:255', Rule::unique('glossary_entries')->where('book_id', $book->id)->ignore($entry)],
            'aliases' => 'array',
            'aliases.*' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'relation' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:10000',
        ], ['name.unique' => 'This name is already in the glossary.']);

        $name = trim($validated['name']);
        $aliases = collect($validated['aliases'] ?? [])->map(fn ($alias) => trim((string) $alias))
            ->filter(fn ($alias) => $alias !== '' && $alias !== $name)->unique()->values()->all();

        return [
            'type' => $validated['type'],
            'name' => $name,
            'aliases' => $aliases,
            'description' => $validated['description'] ?? null,
            'relation' => $validated['relation'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }
}
