<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Product extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
  
    public function allAttributes(){
      return $this->belongsToMany(Attribute::class, 'product_attributes', 'product_id', 'attribute_id')->orderBy('type', 'DESC');
    }
  public function getparent(){
    return $this->belongsTo(ProductParent::class, 'parent_id', 'id');
  }
  public function categoryId(){
    $parent = $this->getParent();
    $categoryId = 5; // default
    if($parent){
      $categoryId = $parent->first()->category_id;
    }
    return $categoryId;
  }
}
