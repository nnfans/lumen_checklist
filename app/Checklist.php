<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Checklist extends TimestampBaseModel
{
    protected $casts = [
        'is_completed' => 'boolean'
    ];

    public function items() {
        return $this->hasMany('App\Item');
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