<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class OfferType extends Model
{
    public function children() {
      return $this->hasMany(OfferType::class, 'parent_id')
          ->with('children')
          ->orderBy('order');
  }
      /**
     * Return the Highest Order Menu Item.
     *
     * @param number $parent (Optional) Parent id. Default null
     *
     * @return number Order number
     */
    public function highestOrderMenuItem($parent = null)
    {
        $order = 1;

        $item = $this->where('parent_id', '=', $parent)
            ->orderBy('order', 'DESC')
            ->first();

        if (!is_null($item)) {
            $order = intval($item->order) + 1;
        }

        return $order;
    }
  
  public function products(){
    $prodIds = json_decode($this->products, true);
    if($prodIds != null && count($prodIds) > 0){
      return \App\Product::whereIn('id', $prodIds)->get();
    } else{
      return [];
    }
  }
}
