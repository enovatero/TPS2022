@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing').' '.$title)

@section('page_header')
    <div class="container-fluid">
        <form method="get" class="form-search form-search-master">
            <div id="search-input">
                <div class="input-group col-md-12">
                    <input type="text" class="form-control" placeholder="{{ __('voyager::generic.search') }}" name="master">
                    <button class="btn btn-info btn-lg" type="submit">
                        <i class="voyager-search"></i>
                    </button>
                </div>
            </div>
        </form>
        <h1 class="page-title">{{ $title }}</h1>
        @can('add', app($model))
            <a href="{{ route('voyager.'.$slug.'.create') }}" class="btn btn-success btn-add-new">
                <i class="voyager-plus"></i> <span>{{ __('voyager::generic.add_new') }}</span>
            </a>
        @endcan
        @can('delete', app($model))
            {{-- @include('voyager::partials.bulk-delete') --}}
        @endcan
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">

                        <div class="custom-table-filters overflow__list-1" >
                            @foreach ($columns as $column)
                                @if ($column['key'] == 'agent')
                                    <div class="filter-item">
                                        <label>
                                            <div>Filtru {{ $column['label'] }}</div>
                                            <select
                                                class="custom-table-select form-control"
                                                onchange="location.href = String('{{ url()->current().'?'.http_build_query(array_merge(request()->all(), [
                                                    'agent' => 'value'
                                                ])) }}').replace('value', this.value)"
                                            >
                                                <option value=""> - </option>
                                                @foreach (App\Models\User::where('role_id', 9)->orderBy('short_name')->pluck('short_name', 'id') as $agent_id => $agent_name)
                                                    <option
                                                        value="{{ $agent_id }}"
                                                        {{ request()->get('agent') == $agent_id ? 'selected' : '' }}
                                                    >
                                                        {{ $agent_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>
                                @elseif ($column['key'] == 'plata')
                                    <div class="filter-item">
                                        <label>
                                            <div>Filtru {{ $column['label'] }}</div>
                                            <select
                                                class="custom-table-select form-control"
                                                onchange="location.href = String('{{ url()->current().'?'.http_build_query(array_merge(request()->all(), [
                                                    'payment_type' => 'value'
                                                ])) }}').replace('value', this.value)"
                                            >
                                                <option value=""> - </option>
                                                @foreach (App\Offer::$payment_types as $pkey => $payment_type)
                                                    <option
                                                        value="{{ $pkey }}"
                                                        {{ request()->payment_type == $pkey ? 'selected' : '' }}
                                                    >
                                                        {{ $payment_type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>

                                @elseif (($column['key'] == 'p' || $column['key'] == 'pjal' || $column['key'] == 'pu') && $tileFence == 0)
                                    <div class="filter-item">
                                        <label>
                                            <div>Filtru {{ $column['label'] }}</div>
                                            <select
                                                class="custom-table-select form-control"
                                                onchange="location.href = String('{{ url()->current().'?'.http_build_query(array_merge(request()->all(), [
                                                    'attr_'.$column['key'] => 'value'
                                                ])) }}').replace('value', this.value)"
                                            >
                                                <option value=""> - </option>
                                                @foreach (App\Offer::$attr_p_values as $pkey => $pvalue)
                                                    <option
                                                        value="{{ $pkey }}"
                                                        {{ request()->get('attr_'.$column['key']) == $pkey ? 'selected' : '' }}
                                                    >
                                                        {{ $pvalue }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>

                                @elseif (($column['key'] == 'ptabla' || $column['key'] == 'pacc') && $tileFence == 1)
                                    <div class="filter-item">
                                        <label>
                                            <div>Filtru {{ $column['label'] }}</div>
                                            <select
                                                class="custom-table-select form-control"
                                                onchange="location.href = String('{{ url()->current().'?'.http_build_query(array_merge(request()->all(), [
                                                    'attr_'.$column['key'] => 'value'
                                                ])) }}').replace('value', this.value)"
                                            >
                                                <option value=""> - </option>
                                                @foreach (App\Offer::$attr_p_values_new as $pkey => $pvalue)
                                                    <option
                                                        value="{{ $pkey }}"
                                                        {{ request()->get('attr_'.$column['key']) == $pkey ? 'selected' : '' }}
                                                    >
                                                        {{ $pvalue }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>

                                @elseif ($column['key'] == 'status')
                                    <div class="filter-item">
                                        <label>
                                            <div>Filtru {{ $column['label'] }}</div>
                                            <select
                                                class="custom-table-select form-control"
                                                onchange="location.href = String('{{ url()->current().'?'.http_build_query(array_merge(request()->all(), [
                                                    'status' => 'value'
                                                ])) }}').replace('value', this.value)"
                                            >
                                                <option value=""> - </option>
                                                @foreach (App\Status::pluck('title', 'id') as $status_id => $status_title)
                                                    <option
                                                        value="{{ $status_id }}"
                                                        {{ request()->get('status') == $status_id ? 'selected' : '' }}
                                                    >
                                                        {{ ucfirst($status_title) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>

                                @elseif ($column['key'] == 'contabilitate')

                                    <div class="filter-item">
                                        <label>
                                            <div>Filtru {{ $column['label'] }}</div>
                                            <select
                                                class="custom-table-select form-control"
                                                onchange="location.href = String('{{ url()->current().'?'.http_build_query(array_merge(request()->all(), [
                                                    'billing_status' => 'value'
                                                ])) }}').replace('value', this.value)"
                                            >
                                                <option value=""> - </option>
                                                @foreach (App\Offer::$billing_statuses as $bkey => $billing_status)
                                                    <option
                                                        value="{{ $bkey }}"
                                                        {{ request()->billing_status == $bkey ? 'selected' : '' }}
                                                    >
                                                        {{ $billing_status }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>

                                @elseif ($column['key'] == 'livrare')
                                    <div class="filter-item">
                                        <label>
                                            <div>Filtru Livrare</div>
                                            <select
                                                class="custom-table-select form-control"
                                                onchange="location.href = String('{{ url()->current().'?'.http_build_query(array_merge(request()->all(), [
                                                    'delivery_type' => 'value'
                                                ])) }}').replace('value', this.value)"
                                            >
                                                <option value=""> - </option>
                                                @foreach (App\Offer::$delivery_types as $dkey => $delivery)
                                                    <option
                                                        value="{{ $dkey }}"
                                                        {{ request()->delivery_type == $dkey ? 'selected' : '' }}
                                                    >
                                                        {{ $delivery }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>

                                @elseif ($column['key'] == 'sursa')
                                    <div class="filter-item">
                                        <label>
                                            <div>Filtru {{ $column['label'] }}</div>
                                            <select
                                                class="custom-table-select form-control"
                                                onchange="location.href = String('{{ url()->current().'?'.http_build_query(array_merge(request()->all(), [
                                                    'distribuitor_id' => 'value'
                                                ])) }}').replace('value', this.value)"
                                            >
                                                <option value=""> - </option>
                                                @foreach (App\Distribuitor::pluck('title', 'id') as $did => $distribuitor_title)
                                                    <option
                                                        value="{{ $did }}"
                                                        {{ request()->distribuitor_id == $did ? 'selected' : '' }}
                                                    >
                                                        {{ $distribuitor_title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>

                                @elseif ($column['key'] == '')
                                    <div class="filter-item">
                                        <label>
                                            <div>Filtru {{ $column['label'] }}</div>
                                        </label>
                                    </div>

                                @endif
                            @endforeach
                        </div>

                        <div class="table-responsive-start">
                            {{-- acest div este folosit ca sa activez/dezactivez modul sticky --}}
                        </div>
                        <div class="table-responsive-fake" style="display:none">
                            {{-- acest div este un placeholder pentru tabel, ca sa pastrez inaltimea paginii cand intru in modul sticky --}}
                        </div>

                        {{-- @php (dump($columns)) @endphp --}}
                        {{-- @php (dump($orders)) @endphp --}}
                        {{-- @php (dump($orderGroups)) @endphp --}}

                        <div class="table-responsive">
                            @foreach ($orderGroups as $day)
                            <div class="table-day">
                                <div class="table-group-details">
                                    <div class="item">
                                        Comenzi din data:
                                        <b style="text-transform: uppercase;">{{ \Carbon\Carbon::parse($day['date'])->format('d M') }}</b>
                                    </div>
                                    <div class="item additional">
                                        @if (Auth::user()->hasPermission("offer_column_valoare")) Subtotal: <span>{{ $day['subtotal_price'] }} lei</span> @endif
                                    </div>
                                    <div class="item additional">
                                        Subtotal: <span>{{ $day['subtotal_ml'] }} ml</span>
                                    </div>
                                </div>
                                <table id="dataTable" class="table table-hover">
                                    <thead>
                                        <tr class="overflow__list-1">
                                            @foreach ($columns as $column)
                                              @if(in_array($column['key'],['oras', 'ptabla', 'pacc', 'sofer', 'masina']) && $tileFence == 0)
                                                @continue
                                              @endif
                                              @if(in_array($column['key'],['print_awb', 'awb', 'pjal', 'pu', 'p']) && $tileFence == 1)
                                                @continue
                                              @endif
                                             <th class="column_{{ $column['key'] }}" style="min-width: {{ optional($column)['width'] ?: 'auto' }}; max-width: {{ optional($column)['width'] ?: 'auto' }}">
                                                    @if ($column['order_by'])
                                                    <a href="{{ url()->current().'?'.http_build_query(array_merge(request()->all(), [
                                                        'order_by' => $column['order_by'],
                                                        'sort_order' => $orderColumn[0] == $column['order_by'] && $orderColumn[1] == 'asc' ? 'desc' : 'asc',
                                                    ])) }}">
                                                    @endif
                                                        @if($column['label'] == 'Nr Comanda')
                                                            #Comanda
                                                        @elseif($column['label'] == 'Metri liniari')
                                                            @if($tileFence == 1) MP @else ML @endif
                                                        @else
                                                        {{ $column['label'] }}
                                                        @endif

                                                        @if ($orderColumn[0] == $column['order_by'])
                                                            @if ($orderColumn[1] == 'asc')
                                                                <i class="voyager-angle-up pull-right"></i>
                                                            @else
                                                                <i class="voyager-angle-down pull-right"></i>
                                                            @endif
                                                        @endif
                                                    @if ($column['order_by'])
                                                    </a>
                                                    @endif
                                                </th>
                                            @endforeach
                                            <th class="actions text-right dt-not-orderable">
                                                {{ __('voyager::generic.actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($day['orders'] as $data)
                                        <tr  class="tr--list--off" >
                                            @foreach ($columns as $column)
                                                @if(in_array($column['key'],['oras', 'ptabla', 'pacc', 'sofer', 'masina']) && $tileFence == 0)
                                                  @continue
                                                @endif
                                                @if(in_array($column['key'],['print_awb', 'awb', 'pjal', 'pu', 'p']) && $tileFence == 1)
                                                  @continue
                                                @endif
                                                <td class="overflow__list-1 @if($column['key'] == 'status') statusTd @endif" class="column_{{ $column['key'] }}">


                                                    @if ($column['key'] == 'nr_com')
                                                        <a href="/admin/offers/{{ $data->id }}/edit">
                                                            {{ $data->numar_comanda }}
                                                        </a>
                                                        @php
                                                            $ordermessages = $data->numar_comanda != null ? \App\Http\Controllers\VoyagerOfferController::getHtmlLogMentions($data->id,5) : null;
                                                        @endphp
                                                        @if ($ordermessages != null)
                                                            <span class="tooltipMessage icon voyager-chat">
                                                                <div class="tooltip_description" style="display:none" title="Mesaje comanda">
                                                                    {!! $ordermessages !!}
                                                                </div>
                                                            </span>
                                                        @endif

                                                    @elseif ($column['key'] == 'culoare')
                                                        @php
                                                            $attributes = $data->attrs;
                                                            $colors = [];
                                                            if ($attributes && count($attributes) > 0) {
                                                                foreach ($attributes as $attr) {
                                                                    $dbAttr = \App\Attribute::find($attr->attribute_id);
                                                                    $type = $dbAttr != null ? $dbAttr->type : null;
                                                                    if ($type != null) {
                                                                        $color = \App\Color::find($attr->col_dim_id);
                                                                        if($color != null){
                                                                          $arrCol = [
                                                                              'color' => $color->value,
                                                                              'colorName' => $color->ral,
                                                                          ];
                                                                          if (!in_array($arrCol, $colors)) {
                                                                              array_push($colors, $arrCol);
                                                                          }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        @endphp
                                                        @if (count($colors) > 0)
                                                            @foreach ($colors as $key => $color)
                                                            <div class="color__color-code--cont" style="margin-bottom:3px; justify-content: flex-start; margin-left: 10px;">
                                                                <span class="color__square" style="background-color: {{ $color['color'] }}"></span>
                                                                <span class="edit__color-code" style="text-transform: uppercase;">
                                                                    {{ $color['colorName'] }}
                                                                </span>
                                                            </div>
                                                            @endforeach
                                                        @else
                                                            <div>-</div>
                                                        @endif

                                                    @elseif ($column['key'] == 'valoare')
                                                        {{ $data->total_final }}

                                                    @elseif ($column['key'] == 'tip_comanda')
                                                        @if ($tileFence == 1)
                                                            {{$data->offerTypeCustom ? $data->offerTypeCustom->title : $data->offerType->title}}
                                                        @else
                                                            {{ $data->offerType->title}}
                                                        @endif
                                                    @elseif ($column['key'] == 'sursa')
                                                        {{ $data->distribuitor ? $data->distribuitor->title : '-' }}

                                                    @elseif ($column['key'] == 'ml')
                                                        {{ $data->prod_ml }}

                                                    @elseif ($column['key'] == 'status')
                                                        @php
                                                            $statusClass = '';
                                                            if ($data->status_name) {
                                                                if ($data->status_name->title == 'noua') $statusClass = 'offer__status--green';
                                                                if ($data->status_name->title == 'refuzata') $statusClass = 'offer__status--orange';
                                                                if ($data->status_name->title == 'anulata') $statusClass = 'offer__status--yellow';
                                                                if ($data->status_name->title == 'modificata') $statusClass = 'offer__status--purple';
                                                                if ($data->status_name->title == 'finalizata') $statusClass = 'offer__status--gray';
                                                                if ($data->status_name->title == 'retur') $statusClass = 'offer__status--red';
                                                                if ($data->status_name->title == 'productie') $statusClass = 'offer__status--blue';
                                                            }
                                                        @endphp
                                                        {{-- <span class="offers__status">
                                                            <span style="text-transform: capitalize;" class="{{ $statusClass }}">
                                                                <p {!! $statusClass == '' ? 'style="color:black"' :'' !!}>
                                                                    {{ $data->status_name ? $data->status_name->title : '-' }}
                                                                </p>
                                                            </span>
                                                        </span>
                                                       --}}
                                                        <select class="offerStatusSelector">
                                                            @foreach (App\Status::pluck('title', 'id') as $status_id => $status_title)
                                                                @php
                                                                    $statusClass = '';
                                                                    if ($data->status_name) {
                                                                        if ($status_title == 'noua') $statusClass = 'offer__status--green';
                                                                        if ($status_title == 'refuzata') $statusClass = 'offer__status--red';
                                                                        if ($status_title == 'anulata') $statusClass = 'offer__status--yellow';
                                                                        if ($status_title == 'modificata') $statusClass = 'offer__status--purple';
                                                                        if ($status_title == 'finalizata') $statusClass = 'offer__status--gray';
                                                                        if ($status_title == 'retur') $statusClass = 'offer__status--red';
                                                                        if ($status_title == 'productie') $statusClass = 'offer__status--blue';
                                                                        if ($status_title == 'expediata') $statusClass = 'offer__status--yellow';
                                                                        if ($status_title == 'livrata') $statusClass = 'offer__status--orange';
                                                                    }
                                                                @endphp
                                                                <option
                                                                    value="{{$status_id}}"
                                                                    statustitle="{{$status_title}}"
                                                                    statusclass="{{$statusClass}}"
                                                                    orderid="{{$data->id}}"
                                                                    {{$data->status == $status_id ? 'selected' : ''}}
                                                                >
                                                                    {{ ucfirst($status_title) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @elseif ($column['key'] == 'livrare')
                                                        @if (isset(App\Offer::$delivery_types[$data->delivery_type]))
                                                            @if( App\Offer::$delivery_types[$data->delivery_type] == 'Fan Courier')
                                                                    Fan

                                                            @elseif( App\Offer::$delivery_types[$data->delivery_type] == 'Nemo Express')
                                                             Nemo
                                                            @elseif( App\Offer::$delivery_types[$data->delivery_type] == 'Livrare TPS' )
                                                            TPS
                                                            @elseif( App\Offer::$delivery_types[$data->delivery_type] == 'Ridicare personala' )
                                                                    ridicare
                                                            @else
                                                                    {{ App\Offer::$delivery_types[$data->delivery_type] }}
                                                            @endif
                                                        @else
                                                            -
                                                        @endif

                                                    @elseif ($column['key'] == 'tip_comanda')
                                                        {{ $data->offerType ? $data->offerType->title : '-' }}

                                                    @elseif ($column['key'] == 'client')
                                                        {{ $data->client ? $data->client->name : '-' }}

                                                    @elseif ($column['key'] == 'print_awb')
                                                        <input
                                                            type="checkbox"
                                                            class="custom-table-checkbox"
                                                            onchange="window.tableChangeCheckboxField(this, {{ $data->id }}, 'print_awb')"
                                                            {{ $data->print_awb ? 'checked' : '' }}
                                                        />

                                                    @elseif ($column['key'] == 'accesorii')
                                                        <input
                                                            type="checkbox"
                                                            class="custom-table-checkbox"
                                                            onchange="window.tableChangeCheckboxField(this, {{ $data->id }}, 'accesories')"
                                                            {{ $data->accesories ? 'checked' : '' }}
                                                        />

                                                    @elseif ($column['key'] == 'print_comanda')
                                                        <input
                                                            type="checkbox"
                                                            class="custom-table-checkbox"
                                                            onchange="window.tableChangeCheckboxField(this, {{ $data->id }}, 'listed')"
                                                            {{ $data->listed ? 'checked' : '' }}
                                                        />

                                                    @elseif ($column['key'] == 'plata')
                                                        <select
                                                            class="custom-table-select form-control"
                                                            onchange="window.tableChangeSelectField(this, {{ $data->id }}, 'payment_type')"
                                                        >
                                                            <option value=""> - </option>
                                                            @foreach (App\Offer::$payment_types as $pkey => $payment_type)
                                                                <option
                                                                    value="{{ $pkey }}"
                                                                    {{ $data->payment_type == $pkey ? 'selected' : '' }}
                                                                >
                                                                    {{ $payment_type }}
                                                                </option>
                                                            @endforeach
                                                        </select>

                                                    @elseif ($column['key'] == 'contabilitate')
                                                        <select
                                                            class="custom-table-select form-control"
                                                            onchange="window.tableChangeSelectField(this, {{ $data->id }}, 'billing_status')"
                                                        >
                                                            <option value=""> - </option>
                                                            @foreach (App\Offer::$billing_statuses as $bkey => $billing_status)
                                                                <option
                                                                    value="{{ $bkey }}"
                                                                    {{ $data->billing_status == $bkey ? 'selected' : '' }}
                                                                >
                                                                    {{ $billing_status }}
                                                                </option>
                                                            @endforeach
                                                        </select>

                                                    @elseif (($column['key'] == 'p' || $column['key'] == 'pjal' || $column['key'] == 'pu') && $tileFence == 0)
                                                        <select
                                                            class="custom-table-select form-control"
                                                            onchange="window.tableChangeSelectField(this, {{ $data->id }}, 'attr_{{ $column['key'] }}')"
                                                        >
                                                            <option value=""> - </option>
                                                            @foreach (App\Offer::$attr_p_values as $pkey => $pvalue)
                                                                <option
                                                                    value="{{ $pkey }}"
                                                                    {{ $data->{'attr_'.$column['key']} == $pkey ? 'selected' : '' }}
                                                                >
                                                                    {{ $pvalue }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @elseif (($column['key'] == 'ptabla' || $column['key'] == 'pacc') && $tileFence == 1)
                                                        <select
                                                            class="custom-table-select form-control"
                                                            onchange="window.tableChangeSelectField(this, {{ $data->id }}, 'attr_{{ $column['key'] }}')"
                                                        >
                                                            <option value=""> - </option>
                                                            @foreach (App\Offer::$attr_p_values_new as $pkey => $pvalue)
                                                                <option
                                                                    value="{{ $pkey }}"
                                                                    {{ $data->{'attr_'.$column['key']} == $pkey ? 'selected' : '' }}
                                                                >
                                                                    {{ $pvalue }}
                                                                </option>
                                                            @endforeach
                                                        </select>

                                                     @elseif ($column['key'] == 'sofer' && $tileFence == 1)
                                                        <select
                                                            class="custom-table-select form-control"
                                                            onchange="window.tableChangeSelectField(this, {{ $data->id }}, 'attr_{{ $column['key'] }}')"
                                                        >
                                                            <option value=""> - </option>
                                                            @foreach ($drivers as $driver)
                                                                <option
                                                                    value="{{ $driver->id }}"
                                                                    {{ $data->{'attr_'.$column['key']} == $driver->id ? 'selected' : '' }}
                                                                >
                                                                    {{ $driver->title }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                     @elseif ($column['key'] == 'masina' && $tileFence == 1)
                                                        <select
                                                            class="custom-table-select form-control"
                                                            onchange="window.tableChangeSelectField(this, {{ $data->id }}, 'attr_{{ $column['key'] }}')"
                                                        >
                                                            <option value=""> - </option>
                                                            @foreach ($cars as $car)
                                                                <option
                                                                    value="{{ $car->id }}"
                                                                    {{ $data->{'attr_'.$column['key']} == $car->id ? 'selected' : '' }}
                                                                >
                                                                    {{ $car->title }}
                                                                </option>
                                                            @endforeach
                                                        </select>

                                                    @elseif ($column['key'] == 'awb')
                                                        {{ $data->delivery_type == 'fan' && $data->awb_id ? $data->fanData->awb : "" }}
                                                        {{ $data->delivery_type == 'nemo' && $data->awb_id ? $data->nemoData->awb : "" }}

                                                    @elseif ($column['key'] == 'data_expediere')
                                                        {{ \Carbon\Carbon::parse($data->delivery_date)->format('d M') }}

                                                    @elseif ($column['key'] == 'agent')
                                                        {{ $data->agent ? $data->agent->name : '-' }}

                                                    @elseif ($column['key'] == 'comanda_distribuitor')
                                                        {{ $data->distribuitor_order }}

                                                    @elseif ($column['key'] == 'intarziere')
                                                        {{ $data->intarziere ?: '-' }}

                                                    @elseif ($column['key'] == 'judet')
                                                        {{ $data->delivery_address ? $data->delivery_address->state_name() : '-' }}

                                                    @elseif ($column['key'] == 'oras')
                                                        {{ $data->delivery_address ? $data->delivery_address->city_name() : '-' }}

                                                    @elseif ($column['key'] == 'telefon')
                                                        {{ $data->delivery_address ? $data->delivery_address->delivery_phone : '-' }}

                                                    @elseif ($column['key'] == 'fisiere')
                                                        @php
                                                            $htmlButtonFiles = "
                                                                <a href='/admin/generatePDF/".$data->id."' class='table-files-link' target='_blank'>
                                                                    <i class='voyager-download'></i>
                                                                    <span class='table-files-name'>Descarca oferta PDF</span>
                                                                </a>
                                                                <a href='/admin/generatePDFFisa/".$data->id."' class='table-files-link' target='_blank'>
                                                                    <i class='voyager-download'></i>
                                                                    <span class='table-files-name'>Fisa de comanda</span>
                                                                </a>
                                                            ";
                                                            if($data->delivery_type == 'fan' && $data->fanData && $data->fanData->cont_id != null && $data->fanData->awb != null){
                                                                $htmlButtonFiles .= "<a target='_blank' class='table-files-link' href='/admin/printAwb/".$data->fanData->awb."/".$data->fanData->cont_id."'>
                                                                    <i class='voyager-download'></i> <span> Descarca AWB PDF</span>
                                                                </a>";
                                                            }
                                                            if($data->delivery_type == 'nemo' && $data->nemoData && $data->nemoData->cont_id != null && $data->nemoData->awb != null){
                                                                $htmlButtonFiles .= "<a target='_blank' class='table-files-link' href='/admin/printAwbNemo/".$data->nemoData->awb."/".$data->nemoData->cont_id."/".$data->nemoData->hash."'>
                                                                    <i class='voyager-download'></i> <span> Descarca AWB PDF</span>
                                                                </a>";
                                                            }
                                                            $offerDocs = '';
                                                            if($data->offerDocs){
                                                              foreach($data->offerDocs as $doc){
                                                                $offerDocs .= "
                                                                  <div class='box-uploaded-file'>
                                                                    <a href='/admin/retrieveTempUrl/".$doc->id."' class='table-files-link' target='_blank'>
                                                                        <i class='voyager-download'></i>
                                                                        <span class='table-files-name'>".$doc->file_name."</span>
                                                                    </a>
                                                                    <span class='voyager-trash btnDeleteFile'><div class='trick-offer-doc-id'>".$doc->id."</div></span>
                                                                  </div>
                                                                ";
                                                              }
                                                            }
                                                            $htmlButtonFiles .= "<div class='container-uploaded-files'>".$offerDocs."</div>";
                                                        @endphp
                                                        <button class="btn btn-xs" type="button" data-toggle="popover" data-placement="top" data-content="
                                                            <div class='table-files-container'>
                                                                {{$htmlButtonFiles}}
                                                                <a class='table-files-link btnUploadFiles' target='_blank'>
                                                                    <div class='trick-offer-id'>{{$data->id}}</div>
                                                                    <i class='voyager-plus' style='margin-right: 10px;'></i>
                                                                    <span class='table-files-name'>Incarca fisiere</span>
                                                                </a>

                                                            </div>
                                                        ">
                                                            Fisiere
                                                        </button>

                                                    @elseif ($column['key'] == '')

                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="no-sort no-click bread-actions btn__hide--cont">
                                                @can('edit', app($model))
                                                    <a style="border: none !important;border-left: none !important;min-width: 1rem !important;max-width: 1rem !important;" href="/admin/offers/{{ $data->id }}/edit" title="Edit" class="btn btn-sm btn-primary pull-right edit btn__display--none1 tooltip__msg-list-2">
                                                        <i class="voyager-edit"></i>
                                                        <span class="hidden-xs hidden-sm">Edit</span>
                                                    </a>
                                                @endcan
                                                @if ($data->numar_comanda != null)
                                                <a style="border: none !important;border-left: none !important;min-width: 1rem !important;max-width: 1rem !important;" title="Trimite SMS" class="btnSendSms toolTipMsg btn__display--none tooltip__msg-list-1" order_id="{{$data->id}}">
                                                    <i class="voyager-telephone"></i>
                                                        <div class="tooltip_description" style="display:none" title="Mesaje comanda">
                                                        </div>
                                                </a>
                                                @endif
                                            </td>

                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endforeach
                        </div>

                        <div class="browse-footer-table" style="margin-top: 20px;">
                            <div class="pull-left">
                                <div role="status" class="show-res" aria-live="polite">
                                    {{ trans_choice('voyager::generic.showing_entries', $orders->total(), [
                                        'from' => $orders->firstItem(),
                                        'to' => $orders->lastItem(),
                                        'all' => $orders->total(),
                                    ]) }}
                                </div>
                            </div>
                            <div class="pull-right">
                                {{ $orders->appends([
                                    // 's' => $search->value,
                                    // 'filter' => $search->filter,
                                    // 'key' => $search->key,
                                    'order_by' => $orderColumn[0],
                                    'sort_order' => $orderColumn[1],
                                    'per_page' => request()->get('per_page'),
                                ])->links() }}
                            </div>
                            <div class="float-right" style="margin-top: 5px; margin-right: 20px;">
                                <select class="form-control" style="width: 68px; height: 40px;" onchange="location.href = String('{{ url()->current().'?'.http_build_query(array_merge(request()->all(), [
                                    'per_page' => 'perpagenum'
                                ])) }}').replace('perpagenum', this.value)">
                                    <option value="10" {{ request()->get('per_page') == 10 ? 'selected':'' }}>10</option>
                                    <option value="25" {{ request()->get('per_page') == 25 ? 'selected':'' }}>25</option>
                                    <option value="50" {{ request()->get('per_page') == 50 ? 'selected':'' }}>50</option>
                                    <option value="100" {{ request()->get('per_page') == 100 ? 'selected':'' }}>100</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <form class="upload-order-files" method="POST" action="uploadDocuments" enctype="multipart/form-data">
      {{csrf_field()}}
      <input type="file" style="display: none !important" name="files[]" class="input-upload-files" multiple>
      <input type="hidden" style="display: none !important" name="offer_id">
    </form>
@stop

@section('css')
    <link rel="stylesheet" href="{{ asset('/css/jquery.tooltip/jquery.tooltip.css') }}">
    <style>
        /* table scrollabil orizontal */
        .table-responsive {
            overflow: auto;
        }
        .table-responsive .table {
            width: auto !important;
            max-width: none !important;
            min-width: 100%;
        }

        .custom-table-filters {
            display: flex;
            flex-direction: row;
            padding: 2px 10px;
        }
        .custom-table-filters .filter-item {
            margin-right: 10px;
        }

        /* header tabel sticky ! :D */
        .table-responsive-fake {
            height: 100vh;
            width: 100%;
        }
        .table-fixed-header {
            background: #f8fafc;
            position: fixed !important;
            top: 60px;
            right: 15px;
            z-index: 1000;
            display: block;
            height: calc(100vh - 60px) !important;
            overflow: auto;
            transition: all .5s cubic-bezier(.19,1,.22,1);
            left: 76px;
            width: calc(100% - 76px - 30px);
        }
        .app-container.expanded .table-fixed-header {
            left: 266px !important;
            width: calc(100% - 266px - 30px) !important;
        }
        .table-responsive .table {
            margin: 0 !important;
        }
        .table-responsive .thead-sticky {
            display: none;
        }
        .table-fixed-header thead:not(.thead-sticky) {
            display: block;
            width: 0;
        }
        .table-fixed-header .thead-sticky {
            display: table-header-group !important;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
        }
        .table-fixed-header .thead-sticky th {
            border-bottom: 1px solid #ddd !important;
        }
        .table-fixed-header tbody {
            height: calc(100vh - 60px);
            overflow-y: auto;
            overflow-x: hidden;
            display: block;
            background: #fff;
        }

        .table-group-details {
            width: 100%;
            text-align: left;
            font-weight: 400;
            padding: 14px 10px;
            padding-bottom: 6px;
            cursor: default;
            color: #000;
        }
        .table-group-details b {
            font-size: 16px;
            color: #6b76d8;
            font-weight: 600;
        }
        .table-group-details .item {
            display: inline-block;
        }
        .table-group-details .item.additional {
            margin-left: 14px;
        }
        .custom-table-checkbox {
            transform: scale(1.2);
        }
        .table-files-container {
            padding: 0;
        }
        .table-files-container .table-files-link {
            display: block;
        }

        .voyager .table {
            border-top: 1px solid #ddd !important;
            border-bottom: 1px solid #ddd !important;
        }
        .voyager .table thead th a {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            flex-wrap: nowrap;
        }
        .voyager .table > thead > tr > th,
        .voyager .table tr.thead > th {
            vertical-align: middle;
        }
        .voyager .table > thead > tr > th .pull-right,
        .voyager .table tr.thead > th .pull-right {
            min-width: auto !important;
        }
        .dt-not-orderable {
            display: table-cell !important;
            text-align: center !important;
        }
        .voyager .table tr.thead > th:not(:first-child) {
            /* color: #6b76d8;
            text-align: center; */
        }
        .voyager .table tr.thead > th {
            background: #f8fafc;
        }
        .voyager tbody > tr td:nth-child(2) {
            box-shadow: none;
        }
        .voyager tbody > tr > td:not(:last-child) {
            border-right: 1px solid #cecece !important;
            padding-left: 4px !important;
            padding-right: 4px !important;
        }
        .voyager tbody .offers__status {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .voyager .table tbody > tr > td.bread-actions {
            border-right: none !important;
        }
    </style>
@stop

@section('javascript')
    <script src="{{ asset('/js/jquery.tooltip.js') }}"></script>
    <script>
        $(document).ready(function() {

            $(".tooltipMessage").tooltip();




            $('[data-toggle="popover"]').popover({
                container: 'body',
                html: true,
            });

          $(document).on("click", ".btnUploadFiles", function(){
            var offer_id = $(this).find(".trick-offer-id").text();
            $(".upload-order-files").find("input[name=offer_id]").val(offer_id);
            $(".input-upload-files").trigger("click");
          });
          $(document).on("change", ".input-upload-files", function() {
              var names = [];
              for (var i = 0; i < $(this).get(0).files.length; ++i) {
                if($(this).get(0).files[i] && $(this).get(0).files[i].name != ''){
                  names.push($(this).get(0).files[i].name);
                }
              }
              if(names.length > 0){
                $(".upload-order-files").trigger("submit");
              } else{
                $(".upload-order-files").trigger("reset");
              }
          });
          $(document).on('submit', '.upload-order-files', function () {
            var textBefore = $(".btnUploadFiles").find(".table-files-name").text();
            $(".btnUploadFiles").find(".table-files-name").text("Se incarca...");
            var formData = new FormData($('.upload-order-files')[0]);
            $(".popover").css("cursor", "wait");
            $(".btnUploadFiles").css("cursor", "wait");
            event.preventDefault();
            $.ajax({
              method: 'POST',
              url: $(this).attr('action'),
              data: formData,
              context: this,
              async: true,
              cache: false,
              dataType: 'json',
              processData: false,
              contentType: false,
            }).done(function (res) {
              if (res.success == true) {
                toastr.success(res.msg, 'Success');
                if(res.uploaded_files.length > 0){
                  var html_append = '';
                  for(var index in res.uploaded_files){
                    html_append += `<div class='box-uploaded-file'>
                      <a href='/admin/retrieveTempUrl/${res.uploaded_files[index].doc_id}' class='table-files-link' target='_blank'>
                          <i class='voyager-download'></i>
                          <span class='table-files-name'>${res.uploaded_files[index].file_name}</span>
                      </a>
                      <span class='voyager-trash btnDeleteFile'><div class='trick-offer-doc-id'>${res.uploaded_files[index].doc_id}</div></span>
                    </div>`;
                  }
                  $(".container-uploaded-files").append(html_append);
                }
              } else {
                toastr.error(res.msg, 'Error');
              }
            $(".popover").css("cursor", "pointer");
            $(".btnUploadFiles").css("cursor", "pointer");
            $(".btnUploadFiles").find(".table-files-name").text(textBefore);
            })
            return false;
          });
          function formatState (state) {
              if (!state.id) {
                return state.text;
              }
              var statusClass = $(state.element).attr('statusclass');
              var statusTitle = $(state.element).attr('statustitle');

              var $state = $(
                `
                <span style="text-transform: capitalize;" class="${statusClass}">
                  <p>${statusTitle}</p>
                </span>
                `
              );
              return $state;
          };
          $(".offerStatusSelector").select2({templateSelection: formatState}).parent().find(".select2-selection__rendered").addClass('offers__status');
          $(".offerStatusSelector").on('select2:select', function (e) {
            var data = e.params.data;
            var statusId = data.id;
            var orderId = $(data.element.outerHTML).attr('orderid');
            // ajax pentru salvarea datelor in DB
            $.ajax({
                  method: 'POST',
                  url: '/admin/changeOfferStatus',//remove this address on POST message after i get all the address data
                  data: {
                    orderId: orderId,
                    statusId: statusId,
                  },
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(resp) {
                  if(resp.success){
                    toastr.success(resp.msg);
                  } else{
                    toastr.error(resp.msg);
                  }
              })
              .fail(function(xhr, status, error) {
                  if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                      .indexOf("CSRF token mismatch") >= 0) {
                      window.location.reload();
                  }
              });
              return true;
          });
          $(document).on("click", ".btnDeleteFile", function(){
              var offer_doc_id = $(this).find(".trick-offer-doc-id").text();
              var vthis = this;
              $(".popover").css("cursor", "wait");
              $(".btnUploadFiles").css("cursor", "wait");
              $.ajax({
                  method: 'POST',
                  url: '/admin/deleteOfferDoc',//remove this address on POST message after i get all the address data
                  data: {
                    offer_doc_id: offer_doc_id,
                  },
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(resp) {
                  if(resp.success){
                    $(vthis).parent().remove();
                    toastr.success(resp.msg);
                  } else{
                    toastr.error(resp.msg);
                  }
                  $(".popover").css("cursor", "pointer");
                  $(".btnUploadFiles").css("cursor", "pointer");
              })
              .fail(function(xhr, status, error) {
                  if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                      .indexOf("CSRF token mismatch") >= 0) {
                      window.location.reload();
                  }
              });
              return true;
          });
        });
    </script>

    <script>
        window.tableChangeCheckboxField = function (el, id, field) {
            $.ajax({
                method: 'POST',
                url: '/admin/comenzi-edit-field',
                data: {
                    _token: $("meta[name=csrf-token]").attr("content"),
                    id: id,
                    field: field,
                    value: el.checked ? 1 : 0,
                },
                dataType: 'json'
            }).done(function(res) {
                if (res.success == false) {
                    toastr.error(res.error, 'Eroare');
                } else {
                    $(el).prop('checked', res.newValue == '1' ? true : false);
                }
            });
        };
        window.tableChangeSelectField = function (el, id, field) {
            $.ajax({
                method: 'POST',
                url: '/admin/comenzi-edit-field',
                data: {
                    _token: $("meta[name=csrf-token]").attr("content"),
                    id: id,
                    field: field,
                    value: el.value,
                },
                dataType: 'json'
            }).done(function(res) {
                if (res.success == false) {
                    toastr.error(res.error, 'Eroare');
                } else {
                    $(el).val(res.newValue);
                }
            });
        };
        $(document).ready(function () {

            // table header sticky
            // $(document).on('scroll', function () {
            //     var isFixed = $('.table-responsive').hasClass('table-fixed-header');
            //     var boundingClient = $('.table-responsive-start').get(0).getBoundingClientRect();
            //     var shouldBeFixed = (boundingClient.top - 60) <= 0;
            //     if (shouldBeFixed && !isFixed) {
            //         $('.table-responsive').addClass('table-fixed-header');
            //         $('.table-responsive-fake').show(); // placeholder ca sa pastrez inaltimea paginii
            //         var theadHeight = $('.table-fixed-header thead').height();
            //         $('.table-fixed-header tbody').css('height', 'calc(100vh - 77px - '+ theadHeight +'px)');
            //     }
            //     if (!shouldBeFixed && isFixed) {
            //         $('.table-responsive').removeClass('table-fixed-header');
            //         $('.table-responsive-fake').hide(); // placeholder ca sa pastrez inaltimea paginii
            //     }
            // });

        });
    </script>
@stop
