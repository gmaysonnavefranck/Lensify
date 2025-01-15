<?php

namespace App\Enums;

enum TextType: string
{
    case POEM = 'Poem';
    case SHORT_STORY = 'Short Story';
    case ESSAY = 'Essay';
    case PLAY_SCENE = 'Play Scene';
    case NOVEL_EXCERPT = 'Novel Excerpt';

    case ACADEMIC_PAPER = 'Academic Paper';
    case CRITICAL_ESSAY = 'Critical Essay';
    case REVIEW = 'Review';
    case SPEECH = 'Speech';

    case LETTER = 'Letter';
    case ARTICLE = 'Article';
    case INTERVIEW = 'Interview';
    case SONG_LYRICS = 'Song Lyrics';

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'value')
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toCommaSeparated(): string
    {
        return implode(', ', self::values());
    }
}
