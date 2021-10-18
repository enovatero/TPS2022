<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Attribute extends Model
{
    public function category(){
      return $this->belongsToMany(Category::class, "category_attributes", "category_id", "attribute_id");
    }
}
