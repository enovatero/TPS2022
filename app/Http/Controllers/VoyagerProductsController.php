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

use App\Category;
use App\Product;
use App\ProductParent;
use App\ProductAttribute;
use App\RulesPrice;
use App\OfferType;

class VoyagerProductsController extends \TCG\Voyager\Http\Controllers\VoyagerBaseController
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
//       dd($request->all());
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        event(new BreadDataAdded($dataType, $data));
      
        
       // get all attributes like 1_20cm where 1 is attributeId _ is delimitator and 20cm is selectedValue
        $attributeValues = $request->input('attributeValues');
        if($attributeValues != null){
          $attrWithValues = [];
          foreach($attributeValues as $key => $attr){
            $attribute = explode("_", $attr);
            $insertProductAttribute = new ProductAttribute;
            $insertProductAttribute->product_id = $data->id;
            $insertProductAttribute->parent_id = $data->parent_id;
            $insertProductAttribute->attribute_id = $attribute[0];
            if(array_key_exists(2, $attribute)){
              $colorHex = $attribute[1];
              $colorVal = $attribute[2];
              $insertProductAttribute->value = strpos($attribute[1], '#') !== false ? json_encode([$colorHex, $colorVal]) : $attribute[1];
            } else{
              if(strpos($attribute[1], '#') !== false){
                $insertProductAttribute->value = json_encode([$attribute[1], null]);
              } else{
                $insertProductAttribute->value = $attribute[1];
              }
            }
            $insertProductAttribute->save();
            $modifiedValueWithAttribute = [
              $attribute[0] => $attribute[1]
            ];
          }
        }
      
        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
              if($data->price != null && $data->parent_id != null){
                $redirect = redirect("/admin/products-complete");
              } else{
                $redirect = redirect("/admin/products-incomplete");
              }
  //             $redirect = redirect()->route("voyager.{$dataType->slug}.index");
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
      
       // get all attributes like 1_20cm where 1 is attributeId _ is delimitator and 20cm is selectedValue
        $attributeValues = $request->input('attributeValues');
        if($attributeValues != null){
          $attrWithValues = [];
          foreach($attributeValues as $key => $attr){
            $attribute = explode("_", $attr);
            $insertProductAttribute = ProductAttribute::where('product_id', $data->id)->where('attribute_id', $attribute[0])->first();
            if($insertProductAttribute == null){
              $insertProductAttribute = new ProductAttribute;
            }
            $insertProductAttribute->product_id = $data->id;
            $insertProductAttribute->parent_id = $data->parent_id;
            $insertProductAttribute->attribute_id = $attribute[0];
            if(array_key_exists(2, $attribute)){
              $colorHex = $attribute[1];
              $colorVal = $attribute[2];
              $insertProductAttribute->value = strpos($attribute[1], '#') !== false ? json_encode([$colorHex, $colorVal]) : $attribute[1];
            } else{
              if(strpos($attribute[1], '#') !== false){
                $insertProductAttribute->value = json_encode([$attribute[1], null]);
              } else{
                $insertProductAttribute->value = $attribute[1];
              }
            }
            $insertProductAttribute->save();
            $modifiedValueWithAttribute = [
              $attribute[0] => $attribute[1]
            ];
          }
        }

        // Delete Images
        $this->deleteBreadImages($original_data, $to_remove);

        event(new BreadDataUpdated($dataType, $data));

        if (auth()->user()->can('browse', app($dataType->model_name))) {
            if($data->price != null && $data->parent_id != null){
              $redirect = redirect("/admin/products-complete");
            } else{
              $redirect = redirect("/admin/products-incomplete");
            }
//             $redirect = redirect()->route("voyager.{$dataType->slug}.index");
        } else {
            $redirect = redirect()->back();
        }

        return $redirect->with([
            'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }
  
      public function destroy(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Init array of IDs
        $ids = [];
        if (empty($id)) {
            // Bulk delete, get IDs from POST
            $ids = explode(',', $request->ids);
        } else {
            // Single item delete, get ID from URL
            $ids[] = $id;
        }
        $prods = Product::whereIn('id', $ids)->get();
        $isComplete = true;
        if($prods && count($prods) > 0){
          $isComplete = $prods[0]->price != null && $prods[0]->parent_id != null ? true : false;
        }
        foreach ($ids as $id) {
            $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);

            // Check permission
            $this->authorize('delete', $data);

            $model = app($dataType->model_name);
            if (!($model && in_array(SoftDeletes::class, class_uses_recursive($model)))) {
                $this->cleanup($dataType, $data);
            }
        }
        $displayName = count($ids) > 1 ? $dataType->getTranslatedAttribute('display_name_plural') : $dataType->getTranslatedAttribute('display_name_singular');

        $res = $data->destroy($ids);
        $data = $res
            ? [
                'message'    => __('voyager::generic.successfully_deleted')." {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_deleting')." {$displayName}",
                'alert-type' => 'error',
            ];

        if ($res) {
            event(new BreadDataDeleted($dataType, $data));
        }
        if($isComplete){
          $redirect = redirect("/admin/products-complete")->with($data);
        } else{
          $redirect = redirect("/admin/products-incomplete")->with($data);
        }
//         return redirect()->route("voyager.{$dataType->slug}.index")->with($data);
        return $redirect;
    }
  
  // iau atributele articolului pe baza produsului din care face parte
  public static function getAttributesByParent(Request $request, $parent_id = null, $selectedAttr = null){
//     try{
      if(count($request->all()) > 0){
        $parent = ProductParent::find($request->input('parent_id'));
        $selectedAttributes = $request->input('selectedAttributes');
      } else{
        $parent = ProductParent::find($parent_id);
        $selectedAttributes = $selectedAttr;
      }
      $category = Category::find($parent->category_id);
      $category_id = $category->id;
      $selectedAttributes = $selectedAttributes != null ? json_decode($selectedAttributes, true) : null;
      $category = Category::with('attributes')->where('id', $category_id)->first();
      $html_attributes = '';
      // fac o filtrare prin atribute
      if($category && $category->attributes && count($category->attributes) > 0){
        foreach($category->attributes as $attribute){
          $foundedAttribute = null;
          $foundedAttributeValue = null;
          if($selectedAttributes != null){
            foreach($selectedAttributes as $key => $attr){
              if($attribute->id == $key){
                $foundedAttribute = $key;
                if($attribute->type == 1){
                  $foundedAttributeValue = $attr[0];
                } else{
                  $foundedAttributeValue = $attr;
                }
                break;
              }
            }
          }
          // creez selecturile pe baza atributelor selectate/gasite din categorie
          $html_attributes .= '<div class="form-group col-md-12 ">
            <label class="control-label" for="name">'.ucfirst($attribute->title).'</label>';
            if($attribute->type == 1){
              if($foundedAttribute != null && $foundedAttributeValue != null){
                $html_attributes .= '<select name="attributeValues[]" class="form-control selectColor"><option disabled>Selecteaza '.$attribute->title.'</option>';
              } else{
                $html_attributes .= '<select name="attributeValues[]" class="form-control selectColor"><option selected disabled>Selecteaza '.$attribute->title.'</option>';
              }
            } else{
              if($foundedAttribute != null && $foundedAttributeValue != null){
                $html_attributes .= '<select name="attributeValues[]" class="form-control retrievedAttribute"><option disabled>Selecteaza '.$attribute->title.'</option>';
              } else{
                $html_attributes .= '<select name="attributeValues[]" class="form-control retrievedAttribute"><option selected disabled>Selecteaza '.$attribute->title.'</option>';
              }
            }
          // populez select-urile cu valorile din atribute
          $values = $attribute->values != null ? json_decode($attribute->values, true) : [];
          $selected = false;
          if(count($values) > 0){
            foreach($values as $value){
              $checkValue = $value;
              if($attribute->type == 1){
                $foundedColor = array_key_first($value);
                $checkValue = $foundedColor;
              }
              if($foundedAttribute != null && $foundedAttributeValue != null && $attribute->id == $foundedAttribute && $checkValue == $foundedAttributeValue){
                $selected = true;
              } else{
                $selected = false;
              }
              if($attribute->type == 1){
                $foundedColor = array_key_first($value);
                $val = $value[$foundedColor];
                if($selected){
                  $html_attributes .= '<option value="'.$attribute->id.'_'.$foundedColor.'_'.$val.'" selected>'.$val.'</option>';
                } else{
                  $html_attributes .= '<option value="'.$attribute->id.'_'.$foundedColor.'_'.$val.'">'.$val.'</option>';
                }
              } else{
                if($selected){
                  $html_attributes .= '<option value="'.$attribute->id.'_'.$value.'" selected>'.$value.'</option>';
                } else{
                  if($attribute->title == 'Dimensiune sistem scurgere' && $value == "125/087"){
                    $html_attributes .= '<option value="'.$attribute->id.'_'.$value.'" selected>'.$value.'</option>';
                  } else{
                    $html_attributes .= '<option value="'.$attribute->id.'_'.$value.'">'.$value.'</option>';
                  }
                }
              }
            }
          }
          $html_attributes .= '</select></div>';
        }
      }
      return ['success' => true, 'html_attributes' => $html_attributes];
//     } catch(\Exception $e){
//       return ['success' => false, 'error' => 'S-a produs o eroare pe server iar datele nu au putut fi preluate!'];
//     }
  }
  // functie care executa php artisan winmentor:fetch pentru a lua produsele din winmentor prin api
  public function forceFetchProductsWinMentor(){
    $callResp = \Artisan::call('winmentor:fetch');
    if($callResp == 0){
      return ['success' => false, 'msg' => 'Produsele nu au fost preluate!'];
    }
    return ['success' => true, 'msg' => 'Produsele au fost preluate cu succes!'];
  }
  
  // filtrez produsele care au completate pretul si parent_id in produse complete
  public function productsComplete(Request $request){
            // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = 'products';

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];

        $searchNames = [];
        if ($dataType->server_side) {
            $searchNames = $dataType->browseRows->mapWithKeys(function ($row) {
                return [$row['field'] => $row->getTranslatedAttribute('display_name')];
            });
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            // preiau toate produsele care au inclusiv campurile pret si parinte selectate, deci sunt produse complete
            $query = $model::select($dataType->name.'.*')->where(function($query){
               $query->where('price', '!=', null);
               $query->orWhere('parent_id', '!=', null);
            });

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%'.$search->value.'%';

                $searchField = $dataType->name.'.'.$search->key;
                if ($row = $this->findSearchableRelationshipRow($dataType->rows->where('type', 'relationship'), $search->key)) {
                    $query->whereIn(
                        $searchField,
                        $row->details->model::where($row->details->label, $search_filter, $search_value)->pluck('id')->toArray()
                    );
                } else {
                    if ($dataType->browseRows->pluck('field')->contains($search->key)) {
                        $query->where($searchField, $search_filter, $search_value);
                    }
                }
            }
            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                        $dataType->name.'.*',
                        'joined.'.$row->details->label.' as '.$orderBy,
                    ])->leftJoin(
                        $row->details->table.' as joined',
                        $dataType->name.'.'.$row->details->column,
                        'joined.'.$row->details->key
                    );
                }

                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;
        
        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        // Define list of columns that can be sorted server side
        $sortableColumns = $this->getSortableColumns($dataType->browseRows);

        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }
        $dataType->display_name_plural = 'Articole';
        return Voyager::view($view, compact(
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortableColumns',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn'
        ));
  }
  // filtrez produsele care nu au completate pretul si parent_id in produse incomplete
    public function productsInComplete(Request $request){
//         \DB::enableQueryLog();
            // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = 'products';

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];

        $searchNames = [];
        if ($dataType->server_side) {
            $searchNames = $dataType->browseRows->mapWithKeys(function ($row) {
                return [$row['field'] => $row->getTranslatedAttribute('display_name')];
            });
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            // preiau toate produsele care au inclusiv campurile pret si parinte selectate, deci sunt produse complete
            $query = $model::select($dataType->name.'.*')->where(function($query){
               $query->where('price', null);
               $query->orWhere('parent_id', null);
            });

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }
            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%'.$search->value.'%';

                $searchField = $dataType->name.'.'.$search->key;
                if ($row = $this->findSearchableRelationshipRow($dataType->rows->where('type', 'relationship'), $search->key)) {
                    $query->whereIn(
                        $searchField,
                        $row->details->model::where($row->details->label, $search_filter, $search_value)->pluck('id')->toArray()
                    );
                } else {
                    if ($dataType->browseRows->pluck('field')->contains($search->key)) {
                        $query->where($searchField, $search_filter, $search_value);
                    }
                }
            }
            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                        $dataType->name.'.*',
                        'joined.'.$row->details->label.' as '.$orderBy,
                    ])->leftJoin(
                        $row->details->table.' as joined',
                        $dataType->name.'.'.$row->details->column,
                        'joined.'.$row->details->key
                    );
                }

                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        // Define list of columns that can be sorted server side
        $sortableColumns = $this->getSortableColumns($dataType->browseRows);

        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }
      
        $dataType->display_name_plural = 'Articole DRAFT';
//         dd(\DB::getQueryLog());

        return Voyager::view($view, compact(
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortableColumns',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn'
        ));
  }
  
  // nu mai folosesc functia momentan, dar o scot definitiv dupa ce termin cu toate JSON-urile din DB
  public static function getPricesByProductOffer($price, $category_id, $offer_type_id, $rule_id, $currency, $qty){
    $priceWithTva = 0;
    $totalPriceWithTva = 0;
    $rulePrice = RulesPrice::find($rule_id);
    $offerype = OfferType::find($offer_type_id);
    $tva = floatVal(setting('admin.tva_products'))/100;
    $formula = $rulePrice != null && $rulePrice['formulas'] ? json_decode($rulePrice['formulas'], true) : [];
    $foundedFormula = null;
    $currency = $currency == null ? : ($offerype && $offerype->exchange != null ? $offerype->exchange : \App\Http\Controllers\Admin\CursBNR::getExchangeRate("EUR"));
    if(count($formula) > 0){
      foreach($formula as $itm){
        if($itm['categorie'] == $category_id){
          $foundedFormula = $itm;
        }
      }
    }
    if($foundedFormula != null){
      $formula = str_replace("PI", $price, $foundedFormula);
      $price = eval('return '.$formula['full_formula'].';');
      $basePriceRon = floatVal(number_format($price*$currency ,2,'.', ''));
      $priceWithTva = $basePriceRon+($basePriceRon*$tva);
      $totalPriceWithTva = number_format($priceWithTva*$qty, 2, '.', '');
    }
    return ['priceWithTva' => $priceWithTva, 'totalPriceWithTva' => $totalPriceWithTva, 'eurPrice' => $price];
  }
}
