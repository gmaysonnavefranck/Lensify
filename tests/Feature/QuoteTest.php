<?php

use App\Models\Quote;
use App\Models\Text;
use App\Models\User;

test('a quote is saved once and listed with its source', function () {
    $user = User::factory()->create();
    $text = Text::factory()->create(['user_id' => $user->id, 'title' => 'Hope']);

    $this->actingAs($user)->postJson('/quotes', ['content' => ' That perches in the soul ', 'text_id' => $text->id])
        ->assertOk()->assertJsonPath('alreadySaved', false);
    $this->actingAs($user)->postJson('/quotes', ['content' => 'That perches in the soul', 'text_id' => $text->id])
        ->assertJsonPath('alreadySaved', true);

    expect(Quote::where('user_id', $user->id)->count())->toBe(1);
    $this->actingAs($user)->get('/quotes')->assertOk()->assertSee('That perches in the soul');
});

test('quotes are private and cannot reference other users texts', function () {
    $owner = User::factory()->create();
    $quote = Quote::create(['user_id' => $owner->id, 'content' => 'mine']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get('/quotes')->assertDontSee('mine');
    $this->actingAs($intruder)->delete("/quotes/{$quote->id}")->assertNotFound();

    $othersText = Text::factory()->create(['user_id' => $owner->id]);
    $this->actingAs($intruder)->postJson('/quotes', ['content' => 'x', 'text_id' => $othersText->id])
        ->assertJsonValidationErrors('text_id');
});
