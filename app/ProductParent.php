<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class ProductParent extends Model
{
  public function um_title(){
//     dd($this->belongsTo(Unit::class)->get());
    return $this->hasOne(Unit::class, 'id', 'um');
  }
}
