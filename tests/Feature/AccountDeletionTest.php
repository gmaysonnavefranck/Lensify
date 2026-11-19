<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Highlight;
use App\Models\Text;
use App\Models\TextNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('deleting an account removes everything the user created and nothing of anyone else', function () {
    $make = function (User $user) {
        $author = Author::factory()->create(['user_id' => $user->id]);
        $author->genres()->attach(Genre::factory()->create(['user_id' => $user->id]));
        $text = Text::factory()->create(['user_id' => $user->id, 'author_id' => $author->id]);
        TextNote::factory()->create(['text_id' => $text->id]);
        Highlight::create(['text_id' => $text->id, 'start_position' => 0, 'end_position' => 3, 'color' => 'yellow']);
        $book = Book::create(['title' => 'Don Quixote', 'user_id' => $user->id, 'author_id' => $author->id]);
        $chapter = Text::factory()->create(['user_id' => $user->id, 'book_id' => $book->id, 'position' => 1, 'author_id' => null]);
        TextNote::factory()->create(['text_id' => $chapter->id]);
        $book->glossaryEntries()->create(['user_id' => $user->id, 'type' => 'character', 'name' => 'Sancho']);
        DB::table('quotes')->insert(['user_id' => $user->id, 'text_id' => $text->id, 'content' => 'A quote']);
        DB::table('vocabulary_words')->insert(['user_id' => $user->id, 'text_id' => $text->id, 'word' => 'hidalgo']);
        DB::table('reading_days')->insert(['user_id' => $user->id, 'date' => now()->toDateString(), 'pages' => 3]);
    };
    $leaving = User::factory()->create();
    $staying = User::factory()->create();
    $make($leaving);
    $make($staying);
    $tables = ['authors', 'author_genre', 'genres', 'texts', 'text_notes', 'highlights', 'books', 'glossary_entries', 'quotes', 'vocabulary_words', 'reading_days'];
    $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);

    $this->actingAs($leaving)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

    // Every table drops to exactly half: the leaving user's rows are gone, the other user's are untouched
    foreach ($tables as $table) {
        expect(DB::table($table)->count())->toBe($before[$table] / 2, "rows left in $table");
    }
    expect(Text::where('user_id', $staying->id)->count())->toBe(2);
});

test('deleting an author keeps their texts, unlinked', function () {
    $user = User::factory()->create();
    $author = Author::factory()->create(['user_id' => $user->id]);
    $text = Text::factory()->create(['user_id' => $user->id, 'author_id' => $author->id]);

    $this->actingAs($user)->delete("/authors/{$author->id}")->assertRedirect();

    expect($text->fresh())->not->toBeNull()->author_id->toBeNull();
});
