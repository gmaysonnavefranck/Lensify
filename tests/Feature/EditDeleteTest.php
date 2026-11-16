<?php

use App\Models\Author;
use App\Models\Quote;
use App\Models\Text;
use App\Models\TextNote;
use App\Models\User;

test('a text can be edited, reusing or creating its author', function () {
    $user = User::factory()->create();
    $existing = Author::create(['name' => 'Emily Dickinson', 'user_id' => $user->id]);
    $text = Text::factory()->create(['user_id' => $user->id, 'author_id' => null]);

    $this->actingAs($user)->patch("/texts/{$text->id}", ['title' => 'Hope', 'author_name' => 'Emily Dickinson', 'year' => '1891'])
        ->assertRedirect();
    expect($text->fresh())->title->toBe('Hope')->author_id->toBe($existing->id)->year->toBe('1891');

    $this->actingAs($user)->patch("/texts/{$text->id}", ['title' => 'Hope', 'author_name' => ''])->assertRedirect();
    expect($text->fresh()->author_id)->toBeNull();
});

test('deleting a text removes its footnotes and keeps quotes unlinked', function () {
    $user = User::factory()->create();
    $text = Text::factory()->create(['user_id' => $user->id]);
    $note = TextNote::create(['text_id' => $text->id, 'start_position' => 0, 'end_position' => 1, 'is_ai_generated' => false, 'note' => 'n', 'text_content' => 'x']);
    $quote = Quote::create(['user_id' => $user->id, 'text_id' => $text->id, 'content' => 'keep me']);

    $this->actingAs($user)->delete("/texts/{$text->id}")->assertRedirect(route('texts.index'));

    expect(Text::find($text->id))->toBeNull()
        ->and(TextNote::find($note->id))->toBeNull()
        ->and($quote->fresh()->text_id)->toBeNull();
});

test('an author can be edited', function () {
    $user = User::factory()->create();
    $author = Author::create(['name' => 'Pesoa', 'user_id' => $user->id]);

    $this->actingAs($user)->patch("/authors/{$author->id}", [
        'name' => 'Fernando Pessoa', 'death_year' => '1935', 'period' => 'Modernism',
        'genres' => ['Poetry'], 'themes' => [], 'famous_works' => [], 'key_facts' => [],
    ])->assertRedirect();

    $author->refresh();
    expect($author->name)->toBe('Fernando Pessoa')
        ->and($author->literaryPeriods()->pluck('name')->all())->toBe(['Modernism'])
        ->and($author->genres()->pluck('name')->all())->toBe(['Poetry']);
});

test('other users cannot edit or delete texts and authors', function () {
    $owner = User::factory()->create();
    $text = Text::factory()->create(['user_id' => $owner->id]);
    $author = Author::create(['name' => 'Mine', 'user_id' => $owner->id]);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->patch("/texts/{$text->id}", ['title' => 'x'])->assertNotFound();
    $this->actingAs($intruder)->delete("/texts/{$text->id}")->assertNotFound();
    $this->actingAs($intruder)->patch("/authors/{$author->id}", ['name' => 'x'])->assertNotFound();

    expect(Text::find($text->id))->not->toBeNull()->and($author->fresh()->name)->toBe('Mine');
});
