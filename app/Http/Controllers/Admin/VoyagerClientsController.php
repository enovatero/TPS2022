<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;

// use models for retrieving/inserting/updating DB
use App\UserAddress;
use App\LegalEntity;
use App\Individual;
use App\Client;
use Carbon\Carbon;

class VoyagerClientsController extends \TCG\Voyager\Http\Controllers\VoyagerBaseController
{
    use BreadRelationshipParser;

    //***************************************
    //               ____
    //              |  _ \
    //              | |_) |
    //              |  _ <
    //              | |_) |
    //              |____/
    //
    //      Browse our Data Type (B)READ
    //
    //****************************************

    public function index(Request $request)
    {
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = $this->getSlug($request);

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object)[
            'value' => $request->get('s'),
            'key' => $request->get('key'),
            'filter' => $request->get('filter')
        ];

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

            $query = $model::select($dataType->name . '.*');

            if ($dataType->scope && $dataType->scope != '' && method_exists(
                    $model,
                    'scope' . ucfirst($dataType->scope)
                )) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can(
                    'delete',
                    app($dataType->model_name)
                )) {
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
                $search_value = ($search->filter == 'equals') ? $search->value : '%' . $search->value . '%';

                $searchField = $dataType->name . '.' . $search->key;
                if ($row = $this->findSearchableRelationshipRow(
                    $dataType->rows->where('type', 'relationship'),
                    $search->key
                )) {
                    $query->whereIn(
                        $searchField,
                        $row->details->model::where($row->details->label, $search_filter, $search_value)->pluck(
                            'id'
                        )->toArray()
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
                        $dataType->name . '.*',
                        'joined.' . $row->details->label . ' as ' . $orderBy,
                    ])->leftJoin(
                        $row->details->table . ' as joined',
                        $dataType->name . '.' . $row->details->column,
                        'joined.' . $row->details->key
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

        return Voyager::view(
            $view,
            compact(
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
            )
        );
    }

    //***************************************
    //                _____
    //               |  __ \
    //               | |__) |
    //               |  _  /
    //               | | \ \
    //               |_|  \_\
    //
    //  Read an item of our Data Type B(R)EAD
    //
    //****************************************

    public function show(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $isSoftDeleted = false;

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists(
                    $model,
                    'scope' . ucfirst($dataType->scope)
                )) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$query, 'findOrFail'], $id);
            if ($dataTypeContent->deleted_at) {
                $isSoftDeleted = true;
            }
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        // Replace relationships' keys for labels and create READ links if a slug is provided.
        $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType, true);

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'read');

        // Check permission
        $this->authorize('read', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'read', $isModelTranslatable);

        $view = 'voyager::bread.read';

        if (view()->exists("voyager::$slug.read")) {
            $view = "voyager::$slug.read";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'isSoftDeleted'));
    }

    //***************************************
    //                ______
    //               |  ____|
    //               | |__
    //               |  __|
    //               | |____
    //               |______|
    //
    //  Edit an item of our Data Type BR(E)AD
    //
    //****************************************

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
            if ($dataType->scope && $dataType->scope != '' && method_exists(
                    $model,
                    'scope' . ucfirst($dataType->scope)
                )) {
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
        // dupa ce am adaugat clientul, ii completez datele daca e persoana fizica in Individuals iar daca e juridica in LegalEntity
        $individual = null;
        $legal_entity = null;
        $addresses = null;
        if ($dataTypeContent->type == 'fizica') {
            $addresses = UserAddress::where('user_id', $id)->get();
            $individual = Individual::where('user_id', $id)->first();
        } else {
            $addresses = UserAddress::where('user_id', $id)->get();
            $legal_entity = LegalEntity::where('user_id', $id)->first();
        }
        if ($addresses && count($addresses) > 0) {
            foreach ($addresses as &$address) {
                $address->state_name = $address->state_name();
                $address->city_name = $address->city_name();
            }
        }
        return Voyager::view(
            $view,
            compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'addresses', 'individual', 'legal_entity')
        );
    }

    // POST BR(E)AD
    public function update(Request $request, $id)
    {
//       dd($request->all());
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Compatibility with Model binding.
        $id = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        $model = app($dataType->model_name);
        $query = $model->query();
        if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
            $query = $query->{$dataType->scope}();
        }
        if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query = $query->withTrashed();
        }

        $data = $query->findOrFail($id);

        // Check permission
        $this->authorize('edit', $data);

        // pot adauga mai multe adrese pentru un client
        $errMessages = [];
        $addressesCounter = $request->input('addressesCounter');
        if ($addressesCounter != null) {
            $addresses = $request->input('address');
            $countries = $request->input('country');
            $states = $request->input('state');
            $cities = $request->input('city');
            $wme_name = $request->input('wme_name');
            $ids = $request->input('ids');
            $addrErrs = 0;
            // verific daca am mai multe adrese adaugate si trec prin fiecare, sa vad ce campuri nu a completat
            for ($key = 0; $key < $addressesCounter; $key++) {
                if ($addresses == null || !array_key_exists($key, $addresses)) {
                    $addrErrs++;
                }
                if ($countries == null || !array_key_exists($key, $countries)) {
                    $addrErrs++;
                }
                if ($states == null || !array_key_exists($key, $states)) {
                    $addrErrs++;
                }
                if ($cities == null || !array_key_exists($key, $cities)) {
                    $addrErrs++;
                }
                if ($wme_name == null || !array_key_exists($key, $wme_name)) {
                    $addrErrs++;
                }
            }
            if ($addrErrs > 0) {
                $errMessages['address'] = [0 => 'Pentru fiecare adresa adaugata, va rugam sa verificati campurile Adresa, Tara, Judet, Oras!'];
            }
        }
        // verific daca iban-ul este corect
        if ($request->iban != null) {
            if (!(new self())->checkIBAN($request->iban)) {
                $addrErrs++;
                $errMessages['iban'] = [0 => 'Te rugam sa introduci un iban valid!'];
            }
        }
        // verific daca CNP-ul este corect
        if ($request->cnp != null) {
            if (!(new self())->validCNP($request->cnp)) {
                $addrErrs++;
                $errMessages['cnp'] = [0 => 'Te rugam sa introduci un CNP valid!'];
            }
        }

        // verific daca numarul de telefon respecta formatul 0722222222
//        if(!preg_match('/^[0-9]{15}+$/', $request->input('phone'))){
//          $errMessages['phone'] = [0 => 'Numarul de telefon nu respecta formatul corect! Ex. 0712345678'];
//          $addrErrs++;
//        }
        $checkPhoneNumber = $this->validatePhoneNumber($request);
        if ($checkPhoneNumber) {
            $errMessages['phone'] = [0 => $checkPhoneNumber];
            $addrErrs++;
        }


        // Validate fields with ajax
//         $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();
        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id);

        if ($val->fails() || $addrErrs > 0) {
            if (count($errMessages) > 0) {
                $errMessages = array_merge($errMessages, $val->errors()->toArray());
            } else {
                $errMessages = $val->errors()->toArray();
            }
//           dd(back()->withInput());
            return back()->withInput()->withErrors($errMessages);
        }

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

        $user_id = $data->id;
        // insert/update data into user_addresses table
        if ($addressesCounter != null) {
            $addresses = $request->input('address');
            $countries = $request->input('country');
            $states = $request->input('state');
            $cities = $request->input('city');
            $cities = $request->input('city');
            $wme_name = $request->input('wme_name');
            $ids = $request->input('ids');

            for ($key = 0; $key < $addressesCounter; $key++) {
                if (array_key_exists($key, $addresses)) {
                    $address = $addresses[$key];
                }
                if (array_key_exists($key, $countries)) {
                    $itemCountry = $countries[$key];
                }
                if ($states != null && array_key_exists($key, $states)) {
                    $itemState = $states[$key];
                }
                if ($cities != null && array_key_exists($key, $cities)) {
                    $itemCity = $cities[$key];
                }
                if ($wme_name != null && array_key_exists($key, $wme_name)) {
                    $itemWmeName = $wme_name[$key];
                }
                if ($ids != null && array_key_exists($key, $ids)) {
                    $itemId = $ids[$key];
                    $editInsertAddress = UserAddress::find($itemId);
                } else {
                    $editInsertAddress = new UserAddress;
                }

                $editInsertAddress->address = $address;
                $editInsertAddress->user_id = $user_id;
                $editInsertAddress->country = $itemCountry;
                $editInsertAddress->state = $itemState;
                $editInsertAddress->wme_name = $itemWmeName;
                $editInsertAddress->city = $itemCity;
                $editInsertAddress->save();
            }
        }
//         // insert/update data into individuals/legal_entities (fizica/juridica)
        if ($request->input('type') == 'fizica' && $request->input('cnp') != null) {
            // check to see if in legal_entities has values for this entry
            if ($request->input('juridica_id') != null) {
                $entity = LegalEntity::find($request->input('juridica_id'));
                $entity->delete();
            }
            $individual = Individual::find($id);
            if ($individual == null) {
                $individual = new Individual;
            }
            $individual->user_id = $user_id;
            $individual->cnp = $request->input('cnp');
            $individual->save();
        } else {
            // check to see if in individuals has values for this entry
            if ($request->input('fizica_id') != null) {
                $individual = Individual::find($request->input('fizica_id'));
                $individual->delete();
            }
            $entity = LegalEntity::find($id);
            if ($entity == null) {
                $entity = new LegalEntity;
            }
            $entity->user_id = $user_id;
            $entity->cui = $request->input('cui');
            $entity->reg_com = $request->input('reg_com');
            $entity->banca = $request->input('banca');
            $entity->iban = $request->input('iban');
            $entity->save();
        }

        if (auth()->user()->can('browse', app($dataType->model_name))) {
            $redirect = redirect()->route("voyager.{$dataType->slug}.index");
        } else {
            $redirect = redirect()->back();
        }

        return $redirect->with([
            'message' => __(
                    'voyager::generic.successfully_updated'
                ) . " {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }


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

        // aceleasi verificari ca la update
        $errMessages = [];
        $addressesCounter = $request->input('addressesCounter');
        if ($addressesCounter != null) {
            $addresses = $request->input('address');
            $countries = $request->input('country');
            $states = $request->input('state');
            $cities = $request->input('city');
            $wme_name = $request->input('wme_name');
            $ids = $request->input('ids');
            $addrErrs = 0;
            for ($key = 0; $key < $addressesCounter; $key++) {
                if ($addresses == null || !array_key_exists($key, $addresses)) {
                    $addrErrs++;
                }
                if ($countries == null || !array_key_exists($key, $countries)) {
                    $addrErrs++;
                }
                if ($states == null || !array_key_exists($key, $states)) {
                    $addrErrs++;
                }
                if ($cities == null || !array_key_exists($key, $cities)) {
                    $addrErrs++;
                }
                if ($wme_name == null || !array_key_exists($key, $wme_name)) {
                    $addrErrs++;
                }
            }
            if ($addrErrs > 0) {
                $errMessages['address'] = [0 => 'Pentru fiecare adresa adaugata, va rugam sa verificati campurile Adresa, Tara, Judet, Oras, Denumire WME!'];
            }
        }
        if ($request->iban != null) {
            if (!(new self())->checkIBAN($request->iban)) {
                $addrErrs++;
                $errMessages['iban'] = [0 => 'Te rugam sa introduci un iban valid!'];
            }
        }
//        if (!preg_match('/^[0-9]{15}+$/', $request->input('phone'))) {
//            $errMessages['phone'] = [0 => 'Numarul de telefon nu respecta formatul corect! Ex. 0712345678'];
//            $addrErrs++;
//        }

        $checkPhoneNumber = $this->validatePhoneNumber($request);
        if ($checkPhoneNumber) {
            $errMessages['phone'] = [0 => $checkPhoneNumber];
            $addrErrs++;
        }

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows);
        if ($val->fails() || $addrErrs > 0) {
            if (count($errMessages) > 0) {
                $errMessages = array_merge($errMessages, $val->errors()->toArray());
            } else {
                $errMessages = $val->errors()->toArray();
            }
//           dd(back()->withInput());
            return back()->withInput()->withErrors($errMessages);
        }
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        $user_id = $data->id;
        // insert/update data into user_addresses table
        if ($addressesCounter != null) {
            $addresses = $request->input('address');
            $countries = $request->input('country');
            $states = $request->input('state');
            $cities = $request->input('city');
            $wme_name = $request->input('wme_name');
            $ids = $request->input('ids');

            for ($key = 0; $key < $addressesCounter; $key++) {
                if (array_key_exists($key, $addresses)) {
                    $address = $addresses[$key];
                }
                if (array_key_exists($key, $countries)) {
                    $itemCountry = $countries[$key];
                }
                if ($states != null && array_key_exists($key, $states)) {
                    $itemState = $states[$key];
                }
                if ($cities != null && array_key_exists($key, $cities)) {
                    $itemCity = $cities[$key];
                }
                if ($wme_name != null && array_key_exists($key, $wme_name)) {
                    $itemWmeName = $wme_name[$key];
                }
                if ($ids != null && array_key_exists($key, $ids)) {
                    $itemId = $ids[$key];
                    $editInsertAddress = UserAddress::find($itemId);
                } else {
                    $editInsertAddress = new UserAddress;
                }

                $editInsertAddress->address = $address;
                $editInsertAddress->user_id = $user_id;
                $editInsertAddress->country = $itemCountry;
                $editInsertAddress->state = $itemState;
                $editInsertAddress->city = $itemCity;
                $editInsertAddress->wme_name = $itemWmeName;
                $editInsertAddress->save();
            }
        }
        if ($request->input('type') == 'fizica' && $request->input('cnp') != null) {
            $individual = new Individual;
            $individual->user_id = $user_id;
            $individual->cnp = $request->input('cnp');
            $individual->save();
        }
        if ($request->input('type') == 'juridica') {
            $entity = new LegalEntity;
            $entity->user_id = $user_id;
            $entity->cui = $request->input('cui');
            $entity->reg_com = $request->input('reg_com');
            $entity->banca = $request->input('banca');
            $entity->iban = $request->input('iban');
            $entity->save();
        }
//         // insert/update data into individuals/legal_entities (fizica/juridica)
//         if($request->input('type') == 'fizica'){
//           // check to see if in legal_entities has values for this entry
//           $entity = LegalEntity::find($request->input('juridica_id'));
//           $entity->delete();
//           $individual = new Individual;
//           $individual->user_id = $user_id;
//           $individual->cnp = $request->input('cnp');
//           $individual->save();
//         } else{
//           // check to see if in individuals has values for this entry
//           $individual = Individual::find($request->input('fizica_id'));
//           $individual->delete();
//           $entity = new LegalEntity;
//           $entity->user_id = $user_id;
//           $entity->cui = $request->input('cui');
//           $entity->reg_com = $request->input('reg_com');
//           $entity->banca = $request->input('banca');
//           $entity->iban = $request->input('iban');
//           $entity->save();
//         }
        event(new BreadDataAdded($dataType, $data));

        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
                $redirect = redirect()->route("voyager.{$dataType->slug}.index");
            } else {
                $redirect = redirect()->back();
            }

            return $redirect->with([
                'message' => __(
                        'voyager::generic.successfully_added_new'
                    ) . " {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
    }

    //***************************************
    //                _____
    //               |  __ \
    //               | |  | |
    //               | |  | |
    //               | |__| |
    //               |_____/
    //
    //         Delete an item BREA(D)
    //
    //****************************************

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
        foreach ($ids as $id) {
            $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);

            // Check permission
            $this->authorize('delete', $data);

            $model = app($dataType->model_name);
            if (!($model && in_array(SoftDeletes::class, class_uses_recursive($model)))) {
                $this->cleanup($dataType, $data);
            }
        }

        $displayName = count($ids) > 1 ? $dataType->getTranslatedAttribute(
            'display_name_plural'
        ) : $dataType->getTranslatedAttribute('display_name_singular');

        $res = $data->destroy($ids);
        $data = $res
            ? [
                'message' => __('voyager::generic.successfully_deleted') . " {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message' => __('voyager::generic.error_deleting') . " {$displayName}",
                'alert-type' => 'error',
            ];

        if ($res) {
            event(new BreadDataDeleted($dataType, $data));
        }

        return redirect()->route("voyager.{$dataType->slug}.index")->with($data);
    }

//   Check IBAN FUNCTION
    public static function checkIBAN($iban)
    {
        $iban = strtolower(str_replace(' ', '', $iban));
        $Countries = array(
            'al' => 28,
            'ad' => 24,
            'at' => 20,
            'az' => 28,
            'bh' => 22,
            'be' => 16,
            'ba' => 20,
            'br' => 29,
            'bg' => 22,
            'cr' => 21,
            'hr' => 21,
            'cy' => 28,
            'cz' => 24,
            'dk' => 18,
            'do' => 28,
            'ee' => 20,
            'fo' => 18,
            'fi' => 18,
            'fr' => 27,
            'ge' => 22,
            'de' => 22,
            'gi' => 23,
            'gr' => 27,
            'gl' => 18,
            'gt' => 28,
            'hu' => 28,
            'is' => 26,
            'ie' => 22,
            'il' => 23,
            'it' => 27,
            'jo' => 30,
            'kz' => 20,
            'kw' => 30,
            'lv' => 21,
            'lb' => 28,
            'li' => 21,
            'lt' => 20,
            'lu' => 20,
            'mk' => 19,
            'mt' => 31,
            'mr' => 27,
            'mu' => 30,
            'mc' => 27,
            'md' => 24,
            'me' => 22,
            'nl' => 18,
            'no' => 15,
            'pk' => 24,
            'ps' => 29,
            'pl' => 28,
            'pt' => 25,
            'qa' => 29,
            'ro' => 24,
            'sm' => 27,
            'sa' => 24,
            'rs' => 22,
            'sk' => 24,
            'si' => 19,
            'es' => 24,
            'se' => 24,
            'ch' => 21,
            'tn' => 24,
            'tr' => 26,
            'ae' => 23,
            'gb' => 22,
            'vg' => 24
        );
        $Chars = array(
            'a' => 10,
            'b' => 11,
            'c' => 12,
            'd' => 13,
            'e' => 14,
            'f' => 15,
            'g' => 16,
            'h' => 17,
            'i' => 18,
            'j' => 19,
            'k' => 20,
            'l' => 21,
            'm' => 22,
            'n' => 23,
            'o' => 24,
            'p' => 25,
            'q' => 26,
            'r' => 27,
            's' => 28,
            't' => 29,
            'u' => 30,
            'v' => 31,
            'w' => 32,
            'x' => 33,
            'y' => 34,
            'z' => 35
        );

        if (array_key_exists(substr($iban, 0, 2), $Countries) && strlen($iban) == $Countries[substr($iban, 0, 2)]) {
            $MovedChar = substr($iban, 4) . substr($iban, 0, 4);
            $MovedCharArray = str_split($MovedChar);
            $NewString = "";

            foreach ($MovedCharArray as $key => $value) {
                if (!is_numeric($MovedCharArray[$key])) {
                    $MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
                }
                $NewString .= $MovedCharArray[$key];
            }

            if (bcmod($NewString, '97') == 1) {
                return true;
            }
        }
        return false;
    }

    // Check CNP function
    public static function validCNP($p_cnp)
    {
        // CNP must have 13 characters
        if (strlen($p_cnp) != 13) {
            return false;
        }
        $cnp = str_split($p_cnp);
        unset($p_cnp);
        $hashTable = array(2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9);
        $hashResult = 0;
        // All characters must be numeric
        for ($i = 0; $i < 13; $i++) {
            if (!is_numeric($cnp[$i])) {
                return false;
            }
            $cnp[$i] = (int)$cnp[$i];
            if ($i < 12) {
                $hashResult += (int)$cnp[$i] * (int)$hashTable[$i];
            }
        }
        unset($hashTable, $i);
        $hashResult = $hashResult % 11;
        if ($hashResult == 10) {
            $hashResult = 1;
        }
        // Check Year
        $year = ($cnp[1] * 10) + $cnp[2];
        switch ($cnp[0]) {
            case 1  :
            case 2 :
                {
                    $year += 1900;
                }
                break; // cetateni romani nascuti intre 1 ian 1900 si 31 dec 1999
            case 3  :
            case 4 :
                {
                    $year += 1800;
                }
                break; // cetateni romani nascuti intre 1 ian 1800 si 31 dec 1899
            case 5  :
            case 6 :
                {
                    $year += 2000;
                }
                break; // cetateni romani nascuti intre 1 ian 2000 si 31 dec 2099
            case 7  :
            case 8 :
            case 9 :
                {                // rezidenti si Cetateni Straini
                    $year += 2000;
                    if ($year > (int)date('Y') - 14) {
                        $year -= 100;
                    }
                }
                break;
            default :
                {
                    return false;
                }
                break;
        }
        return ($year > 1800 && $year < 2099 && $cnp[12] == $hashResult);
    }

    public function syncClientToMentor(Request $request)
    {
        if ($request->input('client_id') == null) {
            return [
                'success' => false,
                'msg' => 'Trebuie sa selectezi un client pentru a-l sincroniza cu Mentor!',
                'warning' => false
            ];
        }
        return (new self())->syncClient($request->input('client_id'));
    }

    public static function syncClient($client_id)
    {
        $host = config('winmentor.host');
        $port = config('winmentor.port');
        $waitTimeoutInSeconds = 3;
        $winMentorServer = false;
        try {
            if ($fp = fsockopen($host, $port, $errCode, $errStr, $waitTimeoutInSeconds)) {
                $winMentorServer = true;
            }
            fclose($fp);
        } catch (\Exception $e) {
        }

        $url = "http://" . config('winmentor.host') . ":" . config(
                'winmentor.port'
            ) . "/datasnap/rest/TServerMethods/InfoPartener//";
        $client = Client::find($client_id);
        if ($client->sync_done == 1) {
            return ['success' => false, 'msg' => 'Clientul a fost deja sincronizat cu WinMentor!', 'warning' => true];
        }
        $userAddresses = $client->userAddress;
        $cui = '';
        $regCom = '';
        $codPartener = '';
        $persoanaFizica = 'DA';
        $usrAddresses = UserAddress::where('user_id', $client->id)->get();
        $usrAddressList = [];
        if ($usrAddresses && count($usrAddresses) == 1) {
            $usrAddress = $usrAddresses[0];
            array_push($usrAddressList, [
                'Denumire' => $usrAddress->wme_name ?? "SEDIU-" . $usrAddress->id,
                'Localitate' => array_key_exists($usrAddress->city_name(), config('winmentor.cities')) ? config(
                    'winmentor.cities'
                )[$usrAddress->city_name()] : $usrAddress->city_name(),
                'TipSediu' => 'SFL',
                'Strada' => $usrAddress->address,
                'Numar' => '',
                'Bloc' => '',
                'Etaj' => '',
                'Apartament' => '',
                'Judet' => array_key_exists($usrAddress->state_name(), config('winmentor.states')) ? config(
                    'winmentor.states'
                )[$usrAddress->state_name()] : $usrAddress->state_name(),
                'Tara' => $usrAddress->country,
                'Telefon' => $usrAddress->delivery_phone != null ? $usrAddress->delivery_phone : $client->phone,
                'eMail' => $client->email
            ]);
        } elseif ($usrAddresses && count($usrAddresses) > 0) {
            foreach ($usrAddresses as $usrAddress) {
                if ($usrAddress->wme_name == 'SEDIU FIRMA') {
                    array_push($usrAddressList, [
                        'Denumire' => $usrAddress->wme_name ?? "SEDIU-" . $usrAddress->id,
                        'Localitate' => array_key_exists($usrAddress->city_name(), config('winmentor.cities')) ? config(
                            'winmentor.cities'
                        )[$usrAddress->city_name()] : $usrAddress->city_name(),
                        'TipSediu' => 'SFL',
                        'Strada' => $usrAddress->address,
                        'Numar' => '',
                        'Bloc' => '',
                        'Etaj' => '',
                        'Apartament' => '',
                        'Judet' => array_key_exists($usrAddress->state_name(), config('winmentor.states')) ? config(
                            'winmentor.states'
                        )[$usrAddress->state_name()] : $usrAddress->state_name(),
                        'Tara' => $usrAddress->country,
                        'Telefon' => $usrAddress->delivery_phone != null ? $usrAddress->delivery_phone : $client->phone,
                        'eMail' => $client->email
                    ]);
                }
            }
            foreach ($usrAddresses as $usrAddress) {
                if ($usrAddress->wme_name != 'SEDIU FIRMA') {
                    array_push($usrAddressList, [
                        'Denumire' => $usrAddress->wme_name ?? "SEDIU-" . $usrAddress->id,
                        'Localitate' => array_key_exists($usrAddress->city_name(), config('winmentor.cities')) ? config(
                            'winmentor.cities'
                        )[$usrAddress->city_name()] : $usrAddress->city_name(),
                        'TipSediu' => 'FL',
                        'Strada' => $usrAddress->address,
                        'Numar' => '',
                        'Bloc' => '',
                        'Etaj' => '',
                        'Apartament' => '',
                        'Judet' => array_key_exists($usrAddress->state_name(), config('winmentor.states')) ? config(
                            'winmentor.states'
                        )[$usrAddress->state_name()] : $usrAddress->state_name(),
                        'Tara' => $usrAddress->country,
                        'Telefon' => $usrAddress->delivery_phone != null ? $usrAddress->delivery_phone : $client->phone,
                        'eMail' => $client->email
                    ]);
                }
            }
        }
        if ($client->type == 'fizica') {
            $individual = Individual::where('user_id', $client->id)->first();
            $cui = '';
            $codPartener = "PF-" . $client->id;
        } else {
            $legalEntity = LegalEntity::where('user_id', $client->id)->first();
            $cui = $legalEntity ? $legalEntity->cui : '';
            $regCom = $legalEntity ? $legalEntity->reg_com : '';
            $persoanaFizica = 'NU';
            if ($usrAddress->country == 'RO') {
                $diff = -1;
                if ($legalEntity) {
                    $updatedDate = Carbon::parse($legalEntity->updated_at);
                    $now = Carbon::now();
                    $diff = $updatedDate->diffInDays($now);
                }
                // am diferenta de la ultimul update mai mare de 30 de zile, iau datele de la anaf si le modific in baza de date pentru firma selectata
                if ($diff >= 30) {
                    $anaf = new \Itrack\Anaf\Client();
                    $dataVerificare = date("Y-m-d");
                    $anaf->addCif($cui, $dataVerificare);
                    $company = $anaf->first();
                    if ($company->getName() != null && $company->getName() != "") {
                        $legalEntity->cui = $company->getCIF();
                        $legalEntity->reg_com = $company->getRegCom();
                        $legalEntity->iban = $company->getTVA()->getTVASplitIBAN();
                        $legalEntity->save();
                        $cui = $legalEntity->cui;
                    }
                }
                $codPartener = "PJ-RO-" . $cui;
            } else {
                $codPartener = 'PJ-' . $usrAddress->country . '-' . $cui;
            }
        }
        $wmeBankData = [];
        if ($client->type != 'fizica' && $legalEntity) {
            $wmeBankData = [
                [
                    'NumarCont' => $legalEntity->iban,
                    'Sucursala' => $legalEntity->banca,
                ]
            ];
        }
        $postData = [
            'TipOperatie' => 'A',
            'CUI' => $cui,
            'CodExtern' => $codPartener, // il generez ca pe TPS vechi
            'CodIntern' => '',
            'RegCom' => $regCom == null ? '' : $regCom,
            'Nume' => $client->name,
            'PersoanaFizica' => $persoanaFizica,
            'Observatii' => 'Client sincronizat din TPS, manual',
            'Sedii' => $usrAddressList,
            'ConturiBancare' => $wmeBankData,
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        if ($winMentorServer) {
            $result = curl_exec($ch);
            $result = json_decode($result, true);
        } else {
            curl_close($ch);
            return ['success' => false, 'msg' => 'Nu s-a putut conecta la serverul WinMentor!', 'warning' => false];
        }
        curl_close($ch);
        if (array_key_exists('Error', $result) && $result['Error'] == "ok" && $winMentorServer) {
            $client->sync_done = 1;
            $client->mentor_partener_code = $codPartener;
            $client->save();
            return ['success' => true, 'msg' => 'Clientul a fost sincronizat cu succes!', 'client' => $client];
        } else {
            return [
                'success' => false,
                'msg' => array_key_exists('error', $result) ? $result['error'] : $result['Error'],
                'warning' => false
            ];
        }
    }

    public function validatePhoneNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|min:10|max:15'
        ], [
            'required' => 'Introduceti un numar de telefon',
            'min' => 'Numarul de telefon trebuie sa aiba minim 10 cifre',
            'max' => 'Numarul de telefon trebuie sa aiba maxim 15 cifre'
        ]);
        $errors = $validator->errors();
        return $errors->first('phone');
    }
}
