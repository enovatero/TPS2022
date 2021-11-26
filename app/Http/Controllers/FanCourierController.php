<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use FanCourier;
use Validator;
use App\Offer;
use App\UserAddress;
use App\FanOrder;
use App\Models\User;

class FanCourierController extends Controller
{
  public static function getCounties(){
    $counties = cache('fancourier_counties');
    if($counties){
      return $counties;
    }
    $city_county = FanCourier::city();
    $counties = [];
    foreach($city_county as $item){
      array_push($counties, $item->judet);
    }
    $counties = array_values(array_unique($counties));
    cache(['fancourier_counties' => $counties], now()->addWeeks(4));
    return $counties;
  }  
  
  // Get all cities by county and with id
  public static function getCitiesWithId(Request $request){
    if(empty($request->input('county'))){
      return [];
    }
    $county = $request->input('county');
    $cities_with_id = cache('fancourier_cities_with_id_'.$county);
    
    if($cities_with_id){
      return $cities_with_id;
    }
    
    $city_by_county = FanCourier::city(['judet'=>$county, 'language'=>'RO']);
    $cities_with_id = [];
    foreach($city_by_county as $item){
      array_push($cities_with_id, [
        'localitate' => $item->localitate,
        'id_localitate_fan' => $item->id_localitate_fan
      ]);
    }
    cache(['fancourier_cities_with_id_'.$county => $cities_with_id], now()->addWeeks(4));
    return $cities_with_id;
  }
  
    // Get all cities by county and agency
  public static function getCitiesAgency(Request $request){
    if(empty($request->input('county'))){
      return [];
    }
    $county = $request->input('county');
    $cities_agency = cache('fancourier_cities_agency_'.$county);
    
    if($cities_agency){
      return $cities_agency;
    }
    
    $city_by_county = FanCourier::LocalitatiSedii(['id'=>$county, 'tip'=>'agentie', 'request_type' => 'get']);
    $city_by_county = json_decode($city_by_county, true);
    $cities_agency = [];
    foreach($city_by_county as $item){
      array_push($cities_agency, [
        'localitate' => $item['nume'],
        'id_localitate_fan' => $item['id']
      ]);
    }
    cache(['fancourier_cities_agency_'.$county => $cities_agency], now()->addWeeks(4));
    return $cities_agency;
  }
  
  // Get all cities by county
  public static function getCities(Request $request){
    if(empty($request->input('county'))){
      return [];
    }
    $county = $request->input('county');
    $cities = cache('fancourier_cities_'.$county);
    if($cities){
      return $cities;
    }
    
    $city_by_county = FanCourier::city(['judet'=>$county, 'language'=>'RO']);
    $cities = [];
    foreach($city_by_county as $item){
      array_push($cities, $item->localitate);
    }
    $cities = array_values(array_unique($cities));
    cache(['fancourier_cities_'.$county => $cities], now()->addWeeks(4));
    return $cities;
  }
  public function test(){
    $awb = FanCourier::PrintAwb([
        'nr'=>'2308000120020',
    ]);
    print_r($awb);
  }
  
  public static function printAwb($awb, $client_id){
    config(['fancourier.client_id' => $client_id]);
    app()->bind('fancourier','SeniorProgramming\FanCourier\Services\ApiService');
    if($awb){
      $awb_file = FanCourier::PrintAwb([
          'nr' => $awb,
      ]);
    } else{
      abort(response('AWB number required', 404));
    }
      echo $awb_file;
  }
  
  public static function getSedii($localitate = null){
     $sedii = FanCourier::SediiFAN([
        'localitate' => $localitate,
        'destinatar' => 0,
        'request_type' => 'get',
    ]);
    return $sedii;
  }
  
  public static function getKmExteriori($localitate){
    $km_exteriori = FanCourier::KmExteriori([
        'localitate_id' => $localitate,
        'request_type' => 'get',
    ]);
    return ['success' => true, 'km' => $km_exteriori];
  }
  
  public function getPrice(){
    $price = FanCourier::price([
        'serviciu' => 'standard',
        'localitate_dest' => 'Targu Mures',
        'judet_dest' => 'Mures',
        'plicuri' => 1,
        'colete' => 2,
        'greutate' => 5,
        'lungime' => 10,
        'latime' => 10,
        'inaltime' => 10,
        'val_decl' => 600,
        'plata_ramburs' => 'destinatar',
        'plata_la' => 'destinatar',
    ]);
    return $price;
  }
  public static function generateAwb(Request $request){
       $form_data = $request->only([
          'order_id',
          'deliveryAccount',
          'numar_colete',
          'greutate_totala',
          'ramburs_numerar',
          'inaltime_pachet',
          'latime_pachet',
          'lungime_pachet',
          'continut_pachet',
          'ridicare_sediu_fan',
          'plata_expeditie',
          'deliveryAddressAWB',
        ]);
      $validationRules = [
          'order_id'        => ['required'],
          'deliveryAccount' => ['required'],
          'deliveryAddressAWB' => ['required'],
          'numar_colete'    => ['required'],
          'greutate_totala' => ['required'],
          'ramburs_numerar' => ['required'],
          'inaltime_pachet' => ['required'],
          'latime_pachet'   => ['required'],
          'lungime_pachet'  => ['required'],
          'continut_pachet' => ['required'],
      ]; 
      $validationMessages = [
          'order_id.required'        => 'Selectati o comanda pentru a genera awb-ul',
          'deliveryAccount.required' => 'Selectati un cont pentru FanCourier',
          'deliveryAddressAWB.required' => 'Selectati o adresa de livrare',
          'numar_colete.required'     => 'Numarul de colete este obligatoriu',
          'greutate_totala.required'  => 'Greutatea totala este obligatorie',
          'ramburs_numerar.required'  => 'Adaugati valoarea rambursului',
          'inaltime_pachet.required'  => 'Adaugati inaltimea pachetului',
          'latime_pachet.required'    => 'Adaugati latimea pachetului',
          'lungime_pachet.required'   => 'Adaugati lungimea pachetului',
          'continut_pachet.required'  => 'Adaugati continutul pachetului',
      ];
//       if(isset($form_data['ridicare_sediu_fan']) && $form_data['ridicare_sediu_fan'] == 'on'){
//         $form_data['sediu_fan'] = 'De adaugat sediu FAN daca va fi nevoie + implementare';
//       }
      $validator = Validator::make($form_data, $validationRules, $validationMessages);
      if ($validator->fails()){
        return ['success' => false, 'msg' => $validator->errors()->toArray()];
      } else{
        $offer = Offer::with('fanData')->find($form_data['order_id']);
        $agent = User::find($offer->agent_id);
        $userAddress = UserAddress::find($form_data['deliveryAddressAWB']);
        $userData = $userAddress->userData();
        $totalPlata = $form_data['ramburs_numerar'];
        
        $date_awb = [
            'tip_serviciu' => $totalPlata > 0 ? 'cont colector' : 'standard', 
            'banca' => '',
            'iban' =>  'RO24BTRL04501202U46323XX', // de pus in settings in admin
            'nr_plicuri' => 0,
            'nr_colete' => $form_data['numar_colete'],
            'greutate' => $form_data['greutate_totala'],
            'plata_expeditie' => $form_data['plata_expeditie'],
            'ramburs_bani' => $totalPlata > 0 ? floatval($totalPlata) : '',
            'plata_ramburs_la' => $totalPlata > 0 ? 'expeditor' : '',
            'valoare_declarata' => $form_data['ramburs_numerar'],
            'persoana_contact_expeditor' => $agent->name ?: 'Top Profil Sistem',
            'persoana_contact_expeditor_adresa' => 'Sos. de Centura,  nr. 3',
            'observatii' => 'A se contacta telefonic',
            'continut' => $form_data['continut_pachet'] ?: 'Sisteme acoperis',
            'nume_destinar' => $userAddress->delivery_contact,
            'persoana_contact' => $userAddress->delivery_contact ?: $userData->name,
            'telefon' => $userAddress->phone ?: $userData->phone,
            'fax' => '',
            'email' => $userAddress->email ?: $userData->email,
            'judet' => $userAddress->state_name(),
            'localitate' => $userAddress->city_name(),
            'strada' => $userAddress->address,
            'nr' => '',
            'cod_postal' => '000000',
            'bl' => '',
            'scara' => '',
            'etaj'  => '',
            'apartament' => '',
            'inaltime_pachet' => $form_data['inaltime_pachet'],
            'lungime_pachet' => $form_data['lungime_pachet'],
            'latime_pachet' => $form_data['latime_pachet'],
            'restituire' => '',
            'centru_cost' => '',
            'optiuni' => '',
            'packing' => '',
            'date_personale' => ''
        ];
        try{
          config(['fancourier.client_id' => $form_data['deliveryAccount']]);
          app()->bind('fancourier','SeniorProgramming\FanCourier\Services\ApiService');
          
          $awb = FanCourier::generateAwb(['fisier' => [$date_awb]]);
          $created_at = date("Y-m-d H:i:s");
          
          if($offer->awb_id != null && $offer->delivery_type == 'fan'){
            $fanOrder = FanOrder::find($offer->awb_id);
          } else{
            $fanOrder = new FanOrder();
          }
          
          $fanOrder->order_id = $offer->id;
          $fanOrder->cont_id = $form_data['deliveryAccount'];
          $fanOrder->plata_expeditie = $form_data['plata_expeditie'];
          $fanOrder->numar_colete = $form_data['numar_colete'];
          $fanOrder->greutate_totala = $form_data['greutate_totala'];
          $fanOrder->ramburs_numberar = floatval($totalPlata);
          $fanOrder->inaltime_pachet = $form_data['inaltime_pachet'];
          $fanOrder->latime_pachet = $form_data['latime_pachet'];
          $fanOrder->lungime_pachet = $form_data['lungime_pachet'];
          $fanOrder->continut_pachet = $form_data['continut_pachet'];
          $fanOrder->adresa_livrare_id = $form_data['deliveryAddressAWB'];
          $fanOrder->created_at = $created_at;
          $fanOrder->updated_at = $created_at;
          $fanOrder->awb = $awb[0]->awb;
          $fanOrder->save();
          
          $offer->awb_id = $fanOrder->id;
          $offer->save();
          return ['success' => true, 'msg' => 'AWB-ul s-a generat cu succes!', 'awb' => $fanOrder->awb, 'id' => $offer->id, 'client_id' => $fanOrder->cont_id];
        } catch(Exception $e){
          return ['success' => false, 'msg' => 'Eroare: '.$e->getMessage()];
        }
   }
  }
}