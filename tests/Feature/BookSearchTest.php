<?php

use App\Models\Book;
use App\Models\Text;
use App\Models\User;

test('search finds every occurrence across chapters in reading order, 10 at a time', function () {
    $user = User::factory()->create();
    $book = Book::create(['title' => 'Don Quixote', 'user_id' => $user->id]);
    $chapter = fn (int $position, string $content) => Text::factory()->create([
        'user_id' => $user->id, 'book_id' => $book->id, 'position' => $position, 'title' => "Chapter $position", 'content' => $content,
    ]);
    $chapter(2, str_repeat('Rocinante trotted on. ', 12));
    $chapter(1, 'He named his horse ROCINANTE, a lofty name.'."\n\n".'Then Rocinante rested.');
    $chapter(3, 'Sancho and his donkey.');

    $first = $this->actingAs($user)->getJson("/books/{$book->id}/search?q=rocinante")->assertOk();
    expect($first->json('results'))->toHaveCount(10)
        ->and($first->json('hasMore'))->toBeTrue()
        ->and($first->json('results.0'))->toMatchArray([
            'chapter' => 'Chapter 1', 'offset' => 19, 'length' => 9, 'match' => 'ROCINANTE',
            'before' => 'He named his horse ', 'after' => ', a lofty name. Then Rocinante rested.',
        ])
        ->and($first->json('results.2.chapter'))->toBe('Chapter 2');

    $second = $this->actingAs($user)->getJson("/books/{$book->id}/search?q=rocinante&page=2")->assertOk();
    expect($second->json('results'))->toHaveCount(4)
        ->and($second->json('hasMore'))->toBeFalse();

    $this->actingAs($user)->getJson("/books/{$book->id}/search?q=x")->assertUnprocessable();
    $this->actingAs(User::factory()->create())->getJson("/books/{$book->id}/search?q=rocinante")->assertNotFound();
});
