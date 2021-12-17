<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class AttributeColor extends Model
{
    public function color(){
      return $this->hasOne(Color::class, 'id', 'color_id');
    }
}
