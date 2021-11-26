<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Attribute;

class Offer extends Model
{
    protected $casts = [
        'selected_products' => 'array',
    ];
    public function distribuitor(){
      return $this->belongsTo(Distribuitor::class);
    }
    public function status_name(){
      return $this->hasOne(Status::class, 'id', 'status');
    }
    public function client(){
      return $this->belongsTo(Client::class);
    }
    public function delivery_address(){
      return $this->belongsTo(UserAddress::class, 'delivery_address_user', 'id');
    }
  
    public function offerType(){
      return $this->hasOne(OfferType::class, 'id', 'type');
    }
  
    public function fanData(){
      return $this->hasOne(FanOrder::class, 'order_id', 'id');
    }
  
    public function attrs(){
      $attributes = $this->attributes;
      $createdAttrs = [];
      if($attributes['attributes'] != null){
        $attributes['attributes'] = json_decode($attributes['attributes'], true);
        foreach($attributes['attributes'] as $attr){
          $retAttr = explode("_", $attr);
          $attrId = $retAttr[0];
          if(count($retAttr) == 2){
            $value = $retAttr[1];
          }
          if(count($retAttr) == 3){
            $value = $retAttr[2];
          }
          $foundedAttr = Attribute::where('id', $attrId)->first();
          $createdAttrs[] = [
            'title' => ucfirst($foundedAttr['title']),
            'value' => $value,
          ];
        }
      }
      return $createdAttrs;
    }
  
  public function parentsWithProducts(){
    $prices = $this->prices != null && !is_array($this->prices) ? json_decode($this->prices) : (is_array($this->prices) ? $this->prices : []);
    $parents = [];
    if(count($prices) > 0){
      foreach($prices as $price){
        $parent = ProductParent::with('um_title', 'products.getparent.category')->find($price['parent']);
        $parent->qty = $price['qty'] != null ? $price['qty'] : 0;
        array_push($parents, $parent);
      }
    }
//     dd($parents);
    return $parents;
  }
}
