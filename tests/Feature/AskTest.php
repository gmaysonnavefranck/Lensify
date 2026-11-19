<?php

use App\Models\Text;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('a question about a passage is answered with the passage and its context', function () {
    Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode(['answer' => 'Yes: "panza" means belly in Spanish.'])]]]])]);
    $user = User::factory()->create();
    $text = Text::factory()->create(['user_id' => $user->id, 'title' => 'Chapter VII', 'content' => 'He chose a squire named Sancho Panza, a farm labourer.']);

    $this->actingAs($user)->postJson('/chat/ask', ['text_id' => $text->id, 'start' => 24, 'end' => 36, 'question' => 'Is his name about his belly?'])
        ->assertOk()->assertJson(['answer' => 'Yes: "panza" means belly in Spanish.']);

    // Instructions in the system message, the reader's text and question only in the user message, tagged
    Http::assertSent(fn ($request) => $request['messages'][0]['role'] === 'system'
        && str_contains($request['messages'][0]['content'], 'Chapter VII')
        && ! str_contains($request['messages'][0]['content'], 'belly')
        && str_contains($request['messages'][1]['content'], '<passage>Sancho Panza</passage>')
        && str_contains($request['messages'][1]['content'], '<question>Is his name about his belly?</question>')
        && $request['max_completion_tokens'] === 1500);
});

test('questions are validated and only about your own texts', function () {
    Http::fake();
    $text = Text::factory()->create(['content' => 'Some text']);

    $this->actingAs(User::factory()->create())->postJson('/chat/ask', ['text_id' => $text->id, 'start' => 0, 'end' => 4, 'question' => 'Why?'])
        ->assertNotFound();
    $this->actingAs(User::factory()->create())->postJson('/chat/ask', ['text_id' => $text->id, 'start' => 4, 'end' => 2, 'question' => ''])
        ->assertUnprocessable()->assertJsonValidationErrors(['end', 'question']);
    $this->actingAs(User::factory()->create())->postJson('/chat/ask', ['text_id' => $text->id, 'start' => 0, 'end' => 4, 'question' => str_repeat('a', 201)])
        ->assertJsonValidationErrors('question');
    Http::assertNothingSent();
});
