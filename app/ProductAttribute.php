<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class ProductAttribute extends Model
{
    public function attrs(){
      return $this->hasMany(Attribute::class, 'id', 'attribute_id');
    }
    public function getType(){
      $attribute = \App\Attribute::find($this->attribute_id);
      return $attribute->type;
    }
}
