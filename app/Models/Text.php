<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Text extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function textNotes()
    {
        return $this->hasMany(TextNote::class);
    }

    public function parentText()
    {
        return $this->belongsTo(Text::class, 'translation_for');
    }

    public function translations()
    {
        return $this->hasMany(Text::class, 'translation_for');
    }
}
