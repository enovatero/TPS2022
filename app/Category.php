<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Category extends Model
{
    public function attributes(){
      return $this->belongsToMany(Attribute::class, 'category_attributes', 'category_id', 'attribute_id')->orderBy('title', 'ASC');
    }
    public function productParent(){
      return $this->hasMany(ProductParent::class)->orderBy('title', 'ASC');
    }
}
