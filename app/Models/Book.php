<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $guarded = ['id'];

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function chapters()
    {
        return $this->hasMany(Text::class)->orderBy('position');
    }

    public function glossaryEntries()
    {
        return $this->hasMany(GlossaryEntry::class)->orderBy('name');
    }

    public function lastRead()
    {
        return $this->belongsTo(Text::class, 'last_read_text_id');
    }
}
