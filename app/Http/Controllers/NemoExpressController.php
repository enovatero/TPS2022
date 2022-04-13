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
use App\NemoOrder;

class NemoExpressController extends Controller
{
    const CREATE_AWB_ENDPOINT = "create_awb";
    const PRICE_AWB_ENDPOINT = "get_price";
    const PRINT_AWB_ENDPOINT = "print?pdf=true";
    const INFO_AWB_ENDPOINT = "get_info";
    const CITY_LIST_ENDPOINT = "list_cities";
    const GET_SERVICES_LIST= "list_services?type=";
    const GET_HISTORY = "get_history";
    const GET_STATUS = "get_status";

    const SERVICES_TYPE_MAIN = "main";
    const SERVICES_TYPE_EXTRA = "extra";

    /**
     * @param array $data
     * @return mixed
     */
    public static function createAwb($apiKey, $data)
    {
        return (new self())->callApi($apiKey, self::CREATE_AWB_ENDPOINT, $data);
    }

  // creez awb-ul pentru NEMO
      public static function generateAwbNemo(Request $request){
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
            'fragil',
            'packageType',
            'openOnDelivery'
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
            'packageType' => ['required'],
            'openOnDelivery' => ['required'],
        ];
        $validationMessages = [
            'order_id.required'        => 'Selectati o comanda pentru a genera awb-ul',
            'deliveryAccount.required' => 'Selectati un cont pentru Nemo',
            'deliveryAddressAWB.required' => 'Selectati o adresa de livrare',
            'numar_colete.required'     => 'Numarul de colete este obligatoriu',
            'greutate_totala.required'  => 'Greutatea totala este obligatorie',
            'ramburs_numerar.required'  => 'Adaugati valoarea rambursului',
            'inaltime_pachet.required'  => 'Adaugati inaltimea pachetului',
            'latime_pachet.required'    => 'Adaugati latimea pachetului',
            'lungime_pachet.required'   => 'Adaugati lungimea pachetului',
            'continut_pachet.required'  => 'Adaugati continutul pachetului',
            'packageType.required'      => 'Alegeti tipul de pachet',
            'openOnDelivery.required'   => 'Alegeti optiune deschidere colet',

        ];
        $validator = Validator::make($form_data, $validationRules, $validationMessages);
        if ($validator->fails()){
          return ['success' => false, 'msg' => $validator->errors()->toArray()];
        } else{
          $offer = Offer::with('fanData')->find($form_data['order_id']);
          $agent = User::find($offer->agent_id);
          $userAddress = UserAddress::with('legal_entities')->find($form_data['deliveryAddressAWB']);
          $userData = $userAddress->userData();
          $totalPlata = $form_data['ramburs_numerar'];
          $legalEntity = $userAddress->legal_entities;
          // creez array-ul cu catele pe care le trimit catre NEMO
          $date_awb = [
              'type' => $form_data['packageType'] ?: 'package',
              'service_type' => 'standard',
              'ramburs' => floatval($totalPlata),
              'ramburs_type' => $totalPlata > 0 ? 'cont' : 'cash',
              'payer' => $form_data['plata_expeditie'],
              'weight' => $form_data['greutate_totala'],
              'length' => $form_data['lungime_pachet'],
              'width' => $form_data['latime_pachet'],
              'height' => $form_data['inaltime_pachet'],
              'content' => $form_data['continut_pachet'],
              'cnt' => $form_data['numar_colete'],
              'fragile' => $form_data['fragil'],
              'use_default_from_address' => true, // pentru preluarea adresei de livrare default din contul de client nemo
              'to_name' => $userAddress->delivery_contact ?: $userData->name,
              'to_contact' => $userAddress->delivery_contact ?: $userData->name,
              'to_address' => $userAddress->address,
              'to_city' => $userAddress->city_name(),
              'to_county' => $userAddress->state_name(),
              'to_country' => $userAddress->country,
//              'to_zipcode' => "000000",
              'to_email' => $userAddress->email ?: $userData->email,
              'to_phone' => $userAddress->delivery_phone ?: $userData->phone,
              'to_extra' => '',
              'to_cui' => $legalEntity != null ? $legalEntity->cui : '',
              'to_regcom' => $legalEntity != null ? $legalEntity->reg_com : '',
              'service_1' => (isset($form_data['openOnDelivery']) && $form_data['openOnDelivery'] == 1),
          ];
          try{
            // creez obiectul nemo cu deliveryAccount
            $apiKey = $form_data['deliveryAccount'] == 1 ? env('NEMO_API_KEY_IASI') : env('NEMO_API_KEY_BERCENI');
            $nemo = (new self())->createAwb($apiKey, $date_awb); // iau awb-ul
            if(!$nemo['success']){
              return ['success' => false, 'msg' => [0 => $nemo['error']]];
            }
            $created_at = date("Y-m-d H:i:s");

            // creez obiectul $nemoOrder cu datele pe care le-am trimis catre Nemo
            if($offer->awb_id != null && $offer->delivery_type == 'nemo'){
              $nemoOrder = NemoOrder::find($offer->awb_id);
            } else{
              $nemoOrder = new NemoOrder();
            }
            $nemoResponse = json_decode($nemo['response'], true);

            $nemoOrder->order_id = $offer->id;
            $nemoOrder->cont_id = $form_data['deliveryAccount'];
            $nemoOrder->plata_expeditie = $form_data['plata_expeditie'];
            $nemoOrder->type = $form_data['packageType'];
            $nemoOrder->numar_colete = $form_data['numar_colete'];
            $nemoOrder->greutate_totala = $form_data['greutate_totala'];
            $nemoOrder->ramburs_numerar = floatval($totalPlata);
            $nemoOrder->inaltime_pachet = $form_data['inaltime_pachet'];
            $nemoOrder->latime_pachet = $form_data['latime_pachet'];
            $nemoOrder->lungime_pachet = $form_data['lungime_pachet'];
            $nemoOrder->continut_pachet = $form_data['continut_pachet'];
            $nemoOrder->adresa_livrare_id = $form_data['deliveryAddressAWB'];
            $nemoOrder->fragil = $form_data['fragil'];
            $nemoOrder->created_at = $created_at;
            $nemoOrder->updated_at = $created_at;
            $nemoOrder->awb = $nemoResponse['data']['no'];
            $nemoOrder->status = $nemoResponse['data']['status'];
            $nemoOrder->hash = hash_hmac('ripemd160', $nemoResponse['data']['no'], $apiKey);
            $nemoOrder->open_on_delivery = isset($form_data['openOnDelivery']) ? $form_data['openOnDelivery'] : 0;
            $nemoOrder->save();

            // updatez awb-ul in baza de date la oferta pentru care am generat awb-ul
            $offer->awb_id = $nemoOrder->id;
            $offer->save();
            return ['success' => true, 'msg' => 'AWB-ul s-a generat cu succes!', 'awb' => $nemoOrder->awb, 'id' => $offer->id, 'client_id' => $nemoOrder->cont_id, 'hash' => $nemoOrder->hash, 'html_log' => \App\Http\Controllers\VoyagerOfferController::getHtmlLog($offer)];
          } catch(Exception $e){
            return ['success' => false, 'msg' => 'Eroare: '.$e->getMessage()];
          }
      }
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function priceAwb($data)
    {
        $response = (new self())->callApi(self::PRICE_AWB_ENDPOINT, $data);
        return json_decode($response);
    }

  // printez awb-ul pe baza unui numar de awb, a unui id de client nemo si a unui hash generat si salvat in baza de date in nemo_orders
    public function printAwbNemo($awb_no, $client_id, $hash){
      if ($client_id != null && $awb_no != null && $hash != null) {
        $nemoOrder = NemoOrder::where(['awb' => $awb_no, 'cont_id' => $client_id])->first();
        if($nemoOrder != null){
          $hash = $nemoOrder->hash;
          if ($nemoOrder->hash !== $hash) {
              die("Permission denied.");
          }
          $apiKey = $client_id == 1 ? env('NEMO_API_KEY_IASI') : env('NEMO_API_KEY_BERCENI');
          $awb = (new self())->printAwb($apiKey, $nemoOrder->awb);
          if(strpos($awb['response'], 'Shipment is canceled') !== false){
            return ("Expedierea AWB-ului ".$awb_no." a fost anulata");
          }
          header("Content-type:application/pdf");
          header("Content-Disposition:attachment;filename=awb-{$nemoOrder->awb}.pdf");
          die($awb['response']);
        }
      }
    }

    /**
     * @param $awbId
     * @return bool|string
     */
    public function printAwb($apiKey, $awbId)
    {
        return (new self())->callApi($apiKey, self::PRINT_AWB_ENDPOINT, array(
            "awbno" => $awbId
        ));
    }

    /**
     * @param $awbId
     * @return mixed
     */
    public function infoAwb($awbId)
    {
        $response = (new self())->callApi(self::INFO_AWB_ENDPOINT, array(
            "awbno" => $awbId
        ));
        return json_decode($response);
    }

    /**
     * @return mixed
     */
    public function getCityList()
    {
        $response = (new self())->callApi(self::CITY_LIST_ENDPOINT);
        return json_decode($response);
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function getServicesList($type = self::SERVICES_TYPE_MAIN)
    {
        $response = (new self())->callApi(self::GET_SERVICES_LIST . $type);
        return json_decode($response);
    }

      /**
     * @param string $awbNo
     * @param string $full
     * @return mixed
     */
    public static function getStatus($apiKey, $awbId)
    {
         return (new self())->callApi($apiKey, self::GET_STATUS, array(
            "awbno" => $awbId
        ));
    }

    /**
     * @param string $awbNo
     * @param string $full
     * @return mixed
     */
    public function getHistory($awbNo, $full = "false")
    {
        $endpoint = self::GET_HISTORY . "&awbno=$awbNo&full=$full";
        $response = (new self())->callApi($endpoint);
        return json_decode($response);
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $error;
    }

    /**
     * @param $endpoint
     * @param array $params
     * @return bool|mixed|string
     */
    private static function callApi($apiKey, $endpoint, $params = array())
    {
        $error = null;
        $curl = curl_init();
        $apiKeyQuery = "?api_key=" . $apiKey;
        if (strpos($endpoint, '?') !== false) {
            $apiKeyQuery = "&api_key=" . $apiKey;
        }

        curl_setopt($curl, CURLOPT_URL, env('NEMO_API_URL') . "/API/" . $endpoint . $apiKeyQuery);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);

        if (!empty($params)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        // Protocol error
        if ($err) {
            $error = "There was an error connecting to the API. Response Error: " . $err;
            error_log($err);
            return false;
        }

        // Error if http code not is 200
        if ($httpCode !== 200) {
            switch ($httpCode) {
                case 404:
                    $error = "The API URL seems to be incorrect.";
                    break;
                case 500:
                    $error = "There is a server issue at the API level, please try again later.";
                    break;
                default:
                    $error = "There was an error connecting to the API. Error code: " . $httpCode;
                    break;
            }

            error_log("API connect error, HTTP status: " . $httpCode);
        }

        // Bad login error
        if (strpos($response, 'BAD_LOGIN') !== false) {
            $response = json_decode($response);
            $error = $response->message;
        }
        if($error != null){
          return ['success' => false, 'error' => $error];
        }
        return ['success' => true, 'response' => $response];
    }
}
