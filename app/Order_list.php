<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class Order_list extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $table = "order_list";

    protected $fillable = [
        'id', 'order_id','merchant_id', 'product_id', 'user_id', 'order_list_status_id', 'amount'
    ];

    protected $hidden = [
        
    ];
}