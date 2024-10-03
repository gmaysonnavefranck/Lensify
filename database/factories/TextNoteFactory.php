<?php

namespace Database\Factories;

use App\Models\Text;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TextNote>
 */
class TextNoteFactory extends Factory
{
    protected $model = \App\Models\TextNote::class;

    public function definition()
    {
        return [
            'text_id' => Text::factory(), // This will be overridden in the seeder if needed
            'start_position' => 0, // Placeholder for start position
            'end_position' => 0,   // Placeholder for end position
            'is_ai_generated' => $this->faker->boolean(),
            'note' => $this->faker->sentence(),
            'text_content' => '', // Placeholder for text content
        ];
    }

    public function forText(Text $text)
    {
        $textContent = $text->content;
        $contentLength = strlen($textContent);

        // Generate start and end positions within the text content
        $startPosition = rand(0, $contentLength - 50); // Ensuring we have space for a small note
        $endPosition = min($startPosition + rand(20, 50), $contentLength); // Avoid going out of bounds

        return $this->state([
            'text_id' => $text->id,
            'start_position' => $startPosition,
            'end_position' => $endPosition,
            'text_content' => substr($textContent, $startPosition, $endPosition - $startPosition),
        ]);
    }
}
