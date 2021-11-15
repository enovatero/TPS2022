<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use FanCourier;
use Validator;
use App\Offer;

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
  
  public static function printAwb($awb){
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
    // Datele required pe care le primesc din formular
       $form_data = $request->only([
          'destinatar_name',
          'destinatar_contact',
          'destinatar_phone',
          'destinatar_fax',
          'destinatar_email',
          'destinatar_county',
          'destinatar_city',
          'destinatar_km',
          'sediu_fan',
          'destinatar_strada',
          'destinatar_nr',
          'destinatar_cod_postal',
          'destinatar_bloc',
          'destinatar_observatii',
          'destinatar_etaj',
          'destinatar_apartament',
          'expeditor_centru',
          'expeditor_persoana_contact',
          'expeditor_agentie',
          'expeditor_localitate',
          'tip_serviciu',
          'numar_plicuri',
          'numar_colete',
          'greutate_totala',
          'plata_ramburs',
          'ramburs_numerar',
          'ramburs_currency',
          'valoare_declarata',
          'inaltime_pachet',
          'latime_pachet',
          'lungime_pachet',
          'observatii_pachet',
          'continut_pachet',
          'ridicare_sediu_fan',
          'order_id',
        ]);
      $validationRules = [
          'destinatar_name' => ['required'],
          'destinatar_phone' => ['required'],
          'destinatar_county' => ['required'],
          'destinatar_city' => ['required'],
          'destinatar_strada' => ['required'],
          'destinatar_cod_postal' => ['required'],
          'expeditor_persoana_contact' => ['required'],
          'expeditor_agentie' => ['required'],
          'expeditor_localitate' => ['required'],
          'tip_serviciu' => ['required'],
          'numar_plicuri' => ['required'],
          'numar_colete' => ['required'],
          'greutate_totala' => ['required'],
      ]; 
      if(isset($form_data['ridicare_sediu_fan']) && $form_data['ridicare_sediu_fan'] == 'on'){
        $validationRules['sediu_fan'] = ['required'];
      }
      $validationMessages = [
          'destinatar_name.required' => 'Numele destinatarului este obligatoriu',
          'destinatar_phone.required' => 'Numarul de telefon al destinatarului este obligatoriu',
          'destinatar_county.required' => 'Judetul destinatarului este obligatoriu',
          'destinatar_city.required' => 'Orasul destinatarului este obligatoriu',
          // check if the "bifa" was checked
          'sediu_fan.required' => 'Selectati un sediu FAN',
          'destinatar_strada.required' => 'Strada destinatarului este obligatorie',
          'destinatar_cod_postal.required' => 'Codul postal este obligatoriu',
          'expeditor_persoana_contact.required' => 'Persoana de contact EXPEDITOR este obligatorie',
          'expeditor_agentie.required' => 'Selectati o agentie FAN',
          'expeditor_localitate.required' => 'Selectati localitatea agentiei FAN',
          'tip_serviciu.required' => 'Tipul de serviciu este obligatoriu',
          'numar_plicuri.required' => 'Adaugati un numar de plicuri',
          'numar_colete.required' => 'Numarul de colete este obligatoriu',
          'greutate_totala.required' => 'Greutatea totala este obligatorie',
      ];
      $validator = Validator::make($form_data, $validationRules, $validationMessages);
      if ($validator->fails()){
        return ['success' => false, 'msg' => $validator->errors()->toArray()];
      } else{
//         dd($form_data);
        $date_awb = [
                'tip_serviciu' => 'standard', 
                'banca' => '',
                'iban' =>  '',
                'nr_plicuri' => $form_data['numar_plicuri'],
                'nr_colete' => $form_data['numar_colete'],
                'greutate' => $form_data['greutate_totala'],
                'plata_expeditie' => $form_data['plata_ramburs'],
                'ramburs_bani' => $form_data['ramburs_numerar'],
                'plata_ramburs_la' => $form_data['plata_ramburs'],
                'valoare_declarata' => $form_data['valoare_declarata'],
                'persoana_contact_expeditor' => $form_data['expeditor_persoana_contact'],
                'observatii' => $form_data['observatii_pachet'],
                'continut' => $form_data['continut_pachet'],
                'nume_destinar' => $form_data['destinatar_name'],
                'persoana_contact' => $form_data['destinatar_contact'] == null ? $form_data['destinatar_name'] : $form_data['destinatar_contact'] ,
                'telefon' => $form_data['destinatar_phone'],
                'fax' => $form_data['destinatar_fax'],
                'email' => $form_data['destinatar_email'],
                'judet' => $form_data['destinatar_county'],
                'localitate' => $form_data['destinatar_city'],
                'strada' => $form_data['destinatar_strada'],
                'nr' => $form_data['destinatar_nr'],
                'cod_postal' => $form_data['destinatar_cod_postal'],
                'bl' => $form_data['destinatar_bloc'],
                'scara' => $form_data['destinatar_observatii'],
                'etaj'  => $form_data['destinatar_etaj'],
                'apartament' => $form_data['destinatar_apartament'],
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
          $awb = FanCourier::generateAwb(['fisier' => [$date_awb]]);
          $offer = Offer::find($form_data['id']);
          $offer->awb_fancourier = $awb[0]->awb;
          $offer->save();
          return ['success' => true, 'msg' => 'AWB-ul s-a generat cu succes!', 'awb' => $offer->awb_fancourier, 'id' => $offer->id];
        } catch(Exception $e){
          return ['success' => false, 'msg' => 'Eroare: '.$e->getMessage()];
        }
   }
  }
}