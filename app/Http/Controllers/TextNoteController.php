<?php

namespace App\Http\Controllers;

use App\Models\TextNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TextNoteController extends Controller
{
    public function update(Request $request, TextNote $textNote)
    {
        $this->authorizeOwner($textNote);

        $textNote->update($request->validate(['note' => 'required|string']));

        return back();
    }

    public function destroy(TextNote $textNote)
    {
        $this->authorizeOwner($textNote);

        $textNote->delete();

        return back();
    }

    private function authorizeOwner(TextNote $textNote): void
    {
        abort_unless($textNote->text?->user_id === Auth::id(), 404);
    }
}
