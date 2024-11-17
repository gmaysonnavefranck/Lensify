<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function texts()
    {
        return $this->hasMany(Text::class);
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
