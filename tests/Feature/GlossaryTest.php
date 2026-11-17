<?php

use App\Models\Book;
use App\Models\GlossaryEntry;
use App\Models\Text;
use App\Models\User;

function bookWithChapter(User $user): array
{
    $book = Book::create(['title' => 'Don Quixote', 'user_id' => $user->id]);
    $chapter = Text::factory()->create(['user_id' => $user->id, 'book_id' => $book->id, 'position' => 1]);

    return [$book, $chapter];
}

test('entries are added, given aliases and shown while reading', function () {
    $user = User::factory()->create();
    [$book, $chapter] = bookWithChapter($user);

    $this->actingAs($user)->post("/books/{$book->id}/glossary", [
        'type' => 'character', 'name' => ' Sancho Panza ', 'aliases' => ['Sancho', 'Sancho Panza', ''],
        'description' => "Don Quixote's squire", 'relation' => 'Squire', 'notes' => 'Rides a donkey',
    ])->assertRedirect();

    $entry = GlossaryEntry::firstWhere('book_id', $book->id);
    expect($entry->name)->toBe('Sancho Panza')->and($entry->aliases)->toBe(['Sancho']);

    // Selecting another name for him adds it as an alias instead of a new entry
    $this->actingAs($user)->post("/books/{$book->id}/glossary", ['alias_of' => $entry->id, 'name' => 'the squire']);
    expect($entry->fresh()->aliases)->toBe(['Sancho', 'the squire'])
        ->and(GlossaryEntry::count())->toBe(1);

    $this->actingAs($user)->get("/texts/{$chapter->id}")
        ->assertInertia(fn ($page) => $page->where('glossary.0.name', 'Sancho Panza')->where('glossary.0.relation', 'Squire'));
    $this->actingAs($user)->get("/books/{$book->id}")
        ->assertInertia(fn ($page) => $page->has('glossary', 1));
});

test('a name can only be in a book glossary once', function () {
    $user = User::factory()->create();
    [$book] = bookWithChapter($user);
    $this->actingAs($user)->post("/books/{$book->id}/glossary", ['type' => 'place', 'name' => 'La Mancha']);

    $this->actingAs($user)->post("/books/{$book->id}/glossary", ['type' => 'place', 'name' => 'La Mancha'])
        ->assertSessionHasErrors('name');
    $this->actingAs($user)->post("/books/{$book->id}/glossary", ['type' => 'ghost', 'name' => 'Toboso'])
        ->assertSessionHasErrors('type');
    expect(GlossaryEntry::count())->toBe(1);
});

test('entries can be edited and removed, and go away with their book', function () {
    $user = User::factory()->create();
    [$book] = bookWithChapter($user);
    $this->actingAs($user)->post("/books/{$book->id}/glossary", ['type' => 'character', 'name' => 'Dulcinea']);
    $entry = GlossaryEntry::first();

    $this->actingAs($user)->patch("/glossary/{$entry->id}", ['type' => 'character', 'name' => 'Dulcinea del Toboso', 'aliases' => ['Aldonza Lorenzo']]);
    expect($entry->fresh())->name->toBe('Dulcinea del Toboso')->aliases->toBe(['Aldonza Lorenzo']);

    $this->actingAs($user)->delete("/glossary/{$entry->id}")->assertRedirect();
    expect(GlossaryEntry::count())->toBe(0);

    $this->actingAs($user)->post("/books/{$book->id}/glossary", ['type' => 'place', 'name' => 'Toboso']);
    $this->actingAs($user)->delete("/books/{$book->id}");
    expect(GlossaryEntry::count())->toBe(0);
});

test('glossaries are private', function () {
    $owner = User::factory()->create();
    [$book] = bookWithChapter($owner);
    $entry = GlossaryEntry::create(['user_id' => $owner->id, 'book_id' => $book->id, 'type' => 'character', 'name' => 'Rocinante']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->post("/books/{$book->id}/glossary", ['type' => 'place', 'name' => 'x'])->assertNotFound();
    $this->actingAs($intruder)->patch("/glossary/{$entry->id}", ['type' => 'place', 'name' => 'x'])->assertNotFound();
    $this->actingAs($intruder)->delete("/glossary/{$entry->id}")->assertNotFound();

    // Can't attach an alias to an entry from someone else's book
    [$ownBook] = bookWithChapter($intruder);
    $this->actingAs($intruder)->post("/books/{$ownBook->id}/glossary", ['alias_of' => $entry->id, 'name' => 'x'])
        ->assertSessionHasErrors('alias_of');

    expect($entry->fresh())->name->toBe('Rocinante')->aliases->toBeNull();
});
