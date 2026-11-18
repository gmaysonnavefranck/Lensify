<?php

use App\Models\Text;
use App\Models\TextNote;
use App\Models\User;

function noteFor(User $user): TextNote
{
    $text = Text::factory()->create(['user_id' => $user->id, 'content' => 'Hope is the thing with feathers']);

    return TextNote::create([
        'text_id' => $text->id, 'start_position' => 0, 'end_position' => 4,
        'is_ai_generated' => false, 'note' => 'Original note', 'text_content' => 'Hope',
    ]);
}

test('owner can edit and delete a footnote', function () {
    $user = User::factory()->create();
    $note = noteFor($user);

    $this->actingAs($user)->patch("/notes/{$note->id}", ['note' => 'Edited note'])->assertNoContent();
    expect($note->fresh()->note)->toBe('Edited note');

    $this->actingAs($user)->delete("/notes/{$note->id}")->assertNoContent();
    expect(TextNote::find($note->id))->toBeNull();
});

test('other users cannot touch a footnote or see the text', function () {
    $note = noteFor(User::factory()->create());
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->patch("/notes/{$note->id}", ['note' => 'Hacked'])->assertNotFound();
    $this->actingAs($intruder)->delete("/notes/{$note->id}")->assertNotFound();
    $this->actingAs($intruder)->get("/texts/{$note->text_id}")->assertNotFound();

    expect($note->fresh()->note)->toBe('Original note');
});
