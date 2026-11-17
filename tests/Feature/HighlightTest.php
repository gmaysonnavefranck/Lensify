<?php

use App\Models\Highlight;
use App\Models\Text;
use App\Models\User;

test('highlights are added, recolored, replaced and removed', function () {
    $user = User::factory()->create();
    $text = Text::factory()->create(['user_id' => $user->id, 'content' => 'In a village of La Mancha, the name of which I have no desire to call to mind']);

    $this->actingAs($user)->post("/texts/{$text->id}/highlights", ['start_position' => 3, 'end_position' => 10, 'color' => 'yellow'])
        ->assertCreated()->assertJsonPath('highlight.color', 'yellow');
    $highlight = Highlight::sole();

    $this->actingAs($user)->get("/texts/{$text->id}")
        ->assertInertia(fn ($page) => $page->where('text.highlights.0.color', 'yellow'));

    $this->actingAs($user)->patch("/highlights/{$highlight->id}", ['color' => 'green']);
    expect($highlight->fresh()->color)->toBe('green');

    // Going over it with another color replaces it; a separate passage stays
    $this->actingAs($user)->post("/texts/{$text->id}/highlights", ['start_position' => 30, 'end_position' => 38, 'color' => 'pink']);
    $this->actingAs($user)->post("/texts/{$text->id}/highlights", ['start_position' => 0, 'end_position' => 25, 'color' => 'blue']);
    expect(Highlight::orderBy('start_position')->pluck('color')->all())->toBe(['blue', 'pink']);

    $this->actingAs($user)->delete('/highlights/'.Highlight::firstWhere('color', 'pink')->id)->assertNoContent();
    expect(Highlight::count())->toBe(1);

    $this->actingAs($user)->delete("/texts/{$text->id}");
    expect(Highlight::count())->toBe(0);
});

test('highlights are validated and private', function () {
    $owner = User::factory()->create();
    $text = Text::factory()->create(['user_id' => $owner->id, 'content' => 'Short text']);
    $highlight = Highlight::create(['text_id' => $text->id, 'start_position' => 0, 'end_position' => 5, 'color' => 'yellow']);

    $this->actingAs($owner)->post("/texts/{$text->id}/highlights", ['start_position' => 2, 'end_position' => 99, 'color' => 'yellow'])
        ->assertSessionHasErrors('end_position');
    $this->actingAs($owner)->post("/texts/{$text->id}/highlights", ['start_position' => 2, 'end_position' => 4, 'color' => 'purple'])
        ->assertSessionHasErrors('color');

    $intruder = User::factory()->create();
    $this->actingAs($intruder)->post("/texts/{$text->id}/highlights", ['start_position' => 0, 'end_position' => 5, 'color' => 'blue'])->assertNotFound();
    $this->actingAs($intruder)->patch("/highlights/{$highlight->id}", ['color' => 'blue'])->assertNotFound();
    $this->actingAs($intruder)->delete("/highlights/{$highlight->id}")->assertNotFound();
    expect(Highlight::sole()->color)->toBe('yellow');
});
