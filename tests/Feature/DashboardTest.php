<?php

use App\Models\Book;
use App\Models\Quote;
use App\Models\ReadingDay;
use App\Models\Text;
use App\Models\User;

test('reading is tracked per day and the dashboard shows it', function () {
    $user = User::factory()->create();
    $today = now()->toDateString();

    $this->actingAs($user)->post('/reading', ['date' => $today, 'pages' => 3, 'seconds' => 120])->assertNoContent();
    $this->actingAs($user)->post('/reading', ['date' => $today, 'pages' => 2, 'seconds' => 60]);
    expect(ReadingDay::sole())->pages->toBe(5)->seconds->toBe(180);

    // Dates far from today and silly amounts are rejected
    $this->actingAs($user)->post('/reading', ['date' => now()->subDays(10)->toDateString(), 'pages' => 1, 'seconds' => 1])
        ->assertSessionHasErrors('date');
    $this->actingAs($user)->post('/reading', ['date' => $today, 'pages' => 1, 'seconds' => 99999])
        ->assertSessionHasErrors('seconds');

    $book = Book::create(['title' => 'Don Quixote', 'user_id' => $user->id]);
    $chapter = Text::factory()->create(['user_id' => $user->id, 'book_id' => $book->id, 'position' => 1, 'content' => str_repeat('a', 100)]);
    $this->actingAs($user)->get("/texts/{$chapter->id}");
    $chapter->update(['read_offset' => 25]);
    Quote::create(['user_id' => $user->id, 'text_id' => $chapter->id, 'content' => 'The truth may be stretched thin, but it never breaks']);

    $this->actingAs($user)->get('/dashboard')->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('days.0.date', $today)
        ->where('days.0.pages', 5)
        ->where('recent.0.book.title', 'Don Quixote')
        ->where('recent.0.percent', 25)
        ->where('quote.content', 'The truth may be stretched thin, but it never breaks')
        ->where('totals.books', 1)
    );

    // Someone else's dashboard shows none of it
    $this->actingAs(User::factory()->create())->get('/dashboard')
        ->assertInertia(fn ($page) => $page->has('days', 0)->has('recent', 0)->where('quote', null));
});
