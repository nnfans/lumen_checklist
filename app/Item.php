<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Item extends TimestampBaseModel
{
    protected $casts = [
        'is_completed' => 'boolean'
    ];

    protected $hidden = [ 'checklist_id' ];

    public function checklist() {
        return $this->belongsTo('Checklist');
    }

    public function getDueAttribute($value) {
        return Carbon::parse($value);
    }

    public function getCompletedAtAttribute($value) {
        return Carbon::parse($value);
    }
}