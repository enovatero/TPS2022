<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Client extends Model
{
   public function userAddress(){
     return $this->hasMany(UserAddress::class, 'user_id', 'id');
   } 
}
