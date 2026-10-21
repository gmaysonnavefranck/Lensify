<?php

use App\Models\Text;
use App\Models\User;
use App\Models\VocabularyWord;
use Illuminate\Support\Facades\Http;

test('a word is saved with an AI definition and normalized', function () {
    Http::fake(['api.openai.com/*' => Http::response([
        'choices' => [['message' => ['content' => '{"definition":"noun: the plumage of a bird."}']]],
    ])]);
    $user = User::factory()->create();
    $text = Text::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->postJson('/vocabulary', [
        'word' => 'Feathers,', 'context' => 'Hope is the thing with feathers', 'text_id' => $text->id,
    ])->assertOk()->assertJsonPath('word.definition', 'noun: the plumage of a bird.')->assertJsonPath('alreadySaved', false);

    // Saving it again returns the existing word without a second AI call
    $this->actingAs($user)->postJson('/vocabulary', ['word' => 'feathers'])->assertJsonPath('alreadySaved', true);

    expect(VocabularyWord::where('user_id', $user->id)->pluck('word')->all())->toBe(['feathers']);
    Http::assertSentCount(1);
});

test('the word is still saved when the AI fails', function () {
    Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'down']], 500)]);
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/vocabulary', ['word' => 'abash'])
        ->assertOk()->assertJsonPath('word.word', 'abash')->assertJsonPath('word.definition', null);
});

test('users only see and change their own words', function () {
    $word = VocabularyWord::create(['user_id' => User::factory()->create()->id, 'word' => 'secret']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get('/vocabulary')->assertOk()->assertDontSee('secret');
    $this->actingAs($intruder)->patch("/vocabulary/{$word->id}", ['definition' => 'x'])->assertNotFound();
    $this->actingAs($intruder)->delete("/vocabulary/{$word->id}")->assertNotFound();
});
