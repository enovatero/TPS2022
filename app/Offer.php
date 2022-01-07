<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Attribute;

class Offer extends Model
{
  public static $delivery_types = [
    'fan'      => 'Fan Courier',
    'nemo'     => 'Nemo Express',
    'tps'      => 'Livrare TPS',
    'ridicare' => 'Ridicare personala',
  ];
  public static $billing_statuses = [
    1 => 'Facturat',
    2 => 'Nefacturat',
    3 => 'Aviz',
    4 => 'Avans Facturat',
  ];
  public static $payment_types = [
    1 => 'Neachitat',
    2 => 'OP',
    3 => 'Online',
    4 => 'Cash',
    5 => 'Card',
    6 => 'Link2Pay',
    7 => 'La Termen',
    8 => 'Avans',
    9 => 'Ramburs',
  ];
  public static $attr_p_values = [
    1 => '1K',
    2 => '2K',
    3 => '1O',
    4 => '2O',
    5 => '1K/2O',
    6 => '2O/1K',
  ];
  public static $attr_p_values_new = [
    1 => '1K',
    2 => '2K',
    3 => '1O',
    4 => '2O',
    5 => '1K/2O',
    6 => '2O/1K',
    7 => 'N/A',
  ];
  
    protected $casts = [];
    public function distribuitor(){
      return $this->belongsTo(Distribuitor::class)->orderBy('title', 'ASC');
    }
    public function status_name(){
      return $this->hasOne(Status::class, 'id', 'status');
    }
    public function agent(){
      return $this->hasOne(Models\User::class, 'id', 'agent_id');
    }
    public function client(){
      return $this->belongsTo(Client::class)->orderBy('name', 'ASC');
    }
    public function category(){
      return $this->belongsTo(Category::class)->orderBy('title', 'ASC');
    }
    public function delivery_address(){
      return $this->belongsTo(UserAddress::class, 'delivery_address_user', 'id');
    }
    public function offerType(){
      return $this->hasOne(OfferType::class, 'id', 'type');
    }
    public function rulePrice(){
      return $this->hasOne(RulesPrice::class, 'id', 'price_grid_id');
    }
    public function fanData(){
      return $this->hasOne(FanOrder::class, 'order_id', 'id');
    }
    public function nemoData(){
      return $this->hasOne(NemoOrder::class, 'order_id', 'id');
    }
    public function offerDocs(){
      return $this->hasMany(OfferDoc::class, 'offer_id', 'id');
    }
    public function orderProducts(){
      return $this->hasMany(OfferProduct::class, 'offer_id', 'id');
    }
    public function offerWme(){
      return $this->hasOne(OfferWme::class, 'order_id', 'id');
    }
    public function serieName(){
      return $this->hasOne(OfferSerial::class, 'id', 'serie');
    }
    public function attrs(){
      return $this->hasMany(OfferAttribute::class, 'offer_id', 'id');
    }
    public function products(){
      return $this->hasMany(OfferProduct::class, 'offer_id', 'id');
    }
}
