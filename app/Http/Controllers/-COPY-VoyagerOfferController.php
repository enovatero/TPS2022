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
use App\Product;
use App\Status;
use App\ProductParent;
use PDF;

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
        $offer->status = '1';
        $offer->serie = $data->id;
        $offer->distribuitor_id = $request->input('distribuitor_id');
        $offer->agent_id = Auth::user()->id;
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
          $data->total_general = $request->input('totalGeneral') != null ? number_format(floatval($request->input('totalGeneral')), 2, '.', '') : 0;
          $data->reducere = $request->input('reducere') != null ? number_format(floatval(abs($request->input('reducere'))), 2, '.', '') : 0;
          $data->total_final = $request->input('totalCalculatedPrice') != null ? number_format(floatval($request->input('totalCalculatedPrice')), 2, '.', '') : 0;
          $data->save();
        }
      
        // Delete Images
        $this->deleteBreadImages($original_data, $to_remove);

        event(new BreadDataUpdated($dataType, $data));

        if (auth()->user()->can('browse', app($dataType->model_name))) {
//             $redirect = redirect()->route("voyager.{$dataType->slug}.index");
            $redirect = redirect("admin/offers/".$data->id."/edit");
        } else {
            $redirect = redirect()->back();
        }

        return $redirect->with([
            'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }  
  
    public function create(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        $dataTypeContent = (strlen($dataType->model_name) != 0)
                            ? new $dataType->model_name()
                            : false;

        foreach ($dataType->addRows as $key => $row) {
            $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'add');

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'add', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }
        $userAddresses = null;
        $priceRules = null;
        $allProducts = null;
        $select_html_grids = null;
        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'userAddresses', 'priceRules', 'allProducts', 'select_html_grids'));
    }
  
    public function edit(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$query, 'findOrFail'], $id);
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }
      
        $userAddresses = \App\UserAddress::where('user_id', $dataTypeContent->client_id)->get();
        $offerType = \App\OfferType::find($dataTypeContent->type);
        $offerType->parents = $offerType->parents();
        $cursValutar = $dataTypeContent->curs_eur != null ? $dataTypeContent->curs_eur : ($offerType->exchange != null ? $offerType->exchange : \App\Http\Controllers\Admin\CursBNR::getExchangeRate("EUR"));
        $dataTypeContent->curs_eur = $cursValutar;
        $priceRules = \App\RulesPrice::get();
        $priceGridId = $dataTypeContent->price_grid_id != null ? $dataTypeContent->price_grid_id : -1;
        $selectedAddress = \App\UserAddress::find($dataTypeContent->delivery_address_user);
        if($selectedAddress != null){
          $selectedAddress->city_name = $selectedAddress->city_name();
          $selectedAddress->state_name = $selectedAddress->state_name();
          $selectedAddress->phone = $selectedAddress->delivery_phone != null ? $selectedAddress->delivery_phone : $selectedAddress->userData()->phone;
          $selectedAddress->name = $selectedAddress->delivery_contact != null ? $selectedAddress->delivery_contact : $selectedAddress->userData()->name;
        }
        if($userAddresses != null && count($userAddresses) > 0){
          foreach($userAddresses as &$addr){
            $addr->city_name = $addr->city_name();
            $addr->state_name = $addr->state_name();
            $addr->phone = $addr->delivery_phone != null ? $addr->delivery_phone : $addr->userData()->phone;
            $addr->name = $addr->delivery_contact != null ? $addr->delivery_contact : $addr->userData()->name;
          }
        }
      
        $attributesProds = [];
        $createdAttributes = [];
        if($offerType->parents && count($offerType->parents) > 0){
          $prodIds = [];
          foreach($offerType->parents as $parent){
            if($parent->products && count($parent->products) > 0){
              foreach($parent->products as &$product){
                array_push($prodIds, $product->id);
              }
            }
          }
          if(count($prodIds) > 0){
            $arrayOfAttrValues = [];
            $prodAttrs = \App\ProductAttribute::with('attrs')->whereIn('product_id', $prodIds)->get();
            $prodAttrs = $prodAttrs->toArray();
            foreach($prodAttrs as $key => &$attr){
              $attr['attrs'] = $attr['attrs'][0];
              unset($attr['attrs']['created_at']);
              unset($attr['attrs']['updated_at']);
              $attr['value'] = json_decode($attr['value'], true) && json_last_error() != 4 ? json_decode($attr['value'], true) : $attr['value'];
              $attr['attrs']['values'] = $attr['value'];
              if(!in_array($attr['attrs'], $createdAttributes)){
                array_push($createdAttributes, $attr['attrs']);
              }
            }
            $mergedAttributes = [];
            foreach($createdAttributes as $key => &$attr){
              $copyElement = $attr;
              unset($copyElement['values']);
              $mergedAttributes[$attr['id']] = $copyElement;
            }
            foreach($createdAttributes as $key => &$attr){
              if(!array_key_exists('values', $mergedAttributes[$attr['id']])){
                $mergedAttributes[$attr['id']]['values'] = [];
              }
              if(!in_array($attr['values'], $mergedAttributes[$attr['id']]['values'])){
                array_push($mergedAttributes[$attr['id']]['values'], $attr['values']);
              }
            }
            $createdAttributes = $mergedAttributes;
            function array_sort_by_column(&$arr, $col, $dir = SORT_DESC) {
                $sort_col = array();
                foreach ($arr as $key => $row) {
                    $sort_col[$key] = $row[$col];
                }

                array_multisort($sort_col, $dir, $arr);
            }

            array_sort_by_column($createdAttributes, 'type');

            $attributesProds = $prodAttrs;
          }
        }
        $offer = $dataTypeContent;
        $parents = $offerType->parents;
        $allRules = (new self())->getRulesPrices($priceRules);
        $allProducts = [];
      
        foreach($parents as $parent){
          foreach($parent->products as &$product){
            $product->rulesPrices = (new self())->getRulesPricesByProductCategory($allRules, $product->categoryId(), $product->price, $cursValutar);
            array_push($allProducts, $product->toArray());
          }
        }
      
        $select_html_grids = "<select name='price_grid_id' class='form-control'>";
        if($priceRules){
          foreach($priceRules as $price){
            if($price->id == $dataTypeContent->price_grid_id){
              $select_html_grids .= "<option value='".$price->id."' selected>[".$price->code."] - ".$price->title."</option>";
            } else{ 
              $select_html_grids .= "<option value='".$price->id."'>[".$price->code."] - ".$price->title."</option>";
            }
          }
        }
        $select_html_grids .= "</select>";
      
        return Voyager::view($view, compact(
          'dataType', 
          'dataTypeContent', 
          'isModelTranslatable', 
          'createdAttributes', 
          'userAddresses', 
          'offer',
          'selectedAddress',
          'offerType',
          'priceRules',
          'allProducts',
          'select_html_grids',
        ));
    }
  
    public static function getRulesPricesByProductCategory($allRules, $categoryId, $productPrice = null, $currency = null){
      $tva = floatVal(setting('admin.tva_products'))/100;
      $rulePricesFilteredByCategory = $allRules[$categoryId];
      
      foreach($rulePricesFilteredByCategory as &$item){
        $formula = str_replace("PI", $productPrice, $item['full_formula']);
        $price = eval('return '.$formula.';');
        $formatedPriceFormula = floatVal(number_format($price ,2,'.', ''));
        $item['price'] = number_format($formatedPriceFormula, 2, '.', '');
        if($currency == null){
          $currency = 1;
        }
        $priceWithCurrency = $productPrice*$currency;
        
        $priceWithTva = $priceWithCurrency+($priceWithCurrency*$tva);
        $item['currency_price'] = number_format(floatVal($formatedPriceFormula)*floatVal($currency), 2, '.', '');
        $item['eur_prod_price'] = number_format($productPrice, 2, '.', '');
        $item['ron_cu_tva'] = number_format($priceWithTva, 2, '.', '');
        $item['ron_fara_tva'] = number_format($productPrice*$currency, 2, '.', '');
        
        $item['product_price'] = number_format(floatVal($productPrice)*floatVal($currency), 2, '.', '');
        $item['product_price_tva'] = number_format($productPrice+$productPrice*$tva, 2, '.', '');
      }
      return $rulePricesFilteredByCategory;
    }
  
    public static function getRulesPrices($priceRules){
      foreach($priceRules as $rule){
        $formulas = json_decode($rule->formulas, true);
        foreach($formulas as $formula){
          $allRules[$formula['categorie']] = [];
        }
      }
      
      foreach($priceRules as &$rule){
        $rule->formulas = json_decode($rule->formulas, true);
        foreach($rule->formulas as $formula){
          $formArr = [
            'id' => $rule->id,
            'code' => $rule->code,
            'title' => $rule->title,
            'tip_obiect' => $formula['tip_obiect'],
            'categorie' => $formula['categorie'],
            'categorie_name' => $formula['categorie_name'],
            'variabila' => $formula['variabila'],
            'operator' => $formula['operator'],
            'formula' => $formula['formula'],
            'full_formula' => $formula['full_formula'],
          ];
          array_push($allRules[$formula['categorie']], $formArr);
        }
      }
      return $allRules;
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
  
    public function getPricesByProductAndCategory(Request $request){
      
      $product_id = $request->input("product_id");
      $product = Product::find($product_id);
      $category_id = $request->input("category_id");
      $cleanRulePrices = \App\RulesPrice::getFormulaByCategory($category_id);
      $cleanRulePrices = $cleanRulePrices->toArray();
      $rulePrices = \App\RulesPrice::getFormulasWithPricesByProduct($cleanRulePrices, $product->price, $request->input('currency'));
      
      return ['success' => true, 'rulePrices' => $rulePrices];
    }
  
    public function getPricesByProductAndCategoryOld(Request $request){
      $product_id = $request->input("product_id");
      $product = Product::find($product_id);
      $category_id = $request->input("category_id");
      $cleanRulePrices = \App\RulesPrice::getFormulaByCategory($category_id);
      $cleanRulePrices = $cleanRulePrices->toArray();
      $rulePrices = \App\RulesPrice::getFormulasWithPricesByProduct($cleanRulePrices, $product->price, $request->input('currency'));
      return ['success' => true, 'rulePrices' => $rulePrices];
  }
  
  public function ajaxSaveUpdateOffer(Request $request){
//     dd($request->all());
    $offer_id = $request->input('offer_id');
    if($offer_id != null){
      $offer = Offer::find($offer_id);
    } else{
      $offer = new Offer;
    }
    
    $attributes = $request->input('selectedAttribute') != null ? $request->input('selectedAttribute') : [];
    $parentIds = $request->input('parentIds');
    $offerQty = $request->input('offerQty');
    $selectedProducts = $request->input('selectedProducts');
    if($selectedProducts != null){
      $selectedProducts = array_map('intval', explode(',', $selectedProducts));
    }
    
    $attributesArray = [];
    if($offerQty && count($offerQty) > 0){
      foreach($offerQty as $key => $qty){
        $par = null;
        if(array_key_exists($key, $parentIds)){
          $par = $parentIds[$key];
        }
        if($qty != null){
          $attributesArray[] = [
            'parent' => $par,
            'qty' => $qty,
          ];
        }
      }
    }
    
    $offer->type = $request->input('type');
    $offer->offer_date = $request->input('offer_date');
    $offer->client_id = $request->input('client_id');
    $offer->distribuitor_id = $request->input('distribuitor_id');
    $offer->price_grid_id = $request->input('price_grid_id');
    $offer->curs_eur = $request->input('curs_eur');
    $offer->agent_id = Auth::user()->id;
    $offer->delivery_address_user = $request->input('delivery_address_user');
    $offer->delivery_date = $request->input('delivery_date');
    $offer->observations = $request->input('observations');
    $offer->created_at = $request->input('created_at');
    $offer->updated_at = $request->input('updated_at');
    $offer->status = $request->input('status');
    $offer->serie = $request->input('serie');
    $offer->attributes = count($attributes) > 0 ? json_encode($attributes) : null;
    $offer->prices = json_encode($attributesArray);
    $offer->total_general = $request->input('totalGeneral') != null ? number_format(floatval($request->input('totalGeneral')), 2, '.', '') : 0;
    $offer->reducere = $request->input('reducere') != null ? number_format(floatval(abs($request->input('reducere'))), 2, '.', '') : 0;
    $offer->total_final = $request->input('totalCalculatedPrice') != null ? number_format(floatval($request->input('totalCalculatedPrice')), 2, '.', '') : 0;
    $offer->transparent_band = $request->input('transparent_band') == 'on' ? 1 : 0;
    $offer->packing = $request->input('packing');
    $offer->delivery_details = $request->input('delivery_details');
    $offer->delivery_type = $request->input('delivery_type');
    $offer->selected_products = $selectedProducts;
    $offer->save();
    
    
    return ['success' => true, 'offer_id' => $offer->id];
  }
  
//   public function generatePDF(Request $request){
  public function generatePDF($offer_id){
    $offer = Offer::with(['distribuitor', 'client', 'delivery_address'])->find($offer_id);
    $dimension = 0;
    $boxes = 0;
    $totalQty = 0;
    if($offer != null){
      $offer->prices = json_decode($offer->prices, true);
      if($offer->prices && count($offer->prices) > 0){
        $newPrices = [];
        foreach($offer->prices as $item){
          $parent = ProductParent::find($item['parent']);
          array_push($newPrices, [
            'dimension' => $parent->dimension,
            'parent' => $item['parent'],
            'qty' => $item['qty'],
          ]);
          $dimension += $dimension != null && $dimension != 0 ? $dimension*$item['qty'] : $item['qty'];
          $totalQty += $item['qty'];
        }
        $boxes = intval(ceil($totalQty/25)); // rotunjire la urmatoarea valoare
        $offer->prices = $newPrices;
      }
      $offer->dimension = $dimension;
      $offer->boxes = $boxes;
      $pdf = PDF::loadView('vendor.pdfs.offer_pdf',['offer' => $offer]);
      return $pdf->download('Oferta_TPS'.$offer->serie.'_'.date('m-d-Y').'.pdf');
    }
    return ['success' => false];
  }
  
  public function generatePDFFisa($offer_id){
    $offer = Offer::with(['distribuitor', 'client', 'delivery_address'])->find($offer_id);
    $dimension = 0;
    $boxes = 0;
    $totalQty = 0;
    if($offer != null){
      $offer->prices = json_decode($offer->prices, true);
      if($offer->prices && count($offer->prices) > 0){
        $newPrices = [];
        foreach($offer->prices as $item){
          $parent = ProductParent::find($item['parent']);
          array_push($newPrices, [
            'dimension' => $parent->dimension,
            'parent' => $item['parent'],
            'qty' => $item['qty'],
          ]);
          $dimension += $dimension != null && $dimension != 0 ? $dimension*$item['qty'] : $item['qty'];
          $totalQty += $item['qty'];
        }
        $boxes = intval(ceil($totalQty/25)); // rotunjire la urmatoarea valoare
        $offer->prices = $newPrices;
      }
    }
    $offer->dimension = $dimension;
    $offer->boxes = $boxes;
    $pdf = PDF::loadView('vendor.pdfs.offer_pdf_order',['offer' => $offer]);
    return $pdf->stream('Fisa Comanda_TPS'.$offer->numar_comanda.'_'.date('m-d-Y').'.pdf');
  }
  
  public function retrieveOffersPerYearMonth(Request $request){
    $year = $request->input('year');
    $month = $request->input('month');
    $calendarOrders = Offer::with('status_name')->select('offer_date','serie', 'status')->whereRaw('YEAR(offer_date) = '.$year.' AND MONTH(offer_date) = '.$month)->get();
    if($calendarOrders && count($calendarOrders) > 0){
      foreach($calendarOrders as &$order){
        $order->day = \Carbon\Carbon::parse($order->offer_date)->format('d');
        $order->month = \Carbon\Carbon::parse($order->offer_date)->format('m');
        $order->year = \Carbon\Carbon::parse($order->offer_date)->format('Y');
        $order->status = $order->status_name != null ? $order->status_name->title : 'noua';
      }
    }
    return ['success' => true, 'calendarOrders' => $calendarOrders];
  }
  
  public function changeStatus(Request $request){
    if($request->input('order_id') == null){
      return ['success' => false, 'msg' => 'Te rugam sa selectezi o comanda pentru a schimba statusul!'];
    }
    if($request->input('status') == null){
      return ['success' => false, 'msg' => 'Te rugam sa selectezi un status!'];
    }
    try{
      $offer = Offer::find($request->input('order_id'));
      $status = Status::where('title', 'like', '%'.$request->input('status').'%')->first();
      $offer->status = $status != null ? $status->id : 1;
      $offer->save();
      return ['success' => true, 'msg' => 'Statusul a fost modificat cu succes!'];
    } catch(\Exception $e){
      return ['success' => false, 'msg' => 'Statusul nu a putut fi modificat!'];
    }
  }
  
  public function launchOrder(Request $request){
    if($request->input('order_id') == null){
      return ['success' => false, 'msg' => 'Te rugam sa selectezi o comanda pentru a lansa comanda!'];
    }
    try{
      $offer = Offer::find($request->input('order_id'));
      $status = Status::where('title', 'like' , '%productie%')->first();
      $offer->status = $status != null ? $status->id : 1;
      $nextOrderNumber = Offer::where('numar_comanda', '!=', null)->count() + 1;
      $offer->numar_comanda = $nextOrderNumber;
      $offer->save();
      return ['success' => true, 'msg' => 'Comanda a fost lansata cu succes!', 'status' => $status->title];
    } catch(\Exception $e){
      return ['success' => false, 'msg' => 'Statusul nu a putut fi modificat!'];
    }
  }
  
}
