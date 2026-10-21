<?php

use App\Models\Author;
use App\Models\User;

test('a living author can be saved without death year or period', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/authors', [
        'name' => 'Chimamanda Ngozi Adichie',
        'nationality' => 'Nigerian',
        'born_year' => '1977',
        'death_year' => '',
        'period' => '',
        'genres' => ['Novel'],
        'themes' => ['Identity', 'Migration'],
        'famous_works' => ['Americanah'],
        'key_facts' => ['Writes in English.'],
    ])->assertRedirect(route('authors.index'));

    $author = Author::where('user_id', $user->id)->sole();
    expect($author->death_year)->toBeNull()
        ->and($author->themes()->pluck('name')->all())->toBe(['Identity', 'Migration'])
        ->and($author->literaryPeriods()->count())->toBe(0);
});

test('authors of other users are not visible', function () {
    $other = Author::factory()->create();

    $this->actingAs(User::factory()->create())->get('/authors/' . $other->id)->assertNotFound();
});
