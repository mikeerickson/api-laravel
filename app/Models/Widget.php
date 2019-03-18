<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    protected $guarded = [];

    public function getMetaAttribute($data)
    {
        return json_decode($data);
    }
}
