<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class OfferEvent extends Model
{
    public function offer(){
      return $this->hasOne(Offer::class, 'id', 'offer_id');
    }
}
