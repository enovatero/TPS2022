<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class UserAddress extends Model
{
  protected $table = 'user_addresses';
  protected $fillable = [
    'type',
    'offer_date', 
    'client_id', 
    'distribuitor_id', 
    'price_grid_id', 
    'curs_eur', 
    'agent_id', 
    'color_id', 
    'dimension_id', 
    'delivery_address_user', 
    'delivery_date', 
    'observations', 
    'created_at', 
    'updated_at', 
    'status', 
    'serie'
  ];
  
  public function individuals(){
    return $this->hasOne(Individual::class, 'user_id', 'id');
  }
  public function legal_entities(){
    return $this->hasOne(LegalEntity::class, 'user_id', 'id');
  }
  public function userData(){
    return \DB::table('clients')->where('id', $this->user_id)->first();
  }
  public function city_name(){
    $city = \DB::table('cities')->select('city_name')->where('id', $this->city)->first();
    return $city->city_name ?? 'Bucuresti';
  }
  public function state_name(){
    $state = \DB::table('states')->select('state_name')->where(['state_code' => $this->state, 'country_code' => $this->country])->first();
    return $state->state_name ?? 'Bucuresti';
  }
  
}
