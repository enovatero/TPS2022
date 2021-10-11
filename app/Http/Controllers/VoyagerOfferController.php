<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;

use App\Offer;
use App\Client;
use App\UserAddress;
use App\LegalEntity;
use App\Individual;

class VoyagerOfferController extends \TCG\Voyager\Http\Controllers\VoyagerBaseController
{
   use BreadRelationshipParser;
  
      /**
     * POST BRE(A)D - Store data.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
//       dd("da");
        $slug = $this->getSlug($request);            
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        // Check permission
        $this->authorize('add', app($dataType->model_name));
        $addrErrs = 0;
        if($request->input('client_id') == -1){
          
          $errMessages = [];
          $addresses = $request->input('address');
          $countries = $request->input('country');
          $states = $request->input('state');
          $cities = $request->input('city');
          if($addresses == null || !array_key_exists(0, $addresses)){
            $addrErrs++;
          }
          if($countries == null || !array_key_exists(0, $countries)){
            $addrErrs++;
          }
          if($states == null || !array_key_exists(0, $states)){
            $addrErrs++;
          }
          if($cities == null || !array_key_exists(0, $cities)){
            $addrErrs++;
          }
          if($addrErrs > 0){
            $errMessages['address'] = [0 => 'Va rugam sa verificati campurile Adresa, Tara, Judet, Oras!'];
          }
          $pers_type = $request->input('persoana_type');
          $cui = $request->input('cui');
          $name = $request->input('name');
          $reg_com = $request->input('reg_com');
          $banca = $request->input('banca');
          $iban = $request->input('iban');
          $cnp = $request->input('cnp');
          $email = $request->input('email');
          $phone = $request->input('phone');
          if($name == null){
            $errMessages['name'] = [0 => 'Va rugam sa completati numele!'];
          }
          if($email == null){
            $errMessages['email'] = [0 => 'Va rugam sa completati adresa de email!'];
          }
          if($phone == null){
            $errMessages['phone'] = [0 => 'Va rugam sa completati nr. de telefon!'];
          }
          if($pers_type == 'fizica'){
            if($cnp == null){
              $errMessages['cnp'] = [0 => 'Va rugam sa completati CNP-ul!'];
            }
          } else{
            if($cui == null){
              $errMessages['cui'] = [0 => 'Va rugam sa completati CUI-ul!'];
            }
            if($reg_com == null){
              $errMessages['reg_com'] = [0 => 'Va rugam sa completati Registrul comertului!'];
            }
            if($banca == null){
              $errMessages['banca'] = [0 => 'Va rugam sa completati Banca!'];
            }
            if($iban == null){
              $errMessages['iban'] = [0 => 'Va rugam sa completati IBAN-ul!'];
            }
          }
        }
        // Validate fields with ajax
//         $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $val = $this->validateBread($request->all(), $dataType->addRows);

        if ($val->fails() || $addrErrs > 0) {
          if(count($errMessages) > 0){
            $errMessages = array_merge($errMessages, $val->errors()->toArray());         
          } else{
            $errMessages = $val->errors()->toArray();
          }
//           dd(back()->withInput());
          return back()->withInput()->withErrors($errMessages);
        }
        
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        event(new BreadDataAdded($dataType, $data));
      
        $offer = Offer::find($data->id);
        $offer->status = 'noua';
        $offer->serie = $data->id.''.(new self())->generateRandomId(3);
        $offer->save();
      
        if($data->client_id == -1){
          
          $client = new Client;
          $client->name = $request->input('name');
          $client->email = $request->input('email');
          $client->phone = $request->input('phone');
          $client->type = $request->input('persoana_type');
          $currentDate = date('Y-m-d H:i:s');
          $client->created_at = $currentDate;
          $client->updated_at = $currentDate;
          $client->save();
          $user_id = $client->id;
          
          $offer->client_id = $user_id;
          $offer->save();

          // insert/update data into user_addresses table
          $addresses = $request->input('address');
          $countries = $request->input('country');
          $states = $request->input('state');
          $cities = $request->input('city');
          $ids = $request->input('ids');

          if(array_key_exists(0, $addresses)){
            $address = $addresses[0];
          }
          if(array_key_exists(0, $countries)){
            $itemCountry = $countries[0];
          }
          if($states != null && array_key_exists(0, $states)){
            $itemState = $states[0];
          }
          if($cities != null && array_key_exists(0, $cities)){
            $itemCity = $cities[0];
          }

          $editInsertAddress = new UserAddress;
          $editInsertAddress->address = $address;
          $editInsertAddress->user_id = $user_id;
          $editInsertAddress->country = $itemCountry;
          $editInsertAddress->state = $itemState;
          $editInsertAddress->city = $itemCity;
          $editInsertAddress->save();
  //         // insert/update data into individuals/legal_entities (fizica/juridica)
          if($request->input('type') == 'fizica'){
            $individual = new Individual;
            $individual->user_id = $user_id;
            $individual->cnp = $request->input('cnp');
            $individual->save();
          } else{
            $entity = new LegalEntity;
            $entity->user_id = $user_id;
            $entity->cui = $request->input('cui');
            $entity->reg_com = $request->input('reg_com');
            $entity->banca = $request->input('banca');
            $entity->iban = $request->input('iban');
            $entity->save();
          }
        }
      

        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
//                 $redirect = redirect()->route("voyager.{$dataType->slug}.index");
              $redirect = redirect("/admin/offers/{$data->id}/edit");
            } else {
                $redirect = redirect()->back();
            }

            return $redirect->with([
                'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
    }
  
    // POST BR(E)AD
    public function update(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Compatibility with Model binding.
        $id = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        $model = app($dataType->model_name);
        $query = $model->query();
        if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
            $query = $query->{$dataType->scope}();
        }
        if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query = $query->withTrashed();
        }

        $data = $query->findOrFail($id);

        // Check permission
        $this->authorize('edit', $data);

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();

        // Get fields with images to remove before updating and make a copy of $data
        $to_remove = $dataType->editRows->where('type', 'image')
            ->filter(function ($item, $key) use ($request) {
                return $request->hasFile($item->field);
            });
        $original_data = clone($data);
      
        $this->insertUpdateData($request, $slug, $dataType->editRows, $data);

        if($request->input('delivery_address_user') != null){
          $data->delivery_address_user = $request->input('delivery_address_user') == -2 ? null : $request->input('delivery_address_user');
          $data->save();
        }
      
        // Delete Images
        $this->deleteBreadImages($original_data, $to_remove);

        event(new BreadDataUpdated($dataType, $data));

        if (auth()->user()->can('browse', app($dataType->model_name))) {
            $redirect = redirect()->route("voyager.{$dataType->slug}.index");
        } else {
            $redirect = redirect()->back();
        }

        return $redirect->with([
            'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }  
    public static function generateRandomId($length = 5) {
      $characters = '0123456789';
      $charactersLength = strlen($characters);
      $randomString = '0';
      for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
      }
      if($randomString[0]== 0){
        $randomString[0] = "1";
      }
      return $randomString;
    }
}
