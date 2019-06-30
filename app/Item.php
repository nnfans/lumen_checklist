<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Item extends TimestampBaseModel
{

    protected $hidden = [ 'checklist_id' ];

    public function checklist() {
        return $this->belongsTo('Checklist');
    }
}