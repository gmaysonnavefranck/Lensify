<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Author extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Standalone texts only; book chapters are reached through their book
    public function texts()
    {
        return $this->hasMany(Text::class)->whereNull('book_id');
    }

    // The current user's author with this name, created if missing; null for a blank name
    public static function idFor(?string $name): ?int
    {
        $name = trim($name ?? '');
        if ($name === '') {
            return null;
        }

        return static::firstOrCreate(['name' => $name, 'user_id' => Auth::id()])->id;
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function themes()
    {
        return $this->belongsToMany(Theme::class);
    }

    public function literaryPeriods()
    {
        return $this->belongsToMany(LiteraryPeriod::class);
    }
}
