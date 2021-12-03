@php
$edit = !is_null($dataTypeContent->getKey());
$add  = is_null($dataTypeContent->getKey());
$isNewClient = false;
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_title', __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        Oferta {{$edit ? '#'.$dataTypeContent->serie : 'noua'}} {{$edit && $dataTypeContent->status_name->title == 'retur' ? ' - RETUR' : ''}}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid {{$edit && $dataTypeContent->status != 1 ? 'comanda-productie' : ''}}">
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
                    <a target="_blank" class="btn btn-success btn-add-new" href="/admin/printAwb/{{$dataTypeContent->fanData->awb}}/{{$dataTypeContent->fanData->cont_id}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                        <i class="voyager-eye" style="margin-right: 10px;"></i> <span> Vizualizeaza AWB</span>
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
                                                      <option value="{{$attr['id']}}_{{$val}}" @if($isSelected || ($attr['title'] == 'Dimensiune sistem scurgere' && $val == '125/087')) selected @endif>{{$val}}</option>
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
                              @include('vendor.voyager.products.offer_box', ['parents' => $offerType->parents, 'reducere' => $dataTypeContent->reducere, 'offer' => $offer])
                            </div>
                          </div>
                          <div class="col-md-12 butoane-oferta">
                            <a target="_blank" class="btn btn-success btn-add-new" href="/admin/generatePDF/{{$dataTypeContent->id}}" style="border-left: 6px solid #57c7d4; color: #57c7d4;margin-left: 15px;">
                                <i class="voyager-download" style="margin-right: 10px;"></i> <span> Descarca oferta PDF</span>
                            </a>  
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
                          <div class="panel-footer">
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
          <div class="col-md-12" id="awb" style="display: none;">
            <div class="panel panel-delivery-method">
              <form class="panel-body form-fan-courier delivery-method delivery-fan" method="POST" @if($edit && $dataTypeContent->delivery_type == 'fan' || $dataTypeContent->delivery_type == null) style="display: block;" @endif>
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
                  <button type="button" class="btn btn-primary btnGreenNew btnGenerateAwb">Genereaza AWB</button>
                </div>
              </form>
              <form class="panel-body form-fan-courier delivery-method delivery-nemo" method="POST" @if($edit && $dataTypeContent->delivery_type == 'nemo' || $dataTypeContent->delivery_type == null) style="display: block;" @endif>
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
                    <div class="form-group">
                      <label for="on">Fragil?</label>
                      <select name="fragil" class="form-control">
                        <option value="off" selected>Nu</option>
                        <option value="on">Da</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="deliveryAccount">Cont Nemo</label>
                      <select name="deliveryAccount" class="form-control">
                          <option disabled="" selected="">Alege...</option>
                          <option @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->fan_client_id == '1') selected @endif value="1">Top Profil Sistem Iasi</option>
                          <option @if($edit && $dataTypeContent->fanData && $dataTypeContent->fanData->fan_client_id == '2') selected @endif value="2">Top Profil Sistem Berceni</option>
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
                  <button type="button" class="btn btn-primary btnGreenNew btnGenerateAwbNemo">Genereaza AWB</button>
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
<script src="../../../js/lodash.min.js"></script>
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
            
            var tip_oferta_label = $("#tip_oferta > label").html();
            $("#tip_oferta").html('');
            $("#tip_oferta").append(tip_oferta_label);
            $("#tip_oferta").append("<input name='type' type='hidden' class='form-control' value='{{$dataTypeContent->type}}'/>");
            $("#tip_oferta").append("<input type='text' readonly class='form-control' value='{{$offerType->title ?? ''}}'/>");
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
                    var transparent_band = res.transparent_band == 1 ? true : false;
                    $("input[name=transparent_band]").prop("checked", transparent_band).trigger("click");
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
          var timeoutSelectAttribute = null;
          $('.selectAttribute').on('select2:select', function (e) {
              var data = e.params.data;
              var attributes = [];
              $('.selectAttribute').each(function(index){
                if ($(this).has('option:selected')){
                  var valoareSelectata = $(this).val();
                  if(valoareSelectata != null){
                    attributes.push(valoareSelectata);
                  }
                }
              });
              if(isEdit){
                 clearTimeout(timeoutSelectAttribute);
                  timeoutSelectAttribute = setTimeout(function() {
                    retrievePricesForSelectedAttributes(attributes, {{$dataTypeContent->id}});
                }, 1500);
              }
          });
          function retrievePricesForSelectedAttributes(attributes, order_id){
            var vthis = this;
            $.ajax({
                method: 'POST',
                url: '/admin/retrievePricesForSelectedAttributes',//remove this address on POST message after i get all the address data
                data: {
                  order_id: order_id,
                  attributes: attributes
                },
                context: this,
                async: true,
                cache: false,
                dataType: 'json'
            }).done(function(res) {
                if(res.success){
                  window.location.reload();
                } else{
                  toastr.error("S-a produs o eroare la calculul preturilor");
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

          $(document).on("input", ".changeQty", function(){
            var parent_id = $(this).attr("parentId");
            var currentVal = $(this).val();
            currentVal = currentVal == '' ? 0 : currentVal;
            // calculez pretul cu cantitatea asta
          });

          $("select[name=price_grid_id]").on('select2:select', function (e) {
            var data = e.params.data;
            $(".reducereRon").text('0.00');
            $("input[name=reducere]").val('');
          });
          
          $("select").on('select2:select', function (e) {
            var vthis = this;
            setTimeout(function(){ 
              if($(vthis).attr('name') == 'price_grid_id'){
                saveNewDataToDb(true);
              } else{
                saveNewDataToDb(false);
              }
            }, 1000);
          });
          
          var timeout = null;

          $('input').keyup(function() {
              var vthis = this;
              clearTimeout(timeout);
              timeout = setTimeout(function() {
                if($(vthis).attr("name") == "offerQty[]"){
                  saveNewDataToDb(true);
                } else{
                  saveNewDataToDb(false);
                }
              }, 500);
          });
          
          $('textarea').keyup(function() {
              clearTimeout(timeout);
              timeout = setTimeout(function() {
                  saveNewDataToDb(false);
              }, 500);
          });
          
          $('input[type=date]').change(function() {
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
          });
          
          $("#mod_livrare>select[name=delivery_type]").on('select2:select', function (e) {
            var data = e.params.data;
            console.log(data);
            $(".delivery-method").hide();
            $(".delivery-"+data.id).show();
            $("#mod_livrare_detaliu>select[name=delivery_type]").val(data.id).trigger("change");
          });
          
          function saveNewDataToDb(reload = false){
            $.ajax({
                method: 'POST',
                url: '/admin/ajaxSaveUpdateOffer',//remove this address on POST message after i get all the address data
                data: $(".form-edit-add").serializeArray(),
                context: this,
                async: true,
                cache: false,
                dataType: 'json'
            }).done(function(res) {
                if(reload){
                  window.location.reload();
                }
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
          
          
          $(".btnGenerateAwb").click(function(){
            var order_id = "{!! $dataTypeContent && $dataTypeContent->id ? $dataTypeContent->id : 'null' !!}";
            var vthis = this;
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
                  window.open(
                    "/admin/printAwb/" + resp.awb + "/" + resp.client_id,
                    '_blank' // <- This is what makes it open in a new window.
                  );
//                   window.location.href = "/admin/printAwb/" + resp.awb;
                  toastr.success(resp.msg);
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
          $(document).on("click", ".btnAcceptOffer", function(){
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
                  if(resp.status == 'productie'){
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
                      `;
                  }
                  $(".butoane-oferta").append(html_append);
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
        });
    </script>
@stop
