<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class Ingredient_stock_change extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $table = "ingredient_stock_change";

    protected $fillable = [
        'ingredient_id', 'amount_before','amount_after', 'minimum_amount_before',  'minimum_amount_after', 'expired_at_before', 'expired_at_after'
    ];

    protected $hidden = [

    ];
}
