<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class UserAddress extends Model
{
  protected $table = 'user_addresses';
  
  public function individuals(){
    return $this->hasOne(Individual::class, 'user_id', 'id');
  }
  public function legal_entities(){
    return $this->hasOne(LegalEntity::class, 'user_id', 'id');
  }
  public function city_name(){
    return \DB::table('cities')->select('city_name')->where('id', $this->city)->first();
  }
  public function state_name(){
    return \DB::table('states')->select('state_name')->where('id', $this->state)->first();
  }
  
}
