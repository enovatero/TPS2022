<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class OffertypePreselectedColor extends Model
{
    public function color(){
      return $this->hasOne(Color::class, 'id', 'color_id');
    }
    public function selectedcolor(){
      return $this->hasOne(Color::class, 'id', 'selected_color_id');
    }
}
