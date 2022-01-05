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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use App\Mail\Mentions;
use Carbon\Carbon;

use App\Offer;
use App\Client;
use App\UserAddress;
use App\Models\User;
use App\LegalEntity;
use App\Individual;
use App\Product;
use App\Status;
use App\ProductParent;
use App\RulePricesFormula;
use App\OfferProduct;
use App\OfferPrice;
use App\ProductAttribute;
use App\OfferType;
use App\OfferEvent;
use App\OrderWme;
use App\OfferSerial;
use App\OfferAttribute;
use App\OffertypePreselectedColor;
use App\Attribute;
use PDF;
use GuzzleHttp\Client as GuzzleClient;

class VoyagerOfferController extends \TCG\Voyager\Http\Controllers\VoyagerBaseController
{
   use BreadRelationshipParser;

    // listez ofertele (atat comenzi cat si oferte)
    public function list_offers(Request $request)
    {
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = 'offers';

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
            $query = $model::select($dataType->name.'.*');

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

        $dataType->display_name_plural = 'Lista oferte';

        $is_order_page = false;

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
            'showCheckboxColumn',
            'is_order_page'
        ));
    }

    // listez comenzile (au numar_comanda != null)
    // controller/view custom refacut complet
    public function list_orders_custom(Request $request)
    {
        $user = Auth::user();
        $title = 'Lista comenzi';
        $model = Offer::class;
        $slug = 'offers';
        $this->authorize('browse', app($model));

        // get allowed columns
        $columns = [
            // [
            //     'key' => // folosit in view, si in migratia care adauga permisiuni
            //     'order_by' => // pe ce coloana din db se face order by (daca e null nu se face orderby)
            //     'label' => // textul afisat in capul tabelului
            // ],
            [
                'key' => 'nr_com',
                'order_by' => 'serie',
                'label' => 'Nr Comanda',
                'width' => '113px',
            ],
            [
                'key' => 'agent',
                'order_by' => null,
                'label' => 'Agent',
                'width' => '110px',
            ],
            [
                'key' => 'tip_comanda',
                'order_by' => 'type',
                'label' => 'Tip Comanda',
                'width' => '110px',
            ],
            [
                'key' => 'client',
                'order_by' => 'client_id',
                'label' => 'Client',
                'width' => '110px',
            ],
            [
                'key' => 'print_awb',
                'order_by' => 'print_awb',
                'label' => 'Print AWB',
                'width' => '47px',
            ],
            [
                'key' => 'ml',
                'order_by' => 'prod_ml',
                'label' => 'Metri liniari',
                'width' => '52px',
            ],
            [
                'key' => 'accesorii',
                'order_by' => 'accesories',
                'label' => 'Accesorii',
                'width' => '70px',
            ],
            [
                'key' => 'livrare',
                'order_by' => 'delivery_type',
                'label' => 'Mod Livrare',
                'width' => '110px',
            ],
            [
                'key' => 'judet',
                'order_by' => null,
                'label' => 'Judet',
                'width' => '90px',
            ],
            [
                'key' => 'data_expediere',
                'order_by' => 'delivery_date',
                'label' => 'Data Expediere',
                'width' => '95px',
            ],
            [
                'key' => 'status',
                'order_by' => 'status',
                'label' => 'Stare',
                'width' => '80px',
            ],
            [
                'key' => 'p',
                'order_by' => 'attr_p',
                'label' => 'P.',
                'width' => '100px',
            ],
            [
                'key' => 'pjal',
                'order_by' => 'attr_pjal',
                'label' => 'P. JAL.',
                'width' => '100px',
            ],
            [
                'key' => 'pu',
                'order_by' => 'attr_pu',
                'label' => 'P. U.',
                'width' => '100px',
            ],
            [
                'key' => 'intarziere',
                'order_by' => null,
                'label' => 'Intarziere',
                'width' => '80px',
            ],
            [
                'key' => 'culoare',
                'order_by' => null,
                'label' => 'Culoare',
                'width' => '120px',
            ],
            [
                'key' => 'plata',
                'order_by' => 'payment_type',
                'label' => 'Plata',
                'width' => '128px',
            ],
            [
                'key' => 'contabilitate',
                'order_by' => 'billing_status',
                'label' => 'Contabilitate',
                'width' => '155px',
            ],
            [
                'key' => 'comanda_distribuitor',
                'order_by' => 'distribuitor_order',
                'label' => 'Comanda Distribuitor',
                'width' => '100px',
            ],
            [
                'key' => 'fisiere',
                'order_by' => null,
                'label' => 'Fisiere',
                'width' => '94px',
            ],
            [
                'key' => 'print_comanda',
                'order_by' => 'listed',
                'label' => 'Listat',
                'width' => '50px',
            ],
            [
                'key' => 'awb',
                'order_by' => 'awb_id',
                'label' => 'AWB',
                'width' => '160px',
            ],
            [
                'key' => 'telefon',
                'order_by' => null,
                'label' => 'Telefon',
                'width' => '110px',
            ],
            [
                'key' => 'sursa',
                'order_by' => 'distribuitor_id',
                'label' => 'Sursa',
                'width' => '126px',
            ],
            [
                'key' => 'valoare',
                'order_by' => 'total_final',
                'label' => 'Valoare (RON)',
                'width' => '80px',
            ],
        ];
        foreach ($columns as $index => $column) {
            if (!$user->hasPermission("offer_column_{$column['key']}")) {
                unset($columns[$index]);
            }
        }
        $columns = array_values($columns);

        $tileFence = 1; // 0-gard(sipca,etc), 1-tigla
        if($request->getPathInfo() == '/admin/lista-comenzi-sipca'){
          $tileFence = 0;
        }
      
        $query = Offer::query();
        $query->where('numar_comanda', '!=', null);
        $query->whereHas('offerType', function (Builder $qr) use($tileFence){
            $qr->where('tile_fence', $tileFence);
        });
        $query->with([
            'client',
            'agent',
            'products.getParent',
            'offerType',
            'status_name',
            'distribuitor',
            'delivery_address',
        ]);

        // dynamic filters based on columns
        foreach ($columns as $column) {
            if ($column['order_by'] && $request->get($column['order_by'], false)) {
                $query->where($column['order_by'], $request->get($column['order_by'], false));
            }
        }

        // order by date, and user selectable column
        $orderColumn = ['offer_date', 'desc'];
        $query->orderBy($orderColumn[0], $orderColumn[1]);
        if ($request->order_by) {
            $query->orderBy($request->order_by, $request->sort_order);
            $orderColumn = [$request->order_by, $request->sort_order];
        }

        // paginate and make query
        $orders = $query->paginate($request->get('per_page', 10));
        if (count($orders) == 0 && $orders->total() > 0) {
            return redirect(url()->current().'?'.http_build_query(array_merge(request()->all(), [
                'page' => $orders->lastPage(),
            ])));
        }

        // calculate delayed orders
        foreach ($orders as $order) {
          $not_delivered_statuses = [
            1, // noua
            2, // finalizata
            // 3, // anulata
            4, // retur
            5, // modificata
            6, // productie
            // 7, // livrata
            // 8, // expediata
          ];
          if (in_array($order->status, $not_delivered_statuses)) {
            $delivery_date = Carbon::parse($order->delivery_date);
            $today = Carbon::now()->startOfDay();
            if ($delivery_date->lt($today)) {
              $order->intarziere = $delivery_date->diffInDays($today).'Z';
              $order->offer_date = $today->format('Y-m-d');
            }
          }
        }

        // group by date, and calculate day stats
        $orderGroups = [];
        foreach ($orders->groupBy('offer_date') as $day => $dayOrders) {
            $subtotalPrice = 0;
            $subtotalMl = 0;
            $subtotalPrice = round(Offer::where('offer_date', $day)->sum('total_final'), 2);
            foreach ($dayOrders->all() as $order) {
                $order->prod_ml = 0;
                foreach ($order->products as $prod) {
                    if ($prod->qty > 0 && $prod->getParent->um == 8) {
                        $order->prod_ml += $prod->qty;
                    }
                    if ($prod->qty > 0 && $prod->getParent->um == 1 && $prod->getParent->dimension > 0) {
                        $order->prod_ml += $prod->qty * $prod->getParent->dimension;
                    }
                }
                $updateOrder = $order->fresh();
                $updateOrder->prod_ml = $order->prod_ml;
                $updateOrder->save();
                $subtotalMl += $order->prod_ml;
            }

            $orderGroups[] = [
                'date'           => $day,
                'orders'         => $dayOrders,
                'subtotal_price' => $subtotalPrice,
                'subtotal_ml'    => $subtotalMl,
            ];
        }
        $orderGroups = collect($orderGroups)->sortByDesc('date');

        return view('voyager::offers.lista', [
            'title'       => $title,
            'model'       => $model,
            'slug'        => $slug,
            'orderColumn' => $orderColumn,
            'columns'     => $columns,
            'orders'      => $orders,
            'orderGroups' => $orderGroups->values()->all(),
        ]);
    }

    public function orderEditField(Request $request)
    {
      $offer = Offer::find($request->id);
      if (!$offer) {
        return ['success' => false, 'error' => 'Comanda nu exista'];
      }
      $allowedFields = [
        'listed',
        'print_awb',
        'accesories',
        'billing_status',
        'payment_type',
        'attr_p',
        'attr_pjal',
        'attr_pu',
      ];
      if (in_array($request->field, $allowedFields)) {
        $offer->{$request->field} = $request->value;
        $offer->save();
      }
      return ['success' => true, 'newValue' => $offer->{$request->field}];
    }

    // listez comenzile (au numar_comanda != null)
    public function list_orders(Request $request)
    {
        // noul controller cu view complet custom
        return $this->list_orders_custom($request);


        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = 'offers';

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
            $query = $model::select($dataType->name.'.*')->where('numar_comanda', '!=', null);

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
                    $query->orderBy($dataType->order_column, $dataType->order_direction),
                    $getter,
                ]);
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

        $dataType->display_name_plural = 'Lista comenzi';
        $is_order_page = true;
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
            'showCheckboxColumn',
            'is_order_page'
        ));
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
          // verific daca au fost completate toate adresele
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
      // daca am erori in $errMessages, atunci le afisez in pagina

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

        // iau oferta creata cu insertUpdateData si-i modific datele de mai jos
        $offer = Offer::find($data->id);
        $offer->status = '1';
        $offer->serie = $data->id;
        $offer->distribuitor_id = $request->input('distribuitor_id');
        $offer->agent_id = Auth::user()->id;
        $offer->save();

        // daca am adaugat un client nou, cu adrese, il creez, verific datele din adresa adaugata si le salvez
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
          // creez adresa user-ului adaugat
          $editInsertAddress = new UserAddress;
          $editInsertAddress->address = $address;
          $editInsertAddress->user_id = $user_id;
          $editInsertAddress->country = $itemCountry;
          $editInsertAddress->state = $itemState;
          $editInsertAddress->city = $itemCity;
          $editInsertAddress->save();
          $offer->delivery_address_user = $editInsertAddress->id;
          $offer->save();

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
          try{
            \App\Http\Controllers\Admin\VoyagerClientsController::syncClient($client->id);
          } catch(\Exception $e){}
        }

      // salvez culorile default pe baza culorii selectate
      $selectedColor = $request->input('selectedColor');
      (new self())->updateOfferAttributeForPreselectedColor($request, $offer->id, $offer->type);

      // salvez log-ul pentru oferta nou creata
        $message = "a creat o oferta noua";
        (new self())->createEvent($offer, $message);

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
  
    public function retrievePreselectedColors(Request $request){
      return ['success' => true , 'colors' => (new self())->updateOfferAttributeForPreselectedColor($request->input('selectedColor'), $request->input('offerId'), $request->input('offerType'))];
    }
  
    public static function updateOfferAttributeForPreselectedColor($selectedColor, $offerId, $offerType){
      $colors = [];
      if($selectedColor != null){
        // pentru ca am modificat frontend-ul ca sa imi afiseze in selector culoarea,
        // acum valoare este un sir concatenat cu _ dupa care iau id-ul de culoare
        $selectedColor = explode('_', $selectedColor)[1];
        // iau culorile preselectate pentru tipul de oferta selectat
        $colors = OffertypePreselectedColor::with('selectedcolor')->where(['color_id' => $selectedColor, 'offer_type_id' => $offerType])->get();
        // daca am culori trec prin fiecare
        if($colors && count($colors) > 0){
          $createdAt = date('Y-m-d H:i:s');
          foreach($colors as $color){
            // inserez atributul selectat pentru oferta curenta(pentru ca atunci cand intru pe edit, sa am precompletate culorile aferente acestui tip de oferta)
            $offerAttribute = new OfferAttribute();
            $offerAttribute->offer_id = $offerId;
            $offerAttribute->attribute_id = $color->attribute_id;
            $offerAttribute->col_dim_id = $color->selected_color_id;
            $offerAttribute->created_at = $createdAt;
            $offerAttribute->updated_at = $createdAt;
            $offerAttribute->save();
          }
          // daca ajung la un numar "mare" de date in offerAttribute, resetez id-ul, pentru ca de fiecare data cand selectez o culoare,
          // le sterg din DB pe cele din oferta curenta si le reinserez in noua formula
          $offAttrsCount = OfferAttribute::count('id');
          if($offAttrsCount == 50000){
            DB::raw('ALTER TABLE  `offer_attributes` DROP COLUMN `id`');
            DB::raw('ALTER TABLE `offer_attributes` ADD id INT PRIMARY KEY AUTO_INCREMENT');
          }
        }
      }
      return $colors;
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

        // foloseam inainte sa scot butonul de save, la cererea lor, pentru ca am o functie definita mai jos cu care salvez la fiecare modificare facuta in frontend printr-un ajax call
        if($request->input('delivery_address_user') != null){
          $data->delivery_address_user = $request->input('delivery_address_user') == -2 ? null : $request->input('delivery_address_user');
          $data->total_general = $request->input('totalGeneral') != null ? number_format(floatval($request->input('totalGeneral')), 2, '.', '') : 0;
          $data->reducere = $request->input('reducere') != null ? number_format(floatval(abs($request->input('reducere'))), 2, '.', '') : 0;
          $data->total_final = $request->input('totalCalculatedPrice') != null ? number_format(floatval($request->input('totalCalculatedPrice')), 2, '.', '') : 0;
          $data->save();
        }

        // salvez log-ul cu oferta modificata

        $message = "a modificat oferta";
        (new self())->createEvent($offer, $message);

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

        // definesc variabilele cu valoarea null pe care le "verific"(le verific in pagina de edit si trebuie sa le am definite si in create pentru ca e acelasi view)
        $userAddresses = null;
        $priceRules = null;
        $allProducts = null;
        $select_html_grids = null;
        $offerProducts = null;
        $adminUsers = null;
        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'userAddresses', 'priceRules', 'allProducts', 'select_html_grids', 'offerProducts', 'adminUsers'));
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

        // cand intru pe pagina de edit, trebuie sa iau toate produsele pe baza tipului de oferta selectat
        $createdAttributes = [];
        $offer = $dataTypeContent;
        // iar adresele user-ului selectat
        $userAddresses = \App\UserAddress::where('user_id', $offer->client_id)->get();
        // iau tipul de oferta selectata
        $offerType = OfferType::find($offer->type);

        // parintii sunt produsele(asa au vrut ei sa definim parintii ca fiind produse)
        $offerType->parents = $offerType->parents();
        // creez $parentIds pe baza id-urilor produselor din tipul de oferta
        $parentIds = $offerType->parents && $offerType->parents->pluck('id') ? $offerType->parents->pluck('id') : null;
        $offerProducts = null;
        // iau cursul valutar(daca am in oferta, il iau de acolo, daca nu, il iau din tipul de oferta. Daca nici acolo nu e definit, il iau pe cel live de la BNR)
        $cursValutar = $offer->curs_eur != null ? $offer->curs_eur : ($offerType->exchange != null ? $offerType->exchange : \App\Http\Controllers\Admin\CursBNR::getExchangeRate("EUR"));
        $offer->curs_eur = $cursValutar;
        // iau toate regulile de pret
        $priceRules = \App\RulesPrice::get();
        $priceGridId = $offer->price_grid_id != null ? $offer->price_grid_id : 6;
        // iau adresele user-ului
        $selectedAddress = \App\UserAddress::find($offer->delivery_address_user);
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
        $filteredColors = [];
        $filteredDimensions = [];

        // Fiecare articol din produse are atribute selectate. Eu trebuie sa le filtrez pentru a nu afisa acelasi atribut de mai multe ori. Spre exemplu produsul X are culoarea Rosu iar Y are culoarea Rosu
        // Trebuie sa le filtrez si sa afisez o singura data culoarea Rosu
        if($offerType->parents && count($offerType->parents) > 0){
          // iau id-urile de articole pe baza id-urilor de produse din tipul de oferta selectat
          $prods = Product::select('id')->whereIn('parent_id', $parentIds)->get();
          if(count($prods) > 0){
            $arrayOfAttrValues = [];
            // trebuie sa le iau cu select distinct, ceva
            $prodIds = $prods->pluck('id');
            $idsString = str_replace("[", "", json_encode($prodIds));
            $idsString = str_replace("]", "", $idsString);
            $colors = DB::select( DB::raw('SELECT DISTINCT product_attributes.id, CONCAT(attributes.title, "_", attributes.id) as attr_title, CONCAT(colors.value, "_", colors.id) as color_value, colors.ral as color_ral
                              FROM product_attributes
                              JOIN attributes ON product_attributes.attribute_id = attributes.id
                              LEFT JOIN colors ON product_attributes.color_id = colors.id
                              WHERE product_attributes.color_id IS NOT NULL AND product_attributes.product_id IN ('.$idsString.') GROUP BY attr_title, color_value, color_ral'));
            $dimensions = DB::select( DB::raw('SELECT DISTINCT CONCAT(attributes.title, "_", attributes.id) as attr_title, CONCAT(dimensions.value, ";_", dimensions.id) as dimension_value
                          FROM product_attributes
                          JOIN attributes ON product_attributes.attribute_id = attributes.id
                          LEFT JOIN dimensions ON dimensions.id = product_attributes.dimension_id
                          WHERE product_attributes.dimension_id IS NOT NULL AND product_attributes.product_id IN ('.$idsString.') GROUP BY attr_title, dimension_value'));
            if($colors && count($colors)){
              foreach($colors as &$color){
                if($color->color_value != null){
                    $attrArr = explode("_", $color->attr_title);
                    $color->attr_title = $attrArr[0];
                    $color->attr_id = $attrArr[1];
                    $colorArr = explode("_", $color->color_value);
                    $color->color_value = $colorArr[0];
                    $color->color_id = $colorArr[1];
                    if(!array_key_exists($color->attr_title, $filteredColors)){
                      $filteredColors[$color->attr_title] = [];
                      array_push($filteredColors[$color->attr_title], $color);
                    } else{
                      if(!in_array($color, $filteredColors[$color->attr_title])){
                        array_push($filteredColors[$color->attr_title], $color);
                      }
                    }
                }

              }
            }
            if($dimensions && count($dimensions) > 0){
              foreach($dimensions as &$dimension){
                $attrArr = explode("_", $dimension->attr_title);
                $dimension->attr_title = $attrArr[0];
                $dimension->attr_id = $attrArr[1];
                $dimensionArr = explode(";_", $dimension->dimension_value);
                $dimension->dimension_value = $dimensionArr[0];
                $dimension->dimension_id = $dimensionArr[1];
                if(!array_key_exists($dimension->attr_title, $filteredDimensions)){
                  $filteredDimensions[$dimension->attr_title] = [];
                  array_push($filteredDimensions[$dimension->attr_title], $dimension);
                } else{
                  if(!in_array($dimension, $filteredDimensions[$dimension->attr_title])){
                    array_push($filteredDimensions[$dimension->attr_title], $dimension);
                  }
                }
              }
            }
          }
          $offerProducts = OfferProduct::with('prices')->where('offer_id', $offer->id)->get();
          // pentru oferta pe care ma aflu acum, iau produsele din tabelul de legatura pentru a le lasa disponibil campul cantitate si pentru a afisa preturile calculate pe baz grilelor de pret
          // (pentru celelalte produse pe care nu le gasesc in obiectul asta)
          // voi avea campul cantitate readonly iar restul campurilor vor avea valoarea 0
          if($offerProducts && count($offerProducts) > 0){
            foreach($offerProducts as $offProd){
              // filtrez produsele din oferta pe baza parent_id
              $checkedParent = $offerType->parents->filter(function($item) use($offProd){
                  return $item->id == $offProd->parent_id;
              })->first();
              $checkedParent->offerProducts = $offProd;
            }
          }
        }
        // transform price_grid_id input in select dropdown cu valorile selectate(pentru ca eu l-am facut input si trebuie sa il am select - au cerut ca sa am preselectata grila de pret pe Lista - id: 6 )
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

        $adminUsers = User::get();
        $offerEvents = OfferEvent::where('offer_id', $offer->id)->where('is_mention', 0)->orderBy('created_at', 'DESC')->get();
        $offerMessages = OfferEvent::where('offer_id', $offer->id)->where('is_mention', 1)->orderBy('created_at', 'DESC')->get();

        $offerAttributes = OfferAttribute::where('offer_id', $offer->id)->get();
        $offerSelectedAttrsArray = [];
        if($offerAttributes && count($offerAttributes) > 0){
          foreach($offerAttributes as $offAttr){
            array_push($offerSelectedAttrsArray, [$offAttr->attribute_id, $offAttr->col_dim_id]);
          }
        }
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
          'select_html_grids',
          'offerProducts',
          'adminUsers',
          'offerEvents',
          'offerMessages',
          'filteredColors',
          'filteredDimensions',
          'offerSelectedAttrsArray',
        ));
    }

    public function retrievePricesForSelectedAttributes($order_id, $attributes, $modifyOfferProductsPrices){
      $offer = Offer::find($order_id);
      $offerType = OfferType::find($offer->type);
      $offerType->parents = $offerType->parents();
      $parentIds = $offerType->parents->pluck('id');
      $offerProdsIds = [];
      $allProducts = [];
      $attrQueryArray = [];
      $totalCalculat = 0;
      foreach($attributes as $key => $attr){
        $key_column = 'color_id';
        $attr = explode("_", $attr);
        $attr_id = $attr[0];
        $attr_val_id = $attr[1];
        $attribute = Attribute::find($attr_id);
        if($attribute->type == 0){
          $key_column = 'dimension_id';
        }
        array_push($attrQueryArray, [
          'attribute_id' => $attr_id,
          $key_column => $attr_val_id,
          'column' => $key_column,
        ]);
      }
      $offerProducts = OfferProduct::with('prices')->where('offer_id', $offer->id)->get();
//       dd($offerProducts && count($offerProducts) > 0 && $modifyOfferProductsPrices);
      // daca am avut ceva selectat pana acum sterg pentru a afisa noua combinatie de atribute selectate
      if($offerProducts && count($offerProducts) > 0 && $modifyOfferProductsPrices){
        $offProdIds = $offerProducts->pluck('id');
        OfferProduct::whereIn('id', $offProdIds)->delete(); // sterg toate valorile pentru ca am produse noi, definite prin atributele selectate
        OfferPrice::whereIn('offer_products_id', $offProdIds)->delete(); // sterg toate valorile pentru ca am produse noi, definite prin atributele selectate
      }
      if(count($attrQueryArray) > 0){
        // trec prin toti parintii ca sa iau produsele cu atributele selectate
        foreach($offerType->parents as $parent){
          // iar nr maxim de atribute pe care-l poate avea un parinte
          $nrOfAttrs = count($attributes) >= count($parent->category->attributes) ? count($parent->category->attributes) : count($attributes);
          // iau atributele
          $productAttrs = ProductAttribute::select("*", DB::raw('IF(COUNT(product_id) = '.$nrOfAttrs.', true, false) as founded'))->where('parent_id', $parent->id)
            ->where(function($query) use($attrQueryArray){
                foreach($attrQueryArray as $item){
                  $query->orWhere('attribute_id', $item['attribute_id'])
                        ->where($item['column'], $item[$item['column']]);
                }
            })->groupBy('product_id')->get();
          // daca am gasit produse cu atributele selectate
          if($productAttrs && count($productAttrs) > 0 && count($productAttrs->where('founded', 1)) == 1){
          // daca gasesc doar 1 produs, iau id-ul si-l pun in offerProdsIds
            $prodAttrIds = $productAttrs->where('founded', 1)->pluck('product_id');
            // le pun in lista de id-uri pentru ca mai jos sa iau produsele cu toate informatiile
            $offerProdsIds = array_merge($offerProdsIds,$prodAttrIds->toArray());
          }
          //cautam produse fara atribute
            if ($nrOfAttrs == 0) {
                $thisParentProductsIds = Product::where('parent_id', $parent->id)->pluck('id')->toArray();
                $offerProdsIds = array_merge($offerProdsIds,$thisParentProductsIds);
            }
        }
      }
      // elimin dublurile, desi teoretic nu am
      $offerProdsIds = array_unique($offerProdsIds);
      // iar produsele pe baza array-ului facut anterior cu id-urile de produse
      $products = Product::whereIn('id', $offerProdsIds)->get();
      // calculez/iau cursul valutar
      $cursValutar = $offer->curs_eur != null ? $offer->curs_eur : ($offerType->exchange != null ? $offerType->exchange : \App\Http\Controllers\Admin\CursBNR::getExchangeRate("EUR"));

      $created_at = date("Y-m-d H:i:s");
//       dd($modifyOfferProductsPrices);
      if($modifyOfferProductsPrices){
        // recreez noile valori pentru offer_products si offer_prices
        foreach($products as $product){
          $rulesPrices = (new self())->getRulesPricesByProductCategory($product->categoryId(), $product->price, $cursValutar);
          if($rulesPrices != null && count($rulesPrices) > 0){
            $offerProduct = new OfferProduct();
            $offerProduct->offer_id = $offer->id;
            $offerProduct->product_id = $product->id;
            $offerProduct->parent_id = $product->parent_id;
            $offerProduct->qty = 1; // default 1 pentru cele gasite. La edit trebuie sa iau ce cantitati se trimit
            $offerProduct->created_at = $created_at;
            $offerProduct->updated_at = $created_at;
            $offerProduct->save();
            // pentru fiecare regula de pret, salvez in baza de date
            // salvez toate datele pentru eventuale rapoarte
            foreach($rulesPrices as $rule){
              $addedDate = date("Y-m-d H:i:s");
              $offerPrice = new OfferPrice();
              $offerPrice->offer_products_id = $offerProduct->id;
              $offerPrice->rule_price_id = $rule->id;
              $offerPrice->rule_id = $rule->rule_id;
              $offerPrice->tip_obiect = $rule->tip_obiect;
              $offerPrice->categorie = $rule->categorie;
              $offerPrice->categorie_name = $rule->categorie_name;
              $offerPrice->variabila = $rule->variabila;
              $offerPrice->operator = $rule->operator;
              $offerPrice->formula = $rule->formula;
              $offerPrice->full_formula = $rule->full_formula;
              $offerPrice->base_price = $product->price;
              $offerPrice->ron_cu_tva = $rule->ron_cu_tva;
              $offerPrice->product_price = $rule->price;
              $offerPrice->currency = $cursValutar;
              $offerPrice->eur_fara_tva = $rule->eur_fara_tva;
              $offerPrice->created_at = $addedDate;
              $offerPrice->updated_at = $addedDate;
              $offerPrice->save();
            }
          }
        }
      }
      // iau produsele din oferta si le filtrez pentru a afisa noile preturi in listarea din pagina
      if($offerProducts && count($offerProducts) > 0){
        foreach($offerProducts as $offProd){
          // filtrez produsele din oferta pe baza parent_id
          $checkedParent = $offerType->parents->filter(function($item) use($offProd){
              return $item->id == $offProd->parent_id;
          })->first();
          $checkedParent->offerProducts = $offProd;
          $ronCuTVA = $checkedParent != null ? $checkedParent->ron_cu_tva : 0;
          $ronTotal = $ronCuTVA*$checkedParent->offerProducts->qty;
          $totalCalculat += $ronTotal;
        }
      }
      $offer->reducere = 0;
      $offer->total_general = number_format($totalCalculat, 2);
      $offer->total_final = number_format($totalCalculat, 2);
      $offer->save();
      $priceRules = \App\RulesPrice::get();
      return view('vendor.voyager.products.offer_box', ['parents' => $offerType->parents, 'reducere' => $offer->reducere, 'offer' => $offer, 'priceRules' => $priceRules])->render();
    }

    // la fel si functia asta... sa termin cu JSON-urile mai intai
    public static function getRulesPricesByProductCategory($categoryId, $productPrice = null, $currency = null){
      $tva = floatVal(setting('admin.tva_products'))/100;
      $rulePricesFilteredByCategory = RulePricesFormula::where('categorie', $categoryId)->get();
      foreach($rulePricesFilteredByCategory as &$item){
        $formula = str_replace("PI", $productPrice, $item['full_formula']);
        $price = eval('return '.$formula.';');
        $formatedPriceFormula = floatVal(number_format($price ,4,'.', ''));
        if($currency == null){
          $currency = 1;
        }
        // pret_de_baza * currency
        $priceWithCurrency = $price*$currency;
        $priceWithTva = $priceWithCurrency+($priceWithCurrency*$tva);

        $item['price'] = number_format($formatedPriceFormula, 2, '.', '');
        $item['eur_fara_tva'] = number_format($price, 2, '.', '');
        $item['ron_cu_tva'] = number_format($priceWithTva, 2, '.', '');

      }
      return $rulePricesFilteredByCategory;
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

  // functie care-mi salveaza in baza de date de fiecare data cand fac modificari in campurile de pe pagina Editare Oferta
  public function ajaxSaveUpdateOffer(Request $request){
    $offer_id = $request->input('offer_id');
    $offerDb = null;
    if($offer_id != null){
      $offer = Offer::find($offer_id);
      $offerDb = Offer::find($offer_id); // mai fac un query pentru ca daca echivalez offerDb cu offer, orice modificare aduc lui offer, se propaga si la offerDb
    } else{
      $offer = new Offer;
    }

    // modific toate campurile pe care le-am editat din frotend
    $offer->type = $request->input('type');
    $offer->offer_date = $request->input('offer_date');
    $offer->client_id = $request->input('client_id');
    $offer->distribuitor_id = $request->input('distribuitor_id');
    $offer->price_grid_id = $request->input('price_grid_id') != null ? $request->input('price_grid_id') : 6; // default Lista
    $offer->curs_eur = $request->input('curs_eur');
    //$offer->agent_id = Auth::user()->id; // asta nu mai trebuie suprascris
    $offer->delivery_address_user = $request->input('delivery_address_user');
    $offer->delivery_date = $request->input('delivery_date');
    $offer->observations = $request->input('observations');
    $offer->created_at = $request->input('created_at');
    $offer->updated_at = $request->input('updated_at');
    $offer->status = $request->input('status');
    $offer->serie = $request->input('serie');
    $offer->total_general = $request->input('totalGeneral') != null ? number_format(floatval($request->input('totalGeneral')), 2, '.', '') : 0;
    $offer->reducere = $request->input('reducere') != null ? number_format(floatval(abs($request->input('reducere'))), 2, '.', '') : 0;
    $offer->total_final = $request->input('totalCalculatedPrice') != null ? number_format(floatval($request->input('totalCalculatedPrice')), 2, '.', '') : 0;
    $offer->transparent_band = $request->input('transparent_band') == 'on' ? 1 : 0;
    $offer->packing = $request->input('packing');
    $offer->delivery_details = $request->input('delivery_details');
    $offer->delivery_type = $request->input('delivery_type');
    $offer->save();

    $selectedAttributes = $request->input('selectedAttribute');
    $updatedAt = date("Y-m-d H:i:s");
    // trebuie sa imi iau color_id si sa-l pun in selectedAttribute din edit-add
    if($selectedAttributes != null && count($selectedAttributes) > 0){
      OfferAttribute::where('offer_id', $offer->id)->delete();
      foreach($selectedAttributes as $selAttr){
        $selAttr = explode("_", $selAttr);
        // primul element e id-ul atributului
        $attrId = $selAttr[0];
        // al doilea element e id-ul culorii/dimensiunii
        $colDimId = $selAttr[1];

        $offerAttribute = new OfferAttribute();
        $offerAttribute->offer_id = $offer->id;
        $offerAttribute->attribute_id = $attrId;
        $offerAttribute->col_dim_id = $colDimId;
        $offerAttribute->updated_at = $updatedAt;
        $offerAttribute->created_at = $updatedAt;
        $offerAttribute->save();
      }
    }
    /*
    Trebuie facut mesajul asta, dar vad cum...
        $resultField = 'Culoare/Dimensiune/Grosime';
        $changedField = 'attributes';
    */
    // verific daca editez o oferta existenta si salvez event-ul
    $message = "a modificat oferta";
    $fromValue = 'empty';
    $toValue = 'empty';
    $changedField = '';
    // am facut o functie care verifica ce camp s-a modificat si care sunt valorile modificate, din ce valoare in ce valoare s-a trecut
    $retrievedFieldWithData = (new self())->getFieldTranslatedName($offerDb, $offer);
    if($retrievedFieldWithData != null){
      // iau diferenta intre ce aveam in db si ce am modificat
      $fromValue = $retrievedFieldWithData['fromValue'] == '' ? 'empty' : $retrievedFieldWithData['fromValue'];
      $toValue = $retrievedFieldWithData['toValue'] == '' ? 'empty' : $retrievedFieldWithData['toValue'];
      $resultField = $retrievedFieldWithData['resultField'];
      $changedField = $retrievedFieldWithData['changedField'];
      $message = ' a modificat <strong>'.$resultField.'</strong> din <strong>'.$fromValue.'</strong> in <strong>'. $toValue.'</strong>';
    }
    // pentru ca am reducere trecut default 0 in baza de date, imi returneaza de fiecare data ca l-am modificat. Verific aici daca l-am modificat cu adevarat sau nu
    if($changedField != 'reducere' || ($fromValue != 'empty' && $toValue != '0.00')){
      (new self())->createEvent($offer, $message);
    }
    $modifyOfferProductsPrices = $request->input('modifyOfferProductsPrices') == "true" ? true : false;
    if($request->input('getPrices') && !$modifyOfferProductsPrices){
      $offerQty = $request->input('offerQty');
      // pentru fiecare produs pentru care am modificat cantitatea, modific si in baza de date
      if($request->input('offerProductIds') != null && $offerQty != null){
        $offerProductIds = $request->input('offerProductIds');
        foreach($offerProductIds as $key => $id){
          $offerProduct = OfferProduct::where('id', $id)->first();
          $offerProduct->qty = $offerQty[$key];
          $offerProduct->save();
        }
      }
    }
    if($request->input('getPrices')){
      $trievedPrices = (new self())->retrievePricesForSelectedAttributes($offer->id, $selectedAttributes, $modifyOfferProductsPrices);
      return ['success' => true, 'offer_id' => $offer->id, 'html_log' => (new self())->getHtmlLog($offer), 'html_prices' => $trievedPrices];
    } else{

    }
    return ['success' => true, 'offer_id' => $offer->id, 'html_log' => (new self())->getHtmlLog($offer)];
  }

  // generez pdf-ul cu oferta
  public function generatePDF($offer_id){
    // iau oferta pe baza id-ului de oferta
    $offer = Offer::with(['distribuitor', 'client', 'delivery_address'])->find($offer_id);
    $dimension = 0;
    $boxes = 0;
    $totalQty = 0;
    if($offer != null){
      // iau produsele pe care le-am salvat in baza de date in offer_product
      $offerProducts = OfferProduct::with(['prices', 'product', 'getParent'])->where('offer_id', $offer->id)->get();
      $offerType = OfferType::find($offer->type);
      $offerType->parents = $offerType->parents();

      // trec prin fiecare produs pentru a calcula dimensiunea, totalul de cantitati si cutii
      if($offerProducts && count($offerProducts) > 0){
        $newPrices = [];
        foreach($offerProducts as &$offProd){
          $checkRule = $offProd->prices->filter(function($item) use($offer){
              return $item->rule_id == $offer->price_grid_id;
          })->first();
          $offProd->selectedPrices = $checkRule;
          array_push($newPrices, [
            'dimension' => $offProd->getParent->dimension,
            'parent' => $offProd->getParent,
            'qty' => $offProd->qty,
          ]);
          $dimension += $offProd->getParent->dimension != null && $offProd->getParent->dimension != 0 ? $offProd->getParent->dimension*$offProd->qty : $offProd->qty;
          $totalQty += $offProd->qty;
        }
        $boxes = intval(ceil($totalQty/25)); // rotunjire la urmatoarea valoare
        $offer->prices = $newPrices;

      }
      $offer->dimension = $dimension;
      $offer->boxes = $boxes;
      // trimit toate datele in offer_pdf si generez pdf-ul
      $attributes = OfferAttribute::with('attribute')->where('offer_id', $offer->id)->get();
      $pdf = PDF::loadView('vendor.pdfs.offer_pdf',['offer' => $offer, 'offerProducts' => $offerProducts, 'attributes' => $attributes]);

      $message = "a generat PDF oferta";
      (new self())->createEvent($offer, $message);

      return $pdf->download('Oferta_TPS'.$offer->serie.'_'.date('m-d-Y').'.pdf');
    }
    return ['success' => false];
  }

  // acelasi lucru ca mai sus
  public function generatePDFFisa($offer_id){
    $offer = Offer::with(['distribuitor', 'client', 'delivery_address'])->find($offer_id);
    $dimension = 0;
    $boxes = 0;
    $totalQty = 0;
    if($offer != null){
      $offerProducts = OfferProduct::with(['prices', 'product', 'getParent'])->where('offer_id', $offer->id)->get();
      $offerType = OfferType::find($offer->type);
      $offerType->parents = $offerType->parents();

      if($offerProducts && count($offerProducts) > 0){
        $newPrices = [];
        foreach($offerProducts as &$offProd){
          $checkRule = $offProd->prices->filter(function($item) use($offer){
              return $item->rule_id == $offer->price_grid_id;
          })->first();
          $offProd->selectedPrices = $checkRule;
          array_push($newPrices, [
            'dimension' => $offProd->getParent->dimension,
            'parent' => $offProd->getParent,
            'qty' => $offProd->qty,
          ]);
          $dimension += $offProd->getParent->dimension != null && $offProd->getParent->dimension != 0 ? $offProd->getParent->dimension*$offProd->qty : $offProd->qty;
          $totalQty += $offProd->qty;
        }
        $boxes = intval(ceil($totalQty/25)); // rotunjire la urmatoarea valoare

      }
      $offer->dimension = $dimension;
      $offer->boxes = $boxes;
      $attributes = OfferAttribute::with('attribute')->where('offer_id', $offer->id)->get();
      $pdf = PDF::loadView('vendor.pdfs.offer_pdf_order',['offer' => $offer, 'offerProducts' => $offerProducts, 'attributes' => $attributes]);
      $message = "a generat Fisa PDF oferta";
      (new self())->createEvent($offer, $message);
      return $pdf->download('Fisa Comanda_TPS'.$offer->numar_comanda.'_'.date('m-d-Y').'.pdf');
    }
    return ['success' => false];
  }
  // folosesc in dashboard pentru a afisa ofertele in functie de ce s-a selectat din calendar
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

  // schimb statusul comenzii in baza de date
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
      $oldStatus = Status::find($offer->status);
      $offer->status = $status != null ? $status->id : 1;
      $offer->save();
      $message = "a schimbat statusul din ".$oldStatus->title.' in '.$status->title;
      (new self())->createEvent($offer, $message);
      return ['success' => true, 'msg' => 'Statusul a fost modificat cu succes!', 'html_log' => (new self())->getHtmlLog($offer)];
    } catch(\Exception $e){
      return ['success' => false, 'msg' => 'Statusul nu a putut fi modificat!'];
    }
  }

  // s-a apasat butonul de Oferta acceptata - lanseaza comanda
  public function launchOrder(Request $request){
    if($request->input('order_id') == null){
      return ['success' => false, 'msg' => 'Te rugam sa selectezi o comanda pentru a lansa comanda!'];
    }
    try{
      $offer = Offer::find($request->input('order_id'));
      $lastStatus = $offer->status;
      $offer->status = 6; // comanda lansata in productie
      // generez un numar de comanda pe baza comenzilor create anterior. Ex: count(comenzi) + 1
      $nextOrderNumber = Offer::where('numar_comanda', '!=', null)->where('serie', $offer->serie)->max('numar_comanda');
      if($nextOrderNumber == 0){
        if($offer->serie == null){
          return ['success' => false, 'msg' => 'Selecteaza o serie pentru a putea lansa comanda!'];
        }
        $nextOrderNumber = OfferSerial::find($offer->serie)->from;
      }
      $nextOrderNumber += 1;
      $offer->numar_comanda = $nextOrderNumber;
      $offer->save();
      $checkSync = (new self())->syncOrderToWinMentor($offer->id);
      if($checkSync['success'] == true){
        $message = "a lansat comanda";
        (new self())->createEvent($offer, $message);

        $status = Status::find($offer->status);

        return ['success' => true, 'msg' => 'Comanda a fost lansata cu succes!', 'status' => $status->title, 'html_log' => (new self())->getHtmlLog($offer), 'numar_comanda' => $nextOrderNumber];
      } else{
        $offer->numar_comanda = null;
        $offer->status = $lastStatus;
        $offer->save();
        return ['success' => false, 'msg' => $checkSync['msg']];
      }
    } catch(\Exception $e){
      return ['success' => false, 'msg' => 'Comanda nu a putut fi lansata - '.$e->getMessage()];
    }
  }

  // creez evenimentul pe care-l salvez in log-uri
  public static function createEvent($offer, $message, $is_mention = false){
    try{
      $created_at = date("Y-m-d H:i");
      $is_mention = $is_mention ? 1 : 0;
      $offerEvent = new OfferEvent();
      $offerEvent->offer_id = $offer->id;
      $offerEvent->user_name = $offer->agent->name;
      $offerEvent->user_id = $offer->agent_id;
      $offerEvent->message = $message;
      $offerEvent->is_mention = $is_mention;
      $offerEvent->created_at = $created_at;
      $offerEvent->updated_at = $created_at;
      $offerEvent->save();
    } catch(\Exception $e){}
  }

    // functia care-mi traduce campul modificat si verifica ce camp s-a modificat in baza de date
  public static function getFieldTranslatedName($oldObj, $newObj){
    $resultField = '';
    $fromValue = 'empty';
    $toValue = 'empty';
    $changedField = '';
    switch($newObj){
      case $newObj->wasChanged('serie'):
        $fromValue = $oldObj->serie;
        $toValue = $newObj->serie;
        $resultField = 'Numar oferta';
        $changedField = 'serie';
        break;
      case $newObj->wasChanged('client_id'):
        $fromValue = $oldObj->client->name;
        $toValue = $newObj->client->name;
        $resultField = 'Client';
        $changedField = 'client_id';
        break;
      case $newObj->wasChanged('type'):
        $fromValue = $oldObj->offerType->title;
        $toValue = $newObj->offerType->title;
        $resultField = 'Tip oferta';
        $changedField = 'type';
        break;
      case $newObj->wasChanged('offer_date'):
        $fromValue = $oldObj->offer_date;
        $toValue = $newObj->offer_date;
        $resultField = 'Data oferta';
        $changedField = 'offer_date';
        break;
      case $newObj->wasChanged('distribuitor_id'):
        $fromValue = $oldObj->distribuitor->title;
        $toValue = $newObj->distribuitor->title;
        $resultField = 'Sursa';
        $changedField = 'distribuitor_id';
        break;
      case $newObj->wasChanged('curs_eur'):
        $fromValue = $oldObj->curs_eur;
        $toValue = $newObj->curs_eur;
        $resultField = 'Curs valutar';
        $changedField = 'curs_eur';
        break;
      case $newObj->wasChanged('price_grid_id'):
        $fromValue = $oldObj->rulePrice->title;
        $toValue = $newObj->rulePrice->title;
        $resultField = 'Grila pret';
        $changedField = 'price_grid_id';
        break;
      case $newObj->wasChanged('delivery_date'):
        $fromValue = $oldObj->delivery_date;
        $toValue = $newObj->delivery_date;
        $resultField = 'Data livrare';
        $changedField = 'delivery_date';
        break;
      case $newObj->wasChanged('observations'):
        $fromValue = $oldObj->observations;
        $toValue = $newObj->observations;
        $resultField = 'Observatii';
        $changedField = 'observations';
        break;
      case $newObj->wasChanged('status'):
        $fromValue = $oldObj->status_name->title;
        $toValue = $newObj->status_name->title;
        $resultField = 'Status';
        $changedField = 'status';
        break;
      case $newObj->wasChanged('delivery_type'):
        $fromValue = $oldObj->delivery_type;
        $toValue = $newObj->delivery_type;
        $resultField = 'Mod livrare';
        $changedField = 'delivery_type';
        break;
      case $newObj->wasChanged('delivery_details'):
        $fromValue = $oldObj->delivery_details;
        $toValue = $newObj->delivery_details;
        $resultField = 'Date livrare';
        $changedField = 'delivery_details';
        break;
      case $newObj->wasChanged('packing'):
        $fromValue = $oldObj->packing;
        $toValue = $newObj->packing;
        $resultField = 'Ambalare';
        $changedField = 'packing';
        break;
      case $newObj->wasChanged('transparent_band'):
        $fromValue = $oldObj->transparent_band == 0 ? 'Nu' : 'Da';
        $toValue = $newObj->transparent_band == 0 ? 'Nu' : 'Da';
        $resultField = 'Banda transparenta';
        $changedField = 'transparent_band';
        break;
      case $newObj->wasChanged('reducere'):
        $fromValue = $oldObj->reducere;
        $toValue = $newObj->reducere;
        $resultField = 'Reducere';
        $changedField = 'reducere';
        break;
      case $newObj->wasChanged('delivery_address_user'):
        $fromValue = $oldObj->delivery_address->address.', '.$oldObj->delivery_address->city_name.', '.$oldObj->delivery_address->state_name.', '.$oldObj->delivery_address->country.', '.$oldObj->delivery_address->delivery_phone.', '.$oldObj->delivery_address->delivery_contact;
        $toValue = $newObj->delivery_address->address.', '.$newObj->delivery_address->city_name.', '.$newObj->delivery_address->state_name.', '.$newObj->delivery_address->country.', '.$newObj->delivery_address->delivery_phone.', '.$newObj->delivery_address->delivery_contact;
        $resultField = 'Adresa livrare';
        $changedField = 'delivery_address_user';
        break;
    }
    if($resultField == '' && $fromValue == 'empty' && $toValue == 'empty'){
      return null;
    }
    return [
      'changedField' => $changedField,
      'resultField' => $resultField,
      'fromValue' => $fromValue,
      'toValue' => $toValue,
    ];
  }

  // pentru o anumita oferta, iau log-ul
  public static function getHtmlLog($offer){
    $offerEvents = OfferEvent::where(['offer_id' => $offer->id, 'is_mention' => 0])->orderBy('created_at', 'DESC')->get();
    return view('vendor.voyager.partials.log_events', ['offerEvents' => $offerEvents])->render();
  }

  public static function getHtmlLogMentions($offer_id, $limit = 0){
    $orderMentions = $limit != 0 ? OfferEvent::where(['offer_id' => $offer_id, 'is_mention' => 1])->orderBy('created_at', 'DESC')->take($limit)->get() : OfferEvent::where(['offer_id' => $offer_id, 'is_mention' => 1])->orderBy('created_at', 'DESC')->get();
    return view('vendor.voyager.partials.log_events', ['offerEvents' => $orderMentions])->render();
  }

  // functie care salveaza mentiunile facute pe comanda/oferta si care trimite email catre cei tag-uiti si catre o lista de email-uri din settings
  public function saveMention(Request $request){
    if($request->input('order_id') == null){
      return ['success' => false, 'msg' => 'Te rugam sa selectezi o comanda pentru a lansa comanda!'];
    }
    if($request->input('message') == null){
      return ['success' => false, 'msg' => 'Te rugam sa introduci un mesaj!'];
    }
    try{
      $offer = Offer::find($request->input('order_id'));
      $message = 'Mesaj intern: '.$request->input('message');
      (new self())->createEvent($offer, $message, true);

      $taggedUsers = $request->input('mentionIds');
      if($taggedUsers != null){
        $taggedUsers = explode(",", $taggedUsers);
        $adminUsers = User::whereIn('id', $taggedUsers)->get();
        foreach($adminUsers as $user){
          Mail::to($user->email)->send(new Mentions($offer->id, $offer->id, $message, $offer->agent->name, $user->name, false)); // trimite catre userii tag-uiti
        }
        $adminEmails = explode(" ", setting('admin.cc_emails'));
        foreach($adminEmails as $email){
          Mail::to($email)->send(new Mentions($offer->id, $offer->id, $message, $offer->agent->name, 'Admin', true)); // trimite catre email-urile din admin
        }
      }

      return ['success' => true, 'msg' => 'Mesaj salvat cu succes!', 'html_log' => (new self())->getHtmlLog($offer), 'html_messages' => (new self())->getHtmlLogMentions($offer->id)];
    } catch(\Exception $e){
      return ['success' => false, 'msg' => 'Mesajul nu a putut fi salvat!'];
    }
  }

  // trimis SMS catre numarul de telefon al clientului atasat comenzii cu id-ul order_id
  public static function sendSms(Request $request){
    // verific daca s-a facut call-ul cu un id de comanda
    if($request->input('order_id') == null){
      return ['success' => false, 'msg' => 'Mesajul nu a putut fi trimis pentru ca nu exista comanda!'];
    }
    // iau comanda din baza de date
    $offer = Offer::find($request->input('order_id'));
    // iau datele clientului din baza de date
    if($offer->delivery_address_user == null){
      return ['success' => false, 'msg' => 'Nicio adresa de livrare selectata pentru aceasta comanda!'];
    }
    $userAddress = UserAddress::find($offer->delivery_address_user);
    $userData = $userAddress->userData();
    // iau nr de telefon fie din adresa selectata in comanda, fie din datele clientului
    $phone = $userAddress->phone ?: $userData->phone;
    // daca nu re niciun nr de telefon, ii dau o eroare
    if($phone == null){
      return ['success' => false, 'msg' => 'Niciun numar de telefon asociat comenzii!'];
    }
    // completez numarul de telefon cu +4 pentru ca sa pot trimite sms-ul
   	if(strpos($phone, "+4") !== 0 && strlen($phone) == 10){
   		$phone = '+4'.$phone;
   	}
    // fac request-ul catre smso si trimit sms-ul
   	$client = new GuzzleClient;
    try{
      $message = (new self())->replaceDataInTemplate($offer);
      if($message['success']){
        $body = $message['data'];
      } else{
        return ['success' => false, 'msg' => $message['msg']];
      }
      $request = $client->request('POST', 'https://app.smso.ro/api/v1/send', [
              'headers' => [
                  'X-Authorization' => env('SMSO_KEY'),
              ],
             'form_params' => [
                 'to' => $phone,
                 'body' => $body, // iau template-ul din settings si il modific cu datele din order
                 'sender' => 4,
             ],
      ]);
      $message = 'a trimis SMS catre numarul de telefon <strong>'.$phone.'</strong>';
      (new self())->createEvent($offer, $message, true);
      return ['success' => true, 'msg' => 'Mesajul a fost trimis cu succes!'];
    } catch(\Exception $e){
      return ['success' => false, 'msg' => 'Mesajul nu a putut fi trimis!'];
    }
  }

  // returnez mesajul pe baza template-ului din setting
  public static function replaceDataInTemplate($offer){
    $message = setting('admin.message_template');
    $courierObj = $offer->delivery_type == 'fan' ? $offer->fanData : $offer->nemoData;
    if($courierObj == null){
      return ['success' => false, 'msg' => 'Nu se poate trimite SMS pentru ca nu s-a generat un AWB pentru aceasta comanda!'];
    }
    $courier = $offer->delivery_type == 'fan' ? 'FanCourier' : 'NemoExpress';
    $message = str_replace(
      array('{nr_comanda}', '{valoare_comanda}', '{courier}', '{awb}', '{ramburs}'),
      array($offer->numar_comanda, $offer->total_final, $courier, $courierObj->awb, $courierObj->ramburs_numerar),
      $message
    );
    return ['success' => true, 'data' => $message];
  }

  public static function syncOrderToWinMentor($order_id){
    $host = config('winmentor.host');
    $port = config('winmentor.port');
    $waitTimeoutInSeconds = 3;
    $winMentorServer = false;
    try{
      if($fp = fsockopen($host,$port,$errCode,$errStr,$waitTimeoutInSeconds)){
         $winMentorServer = true;
      }
      fclose($fp);
    } catch(\Exception $e){}

    $url = "http://".config('winmentor.host').":".config('winmentor.port')."/datasnap/rest/TServerMethods/ComandaClient//";
    $order = offer::with('serieName')->find($order_id);
    $reducere = $order->reducere;
    $total = $order->total_general;
    $discount = $reducere != null && $reducere != 0 ? number_format($reducere/$total, 8)*100 : "";
    $client = Client::find($order->client_id);
    if($client->mentor_partener_code == null){
      $clientSync = \App\Http\Controllers\Admin\VoyagerClientsController::syncClient($client->id);
      if($clientSync['success']){
        $client = $clientSync['client'];
      }
    }
    $items = [];
    $products = $order->orderProducts;
    foreach($products as $product){
      $prodPrice = $product->pricesByRule($order->price_grid_id)->first();
      array_push($items, [
        "ID" => $product->product->mentor_cod_obiect,
        "Pret" => $prodPrice->ron_cu_tva,
        "Observatii" => "",
        "Cant" => $product->qty,
        "ZilePlata" => "3",
        "CAMPEXTENSIELINIECOMANDA" => "",
        "Rezervari" =>
            [
                /*
                "Gestiune" => "DC",
                "Serie" => "ABCDE",
                "LocatieGest" => "",
                "Cant" => "3"
                */
            ],
        "Discount" => $discount,
        "AdDim" => "",
        "D1" => "",
        "D2" => "",
        "D3" => "",
        "CantUM1" => ""
      ]);
    }
    $orderUser = User::find($order->agent_id);
    $postData = [
        'NrDoc' => $order->numar_comanda,
        'SerieDoc' => $order->serieName->name,
        'DataDoc' => Carbon::parse($order->delivery_date)->format('d.m.Y'),
        'IDClient' => $client->mentor_partener_code,
        'Observatii' => '',
        'CAMPEXTENSIECOMANDA' => '',
        'Moneda' => 'RON',
        'PretCuAmanuntul'=> 'DA',
        'CodSubunitate' => '2',
        'Agent' => $orderUser->wme_user_id ?: 0,
        'Items' => $items,
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/plain'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    if($winMentorServer){
       $result = curl_exec($ch);
       $result = json_decode($result, true);
    } else{
       curl_close ($ch);
       return ['success' => false, 'msg' => '[WinMentor] Nu s-a putut conecta la serverul WinMentor!', 'warning' => false];
    }
    curl_close ($ch);
    if (array_key_exists('Error', $result) && $result['Error'] == "ok" && $winMentorServer){
      $createdAt = date('Y-m-d H:i:s');
      $orderWme = OrderWme::where('order_id', $order->id)->first();
      if($orderWme == null){
        $orderWme = new OrderWme();
      }
      $orderWme->order_id = $order->id;
      $orderWme->cod_comanda = $result['CodComanda'];
      $orderWme->numar_comanda = $result['NumarComanda'];
      $orderWme->created_at = $createdAt;
      $orderWme->updated_at = $createdAt;
      $orderWme->save();
      return ['success' => true, 'msg' => '[WinMentor] Comanda a fost trimisa cu succes la WinMentor!'];
    } else{
      $eroare = array_key_exists('error', $result) ? '[WinMentor] '.$result['error'] : '[WinMentor] '.$result['Error'];
      if($eroare == "[WinMentor] Nu ai precizat ID client"){
        $eroare = '[WinMentor] Nu ai precizat ID client - Sincronizeaza mai intai clientul, dupa care lanseaza comanda!';
      }
      return ['success' => false, 'msg' => $eroare, 'warning' => false];
    }
  }

  public function getColorsByOfferType(Request $request, $offerTypeId = null){
    if($offerTypeId == null){
      $offerTypeId = $request->input('offerTypeId');
    }
    $selectedColors = OffertypePreselectedColor::with('color')->where('offer_type_id', $offerTypeId)->groupBy('color_id')->get();
    $html_colors = '';
    if($selectedColors && count($selectedColors) > 0){
      $html_colors .= '<div class="form-group col-md-12 ">
            <label class="control-label" for="name">Culoare</label>
            <select name="selectedColor" class="form-control selectColorOfferType"><option selected disabled>Selecteaza culoarea</option>';
      foreach($selectedColors as $item){
        if($item->color){
          $html_colors .= '<option value="'.$item->attribute_id.'_'.$item->color->id.'_'.$item->color->value.'_'.$item->color->ral.'">'.$item->color->ral.'</option>';
        }
      }
      $html_colors .= '</select></div>';
    }
    return ['success' => true, 'html_colors' => $html_colors];
  }
  
  public function changeOfferStatus(Request $request){
    $order = Offer::find($request->orderId);
    $statusId = $request->statusId;
    if (!$order) {
      return ['success' => false, 'msg' => 'Comanda nu exista'];
    }
    if(!$statusId){
      return ['success' => false, 'msg' => 'Selecteaza un status!'];
    }
    $order->status = $statusId;
    $order->save();
    return ['success' => true, 'msg' => 'Statusul a fost modificat cu succes!'];
  }

}
