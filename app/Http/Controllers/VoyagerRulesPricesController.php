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

use App\RulesPrice;
use App\RulePricesFormula;
use App\Category;

class VoyagerRulesPricesController extends \TCG\Voyager\Http\Controllers\VoyagerBaseController
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
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        event(new BreadDataAdded($dataType, $data));
        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
//                 $redirect = redirect()->route("voyager.{$dataType->slug}.index");
                $redirect = redirect("/admin/rules-prices/{$data->id}/edit");
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
  
  public function saveRulePrice(Request $request){
    try{
      $formulas = [];
      $formulaArray = $request->input('formulaArray');
      $rule_id = $request->input('rule_id');
      
      $formulaForSave = new RulePricesFormula();
      $formulaForSave->rule_id = $rule_id;
      $formulaForSave->tip_obiect = $formulaArray['tip_obiect'];
      $formulaForSave->categorie = $formulaArray['categorie'];
      $formulaForSave->categorie_name = $formulaArray['categorie_name'];
      $formulaForSave->variabila = $formulaArray['variabila'];
      $formulaForSave->operator = $formulaArray['operator'];
      $formulaForSave->formula = $formulaArray['formula'];
      $formulaForSave->full_formula = $formulaArray['full_formula'];
      $formulaForSave->save();
      
      return ['success' => true, 'msg' => 'Formula a fost adaugata cu succes!', 'categorie' => $formulaForSave->categorie];
    } catch(\Exception $e){
      return ['success' => false, 'error' => 'S-a produs o eroare pe server iar datele nu au putut fi salvate!'];
    }
  }
    public function removeFormula(Request $request){
    try{
      $formula_id = $request->input('formula_id');
      $formula = RulePricesFormula::where('id', $formula_id)->first();
      $categoryForAdd = Category::find($formula->categorie);
      $formula->delete();
      return ['success' => true, 'msg' => 'Formula a fost stearsa cu succes!', 'categoryForAdd' => $categoryForAdd];
    } catch(\Exception $e){
      return ['success' => false, 'error' => 'S-a produs o eroare pe server iar datele nu au putut fi salvate!'];
    }
  }
  
}
