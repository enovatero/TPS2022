<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Validator;
use App\UserAddress;

class AddressController extends Controller
{
    public function getCountiesByCountry(Request $request){
//       cache()->flush();exit;
      $counties = $this->getCounties();
      $selectHtml = '<option selected disabled>Alege judetul/regiunea</option>';
      if($request->input('country_code') != null){
        foreach($counties as $state){
          if($state->country_code == $request->input('country_code')){
            $selectHtml .= '<option value="'.$state->state_code.'">'.$state->state_name.'</option>';
          }
        }
      }
      return ['success' => true, 'html' => $selectHtml];
    }
  
    public static function getCounties(){
      $counties = cache('counties');
      if($counties){
        return $counties;
      }
      $counties = DB::table('states')->get();
      cache(['counties' => $counties]);
      return $counties;
    } 
  
    public function getCitiesByState(Request $request){
//       cache()->flush();exit;
      $cities = $this->getCities($request->input('state_code'), $request->input('country_code'));
      $selectHtml = '<option selected disabled>Alege orasul</option>';
      if($cities != null){
        foreach($cities as $city){
          $selectHtml .= '<option value="'.$city->id.'">'.$city->city_name.'</option>';
        }
      }
      return ['success' => true, 'html' => $selectHtml];
    }
  
    public static function getCities($stateCode, $countryCode){
      $cities = cache('cities_'.$stateCode.'_'.$countryCode);
      if($cities){
        return $cities;
      }
      $cities = DB::table('cities')->where(['state_code' => $stateCode, 'country_code' => $countryCode])->get();
      cache(['cities_'.$stateCode.'_'.$countryCode => $cities]);
      return $cities;
    } 
  
    public function removeAddress(Request $request){
      $form_data = $request->only('id');
      $validationRules = [
        'id'    => ['required'],
      ];

      $validationMessages = [
          'id.required'    => "Te rugam sa selectezi o adresa valida!",
      ];
      $validator = Validator::make($form_data, $validationRules, $validationMessages);
      if ($validator->fails()){
          return ['success' => false, 'msg' => $validator->errors()->all()];  
      }
      else{
        $address = UserAddress::where('id', $request->input('id'))->first();
        $address->delete();
        return ['success' => true, 'msg' => 'Adresa stearsa cu succes!'];
      }
    }
    
    public function getUserAddresses(Request $request){
      $form_data = $request->only('user_id');
      $validationRules = [
        'user_id'    => ['required'],
      ];
      $validationMessages = [
          'user_id.required'    => "Te rugam sa selectezi un user valid!",
      ];
      $validator = Validator::make($form_data, $validationRules, $validationMessages);
      if ($validator->fails()){
          return ['success' => false, 'msg' => $validator->errors()->all()];  
      } else{
        $address = UserAddress::where('id', $request->input('user_id'))->get();
        foreach($address as &$addr){
          $addr->city_name = $addr->city_name()->city_name;
          $addr->state_name = $addr->state_name()->state_name;
        }
        return ['success' => true, 'userAddresses' => $address];
      }
    }
}