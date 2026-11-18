<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadingDay extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['date' => 'date:Y-m-d'];
}
