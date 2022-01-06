<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Validator;
use App\UserAddress;
use App\Offer;
use App\Client;

class AddressController extends Controller
{
  // functie pentru preluarea judetelor in functie de tara. Aici ideea e simpla: daca nu am unul din matching-uri tara - judet, atunci le iau din baza de date si le stochez in cache - pentru viteza
    public function getCountiesByCountry(Request $request){
//       cache()->flush();exit;
      $counties = $this->getCounties();
      $selectHtml = '<option selected disabled>Alege judetul/regiunea</option>';
      if($request->input('country_code') != null){
        foreach($counties as $state){
          if($state->country_code == $request->input('country_code')){
            // daca am intrat pe edit, trebuie sa imi creez selectul cu optiunea deja selectata
            if($request->input('selected_state') != null && $request->input('selected_state') == $state->state_code.'_'.$state->state_name){
              $selectHtml .= '<option selected value="'.$state->state_code.'">'.$state->state_name.'</option>';
            } else{
              $selectHtml .= '<option value="'.$state->state_code.'">'.$state->state_name.'</option>';
            }
          }
        }
      }
      return ['success' => true, 'html' => $selectHtml];
    }
  
    // functie pentru preluarea judetelor, fie din cache, fie din baza de date(daca nu exista in cache)
    public static function getCounties(){
      $counties = cache('counties');
      if($counties){
        return $counties;
      }
      $counties = DB::table('states')->orderBy('state_name')->get();
      cache(['counties' => $counties]);
      return $counties;
    } 
  
    // acelasi mod de lucru ca la tara-judet, doar ca aici preiau orasul in functie de judet
    public function getCitiesByState(Request $request){
//       cache()->flush();exit;
      $cities = $this->getCities($request->input('state_code'), $request->input('country_code'));
      $selectHtml = '<option selected disabled>Alege orasul</option>';
      if($cities != null){
        foreach($cities as $city){
          // creez selectul cu optiunea selectata daca sunt pe edit
          if($request->input('selected_city') != null && $request->input('selected_city') == $city->id.'_'.$city->city_name){
            $selectHtml .= '<option selected value="'.$city->id.'">'.$city->city_name.'</option>';
          } else{
            $selectHtml .= '<option value="'.$city->id.'">'.$city->city_name.'</option>';
          }
        }
      }
      return ['success' => true, 'html' => $selectHtml];
    }
  
    // preluare orase fie din cache, fie din baza de date
    public static function getCities($stateCode, $countryCode){
      $cities = cache('cities_'.$stateCode.'_'.$countryCode);
      if($cities){
        return $cities;
      }
      $cities = DB::table('cities')->where(['state_code' => $stateCode, 'country_code' => $countryCode])->orderBy('city_name')->get();
      cache(['cities_'.$stateCode.'_'.$countryCode => $cities]);
      return $cities;
    } 
  
    // sterg o adresa in functie de id-ul adresei din baza de date, din user_addresses
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
    // preiau adresele user-ului cu id-ul X
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
        $client = Client::find($request->input('user_id'));
        foreach($address as &$addr){
          $addr->city_name = $addr->city_name();
          $addr->state_name = $addr->state_name();
          $addr->phone = $addr->delivery_phone != null ? $addr->delivery_phone : $addr->userData()->phone;
          $addr->name = $addr->delivery_contact != null ? $addr->delivery_contact : $addr->userData()->name;
        }
        return ['success' => true, 'userAddresses' => $address, 'transparent_band' => $client->transparent_band];
      }
    }
  
   // functie pentru salvarea/editarea unei adrese pentru user-ul X intr-o oferta
    public function saveNewAddress(Request $request){
      $form_data = $request->only('address_id', 'offer_id', 'client_id', 'country', 'city', 'state', 'delivery_address', 'delivery_phone', 'delivery_contact');
      $validationRules = [
        'offer_id'    => ['required'],
        'client_id'    => ['required'],
        'country'    => ['required'],
        'city'    => ['required'],
        'state'    => ['required'],
        'delivery_address'    => ['required'],
        'delivery_phone'    => ['required'],
        'delivery_phone'    => ['regex:/^[0-9]{10}+$/'],
        'delivery_contact'    => ['required'],
      ];
      $validationMessages = [
          'offer_id.required'    => "Te rugam sa selectezi o oferta valida!",
          'client_id.required'    => "Te rugam sa selectezi un user valid!",
          'country.required'    => "Te rugam sa selectezi o tara!",
          'city.required'    => "Te rugam sa selectezi un oras!",
          'state.required'    => "Te rugam sa selectezi un judet/regiune!",
          'delivery_address.required'    => "Adresa este obligatorie!",
          'delivery_phone.required'    => "Numarul de telefon este obligatoriu!",
          'delivery_phone.regex'    => "Numarul de telefon nu respecta formatul corect! Ex. 0712345678!",
          'delivery_contact.required'    => "Persoana de contact este obligatorie!",
      ];
      
      $validator = Validator::make($form_data, $validationRules, $validationMessages);
      if ($validator->fails()){
          return ['success' => false, 'error' => $validator->errors()->all()];  
      } else{
        if($request->input('address_id') != null){
          $address = UserAddress::find($request->input('address_id'));
        } else{
          $address = new UserAddress;
        }
        $address->user_id = $form_data['client_id'];
        $address->address = $form_data['delivery_address'];
        $address->country = $form_data['country'];
        $address->state = $form_data['state'];
        $address->city = $form_data['city'];
        $address->delivery_phone = $form_data['delivery_phone'];
        $address->delivery_contact = $form_data['delivery_contact'];
        $address->save();
        
        // updatez oferta cu noua adresa adaugata
        $offer = Offer::find($request->input('offer_id'));
        $offer->delivery_address_user = $address->id;
        $offer->save();
        
        $userAddresses = UserAddress::where('user_id', $request->input('client_id'))->get();
        foreach($userAddresses as &$addr){
          $addr->city_name = $addr->city_name();
          $addr->state_name = $addr->state_name();
          $addr->phone = $addr->delivery_phone != null ? $addr->delivery_phone : $addr->userData()->phone;
          $addr->name = $addr->delivery_contact != null ? $addr->delivery_contact : $addr->userData()->name;
        }
        
        return ['success' => true, 'address' => $address, 'userAddresses' => $userAddresses, 'msg' => 'Adresa a fost modificata cu succes!'];
      }
    }
}