<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class screquest extends Model
{
    use HasFactory;

    public function notes() {
        return $this->hasMany('App\Models\screquest_note','screquest_id','id');
    }

    public function info() {
        return $this->hasOne('App\Models\screquest_info','screquest_id','id');
    }

}
