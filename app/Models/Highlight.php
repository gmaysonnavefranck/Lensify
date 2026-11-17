<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Highlight extends Model
{
    public const COLORS = ['yellow', 'green', 'blue', 'pink'];

    protected $guarded = ['id'];

    public function text()
    {
        return $this->belongsTo(Text::class);
    }
}
