<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Client extends Model
{
    public function userAddress()
    {
        return $this->hasMany(UserAddress::class, 'user_id', 'id');
    }

    public function userMainAddress()
    {
        return $this->hasOne(UserAddress::class, 'user_id', 'id')->orderBy('id');
    }

    public function legal_entity()
    {
        return $this->hasOne(LegalEntity::class, 'user_id', 'id');
    }
}
