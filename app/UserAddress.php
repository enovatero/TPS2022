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
  
}
