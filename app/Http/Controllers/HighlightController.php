<?php

namespace App\Http\Controllers;

use App\Models\Highlight;
use App\Models\Text;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class HighlightController extends Controller
{
    public function store(Request $request, Text $text)
    {
        abort_unless($text->user_id === Auth::id(), 404);

        $validated = $request->validate([
            'start_position' => 'required|integer|min:0',
            'end_position' => ['required', 'integer', 'gt:start_position', 'max:'.mb_strlen($text->content)],
            'color' => ['required', Rule::in(Highlight::COLORS)],
        ]);

        // Highlighting over existing highlights replaces them, like going over it with another pen
        $text->highlights()
            ->where('start_position', '<', $validated['end_position'])
            ->where('end_position', '>', $validated['start_position'])
            ->delete();
        $highlight = $text->highlights()->create($validated);

        return response()->json(['highlight' => $highlight->only('id', 'start_position', 'end_position', 'color')], 201);
    }

    public function update(Request $request, Highlight $highlight)
    {
        abort_unless($highlight->text->user_id === Auth::id(), 404);

        $highlight->update($request->validate(['color' => ['required', Rule::in(Highlight::COLORS)]]));

        return response()->noContent();
    }

    public function destroy(Highlight $highlight)
    {
        abort_unless($highlight->text->user_id === Auth::id(), 404);

        $highlight->delete();

        return response()->noContent();
    }
}
