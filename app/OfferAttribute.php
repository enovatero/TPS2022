<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class OfferAttribute extends Model
{
    public function attribute(){
      return $this->hasOne(Attribute::class, 'id', 'attribute_id');
    }
}
