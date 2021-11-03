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
                $redirect = redirect()->route("voyager.{$dataType->slug}.index");
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
            $redirect = redirect()->route("voyager.{$dataType->slug}.index");
        } else {
            $redirect = redirect()->back();
        }

        return $redirect->with([
            'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }
  
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
                  $html_attributes .= '<option value="'.$attribute->id.'_'.$foundedColor.'_'.$val.'" selected></option>';
                } else{
                  $html_attributes .= '<option value="'.$attribute->id.'_'.$foundedColor.'_'.$val.'"></option>';
                }
              } else{
                if($selected){
                  $html_attributes .= '<option value="'.$attribute->id.'_'.$value.'" selected>'.$value.'</option>';
                } else{
                  $html_attributes .= '<option value="'.$attribute->id.'_'.$value.'">'.$value.'</option>';
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
}
