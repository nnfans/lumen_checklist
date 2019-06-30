<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TimestampBaseModel extends Model
{
    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value);
    }

    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value);
    }

}
