<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Attribute extends Model
{
    public function category(){
      return $this->belongsToMany(Category::class, "category_attributes", "category_id", "attribute_id")->orderBy('title', 'ASC');
    }
    public function colors(){
      return $this->belongsToMany(Color::class, "attribute_colors", "attribute_id", "color_id")->orderBy('ral', 'ASC');
    }
    public function dimensions(){
      return $this->belongsToMany(Dimension::class, "attribute_dimensions", "attribute_id", "dimension_id")->orderBy('value', 'ASC');
    }
}
