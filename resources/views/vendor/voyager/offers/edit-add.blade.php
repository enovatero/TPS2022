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
                                        @if($selectedAddress != null && $selectedAddress->id == $address->id)
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
              @if($edit)
                <div class="col-md-12">
                  <div class="box">
                    @include('vendor.voyager.products.offer_box', ['products' => $offerType->parents])
                  </div>
                </div>
              @endif
            </div>
          <div class="col-md-12" id="awb" style="display: none;">
            <div class="panel">
              <div class="panel-body">
                <input type="hidden" name="order_id" id="order_id" value="24073">
                <input type="hidden" name="partner_id" id="partner_id" value="7479">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="deliveryAccount">Cont FAN</label>
                    <select name="deliveryAccount" id="deliveryAccount" class="form-control">
                      <option disabled="" selected="">Alege...</option>
                      <option value="7155019">BERCENI</option>
                      <option value="7165267">MPOS</option>
                      <option value="7177309">IASI</option>
                      <option value="7038192">STANDARD</option>
                    </select>
                  </div>
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
              </div>
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
          if("{{$isNewClient}}"){
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
          var isEdit = {!! $edit != "" ? 'true' : 'false' !!};
          if(isEdit){
            $("#tip_oferta > select").prop("disabled", "disabled");
            $("#data_oferta > input").attr('readonly', true);
            $("#data_oferta > input").css("cursor", "not-allowed !important");
            $("#tip_oferta .selection>span").css("cursor", "not-allowed !important");
            $("input[name=price_grid_id]").prop("type", "hidden");
            var select_html_prices = "<select name='price_grid_id' class='form-control'>";
            var prRules = {!!$priceRules != "" ? $priceRules : 'false' !!};
            var price_grid_id = {!! $priceRules != "" ? $priceRules : 'false' !!};
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
          $("select[name=delivery_address_user]").on("change", function(){
            console.log($(this).val());
            if($(this).val() == -2){
              $(".container-box-adresa .select-country").prop('selectedIndex',0);
              $(".container-box-adresa .select-city").html('');
              $(".container-box-adresa .select-state").html('');
              $(".container-box-adresa input").val('');
              $(".trick-addr-id").val('');
              $(".btnSalveazaAdresa").text('Salveaza adresa noua');
//               $(".container-elements-addresses").slideDown();
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
        });
    </script>
@stop
