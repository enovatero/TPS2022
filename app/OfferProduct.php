<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class OfferProduct extends Model
{
    public function prices(){
      return $this->hasMany(OfferPrice::class, 'offer_products_id', 'id');
    }
    public function pricesByRule($rule_id){
      return $this->hasMany(OfferPrice::class, 'offer_products_id', 'id')->where('rule_id', $rule_id);
    }
    public function product(){
      return $this->hasOne(Product::class, 'id', 'product_id');
    }
    public function getParent(){
      return $this->hasOne(ProductParent::class, 'id', 'parent_id');
    }
}
