<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{   

    protected $fillable = [
        'brand_id',
        'model',
        'serial',
        'quantity'
    ];

    public function brand()
    {
        return $this->hasOne('App\Brand', 'id', 'brand_id');
        //                 ( <Model>, <id_of_specified_Model>, <id_of_this_model> )
    }
}
