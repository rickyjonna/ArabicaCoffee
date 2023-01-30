<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Income_type extends Model
{
    protected $table = "income_type";

    protected $fillable = [
        'id','information'
    ];

    protected $hidden = [

    ];
}
