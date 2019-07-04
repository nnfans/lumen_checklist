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
        if ($value) {
          return Carbon::parse($value);
        } else {
           return null;
        }
    }

    public function getCompletedAtAttribute($value) {
        if ($value) {
          return Carbon::parse($value);
        } else {
          return null;
        }
    }
}