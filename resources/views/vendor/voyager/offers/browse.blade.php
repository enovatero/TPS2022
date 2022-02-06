@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing').' '.$dataType->getTranslatedAttribute('display_name_plural'))

@section('page_header')
    <div class="container-fluid">
        @if ($isServerSide)
            <form method="get" class="form-search form-search-master">
                <div id="search-input">
                    <div class="input-group col-md-12">
                        <input type="text" class="form-control" placeholder="{{ __('voyager::generic.search') }}"
                               name="master" value="{{request()->get('master')}}">
                        <button class="btn btn-info btn-lg" type="submit">
                            <i class="voyager-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        @endif
        <h1 class="page-title">
            <i class="{{ $dataType->icon }}"></i>
            {{ $dataType->getTranslatedAttribute('display_name_plural') }}
            @can('add', app($dataType->model_name))
                <div style="float: right">
                <a href="{{ route('voyager.'.$dataType->slug.'.create') }}" class="btn btn-success btn-add-new">
                    <i class="voyager-plus"></i> <span>{{ __('tps.add_new_offer') }}</span>
                </a>
                </div>
                <div style="clear: both"></div>
            @endcan
        </h1>

        @can('delete', app($dataType->model_name))
            {{-- @include('voyager::partials.bulk-delete') --}}
        @endcan
        @can('edit', app($dataType->model_name))
            {{--
            @if(!empty($dataType->order_column) && !empty($dataType->order_display_column))
                <a href="{{ route('voyager.'.$dataType->slug.'.order') }}" class="btn btn-primary btn-add-new btn__lista-off">
                    <i class="voyager-list"></i> <span>{{ __('voyager::bread.order') }}</span>
                </a>
            @endif
            --}}
        @endcan
        @can('delete', app($dataType->model_name))
            @if($usesSoftDeletes)
                <input type="checkbox" @if ($showSoftDeleted) checked @endif id="show_soft_deletes" data-toggle="toggle"
                       data-on="{{ __('voyager::bread.soft_deletes_off') }}"
                       data-off="{{ __('voyager::bread.soft_deletes_on') }}">
            @endif
        @endcan
        @foreach($actions as $action)
            @if (method_exists($action, 'massAction'))
                @include('voyager::bread.partials.actions', ['action' => $action, 'data' => null])
            @endif
        @endforeach
        @include('voyager::multilingual.language-selector')
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body" style="padding-top: 0">
                        <div class="table-responsive-start">
                            {{-- acest div este folosit ca sa activez/dezactivez modul sticky --}}
                        </div>
                        <div class="table-responsive-fake" style="display:none">
                            {{-- acest div este un placeholder pentru tabel, ca sa pastrez inaltimea paginii cand intru in modul sticky --}}
                        </div>
                        <div class="table-responsive">
                            <table id="dataTable" class="table table-hover">
                                <thead style="display: none">
                                <tr class="tr__delete-col">
                                    @if($showCheckboxColumn)
                                        <th class="dt-not-orderable">
                                            <input type="checkbox" class="select_all">
                                        </th>
                                    @endif
                                    @foreach($dataType->browseRows as $row)
                                        <th>
                                            @if($row->display_name == 'Serie' || $row->display_name == 'Print Awb')
                                            @else
                                                @if ($isServerSide && in_array($row->field, $sortableColumns))
                                                    <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                                        @endif
                                                        {{ $row->getTranslatedAttribute('display_name') }}
                                                        @if ($isServerSide)
                                                            @if ($row->isCurrentSortField($orderBy))
                                                                @if ($sortOrder == 'asc')
                                                                    <i class="voyager-angle-up pull-right"></i>
                                                                @else
                                                                    <i class="voyager-angle-down pull-right"></i>
                                                                @endif
                                                            @endif
                                                    </a>
                                                @endif
                                            @endif
                                        </th>
                                    @endforeach
                                    <th class="actions text-right dt-not-orderable">{{ __('voyager::generic.actions') }}</th>
                                </tr>
                                </thead>
                                <thead class="thead-sticky" style="display: none">
                                <tr>
                                    @if($showCheckboxColumn)
                                        <th class="dt-not-orderable">
                                            <input type="checkbox" class="select_all">
                                        </th>
                                    @endif
                                    @foreach($dataType->browseRows as $row)
                                        <th>

                                            @if ($isServerSide && in_array($row->field, $sortableColumns))
                                                <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                                    @endif
                                                    {{ $row->getTranslatedAttribute('display_name') }}
                                                    @if ($isServerSide)
                                                        @if ($row->isCurrentSortField($orderBy))
                                                            @if ($sortOrder == 'asc')
                                                                <i class="voyager-angle-up pull-right"></i>
                                                            @else
                                                                <i class="voyager-angle-down pull-right"></i>
                                                            @endif
                                                        @endif
                                                </a>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($dataTypeContent as $data)
                                    {{-- @if (request()->ip() == '89.35.129.44') --}}
                                    {{-- aici afisez ziua in care au fost facute urmatoarele intrari --}}
                                    @php
                                        if (!isset($GLOBALS['offerDataUltimiiIntrari'])) {
                                            $GLOBALS['offerDataUltimiiIntrari'] = null;
                                        }
                                        $showDateGroupRow = false;
                                        $subtotalPriceDay = 0;
                                        $subtotalMlDay = 0;
                                        if ($data->offer_date != $GLOBALS['offerDataUltimiiIntrari']) {
                                            $GLOBALS['offerDataUltimiiIntrari'] = $data->offer_date;
                                            $showDateGroupRow = true;
                                        }
                                        if ($showDateGroupRow) {
                                            // calc subtotal price
                                            // asta este cea mai eficienta metoda, nu face query, face suma din comenzile
                                            // pe ziua selectata care sunt afisate pe pagina curenta.
                                            // daca sunt alte comenzi pe aceeasi zi pe urmatoare pagina, nu le ia in calcul.
                                            // $subtotalPriceDay = $dataTypeContent->where('offer_date', $data->offer_date)->sum('total_final');

                                            // asta e metoda mai corecta, dar face query-uri in plus
                                            $subtotalPriceDay = round(app($dataType->model_name)->where('offer_date', $data->offer_date)->sum('total_final'), 2);

                                            // calc subtotal ml - metru linear
                                            foreach ($dataTypeContent->where('offer_date', $data->offer_date)->all() as $dayOffer) {
                                                foreach ($dayOffer->products()->with('getParent')->get() as $prod) {
                                                    if ($prod->qty > 0 && $prod->getParent->um == 8) {
                                                        $subtotalMlDay += $prod->qty;
                                                    }
                                                    if ($prod->qty > 0 && $prod->getParent->um == 1 && $prod->getParent->dimension > 0) {
                                                        $subtotalMlDay += $prod->qty * $prod->getParent->dimension;
                                                    }
                                                }
                                            }
                                        }
                                    @endphp
                                    @if ($showDateGroupRow)
                                        <tr class="table-group-header">
                                            <td colspan="{{ count($dataType->browseRows) + ($showCheckboxColumn ? 2 : 1) }}">
                                                <div class="table-group-details">
                                                    <div class="item">
                                                        {{ $is_order_page ? 'Comenzi' : 'Oferte' }} din data:
                                                        <b>{{ $data->offer_date }}</b>
                                                    </div>
                                                    <div class="item additional">
                                                        @if (Auth::user()->hasPermission("offer_column_valoare"))
                                                            Subtotal: <span>{{ $subtotalPriceDay }} lei</span> @endif
                                                    </div>
                                                    <div class="item additional">
                                                        Subtotal: <span>{{ $subtotalMlDay }} ml</span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr class="thead tr__delete-col">
                                            @if($showCheckboxColumn)
                                                <th class="dt-not-orderable"></th>
                                            @endif
                                            @foreach($dataType->browseRows as $row)
                                                <th>
                                                    @if($row->display_name == 'Serie' || $row->display_name == 'Print Awb')
                                                    @else
                                                        @if ($isServerSide && in_array($row->field, $sortableColumns))
                                                            <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                                                @endif
                                                                {{ $row->getTranslatedAttribute('display_name') }}
                                                                @if ($isServerSide)
                                                                    @if ($row->isCurrentSortField($orderBy))
                                                                        @if ($sortOrder == 'asc')
                                                                            <i class="voyager-angle-up pull-right"></i>
                                                                        @else
                                                                            <i class="voyager-angle-down pull-right"></i>
                                                                        @endif
                                                                    @endif
                                                            </a>
                                                        @endif
                                                    @endif
                                                </th>
                                            @endforeach
                                            <th class="actions text-right dt-not-orderable">{{ __('voyager::generic.actions') }}</th>
                                        </tr>
                                    @endif
                                    {{-- @endif --}}
                                    <tr class="tr__list--off">
                                        @if($showCheckboxColumn)
                                            <td>
                                                <input type="checkbox" name="row_id" id="checkbox_{{ $data->getKey() }}"
                                                       value="{{ $data->getKey() }}">
                                            </td>
                                        @endif
                                        @foreach($dataType->browseRows as $row)
                                            @php
                                                if ($data->{$row->field.'_browse'}) {
                                                    $data->{$row->field} = $data->{$row->field.'_browse'};
                                                }
                                            @endphp
                                            <td class="offer__list--td"
                                                @if($row->field == "numar_comanda" || $row->field == "serie") style="width: 25px" @endif>
                                                @if($row->display_name == 'Serie' || $row->display_name == 'Print Awb')
                                                @else
                                                    @if (isset($row->details->view))
                                                        @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $data->{$row->field}, 'action' => 'browse', 'view' => 'browse', 'options' => $row->details])
                                                    @elseif($row->type == 'image')
                                                        <img
                                                            src="@if( !filter_var($data->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $data->{$row->field} ) }}@else{{ $data->{$row->field} }}@endif"
                                                            style="width:100px">
                                                    @elseif($row->type == 'relationship')
                                                        @if($row->field == "offer_belongsto_status_relationship")
                                                            <span class="offers__status">
                                                      @php
                                                          $dataStatus = \App\Status::find($data->status);
                                                          if (!$dataStatus) {
                                                              $dataStatus = new stdClass();
                                                              $dataStatus->bg_color = null;
                                                              $dataStatus->text_color = null;
                                                          }
                                                          //$dataStatus = $dataStatus == null ? 'No results' : $dataStatus->title;
                                                      @endphp
                                                            <span
                                                                style="text-transform: uppercase; font-weight: bold; padding: 10px !important; background-color: {{$dataStatus->bg_color ?: "lightgray"}}; color: {{$dataStatus->text_color ?: "black"}}">
                                                              @include('voyager::formfields.relationship', ['view' => 'browse','options' => $row->details])
                                                            </span>
                                                    </span>
                                                        @else
                                                            @include('voyager::formfields.relationship', ['view' => 'browse','options' => $row->details])
                                                        @endif
                                                    @elseif($row->type == 'select_multiple')
                                                        @if(property_exists($row->details, 'relationship'))

                                                            @foreach($data->{$row->field} as $item)
                                                                {{ $item->{$row->field} }}
                                                            @endforeach

                                                        @elseif(property_exists($row->details, 'options'))
                                                            @if (!empty(json_decode($data->{$row->field})))
                                                                @foreach(json_decode($data->{$row->field}) as $item)
                                                                    @if (@$row->details->options->{$item})
                                                                        {{ $row->details->options->{$item} . (!$loop->last ? ', ' : '') }}
                                                                    @endif
                                                                @endforeach
                                                            @else
                                                                {{ __('voyager::generic.none') }}
                                                            @endif
                                                        @endif

                                                    @elseif($row->type == 'multiple_checkbox' && property_exists($row->details, 'options'))
                                                        @if (@count(json_decode($data->{$row->field})) > 0)
                                                            @foreach(json_decode($data->{$row->field}) as $item)
                                                                @if (@$row->details->options->{$item})
                                                                    {{ $row->details->options->{$item} . (!$loop->last ? ', ' : '') }}
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            {{ __('voyager::generic.none') }}
                                                        @endif

                                                    @elseif(($row->type == 'select_dropdown' || $row->type == 'radio_btn') && property_exists($row->details, 'options'))
                                                        @if($row->field == "delivery_type")
                                                            @if($data->delivery_type == null)
                                                                -
                                                            @else
                                                                {!! $row->details->options->{$data->{$row->field}} ?? '' !!}
                                                            @endif
                                                        @else
                                                            {!! $row->details->options->{$data->{$row->field}} ?? '' !!}
                                                        @endif
                                                    @elseif($row->type == 'date' || $row->type == 'timestamp')
                                                        @if ( property_exists($row->details, 'format') && !is_null($data->{$row->field}) )
                                                            {{ \Carbon\Carbon::parse($data->{$row->field})->formatLocalized($row->details->format) }}
                                                        @else
                                                            {{ $data->{$row->field} }}
                                                        @endif
                                                    @elseif($row->type == 'checkbox')
                                                        @if(property_exists($row->details, 'on') && property_exists($row->details, 'off'))
                                                            @if($data->{$row->field})
                                                                <span
                                                                    class="label label-info">{{ $row->details->on }}</span>
                                                            @else
                                                                <span
                                                                    class="label label-primary">{{ $row->details->off }}</span>
                                                            @endif
                                                        @else
                                                            {{ $data->{$row->field} }}
                                                        @endif
                                                    @elseif($row->type == 'color')
                                                        <span class="badge badge-lg"
                                                              style="background-color: {{ $data->{$row->field} }}">{{ $data->{$row->field} }}</span>
                                                    @elseif($row->type == 'text')
                                                        @include('voyager::multilingual.input-hidden-bread-browse')
                                                        @if($row->field == "serie" || $row->field == "id" || $row->display_name == 'Print Awb')
                                                            <a href="/admin/offers/{{$data->id}}/edit">{{ mb_strlen( $data->{$row->field} ) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field} }}</a>
                                                            @php
                                                                $ordermessages = $data->id != null ? \App\Http\Controllers\VoyagerOfferController::getHtmlLogMentions($data->id) : null;
                                                            @endphp
                                                            @if($ordermessages != null)
                                                                <span class="tooltipMessage icon voyager-chat">
                                                          <div class="tooltip_description" style="display:none"
                                                               title="Mesaje comanda">
                                                            {!! $ordermessages !!}
                                                          </div>
                                                        </span>
                                                            @endif
                                                        @else
                                                            @if($row->field == "numar_comanda")
                                                                <div>{{ $data->numar_comanda != null ? $data->numar_comanda : '-' }}</div>
                                                            @else
                                                                @if($row->field == "attributes")
                                                                    @php
                                                                        $attributes = json_decode($data->attributes, true);
                                                                        $colors = [];
                                                                        if($attributes && count($attributes) > 0){
                                                                          foreach($attributes as $attr){
                                                                            $elems = explode("_", $attr);
                                                                            $isColor = count($elems) == 3 ? true : false;
                                                                            if($isColor){
                                                                              $colorCode = $elems[1];
                                                                              $colorName = $elems[2];
                                                                              $arrCol = [
                                                                                'color' => $colorCode,
                                                                                'colorName' => $colorName
                                                                              ];
                                                                              if(!in_array($arrCol, $colors)){
                                                                                array_push($colors, $arrCol);
                                                                              }
                                                                            }
                                                                          }
                                                                        }
                                                                    @endphp
                                                                    @if(count($colors) > 0)
                                                                        @foreach($colors as $key => $color)
                                                                            <div class="color__color-code--cont"
                                                                                 style="margin-bottom:3px; justify-content: flex-start; margin-left: 10px;">
                                                                                <span class="color__square"
                                                                                      style="background-color: {{ $color['color'] }}"></span>
                                                                                <span class="edit__color-code"
                                                                                      style="text-transform: uppercase;">{{ $color['colorName'] }} </span>
                                                                            </div>
                                                                        @endforeach
                                                                    @else
                                                                        <div>-</div>
                                                                    @endif
                                                                @else
                                                                    <div>{{ mb_strlen( $data->{$row->field} ) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field} }}</div>
                                                                @endif
                                                            @endif
                                                        @endif
                                                    @elseif($row->type == 'text_area')
                                                        @include('voyager::multilingual.input-hidden-bread-browse')
                                                        <div>{{ mb_strlen( $data->{$row->field} ) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field} }}</div>
                                                    @elseif($row->type == 'file' && !empty($data->{$row->field}) )
                                                        @include('voyager::multilingual.input-hidden-bread-browse')
                                                        @if(json_decode($data->{$row->field}) !== null)
                                                            @foreach(json_decode($data->{$row->field}) as $file)
                                                                <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}"
                                                                   target="_blank">
                                                                    {{ $file->original_name ?: '' }}
                                                                </a>
                                                                <br/>
                                                            @endforeach
                                                        @else
                                                            <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($data->{$row->field}) }}"
                                                               target="_blank">
                                                                Download
                                                            </a>
                                                        @endif
                                                    @elseif($row->type == 'rich_text_box')
                                                        @include('voyager::multilingual.input-hidden-bread-browse')
                                                        <div>{{ mb_strlen( strip_tags($data->{$row->field}, '<b><i><u>') ) > 200 ? mb_substr(strip_tags($data->{$row->field}, '<b><i><u>'), 0, 200) . ' ...' : strip_tags($data->{$row->field}, '<b><i><u>') }}</div>
                                                    @elseif($row->type == 'coordinates')
                                                        @include('voyager::partials.coordinates-static-image')
                                                    @elseif($row->type == 'multiple_images')
                                                        @php $images = json_decode($data->{$row->field}); @endphp
                                                        @if($images)
                                                            @php $images = array_slice($images, 0, 3); @endphp
                                                            @foreach($images as $image)
                                                                <img
                                                                    src="@if( !filter_var($image, FILTER_VALIDATE_URL)){{ Voyager::image( $image ) }}@else{{ $image }}@endif"
                                                                    style="width:50px">
                                                            @endforeach
                                                        @endif
                                                    @elseif($row->type == 'media_picker')
                                                        @php
                                                            if (is_array($data->{$row->field})) {
                                                                $files = $data->{$row->field};
                                                            } else {
                                                                $files = json_decode($data->{$row->field});
                                                            }
                                                        @endphp
                                                        @if ($files)
                                                            @if (property_exists($row->details, 'show_as_images') && $row->details->show_as_images)
                                                                @foreach (array_slice($files, 0, 3) as $file)
                                                                    <img
                                                                        src="@if( !filter_var($file, FILTER_VALIDATE_URL)){{ Voyager::image( $file ) }}@else{{ $file }}@endif"
                                                                        style="width:50px">
                                                                @endforeach
                                                            @else
                                                                <ul>
                                                                    @foreach (array_slice($files, 0, 3) as $file)
                                                                        <li>{{ $file }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                            @if (count($files) > 3)
                                                                {{ __('voyager::media.files_more', ['count' => (count($files) - 3)]) }}
                                                            @endif
                                                        @elseif (is_array($files) && count($files) == 0)
                                                            {{ trans_choice('voyager::media.files', 0) }}
                                                        @elseif ($data->{$row->field} != '')
                                                            @if (property_exists($row->details, 'show_as_images') && $row->details->show_as_images)
                                                                <img
                                                                    src="@if( !filter_var($data->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $data->{$row->field} ) }}@else{{ $data->{$row->field} }}@endif"
                                                                    style="width:50px">
                                                            @else
                                                                {{ $data->{$row->field} }}
                                                            @endif
                                                        @else
                                                            {{ trans_choice('voyager::media.files', 0) }}
                                                        @endif
                                                    @else
                                                        @include('voyager::multilingual.input-hidden-bread-browse')
                                                        <span>{{ $data->{$row->field} }}</span>
                                                    @endif
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="no-sort no-click bread-actions">
                                            @foreach($actions as $action)
                                                @if (strpos(get_class($action), 'Delete') !== false || strpos(get_class($action), 'View') !== false)
                                                    @continue
                                                @endif
                                                <div class="class__btn-sh">
                                                    @if (!method_exists($action, 'massAction'))
                                                        @include('voyager::bread.partials.actions', ['action' => $action])
                                                    @endif
                                                </div>

                                            @endforeach
                                            @if($data->numar_comanda != null)
                                            <!-- <a title="Trimite SMS" class="btn btn-success btn-add-new btnSendSms btn__display--none" order_id="{{$data->id}}"> -->
                                                <a style="border: none !important;border-left: none !important;min-width: 1rem !important;max-width: 1rem !important;"
                                                   title="Trimite SMS" class="btnSendSms toolTipMsg btn__display--none"
                                                   order_id="{{$data->id}}">
                                                    <i class="voyager-telephone"></i>
                                                    <div class="tooltip_description" style="display:none"
                                                         title="Mesaje comanda">
                                                    </div>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($isServerSide)
                            <div class="pull-left">
                                <div role="status" class="show-res" aria-live="polite">{{ trans_choice(
                                    'voyager::generic.showing_entries', $dataTypeContent->total(), [
                                        'from' => $dataTypeContent->firstItem(),
                                        'to' => $dataTypeContent->lastItem(),
                                        'all' => $dataTypeContent->total()
                                    ]) }}</div>
                            </div>
                            <div class="pull-right">
                                {{ $dataTypeContent->appends([
                                    's' => $search->value,
                                    'filter' => $search->filter,
                                    'key' => $search->key,
                                    'order_by' => $orderBy,
                                    'sort_order' => $sortOrder,
                                    'showSoftDeleted' => $showSoftDeleted,
                                ])->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i
                            class="voyager-trash"></i> {{ __('voyager::generic.delete_question') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}
                        ?</h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_form" method="POST">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm"
                               value="{{ __('voyager::generic.delete_confirm') }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right"
                            data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
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
            transition: all .5s cubic-bezier(.19, 1, .22, 1);
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
        $(document).ready(function () {
            $(".tooltipMessage").tooltip();
        });
    </script>
    <!-- DataTables -->
    @if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
        <script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
    @endif
    <script>
        $(document).ready(function () {
            $(".toolTipMsg").tooltip();

            // table header sticky
//             $(document).on('scroll', function () {
//                 var isFixed = $('.table-responsive').hasClass('table-fixed-header');
//                 var boundingClient = $('.table-responsive-start').get(0).getBoundingClientRect();
//                 var shouldBeFixed = (boundingClient.top - 60) <= 0;
//                 if (shouldBeFixed && !isFixed) {
//                     $('.table-responsive').addClass('table-fixed-header');
//                     $('.table-responsive-fake').show(); // placeholder ca sa pastrez inaltimea paginii
//                     var theadHeight = $('.table-fixed-header thead').height();
//                     $('.table-fixed-header tbody').css('height', 'calc(100vh - 77px - '+ theadHeight +'px)');
//                 }
//                 if (!shouldBeFixed && isFixed) {
//                     $('.table-responsive').removeClass('table-fixed-header');
//                     $('.table-responsive-fake').hide(); // placeholder ca sa pastrez inaltimea paginii
//                 }
//             });

            @if (!$dataType->server_side)
            var table = $('#dataTable').DataTable({!! json_encode(
                    array_merge([
                        "order" => $orderColumn,
                        "language" => __('voyager::datatable'),
                        "columnDefs" => [
                            ['targets' => 'dt-not-orderable', 'searchable' =>  false, 'orderable' => false],
                        ],
                    ],
                    config('voyager.dashboard.data_tables', []))
                , true) !!});
            @else
            $('#search-input select').select2({
                minimumResultsForSearch: Infinity
            });
            @endif

            @if ($isModelTranslatable)
            $('.side-body').multilingual();
            //Reinitialise the multilingual features when they change tab
            $('#dataTable').on('draw.dt', function () {
                $('.side-body').data('multilingual').init();
            })
            @endif
            $('.select_all').on('click', function (e) {
                $('input[name="row_id"]').prop('checked', $(this).prop('checked')).trigger('change');
            });
        });


        var deleteFormAction;
        $('td').on('click', '.delete', function (e) {
            $('#delete_form')[0].action = '{{ route('voyager.'.$dataType->slug.'.destroy', '__id') }}'.replace('__id', $(this).data('id'));
            $('#delete_modal').modal('show');
        });

        @if($usesSoftDeletes)
        @php
            $params = [
                's' => $search->value,
                'filter' => $search->filter,
                'key' => $search->key,
                'order_by' => $orderBy,
                'sort_order' => $sortOrder,
            ];
        @endphp
        $(function () {
            $('#show_soft_deletes').change(function () {
                if ($(this).prop('checked')) {
                    $('#dataTable').before('<a id="redir" href="{{ (route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 1]), true)) }}"></a>');
                } else {
                    $('#dataTable').before('<a id="redir" href="{{ (route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 0]), true)) }}"></a>');
                }

                $('#redir')[0].click();
            })
        })
        @endif
        $('input[name="row_id"]').on('change', function () {
            var ids = [];
            $('input[name="row_id"]').each(function () {
                if ($(this).is(':checked')) {
                    ids.push($(this).val());
                    $(this).parent().parent().css("background-color", "#6B76D8")
                } else {
                    $(this).parent().parent().css("background-color", "#fff")

                }
            });
            $('.selected_ids').val(ids);
        });
    </script>
@stop
