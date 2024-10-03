<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Text;
use App\Models\TextNote;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()
            ->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $authors = Author::factory()->count(5)->create([
            'user_id' => $user->id,
        ]);

        $texts = Text::factory()->count(10)->create([
            'user_id' => $user->id, // Associate with the same user
            'author_id' => function () use ($authors) {
                return rand(0, 1) ? $authors->random()->id : null; // Randomly assign an author or null
            },
        ]);

        // For each text, create a few notes, using the actual text content
        foreach ($texts as $text) {
            TextNote::factory()->count(1)->forText($text)->create();
        }
    }
}
