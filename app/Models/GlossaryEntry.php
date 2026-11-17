<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlossaryEntry extends Model
{
    public const TYPES = ['character', 'place', 'other'];

    protected $guarded = ['id'];

    protected $casts = ['aliases' => 'array'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
