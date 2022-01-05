@php
    $edit = !is_null($dataTypeContent->getKey());
    $add  = is_null($dataTypeContent->getKey());
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_title', __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row create__client--page">
            <div class="col-md-12">

                <div class="panel panel-bordered panel__add-client">
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

                        <div class="panel-body">

                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Adding / Editing -->
                            @php
                                $dataTypeRows = $dataType->{($edit ? 'editRows' : 'addRows' )};
                            @endphp
                            
                          <div class="container-elements-left-right" style="width: 100%; display: flex;flex-direction: row;justify-content: space-between;">
                            <div class="container-elements-left" style="width: 50%;">
                            <div class="form__add-client--title" >Detalii client</div>
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

                                  <div class="form-group @if($row->type == 'hidden') hidden @endif col-md-{{ $display_options->width ?? 12 }} {{ $errors->has($row->field) ? 'has-error' : '' }}" @if(isset($display_options->id)){{ "id=$display_options->id" }}@endif>
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
                                  @if($row->field == 'name')
                                    <div class="form-group col-md-12 container-inputs-juridica" @if(isset($dataTypeContent) && $dataTypeContent->type == 'juridica') style="display: block;" @endif>
                                       <label class="control-label" for="name">CUI</label>
                                       <input class="form-control" type="text" name="cui" autocomplete="off" @if(isset($legal_entity) && $legal_entity != null) value="{{$legal_entity->cui}}" @endif/>                          
                                    </div>
                                    <div class="form-group col-md-12 container-inputs-juridica" @if(isset($dataTypeContent) && $dataTypeContent->type == 'juridica') style="display: block;" @endif>
                                       <label class="control-label" for="name">Reg. Com.</label>
                                       <input class="form-control" type="text" name="reg_com" autocomplete="off" @if(isset($legal_entity) && $legal_entity != null) value="{{$legal_entity->reg_com}}" @endif/>                          
                                    </div>
                                    <div class="form-group col-md-12 container-inputs-juridica" @if(isset($dataTypeContent) && $dataTypeContent->type == 'juridica') style="display: block;" @endif>
                                       <label class="control-label" for="name">Banca</label>
                                       <input class="form-control" type="text" name="banca" autocomplete="off" @if(isset($legal_entity) && $legal_entity != null) value="{{$legal_entity->banca}}" @endif/>                          
                                    </div>
                                    <div class="form-group col-md-12 container-inputs-juridica" @if(isset($dataTypeContent) && $dataTypeContent->type == 'juridica') style="display: block;" @endif>
                                       <label class="control-label" for="name">IBAN</label>
                                       <input class="form-control" type="text" name="iban" autocomplete="off" @if(isset($legal_entity) && $legal_entity != null) value="{{$legal_entity->iban}}" @endif/>                          
                                    </div>
                                    <div class="form-group col-md-12 container-inputs-fizica" @if(isset($dataTypeContent) && $dataTypeContent->type == 'juridica') style="display: none;" @endif>
                                       <label class="control-label" for="name">CNP</label>
                                       <input class="form-control" type="text" name="cnp" autocomplete="off" @if(isset($individual) && $individual != null) value="{{$individual->cnp}}" @endif/>                          
                                    </div>
                                    @if(isset($individual) && $individual != null)
                                      <input type="hidden" name="fizica_id" value="{{$individual->id}}"/>
                                    @endif
                                    @if(isset($legal_entity) && $legal_entity != null)
                                      <input type="hidden" name="juridica_id" value="{{$legal_entity->id}}"/>
                                    @endif
                                  @endif
                              @endforeach
                            </div>
                            <div class="container-elements-right" style="width: 50%;">
                            <div class="form__add-client--title" >Detalii adrese client</div>
                              <div class="container-addresses client__adress--container" style="justify-content: flex-start;">
                                <div class="panel-body btn__container--address">
                                  <div type="button" class="btn btn-success btnAddAddress">Adauga adresa</div>
                                  <div type="button" class="btn btn-danger btnRemoveAddress" style="margin-bottom: 5px;">Sterge adresa</div>
                                  <div type="button" class="btn btn-danger btnCancelAddress" style="margin-bottom: 5px; display: none;">Anuleaza stergere</div>
                                </div>
                                <input 
                                       type="hidden" 
                                       name="addressesCounter" 
                                       @if(old('address', $dataTypeContent->address ?? '') != '') 
                                          value="{{count(old('address', $dataTypeContent->address))}}" 
                                       @else 
                                          @if(isset($addresses) && $addresses && count($addresses) > 0)
                                            value="{{count($addresses)}}"
                                          @else
                                            value="1" 
                                          @endif
                                       @endif 
                                       class="addressesCounter"/>
                                <div class="container-addresses-list">
                                    @if(isset($addresses) && $addresses && count($addresses) > 0)
                                      @include('vendor.voyager.formfields.address', ['removeDelete' => false, 'addresses' => $addresses])
                                    @else
                                      @include('vendor.voyager.formfields.address', ['removeDelete' => true, 'addresses' => []])
                                    @endif
                                </div>
                              </div>
                            </div>
                          </div>
                        </div><!-- panel-body -->

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
          <!-- Here -->
          
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
        });
      
      var htmlEdited = `@include('vendor.voyager.formfields.address_cloned', ['removeDelete' => false])`;
      $(".btnAddAddress").click(function(){
        $(".container-addresses-list").append(htmlEdited);
        $(".container-addresses-list>.container-box-adresa").last().find('select').select2();
        $(".btnCancelAddress").hide();
        $(".btnRemoveAddress").show();
        $(".container-delete-adresa").css("display", "none");
        var addrCounter = parseInt($(".addressesCounter").val())+1;
        $(".addressesCounter").val(addrCounter);
        $(".container-addresses-list>.container-box-adresa").last().find('select').prop('required',true);
        $(".container-addresses-list>.container-box-adresa").last().find('input').prop('required',true);
      });
      $(".btnRemoveAddress").click(function(){
        $(this).hide();
        $(".btnCancelAddress").show();
        $(".container-delete-adresa").css("display", "flex");
      });
      $(".btnCancelAddress").click(function(){
        $(this).hide();
        $(".btnRemoveAddress").show();
        $(".container-delete-adresa").css("display", "none");
      });
      $(document).on('click', '.btnDeleteAddress', function(){
        if(confirm("Sunteti sigur ca stergeti aceasta adresa?")){
           var idForDelete = $(this).attr("idForDelete");
           if(typeof(idForDelete) != 'undefined'){
             var vthis = this;
             $.ajax({
                  method: 'POST',
                  url: '/removeAddress',//remove this address on POST message after i get all the address data
                  data: {_token: '{{csrf_token()}}', id: idForDelete},
                  context: this,
                  async: true,
                  cache: false,
                  dataType: 'json'
              }).done(function(res) {
                  if (res.success == false) {
                      toastr.error(res.error, 'Eroare');
                  } else{
                    $(vthis).parent().remove();
                    var addrCounter = parseInt($(".addressesCounter").val())-1;
                    $(".addressesCounter").val(addrCounter);
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
            $(this).parent().remove();
            var addrCounter = parseInt($(".addressesCounter").val())-1;
            $(".addressesCounter").val(addrCounter);
           }
        }
      });
      $(".column-element-address>select").select2();
      $(document).on('select2:open', () => {
        document.querySelector('.select2-search__field').focus();
      });
      $("#option-type-fizica").change(function() {
        $(".container-inputs-juridica").hide();
        $(".container-inputs-fizica").show();
      });
      $("#option-type-juridica").change(function() {
        $(".container-inputs-fizica").hide();
        $(".container-inputs-juridica").show();
      });
      $('.select-country').each(function(item){
        var value = $(this).attr('selectedValue');
        if(value != ''){
          $(this).val(value).trigger('change');
        }
      });
    </script>
    <!-- Only for address field -->
<!--     <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCmM1P-D5Zka0kPEbZSrsR90gpBlDxgm18&callback=initAutocomplete&libraries=places&v=weekly" async></script>
    <script>
      function initAutocomplete() {
        var addressField = $("input[data-google-address]");
        var autocomplete = new google.maps.places.Autocomplete(addressField[0], {
          fields: ["address_components", "geometry"],
          types: ["address"],
        });
        autocomplete.addListener("place_changed", function () {
          var place = autocomplete.getPlace();
          console.log(place);
          let address1 = "";
          let postcode = "";
          for (const component of place.address_components) {
            const componentType = component.types[0];
            switch (componentType) {
              case "street_number": {
                address1 = `${component.long_name} ${address1}`;
            console.log(address1);
                break;
              }
              case "route": {
                address1 += component.short_name;
            console.log(address1);
                break;
              }
              case "postal_code": {
                postcode = `${component.long_name}${postcode}`;
            console.log(postcode);
                break;
              }
              case "postal_code_suffix": {
                postcode = `${postcode}-${component.long_name}`;
            console.log(postcode);
                break;
              }
              case "locality": {
            console.log(component.long_name);
                $(addressField).parent().parent().find("select[name=city]").val(component.long_name);
                break;
              }
              case "administrative_area_level_1": {
            console.log(component.short_name);
                $(addressField).parent().parent().find("select[name=county]").val(component.short_name);
                break;
              }
              case "country":{
            console.log(component.long_name);
                $(addressField).parent().parent().find("select[name=country]").val(component.long_name);
                break;
              }
            }
          }
        });
      }
    </script> -->
@stop
