<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{

    protected $hidden = [ 'checklist_id' ];

    public function checklist() {
        return $this->belongsTo('Checklist');
    }
}