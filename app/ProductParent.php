<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class ProductParent extends Model
{
  use SoftDeletes;
  protected $dates = ['deleted_at'];
  
  public function um_title(){
//     dd($this->belongsTo(Unit::class)->get());
    return $this->hasOne(Unit::class, 'id', 'um');
  }
  public function products(){
    return $this->hasMany(Product::class, 'parent_id', 'id')->orderBy('name', 'ASC');
  }
  public function category(){
    return $this->belongsTo(Category::class);
  }
}
