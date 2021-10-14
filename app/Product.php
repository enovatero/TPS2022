<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Product extends Model
{
    public function listAttributes(){
      $attributes = $this->getAttribute('attributes') != null ? json_decode($this->getAttribute('attributes'), true) : [];
      $attrIds = [];
      if(count($attributes) > 0){
        foreach($attributes as $attribute){
          $attrId = array_key_first($attribute);
          $val = $attribute[$attrId];
          array_push($attrIds, $attrId);
        }  
      }
      $dbAttributes = \App\Attribute::whereIn('id', $attrIds)->get();
      if($dbAttributes && count($dbAttributes) > 0){
        foreach($dbAttributes as &$attr){
          $values = [];
          foreach($attributes as $attribute){
            $attrId = array_key_first($attribute);
            $val = $attribute[$attrId];
            if($attrId == $attr->id){
              array_push($values, $val);
            }
          }
          $attr->values = $values;
        }
      }
      return $dbAttributes;
    }
}
