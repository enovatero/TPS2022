@php
$edit = !is_null($dataTypeContent->getKey());
$add  = is_null($dataTypeContent->getKey());
$isNewClient = false;
$iconUrl = $dataType->icon;
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="../../../css/mention.css">
@stop

@section('page_title', __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        @if($edit && $dataTypeContent->numar_comanda != null)
          <div class="page-title-text">Comanda #{{$dataTypeContent->numar_comanda}}</div>
        @else
          <div class="page-title-text">
            Oferta {{$edit ? '#'.$dataTypeContent->id : 'noua'}} {{$edit && $dataTypeContent->status_name->title == 'retur' ? ' - RETUR' : ''}}
          </div>
        @endif
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid {{$edit && $dataTypeContent->numar_comanda != null ? 'comanda-productie' : ''}}">
        <div class="row">
            @if($edit)
              <div class="col-md-12">
                <ul class="nav nav-tabs" style="width: max-content;">
                  <li class="active">
                      <a data-toggle="tab" href="#oferta" aria-expanded="true" class="btnOferta">Detali oferta</a>
                  </li>
                  <li class="">
                      <a data-toggle="tab" href="#awb" aria-expanded="false" class="btnOfertaAwb">Detalii livrare / AWB</a>
                  </li>
                </ul>
              </div>
              @if($edit)
                <div class="col-md-12 butoane-oferta" test="{{$dataTypeContent->status_name->title}}">
                  <a target="_blank" class="btn btn-success btn-add-new" href="/admin/generatePDF/{{$dataTypeContent->id}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                      <i class="voyager-download" style="margin-right: 10px;"></i> <span> Descarca oferta PDF</span>
                  </a> 
                  @if($dataTypeContent->delivery_type == 'fan' && $dataTypeContent->fanData && $dataTypeContent->fanData->cont_id != null && $dataTypeContent->fanData->awb != null)
                    <a target="_blank" class="btn btn-success btn-add-new btnDownloadAwbFan" href="/admin/printAwb/{{$dataTypeContent->fanData->awb}}/{{$dataTypeContent->fanData->cont_id}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                        <i class="voyager-eye" style="margin-right: 10px;"></i> <span> Descarca AWB PDF</span>
                    </a> 
                  @endif
                   @if($dataTypeContent->delivery_type == 'nemo' && $dataTypeContent->nemoData && $dataTypeContent->nemoData->cont_id != null && $dataTypeContent->nemoData->awb != null)
                    <a target="_blank" class="btn btn-success btn-add-new btnDownloadAwbNemo" href="/admin/printAwbNemo/{{$dataTypeContent->nemoData->awb}}/{{$dataTypeContent->nemoData->cont_id}}/{{$dataTypeContent->nemoData->hash}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                        <i class="voyager-eye" style="margin-right: 10px;"></i> <span> Descarca AWB PDF</span>
                    </a> 
                  @endif
                  @if($dataTypeContent->numar_comanda == null)
                    <a class="btn btn-success btn-add-new btnAcceptOffer" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;" order_id="{{$dataTypeContent->id}}">
                        <i class="voyager-pen" style="margin-right: 10px;"></i> <span>Oferta acceptata - lanseaza comanda</span>
                    </a>  
                  @endif
                  @if($dataTypeContent->numar_comanda != null)
                    <a class="btn btn-success btn-add-new btnFisaComanda" target="_blank" href="/admin/generatePDFFisa/{{$dataTypeContent->id}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                        <i class="voyager-list" style="margin-right: 10px;"></i> <span>Fisa de comanda</span>
                    </a> 
                    @if($dataTypeContent->status_name->title == 'noua' || $dataTypeContent->status_name->title == 'anulata' || $dataTypeContent->status_name->title == 'modificata' || $dataTypeContent->status_name->title == 'productie')
                      <a class="btn btn-success btn-add-new btnSchimbaStatus" status="expediata" order_id="{{$dataTypeContent->getKey()}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                          <i class="voyager-bolt" style="margin-right: 10px;"></i> <span>Comanda expediata</span>
                      </a> 
                    @endif
                    @if($dataTypeContent->status_name->title == 'expediata')
                      <a class="btn btn-success btn-add-new btnSchimbaStatus" status="livrata" order_id="{{$dataTypeContent->getKey()}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                          <i class="voyager-truck" style="margin-right: 10px;"></i> <span>Comanda livrata</span>
                      </a> 
                    @endif
                    @if($dataTypeContent->status_name->title == 'livrata' || $dataTypeContent->status_name->title == 'expediata')
                      <a class="btn btn-success btn-add-new btnSchimbaStatus" status="retur" order_id="{{$dataTypeContent->getKey()}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                          <i class="voyager-warning" style="margin-right: 10px;"></i> <span>Comanda retur</span>
                      </a> 
                    @endif
                  @endif
                </div>
              @endif
            @endif
            <div class="col-md-12" id="oferta">

                <div class="panel panel-bordered" style="border: none; box-shadow: none;">
                    <!-- form start -->
                    <form role="form"
                            class="form-edit-add"
                            action="{{ $edit ? route('voyager.'.$dataType->slug.'.update', $dataTypeContent->getKey()) : route('voyager.'.$dataType->slug.'.store') }}"
                            method="POST" enctype="multipart/form-data">
                        <!-- PUT Method if we are editing -->
                        @if($edit)
                            {{ method_field("PUT") }}
                        @endif

                        <!-- CSRF TOKEN -->
                        {{ csrf_field() }}

                        <div class="container-doua-coloane " style="display: flex;flex-direction: column;">
                          <div style="width: 100%;">
                         @if($edit)
                         <div class="panel-body container-doua-col-left" style="background: #f9f9f9;width: 100%;">

                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                              @php
                                $isNewClient = count($errors) > 0 && array_key_exists('address', $errors->toArray());
                              @endphp
                            @endif

                            <!-- Adding / Editing -->
                            @php
                                $dataTypeRows = $dataType->{($edit ? 'editRows' : 'addRows' )};
                            @endphp

                           <div class="flex__box--cont" >
                           <div class="flex__box1--1 flex-first-box">

<!-- || $row->display_name == 'Client' -->
@foreach($dataTypeRows as $row)
     @if($row->display_name == 'Serie' || $row->display_name == 'Tip oferta'  || $row->display_name == 'Data oferta' || $row->display_name == 'Sursa' || $row->display_name == 'Curs EURO' || $row->display_name == 'Agent' || $row->display_name == 'Grila pret' || $row->display_name == 'Tip oferta custom' )


     @php
         $display_options = $row->details->display ?? NULL;
         if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
             $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
         }
     @endphp
     @if (isset($row->details->legend) && isset($row->details->legend->text))
         <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
     @endif 

    <div @if($row->display_name == 'Tip oferta' || $row->display_name == 'Tip oferta custom') style="width: 100% !important;" @endif class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif >
         {{ $row->slugify }}
         @if((Auth::user()->hasRole('developer') || Auth::user()->hasRole('admin')) && $row->field == "offer_belongsto_status_relationship")
           <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
         @endif
         @if($row->field != "offer_belongsto_status_relationship")
           <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
         @endif
         @include('voyager::multilingual.input-hidden-bread-edit-add')
         @if (isset($row->details->view))
             @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => ($edit ? 'edit' : 'add'), 'view' => ($edit ? 'edit' : 'add'), 'options' => $row->details])
         @elseif ($row->type == 'relationship')
             @if((Auth::user()->hasRole('developer') || Auth::user()->hasRole('admin')) && $row->field == "offer_belongsto_status_relationship")
              @include('voyager::formfields.relationship', ['options' => $row->details])
             @endif
             @if($row->field != "offer_belongsto_status_relationship")
               @include('voyager::formfields.relationship', ['options' => $row->details])
             @endif
         @else
             @if($row->field == 'price_grid_id')
               {!! $select_html_grids !!}
             @else
               {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
             @endif
         @endif

         @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
             {!! $after->handle($row, $dataType, $dataTypeContent) !!}
         @endforeach
         @if ($errors->has($row->field))
             @foreach ($errors->get($row->field) as $error)
                 <span class="help-block">{{ $error }}</span>
             @endforeach
         @endif
     </div>
      @if($row->display_name == 'Serie')
        @php
          $isOrder = $dataTypeContent->numar_comanda != null ? true : false;
        @endphp
        <div class="form-group col-md-12">
         <label class="control-label" for="name">
          @if($isOrder) Numar comanda @else Numar oferta @endif
         </label>
         <input type="text" class="form-control" @if($isOrder != null) value="{{$dataTypeContent->numar_comanda}}" @else value="{{$dataTypeContent->id}}" @endif disabled="disabled">
        </div>
      @endif
    @endif

   @endforeach
   <div class="form-group col-md-12" style="width: 100% !important;">
     <label class="control-label" for="name">
      Metoda de plata
     </label>
      <select
          class="custom-table-select form-control"
          name="payment_type"
      >
          @foreach (App\Offer::$payment_types as $pkey => $payment_type)
              <option
                  value="{{ $pkey }}"
                  {{ $dataTypeContent->payment_type == $pkey ? 'selected' : '' }}
              >
                  {{ $payment_type }}
              </option>
          @endforeach
      </select>
   </div>
   <div class="form-group col-md-12" style="width: 100% !important;">
     <label class="control-label" for="name">
      Numar comanda distribuitor
     </label>
     <input type="text" class="form-control" name="external_number" value="{{$dataTypeContent->external_number}}">
   </div>
 </div>
 @if($edit)

                            <div class="form-group col-md-12 mesaj-intern-container flex__box1">
                                    <label class="control-label">Mesaje comanda</label>
                                    <input name="mentions" type="hidden"/>
                                    <textarea class="form-control" id="mentions" name="mentions_textarea" placeholder="Mesaj nou" rows="5"></textarea>
                                    <button style="float: right;" type="button" class="btn btn-primary save btnSaveMention" order_id="{{$dataTypeContent->id}}">Salveaza mesaj</button>
                                    <div class="form-group col-md-12 mesaj-intern-container">
                                        <div class="log-evenimente-list log-mesaje">
                                          @include('vendor.voyager.partials.log_messages', ['offerMessages' => $offerMessages]) 
                                        </div>
                                    </div>
                                  </div>

                            @endif

                          </div>
@if($edit)
                            <div class="flex__box--cont">
                            <div class="flex__box1--2">
                                  @if($row->field == 'curs_eur' && (count($filteredColors) > 0) || count($filteredDimensions) > 0)
                                    <div class="form-group  col-md-12" >
                                        @foreach($filteredColors as $key => $item)
                                          <div class="form-group">
                                            <label class="control-label" for="name">{{ucfirst($key)}}</label>
                                            <select name="selectedAttribute[]" class="form-control selectColor selectAttribute @if($item[0]->attr_id == 10) preselectColor @endif  selectColAttr-{{$item[0]->attr_id}}">
                                                <option selected disabled>Selecteaza {{strtolower($key)}}</option>
                                                @foreach($item as $color)
                                                  @php
                                                    $currentArr = [$color->attr_id, $color->color_id];
                                                    $selectedColor = '';
                                                    if(in_array($currentArr, $offerSelectedAttrsArray)){
                                                      $selectedColor = 'selected';
                                                    }
                                                  @endphp
                                                  <option value="{{$color->attr_id}}_{{$color->color_id}}_{{$color->color_value}}_{{$color->color_ral}}" {{$selectedColor}}>{{$color->color_ral}}</option>
                                                @endforeach
                                            </select>
                                          </div>
                                        @endforeach
                                        @foreach($filteredDimensions as $key => $item)
                                          <div class="form-group">
                                            <label class="control-label" for="name">{{ucfirst($key)}}</label>
                                            <select name="selectedAttribute[]" class="form-control selectDimension selectAttribute">
                                                <option selected disabled>Selecteaza {{strtolower($key)}}</option>
                                                @foreach($item as $dimension)
                                                  @php
                                                    $currentArr = [$dimension->attr_id, $dimension->dimension_id];
                                                    $selectedDimension = '';
                                                    if(in_array($currentArr, $offerSelectedAttrsArray) || $dimension->dimension_value == '125/087'){
                                                      $selectedDimension = 'selected';
                                                    }
                                                  @endphp
                                                  <option value="{{$dimension->attr_id}}_{{$dimension->dimension_id}}_{{$dimension->dimension_value}}" {{$selectedDimension}}>{{$dimension->dimension_value}}</option>
                                                @endforeach
                                            </select>
                                          </div>
                                        @endforeach
                                    </div>
                                  @endif
                              @foreach($dataTypeRows as $row)
                                @php
                                    $display_options = $row->details->display ?? NULL;
                                    if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
                                        $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
                                    }
                                @endphp
                                @if (isset($row->details->legend) && isset($row->details->legend->text))
                                    <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
                                @endif 

                                @if($row->display_name == 'Observatii') 
                                <div class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif >
                                    {{ $row->slugify }}
                                    
                                    @if((Auth::user()->hasRole('developer') || Auth::user()->hasRole('admin')) && $row->field == "offer_belongsto_status_relationship")
                                      <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    @endif
                                    @if($row->field != "offer_belongsto_status_relationship")
                                      <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    @endif
                                    {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}

                                  </div>

                                @endif
                                 
                                
                            @endforeach
                           
                            </div>
                            @if($edit)
                              
                              <div class="form-group col-md-12 mesaj-intern-container log-evenimente flex__box1 height__1">
                                  <label class="control-label">Log evenimente</label>
                                  <div class="show-hide-log hide-log">
                                    <div class="control-label">Vezi log</div>
                                    <span class="icon voyager-double-up"></span>
                                  </div>
                                  <div class="log-evenimente-list">
                                    @include('vendor.voyager.partials.log_events', ['offerEvents' => $offerEvents]) 
                                  </div>
                              </div>
                            @endif

                            </div>
                            @endif






                            <div class="flex__box third-box">
                           @foreach($dataTypeRows as $row)
                                    @if($row->display_name == 'Observatii') 

                                  

                                    @else

                                @if($row->display_name == 'Serie' || $row->display_name == 'Tip oferta'  || $row->display_name == 'Data oferta' || $row->display_name == 'Sursa' || $row->display_name == 'Curs EURO' || $row->display_name == 'Agent' || $row->display_name == 'Grila pret' || $row->display_name == 'External Number' || $row->display_name == 'Tip oferta custom' ) 

                                    @else
                                @php
                                    $display_options = $row->details->display ?? NULL;
                                    if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
                                        $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
                                    }
                                @endphp
                                @if (isset($row->details->legend) && isset($row->details->legend->text))
                                    <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
                                @endif 

                               <div @if($row->display_name == 'Ambalare' || $row->display_name == 'Banda transparenta') style="width: 49% !important;" @endif class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif >
                                    {{ $row->slugify }}
                                    @if((Auth::user()->hasRole('developer') || Auth::user()->hasRole('admin')) && $row->field == "offer_belongsto_status_relationship")
                                      <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    @endif
                                    @if($row->field != "offer_belongsto_status_relationship")
                                      <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    @endif
                                    @include('voyager::multilingual.input-hidden-bread-edit-add')
                                    @if (isset($row->details->view))
                                        @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => ($edit ? 'edit' : 'add'), 'view' => ($edit ? 'edit' : 'add'), 'options' => $row->details])
                                    @elseif ($row->type == 'relationship')
                                        @if((Auth::user()->hasRole('developer') || Auth::user()->hasRole('admin')) && $row->field == "offer_belongsto_status_relationship")
                                         @include('voyager::formfields.relationship', ['options' => $row->details])
                                        @endif
                                        @if($row->field != "offer_belongsto_status_relationship")
                                          @include('voyager::formfields.relationship', ['options' => $row->details])
                                        @endif
                                    @else
                                        @if($row->field == 'price_grid_id')
                                          {!! $select_html_grids !!}
                                        @else
                                          {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                        @endif
                                    @endif

                                    @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                        {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                    @endforeach
                                    @if ($errors->has($row->field))
                                        @foreach ($errors->get($row->field) as $error)
                                            <span class="help-block">{{ $error }}</span>
                                        @endforeach
                                    @endif
                                </div>
                              
                                      @if($row->display_name == 'Client' && $edit)
                                        <div class="form-group  col-md-12" >
                                        <label class="control-label">Adresa livrare</label>
                                        <select name="delivery_address_user" class="form-control">
                                          <option value="-1" selected disabled>Alege adresa de livrare</option>
                                          <option value="-2">Adauga adresa noua</option>
                                          @if(count($userAddresses) > 0)
                                            @foreach($userAddresses as $address)
                                              @if(($selectedAddress != null && $selectedAddress->id == $address->id) || ($dataTypeContent->delivery_address_user == $address->id))
                                                @php
                                                  $address = $selectedAddress;
                                                @endphp
                                                <option selected value="{{$address->id}}" country="{{$address->country}}" state_code="{{$address->state}}" state_name="{{$address->state_name}}" city_id="{{$address->city}}" city_name="{{$address->city_name}}" address="{{$address->address}}" phone="{{$address->phone}}" contact="{{$address->name}}">{{$address->address}}, {{$address->city_name}}, {{$address->state_name}}</option>
                                              @else
                                                <option value="{{$address->id}}" country="{{$address->country}}" state_code="{{$address->state}}" state_name="{{$address->state_name()}}" city_id="{{$address->city}}" city_name="{{$address->city_name()}}" address="{{$address->address}}" phone="{{$address->phone}}" contact="{{$address->name}}">{{$address->address}}, {{$address->city_name()}}, {{$address->state_name()}}</option>
                                              @endif
                                            @endforeach
                                          @endif
                                        </select>
                                      </div>
                                      <div class="form-group  col-md-12 container-elements-addresses" style="width: 100%; display: none;">
                                          <div class="panel-body container-box-adresa">
                                            <input class="trick-addr-id" value="" type="hidden"/>
                                            <div class="form-group col-md-12 column-element-address" style="width: 100%">
                                               <label class="control-label">Tara</label>
                                               @include('vendor.voyager.formfields.countries', ['selected' => null])                       
                                            </div>
                                            <div class="form-group col-md-12 column-element-address">
                                               <label class="control-label" for="state">Judet/Regiune</label>
                                               <select name="state" class="form-control select-state"></select>
                                            </div>
                                            <div class="form-group col-md-12 column-element-address">
                                               <label class="control-label">Oras/Localitate/Sector</label>
                                               <select name="city" class="form-control select-city"></select>        
                                            </div>
                                            <div class="form-group col-md-12 column-element-address" style="width: 100%;">
                                               <label class="control-label">Introdu adresa(strada, nr, bloc, etaj, ap)</label>
                                               <input class="control-label" type="text" name="delivery_address" data-google-address autocomplete="off" style="padding: 5px;"/>                          
                                            </div>
                                            <div class="form-group col-md-12 column-element-address">
                                               <label class="control-label" for="state">Telefon</label>
                                               <input name="delivery_phone" type="text" style="padding: 5px;"/>
                                            </div>
                                            <div class="form-group col-md-12 column-element-address">
                                               <label class="control-label">Persoana de contact</label>
                                               <input name="delivery_contact" type="text" style="padding: 5px;"/>        
                                            </div>
                                          </div>
                                          <div class="col-md-12 panel-footer" style="    justify-content: flex-end;display: flex;width: 100%;">
                                            <button type="button" class="btn btn-primary btnGreenNew btnSalveazaAdresa">Salveaza adresa noua</button>
                                          </div>
                                      </div>
                                      @endif
                                    @endif
                                    @endif
                              @endforeach
                            </div>
                         

                                      <!-- ADD SECTION -->

                         @else
                         <div class="panel-body container-doua-col-left" style="background: #f9f9f9;width: 100%;display: flex;flex-direction: column;">

@if (count($errors) > 0)
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
  @php
    $isNewClient = count($errors) > 0 && array_key_exists('address', $errors->toArray());
  @endphp
@endif

@php
    $dataTypeRows = $dataType->{($edit ? 'editRows' : 'addRows' )};
@endphp

<div @if($edit) style="width: 70%;margin: 0;" @else style="width: 100%;margin: 0;" @endif class="flex__box--cont" >
<div class="flex__box1--1">

<!-- || $row->display_name == 'Client' -->
@foreach($dataTypeRows as $row)
@if($row->display_name == 'Serie' || $row->display_name == 'Tip oferta'  || $row->display_name == 'Data oferta' || $row->display_name == 'Curs EURO' || $row->display_name == 'Agent' )


@php
$display_options = $row->details->display ?? NULL;
if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
$dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
}
@endphp
@if (isset($row->details->legend) && isset($row->details->legend->text))
<legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
@endif 

<div class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif >
{{ $row->slugify }}
@if((Auth::user()->hasRole('developer') || Auth::user()->hasRole('admin')) && $row->field == "offer_belongsto_status_relationship")
<label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
@endif
@if($row->field != "offer_belongsto_status_relationship")
<label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
@endif
@include('voyager::multilingual.input-hidden-bread-edit-add')
@if (isset($row->details->view))
@include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => ($edit ? 'edit' : 'add'), 'view' => ($edit ? 'edit' : 'add'), 'options' => $row->details])
@elseif ($row->type == 'relationship')
@if((Auth::user()->hasRole('developer') || Auth::user()->hasRole('admin')) && $row->field == "offer_belongsto_status_relationship")
@include('voyager::formfields.relationship', ['options' => $row->details])
@endif
@if($row->field != "offer_belongsto_status_relationship")
@include('voyager::formfields.relationship', ['options' => $row->details])
@endif
@else
@if($row->field == 'price_grid_id')
{!! $select_html_grids !!}
@else
{!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
@endif
@endif

@foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
{!! $after->handle($row, $dataType, $dataTypeContent) !!}
@endforeach
@if ($errors->has($row->field))
@foreach ($errors->get($row->field) as $error)
<span class="help-block">{{ $error }}</span>
@endforeach
@endif
</div>
@endif

@endforeach
</div>
@if($edit)

<div class="form-group col-md-12 mesaj-intern-container flex__box1">
        <label class="control-label">Mesaje comanda</label>
        <input name="mentions" type="hidden"/>
        <textarea class="form-control" id="mentions" name="mentions_textarea" placeholder="Mesaj nou" rows="5"></textarea>
        <button style="float: right;" type="button" class="btn btn-primary save btnSaveMention" order_id="{{$dataTypeContent->id}}">Salveaza mesaj</button>
        <div class="form-group col-md-12 mesaj-intern-container">
            <div class="log-evenimente-list log-mesaje">
              @include('vendor.voyager.partials.log_messages', ['offerMessages' => $offerMessages]) 
            </div>
        </div>
      </div>

@endif

</div>

@if($edit)
<div style="width: 70%;margin: 0;" class="flex__box--cont">
<div class="flex__box1--2">
@if($edit)
      @if($row->field == 'curs_eur' && (count($filteredColors) > 0) || count($filteredDimensions) > 0)
        <div class="form-group  col-md-12" >
            @foreach($filteredColors as $key => $item)
              <div class="form-group">
                <label class="control-label" for="name">{{ucfirst($key)}}</label>
                <select name="selectedAttribute[]" class="form-control selectColor selectAttribute">
                    <option selected disabled>Selecteaza {{strtolower($key)}}</option>
                    @foreach($item as $color)
                      @php
                        $currentArr = [$color->attr_id, $color->color_id];
                        $selectedColor = '';
                        if(in_array($currentArr, $offerSelectedAttrsArray)){
                          $selectedColor = 'selected';
                        }
                      @endphp
                      <option value="{{$color->attr_id}}_{{$color->color_id}}_{{$color->color_value}}_{{$color->color_ral}}" {{$selectedColor}}>{{$color->color_ral}}</option>
                    @endforeach
                </select>
              </div>
            @endforeach
            @foreach($filteredDimensions as $key => $item)
              <div class="form-group">
                <label class="control-label" for="name">{{ucfirst($key)}}</label>
                <select name="selectedAttribute[]" class="form-control selectDimension selectAttribute">
                    <option selected disabled>Selecteaza {{strtolower($key)}}</option>
                    @foreach($item as $dimension)
                      @php
                        $currentArr = [$dimension->attr_id, $dimension->dimension_id];
                        $selectedDimension = '';
                        if(in_array($currentArr, $offerSelectedAttrsArray)){
                          $selectedDimension = 'selected';
                        }
                      @endphp
                      <option value="{{$dimension->attr_id}}_{{$dimension->dimension_id}}_{{$dimension->dimension_value}}" {{$selectedDimension}}>{{$dimension->dimension_value}}</option>
                    @endforeach
                </select>
              </div>
            @endforeach
        </div>
      @endif
    @else
      <div class="container-preselect-colors"></div>
    @endif
  @foreach($dataTypeRows as $row)
    @php
        $display_options = $row->details->display ?? NULL;
        if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
            $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
        }
    @endphp
    @if (isset($row->details->legend) && isset($row->details->legend->text))
        <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
    @endif 

    @if($row->display_name == 'Observatii') 
    <div class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif >
        {{ $row->slugify }}
        
        @if((Auth::user()->hasRole('developer') || Auth::user()->hasRole('admin')) && $row->field == "offer_belongsto_status_relationship")
          <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
        @endif
        @if($row->field != "offer_belongsto_status_relationship")
          <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
        @endif
        {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}

      </div>

    @endif
     
    
@endforeach

</div>
@if($edit)
  
  <div class="form-group col-md-12 mesaj-intern-container log-evenimente flex__box1 height__1">
    <label class="control-label">Log evenimente</label>
    <div class="show-hide-log hide-log">
      <div>Vezi log</div>
      <span class="icon voyager-double-down"></span>
    </div>
    <div class="log-evenimente-list test123">
      @include('vendor.voyager.partials.log_events', ['offerEvents' => $offerEvents]) 
    </div>
  </div>
@endif

</div>
@endif





@if($edit)
<div style="width: 70%;margin: 0;" class="flex__box">
@foreach($dataTypeRows as $row)

        @if($row->display_name == 'Observatii') 

      

        @else

    @if($row->display_name == 'Serie' || $row->display_name == 'Tip oferta'  || $row->display_name == 'Data oferta' || $row->display_name == 'Sursa' || $row->display_name == 'Curs EURO' || $row->display_name == 'Agent' ) 

        @else
    @php
        $display_options = $row->details->display ?? NULL;
        if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
            $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
        }
    @endphp
    @if (isset($row->details->legend) && isset($row->details->legend->text))
        <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
    @endif 

   <div class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif >
        {{ $row->slugify }}
        @if((Auth::user()->hasRole('developer') || Auth::user()->hasRole('admin')) && $row->field == "offer_belongsto_status_relationship")
          <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
        @endif
        @if($row->field != "offer_belongsto_status_relationship")
          <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
        @endif
        @include('voyager::multilingual.input-hidden-bread-edit-add')
        @if (isset($row->details->view))
            @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => ($edit ? 'edit' : 'add'), 'view' => ($edit ? 'edit' : 'add'), 'options' => $row->details])
        @elseif ($row->type == 'relationship')
            @if((Auth::user()->hasRole('developer') || Auth::user()->hasRole('admin')) && $row->field == "offer_belongsto_status_relationship")
             @include('voyager::formfields.relationship', ['options' => $row->details])
            @endif
            @if($row->field != "offer_belongsto_status_relationship")
              @include('voyager::formfields.relationship', ['options' => $row->details])
            @endif
        @else
            @if($row->field == 'price_grid_id')
              {!! $select_html_grids !!}
            @else
              {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
            @endif
        @endif

        @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
            {!! $after->handle($row, $dataType, $dataTypeContent) !!}
        @endforeach
        @if ($errors->has($row->field))
            @foreach ($errors->get($row->field) as $error)
                <span class="help-block">{{ $error }}</span>
            @endforeach
        @endif
    </div>
          @if(isset($display_options->id) && $display_options->id == 'mod_livrare' && $edit)
            <div class="form-group  col-md-12" >
            <label class="control-label">Adresa livrare</label>
            <select name="delivery_address_user" class="form-control">
              <option value="-1" selected disabled>Alege adresa de livrare</option>
              <option value="-2">Adauga adresa noua</option>
              @if(count($userAddresses) > 0)
                @foreach($userAddresses as $address)
                  @if(($selectedAddress != null && $selectedAddress->id == $address->id) || ($dataTypeContent->delivery_address_user == $address->id))
                    @php
                      $address = $selectedAddress;
                    @endphp
                    <option selected value="{{$address->id}}" country="{{$address->country}}" state_code="{{$address->state}}" state_name="{{$address->state_name}}" city_id="{{$address->city}}" city_name="{{$address->city_name}}" address="{{$address->address}}" phone="{{$address->phone}}" contact="{{$address->name}}">{{$address->address}}, {{$address->city_name}}, {{$address->state_name}}</option>
                  @else
                    <option value="{{$address->id}}" country="{{$address->country}}" state_code="{{$address->state}}" state_name="{{$address->state_name()}}" city_id="{{$address->city}}" city_name="{{$address->city_name()}}" address="{{$address->address}}" phone="{{$address->phone}}" contact="{{$address->name}}">{{$address->address}}, {{$address->city_name()}}, {{$address->state_name()}}</option>
                  @endif
                @endforeach
              @endif
            </select>
          </div>
          <div class="form-group  col-md-12 container-elements-addresses" style="width: 100%; display: none;">
              <div class="panel-body container-box-adresa">
                <input class="trick-addr-id" value="" type="hidden"/>
                <div class="form-group col-md-12 column-element-address" style="width: 100%">
                   <label class="control-label">Tara</label>
                   @include('vendor.voyager.formfields.countries', ['selected' => null])                       
                </div>
                <div class="form-group col-md-12 column-element-address">
                   <label class="control-label" for="state">Judet/Regiune</label>
                   <select name="state" class="form-control select-state"></select>
                </div>
                <div class="form-group col-md-12 column-element-address">
                   <label class="control-label">Oras/Localitate/Sector</label>
                   <select name="city" class="form-control select-city"></select>        
                </div>
                <div class="form-group col-md-12 column-element-address" style="width: 100%;">
                   <label class="control-label">Introdu adresa(strada, nr, bloc, etaj, ap)</label>
                   <input class="control-label" type="text" name="delivery_address" data-google-address autocomplete="off" style="padding: 5px;"/>                          
                </div>
                <div class="form-group col-md-12 column-element-address">
                   <label class="control-label" for="state">Telefon</label>
                   <input name="delivery_phone" type="text" style="padding: 5px;"/>
                </div>
                <div class="form-group col-md-12 column-element-address">
                   <label class="control-label">Persoana de contact</label>
                   <input name="delivery_contact" type="text" style="padding: 5px;"/>        
                </div>
              </div>
              <div class="col-md-12 panel-footer" style="    justify-content: flex-end;display: flex;width: 100%;">
                <button type="button" class="btn btn-primary btnGreenNew btnSalveazaAdresa">Salveaza adresa noua</button>
              </div>
          </div>
          @endif
        @endif
        @endif
  @endforeach
</div>
@endif
                         @endif








                            

                        

                            
                            
                            
                        </div>
                          <div class="panel-body container-doua-col-right" @if (count($errors) > 0 && array_key_exists('address', $errors->toArray())) style="display: block !important;" @endif>
                            {{csrf_field()}}
                            @if($edit)
                              <input name="offer_id" type="hidden"/>
                            @endif
                            <div class="form-group  col-md-12 ">     
                               <h4 class="control-label font-weight-bold" for="name">Adaugare client</h4>     
                            </div>
                            <div class="form-group  col-md-12 tip-client-container">
                               <label class="control-label" for="name">Tip client</label>
                               <ul class="radio">
                                  <li>
                                      <input type="radio" id="option-type-fizica" name="persoana_type" value="fizica" @if(old('persoana_type', $dataTypeContent->persoana_type) == 'fizica' || old('persoana_type', $dataTypeContent->persoana_type) == '') checked="" @endif>
                                      <label for="option-type-fizica">Persoana fizica</label>
                                      <div class="check"></div>
                                  </li>
                                  <li>
                                      <input type="radio" id="option-type-juridica" name="persoana_type" value="juridica" @if(old('persoana_type', $dataTypeContent->persoana_type) == 'juridica') checked="" @endif>
                                      <label for="option-type-juridica">Persoana juridica</label>
                                      <div class="check"></div>
                                  </li>
                               </ul>
                            </div>
                              <div class="form-group col-md-12">
                               <label class="control-label" for="name">Nume</label>
                               <input class="form-control" type="text" name="name" autocomplete="off" value="{{ old('name', $dataTypeContent->name ?? '') != '' ? old('name', $dataTypeContent->name) : ''}}"/>                          
                              </div>
                              <div class="form-group col-md-12 container-inputs-juridica" @if(old('persoana_type', $dataTypeContent->persoana_type) == 'juridica') style="display: block" @endif>
                               <label class="control-label" for="name">CUI</label>
                               <input class="form-control" type="text" name="cui" autocomplete="off" value="{{ old('cui', $dataTypeContent->cui ?? '') != '' ? old('cui', $dataTypeContent->cui) : ''}}"/>                          
                              </div>
                              <div class="form-group col-md-12 container-inputs-juridica" @if(old('persoana_type', $dataTypeContent->persoana_type) == 'juridica') style="display: block" @endif>
                               <label class="control-label" for="name">Reg. Com.</label>
                               <input class="form-control" type="text" name="reg_com" autocomplete="off" value="{{ old('reg_com', $dataTypeContent->reg_com ?? '') != '' ? old('reg_com', $dataTypeContent->reg_com) : ''}}"/>                          
                              </div>
                              <div class="form-group col-md-12 container-inputs-juridica" @if(old('persoana_type', $dataTypeContent->persoana_type) == 'juridica') style="display: block" @endif>
                               <label class="control-label" for="name">Banca</label>
                               <input class="form-control" type="text" name="banca" autocomplete="off"value="{{ old('banca', $dataTypeContent->banca ?? '') != '' ? old('banca', $dataTypeContent->banca) : ''}}" />                          
                              </div>
                              <div class="form-group col-md-12 container-inputs-juridica" @if(old('persoana_type', $dataTypeContent->persoana_type) == 'juridica') style="display: block" @endif>
                               <label class="control-label" for="name">IBAN</label>
                               <input class="form-control" type="text" name="iban" autocomplete="off"value="{{ old('iban', $dataTypeContent->iban ?? '') != '' ? old('iban', $dataTypeContent->iban) : ''}}" />                          
                              </div>
                              <div class="form-group col-md-12 container-inputs-fizica" @if(old('persoana_type', $dataTypeContent->persoana_type) == 'juridica') style="display: none" @endif>
                               <label class="control-label" for="name">CNP</label>
                               <input class="form-control" type="text" name="cnp" autocomplete="off" value="{{ old('cnp', $dataTypeContent->cnp ?? '') != '' ? old('cnp', $dataTypeContent->cnp) : ''}}"/>                          
                              </div>
                              <div class="form-group  col-md-12 " >
                                  <label class="control-label" for="name">Email</label>
                                  <input  type="text" class="form-control" name="email" placeholder="Email" value="{{ old('email', $dataTypeContent->email ?? '') != '' ? old('email', $dataTypeContent->email) : ''}}">
                              </div>
                              <div class="form-group  col-md-12 " >
                                  <label class="control-label" for="name">Telefon</label>
                                  <input  type="text" class="form-control" name="phone" placeholder="Telefon" value="{{ old('cnp', $dataTypeContent->cnp ?? '') != '' ? old('cnp', $dataTypeContent->cnp) : ''}}">
                              </div>
                            <div>
                              <div class="form-group col-md-12 column-element-address">
                                 <label class="control-label">Introdu adresa(strada, nr, bloc, etaj, ap)</label>
                                 <input class="form-control" type="text" name="address[]" value="{{ old('address', $dataTypeContent->address ?? '') != '' ? old('address', $dataTypeContent->address)[0] : ''}}"/>                          
                              </div>
                              <div class="form-group col-md-12 column-element-address">
                                 <label class="control-label">Tara</label>
                                 @include('vendor.voyager.formfields.countries', ['selected' => null]) 
                              </div>
                              <div class="form-group col-md-12 column-element-address">
                                 <label class="control-label" for="state">Judet/Regiune</label>
                                 <select name="state[]" class="form-control select-state"></select>
                              </div>
                              <div class="form-group col-md-12 column-element-address">
                                 <label class="control-label">Oras/Localitate/Sector</label>
                                 <select name="city[]" class="form-control select-city"></select>        
                              </div>
                            </div>
                          </div>
                        </div>
                        @if($edit)
                        <input name="offer_id" type="hidden" value="{{$dataTypeContent->getKey()}}"/>
                          <div class="col-md-12">
                            <div class="box container-offer-listing-products">
                              @include('vendor.voyager.products.offer_box', ['parents' => $offerType->parents, 'reducere' => $dataTypeContent->reducere, 'offer' => $offer])
                            </div>
                          </div>
                          <div class="col-md-12 butoane-oferta" test="{{$dataTypeContent->status_name->title}}">
                            <a target="_blank" class="btn btn-success btn-add-new" href="/admin/generatePDF/{{$dataTypeContent->id}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                                <i class="voyager-download" style="margin-right: 10px;"></i> <span> Descarca oferta PDF</span>
                            </a> 
                            @if($dataTypeContent->delivery_type == 'fan' && $dataTypeContent->fanData && $dataTypeContent->fanData->cont_id != null && $dataTypeContent->fanData->awb != null)
                              <a target="_blank" class="btn btn-success btn-add-new btnDownloadAwbFan" href="/admin/printAwb/{{$dataTypeContent->fanData->awb}}/{{$dataTypeContent->fanData->cont_id}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                                  <i class="voyager-eye" style="margin-right: 10px;"></i> <span> Descarca AWB PDF</span>
                              </a> 
                            @endif
                            @if($dataTypeContent->delivery_type == 'nemo' && $dataTypeContent->nemoData && $dataTypeContent->nemoData->cont_id != null && $dataTypeContent->nemoData->awb != null)
                              <a target="_blank" class="btn btn-success btn-add-new btnDownloadAwbNemo" href="/admin/printAwbNemo/{{$dataTypeContent->nemoData->awb}}/{{$dataTypeContent->nemoData->cont_id}}/{{$dataTypeContent->nemoData->hash}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                                  <i class="voyager-eye" style="margin-right: 10px;"></i> <span> Descarca AWB PDF</span>
                              </a> 
                            @endif
                            @if($dataTypeContent->numar_comanda == null)
                              <a class="btn btn-success btn-add-new btnAcceptOffer" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;" order_id="{{$dataTypeContent->id}}">
                                  <i class="voyager-pen" style="margin-right: 10px;"></i> <span>Oferta acceptata - lanseaza comanda</span>
                              </a>  
                            @endif
                            @if($dataTypeContent->numar_comanda != null)
                              <a class="btn btn-success btn-add-new btnFisaComanda" target="_blank" href="/admin/generatePDFFisa/{{$dataTypeContent->id}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                                  <i class="voyager-list" style="margin-right: 10px;"></i> <span>Fisa de comanda</span>
                              </a> 
                              @if($dataTypeContent->status_name->title == 'noua' || $dataTypeContent->status_name->title == 'anulata' || $dataTypeContent->status_name->title == 'modificata' || $dataTypeContent->status_name->title == 'productie')
                                <a class="btn btn-success btn-add-new btnSchimbaStatus" status="expediata" order_id="{{$dataTypeContent->getKey()}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                                    <i class="voyager-bolt" style="margin-right: 10px;"></i> <span>Comanda expediata</span>
                                </a> 
                              @endif
                              @if($dataTypeContent->status_name->title == 'expediata')
                                <a class="btn btn-success btn-add-new btnSchimbaStatus" status="livrata" order_id="{{$dataTypeContent->getKey()}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                                    <i class="voyager-truck" style="margin-right: 10px;"></i> <span>Comanda livrata</span>
                                </a> 
                              @endif
                              @if($dataTypeContent->status_name->title == 'livrata' || $dataTypeContent->status_name->title == 'expediata')
                                <a class="btn btn-success btn-add-new btnSchimbaStatus" status="retur" order_id="{{$dataTypeContent->getKey()}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                                    <i class="voyager-warning" style="margin-right: 10px;"></i> <span>Comanda retur</span>
                                </a> 
                              @endif
                            @endif
                          </div>
                        @endif
                        @if(!$edit)
                          <div class="panel-footer" style="background: #f9f9f9;width: 100%;">
                              @section('submit-buttons')
                                  <button type="submit" class="btn btn-primary save">{{ __('voyager::generic.save') }}</button>
                              @stop
                              @yield('submit-buttons')
                          </div>
                        @endif
                    </form>

                    <iframe id="form_target" name="form_target" style="display:none"></iframe>
                    <form id="my_form" action="{{ route('voyager.upload') }}" target="form_target" method="post"
                            enctype="multipart/form-data" style="width:0;height:0;overflow:hidden">
                        <input name="image" id="upload_file" type="file"
                                 onchange="$('#my_form').submit();this.value='';">
                        <input type="hidden" name="type_slug" id="type_slug" value="{{ $dataType->slug }}">
                        {{ csrf_field() }}
                    </form>

                </div>
            </div>
        </div>
    </div>
          <div class="col-md-12" id="awb" style="display: none;">
            <div class="panel panel-delivery-method">
              <form class="panel-body form-fan-courier delivery-method delivery-fan" method="POST" @if($edit && $dataTypeContent->delivery_type == 'fan' || $dataTypeContent->delivery_type == null) style="display: block;" @else style="display: none;" @endif>
                {{csrf_field()}}
                <input type="hidden" name="order_id" id="order_id" value="{{$dataTypeContent->id}}">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="deliveryAccount">Cont FAN</label>
                    <select name="deliveryAccount" id="deliveryAccount" class="form-control">
                        <option disabled="" selected="">Alege...</option>
                        <option @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->fan_client_id == '7155019') selected @endif value="7155019">BERCENI</option>
                        <option @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->fan_client_id == '7165267') selected @endif value="7165267">MPOS</option>
                        <option @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->fan_client_id == '7177309') selected @endif value="7177309">IASI</option>
                        <option @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->fan_client_id == '7038192') selected @endif value="7038192">STANDARD</option>
                    </select>
                  </div>
<!--                   <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="on">Ridicare sediu FAN</label>
                      <select name="ridicare_sediu_fan" class="form-control">
                        <option value="off" selected>Nu</option>
                        <option value="on">Da</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <div class="sediu"></div>
                    </div>
                  </div> -->
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label>Plata expeditie</label>
                      <select name="plata_expeditie" class="form-control">
                        <option value="expeditor" @if($edit && (($dataTypeContent->fanData && $dataTypeContent->fanData->plata_expeditie == 'expeditor') || $dataTypeContent->fanData == null)) selected @endif>Expeditor</option>
                        <option value="destinatar" @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->plata_expeditie == 'destinatar') @endif>Destinatar</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="packages">Nr. colete</label>
                      <input type="number" name="numar_colete" id="packages" class="form-control" @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->numar_colete) value="{{$dataTypeContent->fanData->numar_colete}}" @else value="1" @endif>
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="weight">Greutate (kg)</label>
                      <input type="number" name="greutate_totala" id="weight" class="form-control" @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->greutate_totala) value="{{$dataTypeContent->fanData->greutate_totala}}" @else value="1" @endif>
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="cashback">Ramburs (ex: 2542.26)</label>
                      <input type="number" name="ramburs_numerar" id="cashback" class="form-control" @if($edit) value="{{$dataTypeContent->total_final}}" @endif>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="height">Inaltime (cm)</label>
                      <input type="number" name="inaltime_pachet" id="height" class="form-control" @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->inaltime_pachet) value="{{$dataTypeContent->fanData->inaltime_pachet}}" @endif>
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="Width">Latime (cm)</label>
                      <input type="number" name="latime_pachet" id="Width" class="form-control" @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->latime_pachet) value="{{$dataTypeContent->fanData->latime_pachet}}" @endif>
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="lenght">Lungime (cm)</label>
                      <input type="number" name="lungime_pachet" id="lenght" class="form-control" @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->lungime_pachet) value="{{$dataTypeContent->fanData->lungime_pachet}}" @endif>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="contents">Continut</label>
                    <input type="text" name="continut_pachet" id="contents" class="form-control" @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->continut_pachet) value="{{$dataTypeContent->fanData->continut_pachet}}" @endif>
                  </div>
                </div>
                <div class="col-md-12">
                  <label for="deliveryAddressAWB">Adresa de livrare</label>
                  <select name="deliveryAddressAWB" id="deliveryAddressAWB" class="form-control">
                    <option disabled="" selected="">Alege...</option>
                    @if($userAddresses != null && count($userAddresses) > 0)
                      @foreach($userAddresses as $address)
                        <option value="{{$address->id}}" @if($edit && $dataTypeContent && $dataTypeContent->fanData && $dataTypeContent->fanData->adresa_livrare_id == $address->id) selected @endif>
                            {{$address->name}} - 
                            {{$address->address}}, 
                            {{$address->city_name}}, 
                            {{$address->state_name}},
                            {{$address->phone}}
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>
                <div class="col-md-12 panel-footer">
                  <button type="button" class="btn btn-primary btnGreenNew btnGenerateAwb" already_generated="{{$dataTypeContent->awb_id && $dataTypeContent->delivery_type == 'fan' ? 1 : 0}}">Genereaza AWB</button>
                </div>
              </form>
              <form class="panel-body form-fan-courier delivery-method delivery-nemo" method="POST" @if($edit && $dataTypeContent->delivery_type == 'nemo') style="display: block;" @else style="display: none;" @endif>
                {{csrf_field()}}
                <input type="hidden" name="order_id" id="order_id" value="{{$dataTypeContent->id}}">
                <div class="col-md-12">
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label>Plata expeditie</label>
                      <select name="plata_expeditie" class="form-control">
                        <option value="client" @if($edit && (($dataTypeContent->nemoData && $dataTypeContent->nemoData->plata_expeditie == 'client') || $dataTypeContent->nemoData == null)) selected @endif>Client</option>
                        <option value="expeditor" @if($edit && (($dataTypeContent->nemoData && $dataTypeContent->nemoData->plata_expeditie == 'expeditor') || $dataTypeContent->nemoData == null)) selected @endif>Expeditor</option>
                        <option value="destinatar" @if($edit && $dataTypeContent->nemoData && $dataTypeContent->nemoData->plata_expeditie == 'destinatar') @endif>Destinatar</option>
                      </select>
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="on">Fragil?</label>
                      <select name="fragil" class="form-control">
                        <option value="nu" selected>Nu</option>
                        <option value="da">Da</option>
                      </select>
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="deliveryAccount">Cont Nemo</label>
                      <select name="deliveryAccount" class="form-control">
                          <option disabled="" selected="">Alege...</option>
                          <option @if($edit && $dataTypeContent->nemoData && $dataTypeContent->nemoData->client_id == '1') selected @endif value="1">Top Profil Sistem Iasi</option>
                          <option @if($edit && $dataTypeContent->nemoData && $dataTypeContent->nemoData->client_id == '2') selected @endif value="2">Top Profil Sistem Berceni</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="packages">Nr. colete</label>
                      <input type="number" name="numar_colete" class="form-control" @if($edit && $dataTypeContent->nemoData && $dataTypeContent->nemoData->numar_colete) value="{{$dataTypeContent->nemoData->numar_colete}}" @else value="1" @endif>
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="weight">Greutate (kg)</label>
                      <input type="number" name="greutate_totala" class="form-control" @if($edit && $dataTypeContent->nemoData && $dataTypeContent->nemoData->greutate_totala) value="{{$dataTypeContent->nemoData->greutate_totala}}" @else value="1" @endif>
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="cashback">Ramburs (ex: 2542.26)</label>
                      <input type="number" name="ramburs_numerar" class="form-control" @if($edit) value="{{$dataTypeContent->total_final}}" @endif>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="height">Inaltime (cm)</label>
                      <input type="number" name="inaltime_pachet" class="form-control" @if($edit && $dataTypeContent->nemoData && $dataTypeContent->nemoData->inaltime_pachet) value="{{$dataTypeContent->nemoData->inaltime_pachet}}" @endif>
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="Width">Latime (cm)</label>
                      <input type="number" name="latime_pachet" class="form-control" @if($edit && $dataTypeContent->nemoData && $dataTypeContent->nemoData->latime_pachet) value="{{$dataTypeContent->nemoData->latime_pachet}}" @endif>
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="lenght">Lungime (cm)</label>
                      <input type="number" name="lungime_pachet" class="form-control" @if($edit && $dataTypeContent->nemoData && $dataTypeContent->nemoData->lungime_pachet) value="{{$dataTypeContent->nemoData->lungime_pachet}}" @endif>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="contents">Continut</label>
                    <input type="text" name="continut_pachet" class="form-control" @if($edit && $dataTypeContent->nemoData && $dataTypeContent->nemoData->continut_pachet) value="{{$dataTypeContent->nemoData->continut_pachet}}" @endif>
                  </div>
                </div>
                <div class="col-md-12">
                  <label for="deliveryAddressAWB">Adresa de livrare</label>
                  <select name="deliveryAddressAWB" class="form-control">
                    <option disabled="" selected="">Alege...</option>
                    @if($userAddresses != null && count($userAddresses) > 0)
                      @foreach($userAddresses as $address)
                        <option value="{{$address->id}}" @if($edit && $dataTypeContent && $dataTypeContent->nemoData && $dataTypeContent->nemoData->adresa_livrare_id == $address->id) selected @endif>
                            {{$address->name}} - 
                            {{$address->address}}, 
                            {{$address->city_name}}, 
                            {{$address->state_name}},
                            {{$address->phone}}
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>
                <div class="col-md-12 panel-footer">
                  <button type="button" class="btn btn-primary btnGreenNew btnGenerateAwbNemo" already_generated="{{$dataTypeContent->awb_id && $dataTypeContent->delivery_type == 'nemo' ? 1 : 0}}">Genereaza AWB</button>
                </div>
              </form>
            </div>
          </div>
    
    <div class="modal fade modal-danger" id="confirm_delete_modal">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="voyager-warning"></i> {{ __('voyager::generic.are_you_sure') }}</h4>
                </div>

                <div class="modal-body">
                    <h4>{{ __('voyager::generic.are_you_sure_delete') }} '<span class="confirm_delete_name"></span>'</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="confirm_delete">{{ __('voyager::generic.delete_confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
    @if($edit)
      <div class="modal fade modal-danger" id="create_new_client" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="voyager-warning"></i>Adaugare client nou</h4>
                </div>
                <div class="alert alert-danger modal-alert-container-client">
                    <ul></ul>
                </div>
                <form class="modal-body form-new-client">
                    
                </form>

                <div class="modal-footer" style="display: flex;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="confirm_create_client" style="height: 3.2rem;">Adauga client</button>
                </div>
            </div>
        </div>
      </div> 
    @endif
    <!-- End Delete File Modal -->
@stop

@section('javascript')
<script src="../../../js/lodash.min.js"></script>
<script src="../../../js/mention.js"></script>
    <script>
        var params = {};
        var $file;

        function deleteHandler(tag, isMulti) {
          return function() {
            $file = $(this).siblings(tag);

            params = {
                slug:   '{{ $dataType->slug }}',
                filename:  $file.data('file-name'),
                id:     $file.data('id'),
                field:  $file.parent().data('field-name'),
                multi: isMulti,
                _token: '{{ csrf_token() }}'
            }

            $('.confirm_delete_name').text(params.filename);
            $('#confirm_delete_modal').modal('show');
          };
        }

        $('document').ready(function () {
            $('.toggleswitch').bootstrapToggle();

            //Init datepicker for date fields if data-datepicker attribute defined
            //or if browser does not handle date inputs
            $('.form-group input[type=date]').each(function (idx, elt) {
                if (elt.hasAttribute('data-datepicker')) {
                    elt.type = 'text';
                    $(elt).datetimepicker($(elt).data('datepicker'));
                } else if (elt.type != 'date') {
                    elt.type = 'text';
                    $(elt).datetimepicker({
                        format: 'L',
                        extraFormats: [ 'YYYY-MM-DD' ]
                    }).datetimepicker($(elt).data('datepicker'));
                }
            });

            @if ($isModelTranslatable)
                $('.side-body').multilingual({"editing": true});
            @endif

            $('.side-body input[data-slug-origin]').each(function(i, el) {
                $(el).slugify();
            });

            $('.form-group').on('click', '.remove-multi-image', deleteHandler('img', true));
            $('.form-group').on('click', '.remove-single-image', deleteHandler('img', false));
            $('.form-group').on('click', '.remove-multi-file', deleteHandler('a', true));
            $('.form-group').on('click', '.remove-single-file', deleteHandler('a', false));

            $('#confirm_delete').on('click', function(){
                $.post('{{ route('voyager.'.$dataType->slug.'.media.remove') }}', params, function (response) {
                    if ( response
                        && response.data
                        && response.data.status
                        && response.data.status == 200 ) {

                        toastr.success(response.data.message);
                        $file.parent().fadeOut(300, function() { $(this).remove(); })
                    } else {
                        toastr.error("Error removing file.");
                    }
                });

                $('#confirm_delete_modal').modal('hide');
            });
            $('[data-toggle="tooltip"]').tooltip();
            $(document).on("change", "#option-type-fizica", function() {
              $(".container-inputs-juridica").hide();
              $(".container-inputs-fizica").show();
            });
            $(document).on("change", "#option-type-juridica", function() {
              $(".container-inputs-fizica").hide();
              $(".container-inputs-juridica").show();
            });
            $('#select_client select').on('select2:select', function (e) {
              var data = e.params.data;
                if(data.id == -1){
                  $(".container-doua-col-right").show();
                } else{
                  $(".container-doua-col-right").hide();
                }
            });
            $("input[name=offer_date]").prop("readonly", true).css('cursor', 'not-allowed');
            $(".show-hide-log").click(function(){
              if($(this).hasClass('show-log')){
                $(this).removeClass('show-log').addClass('hide-log');
                $(this).find(".control-label").text("Vezi log");
                $(".log-evenimente>.log-evenimente-list").css("display", 'none');
              } else{
                $(this).removeClass('hide-log').addClass('show-log');
                $(this).find(".control-label").text("Ascunde log");
                $(".log-evenimente>.log-evenimente-list").css("display", 'flex');
              }
            });
//           var newOption = new Option("Client nou", -1, false, false);
//           $("#select_client>select").append(newOption).trigger('change');
          $("#agent_oferta>input").val("{{Auth::user()->name}}");
          $("#agent_oferta>input").attr("disabled","disabled");
          var isEdit = {!! $edit != "" ? 'true' : 'false' !!};
          if(!isEdit){
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            $("input[name=offer_date]").val(now.toISOString().slice(0,10));
          }
          var isNewClient = {!! $isNewClient != "" && $isNewClient == true ? 'true' : 'false' !!};
          console.log(isNewClient);
          if(isNewClient && !isEdit){
            var newOption = new Option("Adauga client nou", -1, false, false);
            $("select[name=client_id]").append(newOption).val(-1).trigger('change');
          }
          $(".btnOferta").click(function(){
            $("#oferta").show();
            $("#awb").hide();
          });
          $(".btnOfertaAwb").click(function(){
            $("#awb").show();
            $("#oferta").hide();
          });
          if(isEdit){
            $("#tip_oferta > select").prop("disabled", "disabled");
            $("#data_oferta > input").attr('readonly', true);
            $("#data_oferta > input").css("cursor", "not-allowed !important");
            $("#tip_oferta .selection>span").css("cursor", "not-allowed !important");
            $("input[name=price_grid_id]").prop("type", "hidden");
            
            var tip_oferta_label = $("#tip_oferta > label").html();
            $("#tip_oferta").html('');
            $("#tip_oferta").append(tip_oferta_label);
            $("#tip_oferta").append("<input name='type' type='hidden' class='form-control' value='{{$dataTypeContent->type}}'/>");
            $("#tip_oferta").append("<input type='text' readonly class='form-control' value='{{$offerType->title ?? ''}}'/>");
          }
          var new_client_clone = $(".container-doua-col-right").clone();
          if(isEdit){
            $(".container-doua-col-right").remove();
          }
          console.log(new_client_clone);
          $("#select_client > select").on('select2:select', function (e) {
             var data = e.params.data;
              if(data.id != -1){
                 var vthis = this;
                 $(".container-box-adresa select").prop('selectedIndex',0);
                 $(".container-box-adresa input").val('');
                 $.ajax({
                      method: 'POST',
                      url: '/getUserAddresses',
                      data: {_token: $("meta[name=csrf-token]").attr("content"), user_id: data.id},
                      context: this,
                      async: true,
                      cache: false,
                      dataType: 'json'
                  }).done(function(res) {
                      if (res.success == false) {
                          toastr.error(res.error, 'Eroare');
                      } else{
                        var html_addr = completeWithAddresses(res.userAddresses);
                        var html_user_addresses = html_addr[0];
                        var html_awb_addresses = html_addr[1];
                        $("#deliveryAddressAWB").html(html_awb_addresses);
                        $("select[name=delivery_address_user]").html(html_user_addresses);
//                         var transparent_band = res.transparent_band == 1 ? true : false;
//                         $("input[name=transparent_band]").prop("checked", transparent_band).trigger("click");
                        console.log(res.transparent_band);
                        $('select[name=transparent_band] option[value='+res.transparent_band+']').prop('selected', true).trigger("change");
                      }
                  })
                  .fail(function(xhr, status, error) {
                      if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                          .indexOf("CSRF token mismatch") >= 0) {
                          window.location.reload();
                      }
                  });
                return false;
              } else{
                $("#create_new_client>.modal-dialog .modal-body").html(new_client_clone[0]);
                $('#create_new_client').modal('show');
              }
          });
          $("select[name=delivery_address_user]").on('select2:select', function (e) {
            console.log($(this).val());
            if($(this).val() == -2){
              $(".container-box-adresa .select-country").prop('selectedIndex',0);
              $(".container-box-adresa .select-city").html('');
              $(".container-box-adresa .select-state").html('');
              $(".container-box-adresa input").val('');
              $(".trick-addr-id").val('');
              $(".btnSalveazaAdresa").text('Salveaza adresa noua');
              $(".container-elements-addresses").slideDown();
            } else{
              $(".container-elements-addresses").show();
              var country = $(this).find('option:selected').attr('country');
              var state_code = $(this).find('option:selected').attr('state_code');
              var state_name = $(this).find('option:selected').attr('state_name');
              var city_id = $(this).find('option:selected').attr('city_id');
              var city_name = $(this).find('option:selected').attr('city_name');
              var address = $(this).find('option:selected').attr('address');
              var phone = $(this).find('option:selected').attr('phone');
              var contact = $(this).find('option:selected').attr('contact');
              var address_id = $(this).find('option:selected').val();
              $(".trick-addr-id").val(address_id);
              $(".container-box-adresa .select-country").val(country);
              window.selectCountryChange($(".container-box-adresa .select-country")[0], state_code, state_name);
              setTimeout(function(){
                window.selectStateChange($(".container-box-adresa .select-state")[0], city_id, city_name, state_code);
              }, 300);
              $(".container-box-adresa input[name=delivery_address]").val(address);
              $(".container-box-adresa input[name=delivery_phone]").val(phone);
              $(".container-box-adresa input[name=delivery_contact]").val(contact);
              $(".btnSalveazaAdresa").text('Modifica adresa');
            }
          });
          $(".btnSalveazaAdresa").click(function(){
            var country = $(this).parent().parent().parent().find(".select-country").find(":selected").val();
            var city = $(this).parent().parent().parent().find(".select-city").find(":selected").val();
            var state = $(this).parent().parent().parent().find(".select-state").find(":selected").val();
            var delivery_address = $(this).parent().parent().parent().find("input[name=delivery_address]").val();
            var delivery_phone = $(this).parent().parent().parent().find("input[name=delivery_phone]").val();
            var delivery_contact = $(this).parent().parent().parent().find("input[name=delivery_contact]").val();
            var address_id = $(".trick-addr-id").val();
            var client_id = $("select[name=client_id] option:selected").val() == '' ? null : $("select[name=client_id] option:selected").val();
             $.ajax({
                  method: 'POST',
                  url: '/saveNewAddress',
                  data: {
                    _token: $("meta[name=csrf-token]").attr("content"), 
                    client_id: client_id,
                    offer_id: "{{$dataTypeContent->getKey()}}",
                    country: country,
                    city: city,
                    state: state,
                    delivery_address: delivery_address,
                    delivery_phone: delivery_phone,
                    delivery_contact: delivery_contact,
                    address_id: address_id,
                  },
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(res) {
                  if (res.success == false) {
                    console.log(res.error);
                      for(var i = 0 ; i < res.error.length; i++){
                        toastr.error(res.error[i], 'Eroare');
                      }
                  } else{
                    var html_addr = completeWithAddresses(res.userAddresses, res.address.id);
                    var html_user_addresses = html_addr[0];
                    var html_awb_addresses = html_addr[1];
                    $("#deliveryAddressAWB").html(html_awb_addresses);
                    $("select[name=delivery_address_user]").html(html_user_addresses);
                    toastr.success(res.msg, 'Success');
                  }
              })
              .fail(function(xhr, status, error) {
                  if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                      .indexOf("CSRF token mismatch") >= 0) {
                      window.location.reload();
                  }
              });
            return false;
            
          });
          $("select[name=price_grid_id]").select2();
          $("select[name=payment_type]").select2();
          $("select[name=delivery_address_user]").select2();
          if(isEdit){
            $("input[name=delivery_date]").parent().append("<input type='text' class='form-control trick-datepicker datepicker-here'/>");
            $("input[name=delivery_date]").hide();
            $(".trick-datepicker").datepicker({
              language: 'ro', 
              dateFormat: 'dd M',
              onSelect: function (fd, d, picker) {
                if (!d) return;
                var day = d.getDate();
                var month = d.getMonth();
                var year = d.getFullYear();
                var fullDate = day+'-'+month+'-'+year;
                $("input[name=delivery_date]").val(fullDate);
                saveNewDataToDb(false);
              }
            }).data('datepicker').selectDate(new Date("{{\Carbon\Carbon::parse($dataTypeContent->delivery_date)->format('Y')}}", "{{\Carbon\Carbon::parse($dataTypeContent->delivery_date)->format('m')}}", "{{\Carbon\Carbon::parse($dataTypeContent->delivery_date)->format('d')}}"));
          }
          completeWithAddresses = function(addresses, selectedAddr = null){
            var html_user_addresses = `
                        <option value="-1" selected disabled>Alege adresa de livrare</option>
                        <option value="-2">Adauga adresa noua</option>`;
            var html_awb_addresses = `<option disabled="" selected="">Alege...</option>`;
            if(addresses.length > 0){
              for(var i = 0; i < addresses.length; i++){
                console.log(selectedAddr);
                console.log(addresses[i].id);
                console.log(addresses[i].id == selectedAddr);
                if(addresses[i].id == selectedAddr){
                  html_user_addresses += `
                    <option 
                      selected
                      value="${addresses[i].id}" 
                      country="${addresses[i].country}" 
                      state_code="${addresses[i].state}" 
                      state_name="${addresses[i].state_name}" 
                      city_id="${addresses[i].city}"  
                      city_name="${addresses[i].city_name}"  
                      address="${addresses[i].address}" 
                      phone="${addresses[i].phone}" 
                      contact="${addresses[i].name}">
                      ${addresses[i].address}, 
                      ${addresses[i].city_name}, 
                      ${addresses[i].state_name}
                    </option>`;
                } else{
                    html_user_addresses += `
                      <option 
                        value="${addresses[i].id}" 
                        country="${addresses[i].country}" 
                        state_code="${addresses[i].state}" 
                        state_name="${addresses[i].state_name}" 
                        city_id="${addresses[i].city}"  
                        city_name="${addresses[i].city_name}"  
                        address="${addresses[i].address}" 
                        phone="${addresses[i].phone}" 
                        contact="${addresses[i].name}">
                        ${addresses[i].address}, 
                        ${addresses[i].city_name}, 
                        ${addresses[i].state_name}
                      </option>`;
                }
                html_awb_addresses += `
                                  <option value="${addresses[i].id}">
                                      ${addresses[i].name} - 
                                      ${addresses[i].address}, 
                                      ${addresses[i].city_name}, 
                                      ${addresses[i].state_name},
                                      ${addresses[i].phone}
                                  </option>`;
              }
            }
            return [html_user_addresses, html_awb_addresses];
          }
            
            function formatState (state) {
              if (!state.id) {
                return state.text;
              }
              var colorValue = state.element.value.split("_");
              if(colorValue.length > 1){
                var $state = $(
                  '<div style="margin-bottom:3px; text-align: left; display: flex;">'+
                      '<span class="color__square" style="background-color: '+colorValue[2]+'"></span>'+
                      '<span class="edit__color-code" style="text-transform: uppercase; text-align: left;">'+colorValue[3]+'</span>'+
                  '</div>'
                );
                $state.find(".select2-selection__rendered").html('<span class="edit__color-code" style="text-transform: uppercase; text-align: left;">'+colorValue[3]+'</span>');
                return $state;
              } else{
                return state.text;
              }
            };
          
            if($(".selectDimension")[0]){
               $(".selectDimension").select2();
            }
            if($(".selectColor")[0]){
               $(".selectColor").select2({templateSelection: formatState, templateResult: formatState});
            }

          $(document).on("input", ".changeQty", function(){
            var currentVal = $(this).val();
            currentVal = currentVal == '' ? 0 : currentVal;
          });

          $("select[name=price_grid_id]").on('select2:select', function (e) {
            var data = e.params.data;
            $(".reducereRon").text('0.00');
            $("input[name=reducere]").val('');
          });
          
          $("select[name=type]").on('select2:select', function (e) {
            var vthis = this;
            var type_id = e.params.data.id;
            $.ajax({
                method: 'POST',
                url: '/admin/getColorsByOfferType',//remove this address on POST message after i get all the address data
                data: {offerTypeId: type_id, _token: $("meta[name=csrf-token]").attr("content")},
                context: this,
                async: true,
                cache: false,
                dataType: 'json'
            }).done(function(res) {
               if(res.success){
                $(".container-preselect-colors").html(res.html_colors);
                $(".selectColorOfferType").select2({templateSelection: formatState, templateResult: formatState});
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
          
          
          var timeoutSelectAttribute = null;
          $("select").on('select2:select', function (e) {
            var vthis = this;
            if($(vthis).hasClass('selectAttribute') && isEdit){
              if($(vthis).hasClass('preselectColor')){
                var selectedColor = $(vthis).val();
                getPreselectedColorsAndUpdate(selectedColor);
              } else{
                clearTimeout(timeoutSelectAttribute);
                  timeoutSelectAttribute = setTimeout(function() {
                    saveNewDataToDb(true, true);
                }, 1500);
              }
            } else{
              setTimeout(function(){ 
                if($(vthis).hasClass('selectAttribute')){
                  saveNewDataToDb(true);
                } else{
                   if((typeof $(vthis).attr('name') !== 'undefined' && $(vthis).attr('name') !== false && $(vthis).attr('name') == 'price_grid_id')){
                    saveNewDataToDb(true, false);
                  } else{
                    if((typeof $(vthis).attr('name') !== 'undefined' && $(vthis).attr('name') !== false && $(vthis).attr('name') == 'client_id') && $(vthis).val() == -1){
                      return false;
                    } else{
                      saveNewDataToDb(false);
                    }
                  }
                }
              }, 1000);
            }
          });
          
          function getPreselectedColorsAndUpdate(selectedColor){
            $.ajax({
                  method: 'POST',
                  url: '/admin/retrievePreselectedColors',//remove this address on POST message after i get all the address data
                  data: {_token: '{{csrf_token()}}', selectedColor: selectedColor, offerId: '{{$dataTypeContent->id}}', offerType: '{{$dataTypeContent->type}}'},
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(res) {
                 if(res.success){
                  var colors = res.colors;
                  if(colors && colors.length > 0){
                    for(var i=0;i<colors.length;i++){
                      var col_for_select = colors[i].attribute_id + '_' + colors[i].selected_color_id + '_' + colors[i].selectedcolor.value + '_' + colors[i].selectedcolor.ral;
                      var selectAttr = ".selectColAttr-" + colors[i].attribute_id;
                      var option_check = selectAttr + " option[value='"+col_for_select+"']";
                      if($(option_check).length > 0){
                        $(selectAttr).prop('selectedIndex',-1).val(col_for_select).trigger('change');
                      }
                    }
                  }
                 }
                setTimeout(function(){
                   saveNewDataToDb(true, true);
                }, 1500);
              })
              .fail(function(xhr, status, error) {
                  if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                      .indexOf("CSRF token mismatch") >= 0) {
                      window.location.reload();
                  }
              });
              return true;
          }
          
          $(document).on("click", "#confirm_create_client", function(){
            var data = $(".form-new-client").serializeArray();
            $.ajax({
                  method: 'POST',
                  url: '/admin/addNewClient',//remove this address on POST message after i get all the address data
                  data: data,
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(res) {
                 if(res.success){
                   $('#create_new_client').modal('hide');
                   $(".modal-alert-container-client").removeClass('show-errors-popup');
                   $(".modal-alert-container-client").html('');
                   $("#create_new_client>.modal-dialog .modal-body").html('');
                   var newOption = new Option(res.client_name, res.client_id, false, false);
                   $("select[name=client_id]").append(newOption).val(res.client_id).trigger('change');
                 } else{
                   var html_err = '';
                   console.log(res.msg);
                   for(var i = 0; i < res.msg.length; i++){
                   console.log(res.msg[i]);
                     html_err += `<li>${res.msg[i]}</li>`;
                   }
                   $(".modal-alert-container-client").html(html_err).addClass('show-errors-popup');
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
          
          var timeout = null;

          $(document).on("keyup", 'input', function() {
              var vthis = this;
              clearTimeout(timeout);
              timeout = setTimeout(function() {
                if($(vthis).attr("name") == "offerQty[]" || $(vthis).attr('name') == 'curs_eur'){
                  saveNewDataToDb(true, false);
                } else{
                  saveNewDataToDb(false);
                }
              }, 500);
          });
          
          $('textarea').keyup(function() {
            if($(this).attr("name") != "mentions_textarea"){
              clearTimeout(timeout);
              timeout = setTimeout(function() {
                  saveNewDataToDb(false);
              }, 500);
            }
          });
          
          $('input[type=date]').change(function() {
              timeout = setTimeout(function() {
                  saveNewDataToDb(false);
              }, 500);
          });
          
          $('input[name=transparent_band]').change(function(){
            timeout = setTimeout(function() {
                  saveNewDataToDb(false);
              }, 500);
          });
          
  
          $("body .comanda-productie .selectAttribute").prop("disabled", true).css("cursor", "no-drop");
          $("body .comanda-productie input[name=curs_eur]").prop("disabled", true).css("cursor", "no-drop");
          $("body .comanda-productie .changeQty").prop("disabled", true).css("cursor", "no-drop");
          $("body .comanda-productie select[name=price_grid_id]").prop("disabled", true).css("cursor", "no-drop");
          $("body .comanda-productie .totalHandled").prop("disabled", true).css("cursor", "no-drop");

          $("#packing>textarea[name=packing]").attr("maxlength", 30);
          $("#delivery_details>textarea[name=delivery_details]").attr("maxlength", 100);
          
          var mod_livrare_cloned = $("#mod_livrare").clone();
          mod_livrare_cloned.find("span").remove();
          mod_livrare_cloned.find("select")
            .removeClass("select2")
            .removeClass("select2-hidden-accessible")
            .removeAttr("data-select2-id")
            .removeAttr("tabindex")
            .removeAttr("aria-hidden")
            .select2();
          
          $(".panel-delivery-method").prepend(mod_livrare_cloned[0]);
          $(".panel-delivery-method").find("#mod_livrare").attr("id", "mod_livrare_detaliu");
          $(".panel-delivery-method").find("#mod_livrare_detaliu").attr("name", "");
          
          $(document).on('select2:select', '#mod_livrare_detaliu>select[name=delivery_type]', function (e) {
            var data = e.params.data;
            console.log(data);
            $(".delivery-method").hide();
            $(".delivery-"+data.id).show();
            $("#mod_livrare>select[name=delivery_type]").val(data.id).trigger("change");
            timeout = setTimeout(function() {
                  saveNewDataToDb(false);
              }, 500);
          });
          
          $("#mod_livrare>select[name=delivery_type]").on('select2:select', function (e) {
            var data = e.params.data;
            console.log(data);
            $(".delivery-method").hide();
            $(".delivery-"+data.id).show();
            $("#mod_livrare_detaliu>select[name=delivery_type]").val(data.id).trigger("change");
            timeout = setTimeout(function() {
                  saveNewDataToDb(false);
              }, 500);
          });
          
          function saveNewDataToDb(getPrices = false, modifyOfferProductsPrices = false){
            if(isEdit){
              var data = $(".form-edit-add").serializeArray();
              if(getPrices){
                data.push({name: 'getPrices', value: true});
                data.push({name: 'modifyOfferProductsPrices', value: modifyOfferProductsPrices});
              }
              $.ajax({
                  method: 'POST',
                  url: '/admin/ajaxSaveUpdateOffer',//remove this address on POST message after i get all the address data
                  data: data,
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(res) {
                 if(res.success){
                  $("input[name=offer_id]").val(res.offer_id);
                  $(".log-evenimente .log-evenimente-list").html(res.html_log);
                  if(getPrices){
                    $(".container-offer-listing-products").html(res.html_prices);
                  }
                 }
              })
              .fail(function(xhr, status, error) {
                  if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                      .indexOf("CSRF token mismatch") >= 0) {
                      window.location.reload();
                  }
              });
              return true;
            }
            
          }
          $(document).on("input", ".totalHandled", function(){
            var totalHandled = $(this).val();
            var totalGeneral = $("input[name=totalGeneral]").val();
            var reducere = totalGeneral - totalHandled;
            var totalFinal = totalHandled;
            $("input[name=reducere]").val(parseFloat(reducere).toFixed(2));
            $("span.reducereRon").text(parseFloat(reducere).toFixed(2));
            $("input[name=totalFinal]").val(parseFloat(totalFinal).toFixed(2));
            $("span.totalFinalRon").text(parseFloat(totalFinal).toFixed(2));
          });
          
          
          $(document).on("click", ".btnGenerateAwb", function(){
            var vthis = this;
            var order_id = "{!! $dataTypeContent && $dataTypeContent->id ? $dataTypeContent->id : 'null' !!}";
            var already_generated = $(this).attr("already_generated");
            var next_step = true;
            if(already_generated == 1){
              if(confirm("AWB-ul a fost deja generat. Doriti regenerarea AWB-ului?")){
                next_step = true;
              } else{ 
                next_step = false;
              }
            }
            if(next_step){
              $.ajax({
                  method: 'POST',
                  url: '/admin/generateAwb',//remove this address on POST message after i get all the address data
                  data: $(".delivery-fan").serializeArray(),
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(resp) {
                  if(resp.success){
                    $(".btnDownloadAwb").attr("href", "/admin/printAwb/" + resp.awb + "/" + resp.client_id);
                    window.open(
                      "/admin/printAwb/" + resp.awb + "/" + resp.client_id,
                      '_blank' 
                    );
                    toastr.success(resp.msg);
                    $(".log-evenimente .log-evenimente-list").html(resp.html_log);
                  } else{
                    for(var key in resp.msg) {
                      toastr.error(resp.msg[key]);
                    }
                  }
              })
              .fail(function(xhr, status, error) {
                  if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                      .indexOf("CSRF token mismatch") >= 0) {
                      window.location.reload();
                  }
              });
              return true;
            }
            return false;
          });
          $(document).on("click", ".btnGenerateAwbNemo", function(){
            var vthis = this;
            var order_id = "{!! $dataTypeContent && $dataTypeContent->id ? $dataTypeContent->id : 'null' !!}";
            var already_generated = $(this).attr("already_generated");
            var next_step = true;
            if(already_generated == 1){
              if(confirm("AWB-ul a fost deja generat. Doriti regenerarea AWB-ului?")){
                next_step = true;
              } else{ 
                next_step = false;
              }
            }
            if(next_step){
              $.ajax({
                  method: 'POST',
                  url: '/admin/generateAwbNemo',//remove this address on POST message after i get all the address data
                  data: $(".delivery-nemo").serializeArray(),
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(resp) {
                  if(resp.success){
                    $(".btnDownloadAwbNemo").attr("href", "/admin/printAwbNemo/" + resp.awb + "/" + resp.client_id + "/" + resp.hash);
                    window.open(
                      "/admin/printAwbNemo/" + resp.awb + "/" + resp.client_id + "/" + resp.hash,
                      '_blank' 
                    );
                    toastr.success(resp.msg);
                    $(".log-evenimente .log-evenimente-list").html(resp.html_log);
                  } else{
                    for(var key in resp.msg) {
                      toastr.error(resp.msg[key]);
                    }
                  }
              })
              .fail(function(xhr, status, error) {
                  if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                      .indexOf("CSRF token mismatch") >= 0) {
                      window.location.reload();
                  }
              });
              return true;    
            }
            return false;
          });
          $(document).on("click", ".btnSchimbaStatus", function(){
            var order_id = $(this).attr("order_id");
            var status = $(this).attr("status");
            var vthis = this;
            $.ajax({
                method: 'POST',
                url: '/admin/changeStatus',//remove this address on POST message after i get all the address data
                data: {
                  order_id: order_id,
                  status: status,
                },
                context: this,
                async: true,
                cache: false,
                dataType: 'json'
            }).done(function(resp) {
                if(resp.success){
                  $(vthis).remove();
                  var html_append = '';
                  if(status != 'livrata' && status != 'retur' && status != 'expediata'){
                    html_append += 
                      `
                         <a class="btn btn-success btn-add-new btnSchimbaStatus" status="expediata" order_id="{{$dataTypeContent->getKey()}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                            <i class="voyager-bolt" style="margin-right: 10px;"></i> <span>Comanda expediata</span>
                        </a> 
                      `;
                  }
                  if(status == 'expediata' && status != 'retur'){
                    html_append +=
                    `
                     <a class="btn btn-success btn-add-new btnSchimbaStatus" status="livrata" order_id="{{$dataTypeContent->getKey()}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                          <i class="voyager-truck" style="margin-right: 10px;"></i> <span>Comanda livrata</span>
                      </a> 
                    `;
                  }
                  if((status == 'livrata' || status == 'expediata')){
                    $(".butoane-oferta").find($(".btnSchimbaStatus[status=retur]")).remove();
                    html_append +=
                    `
                     <a class="btn btn-success btn-add-new btnSchimbaStatus" status="retur" order_id="{{$dataTypeContent->getKey()}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                          <i class="voyager-warning" style="margin-right: 10px;"></i> <span>Comanda retur</span>
                      </a>
                    `;
                    
                  }
                  if(status == 'retur'){
                    $(".butoane-oferta").find($(".btnSchimbaStatus[status=livrata]")).remove();
                    $(".page-title").text($(".page-title").text() + ' - RETUR');
                  }
                  $(".butoane-oferta").append(html_append);
                  $(".log-evenimente .log-evenimente-list").html(resp.html_log);
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
          function checkDiff(arr) {
            return arr.length !== 0 && new Set(arr).size !== 1;
          }
          $(document).on("click", ".btnAcceptOffer", function(){
            var selectedColors = [];
            var launchOrder = false;
            $('.selectColor').each(function(index){
              if ($(this).has('option:selected')){
                var valoareSelectata = $(this).val();
                if(valoareSelectata != null){
                  valoareSelectata = valoareSelectata.split("_");
                  // iar valoarea RAL si o pun in selectedColors pentru a compara mai tarziu daca am diferente de culori
                  selectedColors.push(valoareSelectata[3]);
                }
              }
            });
            // verific daca exista diferente de culori pentru a afisa un mesaj
            if(checkDiff(selectedColors)){
              if(confirm("Comanda pe care urmeaza sa o lansati are culori diferite. Doriti sa lansati comanda?")){
                launchOrder = true;
              } else{
                launchOrder = false;
              }
            } else{
              launchOrder = true;
            }
            if(launchOrder){
              var order_id = $(this).attr("order_id");
              var vthis = this;
              $.ajax({
                  method: 'POST',
                  url: '/admin/launchOrder',//remove this address on POST message after i get all the address data
                  data: {
                    order_id: order_id,
                  },
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(resp) {
                  if(resp.success){
                    $(vthis).remove();
                    var html_append = '';
                    if(resp.status == 'noua'){
                      $(".page-content.edit-add.container-fluid").addClass("comanda-productie");
                      $("body .comanda-productie .selectAttribute").prop("disabled", true).css("cursor", "no-drop");
                      $("body .comanda-productie input[name=curs_eur]").prop("disabled", true).css("cursor", "no-drop");
                      $("body .comanda-productie .changeQty").prop("disabled", true).css("cursor", "no-drop");
                      $("body .comanda-productie select[name=price_grid_id]").prop("disabled", true).css("cursor", "no-drop");
                      $("body .comanda-productie .totalHandled").prop("disabled", true).css("cursor", "no-drop");
                      html_append += 
                        `
                           <a class="btn btn-success btn-add-new btnFisaComanda" target="_blank" href="/admin/generatePDFFisa/${order_id}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                                <i class="voyager-list" style="margin-right: 10px;"></i> <span>Fisa de comanda</span>
                            </a> 
                            <a class="btn btn-success btn-add-new btnSchimbaStatus" status="expediata" order_id="${order_id}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                                <i class="voyager-bolt" style="margin-right: 10px;"></i> <span>Comanda expediata</span>
                            </a> 
                        `;
                    }
                    $(".butoane-oferta").append(html_append);
                    $(".log-evenimente .log-evenimente-list").html(resp.html_log);
                    $(".page-title-text").html(
                    `
                      Comanda #${resp.numar_comanda}
                    `);
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
            }
          });
          if(isEdit){
            var mentionsArray = [];
            var users = @json($adminUsers);
            console.log(users);
            var myMention = new Mention({
              input: document.querySelector('#mentions'),
              reverse: true,
              options: users,
              update: function() {
                var selectedElements = this.collect();
                if(selectedElements.length > 0){
                  var allIds = this.getAllIds();
                  if(allIds.length > 0){
                    $("input[name=mentions]").val(allIds);
                  }
                }
              },
              template: function(option) {
                 return '<img style="width: 20px;height:20px;border-radius: 50%;overflow:hidden; margin-right: 5px;" src="../../../storage/'+option.avatar+'"/>' + option.name
              }
            });
            $(".btnSaveMention").click(function(){
              var order_id = $(this).attr("order_id");
              var vthis = this;
              var btnText = $(this).text();
              $(this).text("Asteptati...");
              $(this).prop('disabled', true);
              $.ajax({
                  method: 'POST',
                  url: '/admin/saveMention',//remove this address on POST message after i get all the address data
                  data: {
                    order_id: order_id,
                    mentionIds: $("input[name=mentions]").val(),
                    message: $("textarea#mentions").val(),
                  },
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(resp) {
                  if(resp.success){
                    $(".log-evenimente-list").html(resp.html_log);
                    $(".log-mesaje").html(resp.html_messages);
                    $("textarea#mentions").val("");
                    $("input[name=mentions]").val("");
                    toastr.success(resp.msg);
                  } else{
                    toastr.error(resp.msg);
                  }
                  $(vthis).prop('disabled', false);
                  $(vthis).text(btnText);
              })
              .fail(function(xhr, status, error) {
                  if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                      .indexOf("CSRF token mismatch") >= 0) {
                      window.location.reload();
                  }
              });
              return true;
            });
          }
        });
    </script>
@stop
