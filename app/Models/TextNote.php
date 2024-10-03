<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextNote extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function text()
    {
        return $this->belongsTo(Text::class);
    }
}
