@php
$edit = !is_null($dataTypeContent->getKey());
$add  = is_null($dataTypeContent->getKey());
$isNewClient = false;
$priceRules = null;
$priceGridId = null;
$userAddresses = null;
if($edit){
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
}
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_title', __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        Oferta {{$edit ? '#'.$dataTypeContent->serie : 'noua'}}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
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
            @endif
            <div class="col-md-12" id="oferta">

                <div class="panel panel-bordered">
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

                        <div class="container-doua-coloane" style="display: flex;flex-direction: row;justify-content: space-between; flex-wrap: wrap;">
                          <div class="panel-body container-doua-col-left" @if($add) style="width: 50%" @else style="width: 100%" @endif>

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

                            @foreach($dataTypeRows as $row)
                                <!-- GET THE DISPLAY OPTIONS -->
                                @php
                                    $display_options = $row->details->display ?? NULL;
                                    if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
                                        $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
                                    }
                                @endphp
                                @if (isset($row->details->legend) && isset($row->details->legend->text))
                                    <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }};padding: 5px;">{{ $row->details->legend->text }}</legend>
                                @endif

                                <div class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif @if($add) style="width: 100%;" @else style="width: 48%;" @endif>
                                    {{ $row->slugify }}
                                    <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    @include('voyager::multilingual.input-hidden-bread-edit-add')
                                    @if (isset($row->details->view))
                                        @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => ($edit ? 'edit' : 'add'), 'view' => ($edit ? 'edit' : 'add'), 'options' => $row->details])
                                    @elseif ($row->type == 'relationship')
                                        @include('voyager::formfields.relationship', ['options' => $row->details])
                                    @else
                                        {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
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
                                @if($edit)
                                  @if($row->field == 'curs_eur' && count($createdAttributes) > 0)
                                    <div class="form-group col-md-12" style="width: 48%;">
                                          @foreach($createdAttributes as $attr)
                                            <div class="form-group">
                                              <label class="control-label" for="name">{{ucfirst($attr['title'])}}</label>
                                              <select name="selectedAttribute[]" class="form-control {{$attr['type'] == 1 ? 'selectColor' : 'retrievedAttribute'}} selectAttribute">
                                                  <option selected disabled>Selecteaza {{$attr['title']}}</option>
                                                  @foreach($attr['values'] as $val)
                                                    @php
                                                        $offAttrs = $dataTypeContent->attributes != null ? json_decode($dataTypeContent->attributes, true) : null;
                                                        $offerAttributes = [];
                                                        if($offAttrs != null && count($offAttrs) > 0){
                                                          foreach($offAttrs as $att){
                                                            if($att != null){
                                                              array_push($offerAttributes, $att);
                                                            }
                                                          }
                                                        }
                                                    @endphp
                                                    @if(is_array($val) && count($val) > 1)
                                                      @php
                                                        $retrievedAttr = $attr['id'].'_'.$val[0].'_'.$val[1];
                                                        $isSelected = $retrievedAttr != null && $offerAttributes != null && in_array($retrievedAttr, $offerAttributes) ? true : false;
                                                      @endphp
                                                      <option value="{{$attr['id']}}_{{$val[0]}}_{{$val[1]}}" @if($isSelected) selected @endif>{{$val[1]}}</option>
                                                    @else
                                                      @php
                                                        $retrievedAttr = $attr['id'].'_'.$val;
                                                        $isSelected = $retrievedAttr != null && $offerAttributes != null && in_array($retrievedAttr, $offerAttributes) ? true : false;
                                                      @endphp
                                                      <option value="{{$attr['id']}}_{{$val}}" @if($isSelected) selected @endif>{{$val}}</option>
                                                    @endif
                                                  @endforeach
                                              </select>
                                            </div>
                                          @endforeach
                                    </div>
                                  @endif
                                @endif
                            @endforeach
                            @if($edit)
                            <div class="form-group  col-md-12" style="width: 48%;">
                                <div class="form-group  col-md-12" style="width: 100%;">
                                  <label class="control-label">Date livrare</label>
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
                            </div>
                            @endif

                        </div><!-- panel-body -->
                          @if($add)
                            <div class="panel-body container-doua-col-right" @if (count($errors) > 0 && array_key_exists('address', $errors->toArray())) style="display: block !important;" @endif>
                              <div class="form-group  col-md-12 ">     
                                 <h4 class="control-label font-weight-bold" for="name">Adaugare client</h4>     
                              </div>
                              <div class="form-group  col-md-12 ">
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
                                   <input class="control-label" type="text" name="address[]" value="{{ old('address', $dataTypeContent->address ?? '') != '' ? old('address', $dataTypeContent->address)[0] : ''}}"/>                          
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
                          @endif
                        </div>
                        @if($edit)
                        <input name="offer_id" type="hidden" value="{{$dataTypeContent->getKey()}}"/>
                          <div class="col-md-12">
                            <div class="box">
                              @include('vendor.voyager.products.offer_box', ['parents' => $offerType->parents, 'reducere' => $dataTypeContent->reducere])
                            </div>
                          </div>
                        @endif
                        <div class="panel-footer">
                            @section('submit-buttons')
                                <button type="submit" class="btn btn-primary save">{{ __('voyager::generic.save') }}</button>
                            @stop
                            @yield('submit-buttons')
                        </div>
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
          <div class="col-md-12" id="awb" style="display: none;">
            <div class="panel">
              <form class="panel-body" method="POST">
                {{csrf_field()}}
                <input type="hidden" name="order_id" id="order_id" value="{{$dataTypeContent->id}}">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="message-text" class="col-form-label" style="margin-right: 25px;font-size: 14px;">Agentie FAN*</label>
                    <select name="expeditor_agentie" class="form-control" style="width: 230px;">
                      <option value="232">Adjud</option>
                      <option value="1">Alba Iulia</option>
                      <option value="234">Alesd</option>
                      <option value="2">Alexandria</option>
                      <option value="3">Arad</option>
                      <option value="253">Babeni</option>
                      <option value="4">Bacau</option>
                      <option value="5">Baia Mare</option>
                      <option value="256">Baile Govora</option>
                      <option value="6">Barlad</option>
                      <option value="274">Beius</option>
                      <option value="248">Bicaz</option>
                      <option value="7">Bistrita</option>
                      <option value="8">Botosani</option>
                      <option value="9">Braila</option>
                      <option value="10">Brasov</option>
                      <option value="11">Bucuresti</option>
                      <option value="12">Buzau</option>
                      <option value="258">Calafat</option>
                      <option value="13">Calarasi</option>
                      <option value="251">Calimanesti</option>
                      <option value="285">Campeni</option>
                      <option value="15">Campia Turzii</option>
                      <option value="14">Campina</option>
                      <option value="16">Campulung</option>
                      <option value="237">Campulung Moldovenesc</option>
                      <option value="17">Caracal</option>
                      <option value="224">Caransebes</option>
                      <option value="252">Carei</option>
                      <option value="18">Cernavoda</option>
                      <option value="286">Chisoda</option>
                      <option value="19">Cluj-Napoca</option>
                      <option value="269">Comanesti</option>
                      <option value="20">Constanta</option>
                      <option value="21">Craiova</option>
                      <option value="247">Cristuru Secuiesc</option>
                      <option value="22">Curtea de Arges</option>
                      <option value="23">Dej</option>
                      <option value="24">Deva</option>
                      <option value="245">Dragasani</option>
                      <option value="25">Drobeta-Turnu Severin</option>
                      <option value="297">Dumbravita</option>
                      <option value="26">Fagaras</option>
                      <option value="270">Falticeni</option>
                      <option value="233">Fetesti</option>
                      <option value="241">Filiasi</option>
                      <option value="271">Floresti</option>
                      <option value="27">Focsani</option>
                      <option value="263">Gaesti</option>
                      <option value="28">Galati</option>
                      <option value="262">Gheorgheni</option>
                      <option value="29">Giurgiu</option>
                      <option value="243">Gura Humorului</option>
                      <option value="268">Harsova</option>
                      <option value="254">Hateg</option>
                      <option value="244">Horezu</option>
                      <option value="30">Huedin</option>
                      <option value="31">Hunedoara</option>
                      <option value="32">Husi</option>
                      <option value="33">Iasi</option>
                      <option value="265">Iernut</option>
                      <option value="266">Ineu</option>
                      <option value="264">Ludus</option>
                      <option value="34">Lugoj</option>
                      <option value="152">Mangalia</option>
                      <option value="284">Marghita</option>
                      <option value="37">Medias</option>
                      <option value="35">Miercurea-Ciuc</option>
                      <option value="260">Moldova Noua</option>
                      <option value="257">Motru</option>
                      <option value="273">Novaci</option>
                      <option value="38">Odorheiu Secuiesc</option>
                      <option value="39">Oltenita</option>
                      <option value="40">Onesti</option>
                      <option value="41">Oradea</option>
                      <option value="276">Orastie</option>
                      <option value="255">Orsova</option>
                      <option value="43">Pascani</option>
                      <option value="44">Petrosani</option>
                      <option value="42">Piatra-Neamt</option>
                      <option value="45">Pitesti</option>
                      <option value="46">Ploiesti</option>
                      <option value="267">Radauti</option>
                      <option value="221">Ramnicu Sarat</option>
                      <option value="49">Ramnicu Valcea</option>
                      <option value="47">Reghin</option>
                      <option value="48">Resita</option>
                      <option value="50">Roman</option>
                      <option value="222">Rosiori de Vede</option>
                      <option value="249">Roznov</option>
                      <option value="272">Salonta</option>
                      <option value="51">Satu Mare</option>
                      <option value="242">Sebes</option>
                      <option value="52">Sfantu Gheorghe</option>
                      <option value="192">Sibiu</option>
                      <option value="54">Sighetu Marmatiei</option>
                      <option value="55">Sighisoara</option>
                      <option value="298">Simleu Silvaniei</option>
                      <option value="56">Sinaia</option>
                      <option value="57">Slatina</option>
                      <option value="58">Slobozia</option>
                      <option value="275">Sovata</option>
                      <option value="59">Suceava</option>
                      <option value="250">Talmaciu</option>
                      <option value="60">Targoviste</option>
                      <option value="235">Targu Frumos</option>
                      <option value="63">Targu Jiu</option>
                      <option value="62">Targu Mures</option>
                      <option value="223">Targu Neamt</option>
                      <option value="236">Targu Secuiesc</option>
                      <option value="227">Tarnaveni</option>
                      <option value="61">Tecuci</option>
                      <option value="64">Timisoara</option>
                      <option value="261">Toplita</option>
                      <option value="65">Tulcea</option>
                      <option value="226">Turnu Magurele</option>
                      <option value="66">Urziceni</option>
                      <option value="279">Valenii de Munte</option>
                      <option value="67">Vaslui</option>
                      <option value="238">Vatra Dornei</option>
                      <option value="246">Videle</option>
                      <option value="76">Zalau</option>
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label for="message-text" class="col-form-label" style="margin-right: 25px;font-size: 14px;">Localitatea*</label>
                    <select name="expeditor_localitate" class="form-control" style="width: 230px;">
                    </select>
                  </div>
<!--                   <div class="form-group">
                    <label for="deliveryAccount">Cont FAN</label>
                    <select name="deliveryAccount" id="deliveryAccount" class="form-control">
                      <option disabled="" selected="">Alege...</option>
                      <option value="7155019">BERCENI</option>
                    </select>
                  </div> -->
                </div>
                <div class="col-md-12">
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="packages">Nr. colete</label>
                      <input type="text" name="packages" id="packages" class="form-control" value="1">
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="weight">Greutate (kg)</label>
                      <input type="text" name="weight" id="weight" class="form-control" value="1">
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="cashback">Ramburs (ex: 2542.26)</label>
                      <input type="text" name="cashback" id="cashback" class="form-control" value="0.00">
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="height">Inaltime (cm)</label>
                      <input type="text" name="height" id="height" class="form-control">
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="Width">Latime (cm)</label>
                      <input type="text" name="Width" id="Width" class="form-control">
                    </div>
                  </div>
                  <div class="row col-md-3" style="margin-right: 3px !important;">
                    <div class="form-group">
                      <label for="lenght">Lungime (cm)</label>
                      <input type="text" name="lenght" id="lenght" class="form-control">
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="contents">Continut</label>
                    <input type="text" name="contents" id="contents" class="form-control" value="sisteme acoperis">
                  </div>
                </div>
                <div class="col-md-12">
                  <label for="deliveryAddressAWB">Adresa de livrare</label>
                  <select name="deliveryAddressAWB" id="deliveryAddressAWB" class="form-control">
                    <option disabled="" selected="">Alege...</option>
                    @if($userAddresses != null && count($userAddresses) > 0)
                      @foreach($userAddresses as $address)
                        <option value="{{$address->id}}">
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
                  <button type="submit" class="btn btn-primary btnGreenNew btnGenerateAwb">Genereaza AWB</button>
                </div>
              </form>
            </div>
          </div>
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
    <!-- End Delete File Modal -->
@stop

@section('javascript')
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
            $("#option-type-fizica").change(function() {
              $(".container-inputs-juridica").hide();
              $(".container-inputs-fizica").show();
            });
            $("#option-type-juridica").change(function() {
              $(".container-inputs-fizica").hide();
              $(".container-inputs-juridica").show();
            });
//           var newOption = new Option("Client nou", -1, false, false);
//           $("#select_client>select").append(newOption).trigger('change');
          var now = new Date();
          now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
          $("input[name=offer_date]").val(now.toISOString().slice(0,10));
          $("#agent_oferta>input").val("{{Auth::user()->name}}");
          $("#agent_oferta>input").attr("disabled","disabled");
          $('#select_client select').on('select2:select', function (e) {
              var data = e.params.data;
            console.log(data.text);
              if(data.text == "Adauga client nou"){
                $(".container-doua-col-right").show();
              } else{
                $(".container-doua-col-right").hide();
              }
          });
          var isEdit = {!! $edit != "" ? 'true' : 'false' !!};
          if("{{$isNewClient}}" && !isEdit){
            var newOption = new Option("Adauga client nou", -1, false, false);
            $("#select_client>select").append(newOption).trigger('change');
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
            var select_html_prices = "<select name='price_grid_id' class='form-control'>";
            var prRules = {!!$priceRules != "" ? $priceRules : 'false' !!};
            var price_grid_id = {!! $dataTypeContent->price_grid_id != null ? $dataTypeContent->price_grid_id : 1 !!};
            window.currentPriceGrid = price_grid_id;
            if(prRules){
              for(var i = 0; i < prRules.length; i++){
                if(prRules[i].id == price_grid_id){
                  select_html_prices += "<option value='"+prRules[i].id+"' selected>["+prRules[i].code+"] - "+prRules[i].title+"</option>";
                } else{ 
                  select_html_prices += "<option value='"+prRules[i].id+"'>["+prRules[i].code+"] - "+prRules[i].title+"</option>";
                }
              }
            }
            select_html_prices += "</select>";
            $("input[name=price_grid_id]").parent().append(select_html_prices);
            $("input[name=price_grid_id]").remove();
            var tip_oferta_label = $("#tip_oferta > label").html();
            $("#tip_oferta").html('');
            $("#tip_oferta").append(tip_oferta_label);
            $("#tip_oferta").append("<input name='type' type='hidden' class='form-control' value='{{$dataTypeContent->type}}'/>");
            $("#tip_oferta").append("<input type='text' readonly class='form-control' value='{{$offerType->title ?? ''}}'/>");
            
            var selPrices = {!! $dataTypeContent->prices != "" ? $dataTypeContent->prices : "[]"  !!};
            var selAttributes = {!! $dataTypeContent->attributes != "" ? $dataTypeContent->attributes : "[]"  !!};
            if(selPrices != null && selPrices.length > 0){
              var selAttr = [];
              for(var i = 0; i< selPrices.length; i++){
                 if(selPrices[i].qty != null){ 
                  $("input[parentId="+selPrices[i].parent+"]").val(selPrices[i].qty);
                 }
               }
            }
            if(selAttributes != null){
              completePrices(selAttributes, false);
            }
          }
          
          
          $("#select_client > select").on('select2:select', function (e) {
             var data = e.params.data;
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
             $.ajax({
                  method: 'POST',
                  url: '/saveNewAddress',
                  data: {
                    _token: $("meta[name=csrf-token]").attr("content"), 
                    client_id: "{{$dataTypeContent->client_id}}",
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
                      toastr.error(res.error, 'Eroare');
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
          $("select[name=delivery_address_user]").select2();
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
                      '<span class="color__square" style="background-color: '+colorValue[1]+'"></span>'+
                      '<span class="edit__color-code" style="text-transform: uppercase; text-align: left;">'+colorValue[2]+'</span>'+
                  '</div>'
                );
                $state.find(".select2-selection__rendered").html('<span class="edit__color-code" style="text-transform: uppercase; text-align: left;">'+colorValue[2]+'</span>');
                return $state;
              } else{
                return state.text;
              }
            };
          
            if($(".retrievedAttribute")[0]){
               $(".retrievedAttribute").select2();
            }
            if($(".selectColor")[0]){
               $(".selectColor").select2({templateSelection: formatState, templateResult: formatState});
            }
          $('.selectAttribute').on('select2:select', function (e) {
              var data = e.params.data;
              var elements = [];
              $('.selectAttribute').each(function(index){
                if ($(this).has('option:selected')){
                  var valoareSelectata = $(this).val();
                  if(valoareSelectata != null){
                    elements.push(valoareSelectata);
                  }
                }
              });
              if(isEdit){
                completePrices(elements, true);
              }
          });
          function completePrices(elements, reset = true){
            console.log(elements);
            var combinations = checkClassAndParent(elements);
            var arrProductsClasses = [];
            for(var i = 0; i < combinations.length; i++){
              $(".attributeSelector").each(function(){
                var attrNr = parseInt($(this).attr("numberofattributes"));
                var attributes = combinations[i].split(' ');
                if(attributes.length == attrNr){
                  var productAttrs = $.parseJSON($(this).attr('attributes'));
                  if(arraysEqual(attributes, productAttrs)){
                    arrProductsClasses.push($(this).attr("product_id"));
                  }
                }
              });
            }
            $(".attributeSelector").css("background-color", "white");
            var prodIds = [];
            for(var k = 0; k < arrProductsClasses.length; k++){
              var product_id = $(".attributeSelector[product_id="+arrProductsClasses[k]+"]").attr("prod_id");
              var category_id = $(".attributeSelector[product_id="+arrProductsClasses[k]+"]").attr("cat_id");
              var parent_id = $(".attributeSelector[product_id="+arrProductsClasses[k]+"]").attr("par_id");
              var selQty = $(".changeQty[parentId="+parent_id+"]").val();
              prodIds.push(product_id);
              // fetch to get all prices by category and product and currency
              if(reset){
                resetAllInputs();
              }
              getAllPricesByProductAndCategory(product_id, category_id, parent_id, selQty);
              if(!reset){
                setTimeout(function(){  
                  $(".changeQty").trigger("input"); 
                }, 1000);
              }
            }
            if(isEdit){
              $(".selectedProducts").val(prodIds);
              setTimeout(function(){
                var totalFinal = {!! $dataTypeContent->total_final != '' ? $dataTypeContent->total_final : '0' !!};
                var reducere = {!! $dataTypeContent->reducere != '' ? $dataTypeContent->reducere : '0' !!};
                $("input[name=reducere]").val(parseFloat(reducere).toFixed(2));
                $("span.reducereRon").text(parseFloat(reducere).toFixed(2));
                $("input[name=totalFinal]").val(parseFloat(totalFinal).toFixed(2));
                $("input[name=totalCalculatedPrice]").val(parseFloat(totalFinal).toFixed(2));
                $("span.totalFinalRon").text(parseFloat(totalFinal).toFixed(2));
              }, 2000);
            }
          }
          function completeAllInputsWithPrices(parent_id, rulePrices = [], qty = 0, rightFillable = false) {
            if(rulePrices && rulePrices.length > 0){
              for(var i = 0; i < rulePrices.length; i++){
                var rule = rulePrices[i];
                var baseParentEl = ".baseParent-"+parent_id+".baseRule-"+rule.id;
                $(baseParentEl).val(rule.formulas.currency_price);
                var parentEl = "span.parent-"+parent_id+".baseRule-"+rule.id;
                if(rightFillable){
                  if(qty == 0){
                    $(parentEl).text((rule.formulas.currency_price*1).toFixed(2));
                  } else{
                    $(parentEl).text((rule.formulas.currency_price*qty).toFixed(2));
                  }
                } else{
                  $(parentEl).text('0.00');
                }
                if(qty == 0){
                  $(".eurFaraTVA.parent-"+parent_id).val((rule.formulas.eur_prod_price*1).toFixed(2));
                  $(".ronCuTVA.parent-"+parent_id).val((rule.formulas.ron_cu_tva*1).toFixed(2));
//                   $(".ronTotal.parent-"+parent_id).val((rule.formulas.ron_fara_tva*1).toFixed(2));
                  $(".ronTotal.parent-"+parent_id).val('0.00');
                  $(".pret-intrare.parent-"+parent_id).val((rule.formulas.product_price*1).toFixed(2));
                } else{
                  $(".eurFaraTVA.parent-"+parent_id).val((rule.formulas.eur_prod_price*qty).toFixed(2));
                  $(".ronCuTVA.parent-"+parent_id).val((rule.formulas.ron_cu_tva*qty).toFixed(2));
                  $(".ronTotal.parent-"+parent_id).val((rule.formulas.ron_fara_tva*qty).toFixed(2));
                  $(".pret-intrare.parent-"+parent_id).val((rule.formulas.product_price*qty).toFixed(2));
                }
                if(!rightFillable){
                  $(".pretIntrare.parent-"+parent_id).text(parseFloat(rule.formulas.product_price).toFixed(2));
                }
              }
            } else{
              $(".baseParent-"+parent_id).each(function(){
                var basePrice = $(this).val();
                var calcPrice = (basePrice*qty).toFixed(2);
                $(this).parent().find(".parent-"+parent_id).text(calcPrice);
                recalculateTotalPrice(window.currentPriceGrid);
              });
            }
          }
          $(document).on("input", ".changeQty", function(){
            var parent_id = $(this).attr("parentId");
            var currentVal = $(this).val();
            currentVal = currentVal == '' ? 0 : currentVal;
            completeAllInputsWithPrices(parent_id, [], currentVal, true);
            calculateTotalPricePage();
          });
          function checkClassAndParent(elements) {
            var result = [];
            var f = function(prefix, elements) {
              for (var i = 0; i < elements.length; i++) {
                if(prefix == ''){
                  result.push(prefix +''+ elements[i]);
                  f(prefix + elements[i], elements.slice(i + 1));
                } else{
                  result.push(prefix +' '+ elements[i]);
                  f(prefix +' '+ elements[i], elements.slice(i + 1));
                }
              }
            }
            f('', elements);
            return result;
          }
          function arraysEqual(a, b) {
            if (a === b) return true;
            if (a == null || b == null) return false;
            if (a.length !== b.length) return false;
            for (var i = 0; i < a.length; ++i) {
              if (a[i] !== b[i]) return false;
            }
            return true;
         }
          function getAllPricesByProductAndCategory(product_id, category_id, parent_id, selQty = null){
             var vthis = this;
             var ruleprices = [];
             $.ajax({
                  method: 'POST',
                  url: '/admin/getPricesByProductAndCategory',//remove this address on POST message after i get all the address data
                  data: {_token: '{{csrf_token()}}', product_id: product_id, category_id: category_id, currency: $("input[name=curs_eur]").val()},
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(res) {
                  if (res.success == false) {
                      toastr.error(res.error, 'Eroare');
                  } else{
                    window.ruleprices = res.rulePrices;
                    if(window.ruleprices.length > 0){
                      completeAllInputsWithPrices(parent_id, window.ruleprices, selQty);
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
          function recalculateTotalPrice(priceRuleId){
            window.currentPriceGrid = priceRuleId == -1 ? 1 : priceRuleId;
            $(".inputBaseRule").each(function(){
              if($(this).val() != ''){
                var parent_id = $(this).attr("parent_id");
                var qty = $(".changeQty.parentId-"+parent_id).val();
                var price = $(".baseParent-"+parent_id+".baseRule-"+window.currentPriceGrid).val();
                $(".ronTotal.parent-"+parent_id).val((price*qty).toFixed(2));
              }
            });
            calculateTotalPricePage();
          }
          $("select[name=price_grid_id]").on('select2:select', function (e) {
            var data = e.params.data;
            recalculateTotalPrice(data.id);
          });
          function calculateTotalPricePage(){
            var totalPrice = 0;
            $(".ronTotal").each(function(){
              var totPrice = $(this).val();
              totalPrice = totalPrice + parseFloat(totPrice);
            });
            totalPrice = parseFloat(totalPrice).toFixed(2);
            $(".totalGeneralCuTva").text(totalPrice);
            $("input[name=totalGeneral]").val(totalPrice);
            $("input[name=totalFinal]").val(totalPrice);
            $(".totalFinalRon").text(totalPrice);
            $(".totalHandled").val(totalPrice);
            var totalBaseRule = [];
            var totalPretIntrare = 0;
            $(".inputBaseRule").each(function(){
              var base_rule_id = $(this).attr("base_rule_id");
              totalBaseRule[base_rule_id] = 0;
            });
            $(".inputBaseRule").each(function(){
              var base_rule_id = $(this).attr("base_rule_id");
              totalBaseRule[base_rule_id] = parseFloat(totalBaseRule[base_rule_id]) + parseFloat($(this).parent().find(".baseRule-"+base_rule_id).text());
            });
            $(".pretIntrare").each(function(){
              totalPretIntrare = parseFloat(totalPretIntrare) + parseFloat($(this).text());
            });
            $(".totalPricePi").text(totalPretIntrare.toFixed(2));
            if(totalBaseRule.length > 0){
              for (var k in totalBaseRule){
                  if (totalBaseRule.hasOwnProperty(k)) {
                    $(".totalPrice"+k).text(totalBaseRule[k].toFixed(2));
                  }
              }
            }
          }
          function resetAllInputs(){
            $(".changeQty").val('');
            $(".eurFaraTVA").val('0.00');
            $(".ronCuTVA").val('0.00');
            $(".ronTotal").val('0.00');
            $(".totalGeneralCuTva").text('0.00');
            $("input[name=totalGeneral]").val('0.00');
            $("input[name=reducere]").val('0.00');
            $("input[name=totalFinal]").val('0.00');
            $(".totalHandled").val('0.00');
            $(".totalFinalRon").text('0.00');
          }
          
          $("select").on('select2:select', function (e) {
            setTimeout(function(){ 
              saveNewDataToDb();
            }, 1000);
          });
          
          var timeout = null;

          $('input').keyup(function() {
              clearTimeout(timeout);
              timeout = setTimeout(function() {
                  saveNewDataToDb();
              }, 500);
          });
          
          $('textarea').keyup(function() {
              clearTimeout(timeout);
              timeout = setTimeout(function() {
                  saveNewDataToDb();
              }, 500);
          });
          
          $('input[type=date]').change(function() {
              timeout = setTimeout(function() {
                  saveNewDataToDb();
              }, 500);
          });
          
          function saveNewDataToDb(){
            $.ajax({
                method: 'POST',
                url: '/admin/ajaxSaveUpdateOffer',//remove this address on POST message after i get all the address data
                data: $(".form-edit-add").serializeArray(),
                context: this,
                async: true,
                cache: false,
                dataType: 'json'
            }).done(function(res) {
                console.log(res);
               if(res.success){
                $("input[name=offer_id]").val(res.offer_id);
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
          $(".totalHandled").on("input", function(){
            var totalHandled = $(this).val();
            var totalGeneral = $("input[name=totalGeneral]").val();
            var reducere = totalGeneral - totalHandled;
            var totalFinal = totalHandled;
            console.log(reducere);
            $("input[name=reducere]").val(parseFloat(reducere).toFixed(2));
            $("span.reducereRon").text(parseFloat(reducere).toFixed(2));
            $("input[name=totalFinal]").val(parseFloat(totalFinal).toFixed(2));
            $("span.totalFinalRon").text(parseFloat(totalFinal).toFixed(2));
          });
          
          $("select[name=expeditor_agentie]").change(function(){
            var selected_county = $(this).val();
            $.ajax({
                method: 'POST',
                url: '/cityAgentie',//remove this address on POST message after i get all the address data
                data: {
                  county: selected_county,
                },
                context: this,
                async: true,
                cache: false,
                dataType: 'json'
            }).done(function(resp) {
                var $dropdown = $("select[name=expeditor_localitate]");
                $dropdown.empty();
                for(var i = 0; i < resp.length; i++){
                  $dropdown.append($("<option />").val(resp[i].localitate).text(resp[i].localitate));
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
          
          $(".btnGenerateAwb").click(function(){
            
          });
          
        });
    </script>
@stop
