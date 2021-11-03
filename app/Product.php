<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Product extends Model
{
    public function allAttributes(){
      return $this->belongsToMany(Attribute::class, 'product_attributes', 'product_id', 'attribute_id')->withPivot('value')->orderBy('type', 'DESC');
    }
    public function listAttributes($attributes){
      $attributes = $attributes != null ? json_decode($attributes, true) : [];
      if($attributes && count($attributes) > 0){
        foreach($attributes as &$attribute){
          if($attribute['type'] == 1){
            $attribute['values'] = json_decode($attribute['pivot']['value'], true);
          } else{
            $attribute['values'] = $attribute['pivot']['value'];
          }
        }
      }
      return $attributes;
    }
}
