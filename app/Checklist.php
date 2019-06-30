<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{

    public function items() {
        return $this->hasMany('App\Item');
    }
}