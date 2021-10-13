@php
    $edit = !is_null($dataTypeContent->getKey());
    $add  = is_null($dataTypeContent->getKey());
    $currencyBNR = \App\Http\Controllers\Admin\CursBNR::getExchangeRate("EUR");
    $selectedProducts = [];
    if($edit){
      $selectedIds = json_decode($dataTypeContent->products, true);
      $selectedProducts = [];
      if($selectedIds != null && count($selectedIds) > 0){
        $products = \App\ProductParent::orderBy('title', 'ASC')->whereNotIn('id', $selectedIds)->get();
        $selectedProducts = \App\ProductParent::whereIn('id', $selectedIds)->orderBy('id')->get();
      } else{
        $products = \App\ProductParent::orderBy('title', 'ASC')->get();
      }
    } else{
      $products = \App\ProductParent::orderBy('title', 'ASC')->get();
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
        {{ __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">

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
                                      @php
                                        if($row->field == 'subtypes'){
                                          $subtypes = $dataTypeContent->subtypes;
                                        }
                                      @endphp
                                        
                                        @if($row->field == 'subtypes')
                                          <input class="retrieved_subtipes" value="{{$subtypes}}" type="hidden"/>
                                        @endif
                                        <div class="btn__input--cont">     
                                        <span class="bnrCursInput">
                                        {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                    </span>
                                        @if($row->field == 'exchange')
                                          <button type="button" class="btn btn-success ml-5 btnCursBnr">Curs EUR BNR</button>
                                        @endif
                                        </div>
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
                            @if($add)
                              <input class="prodsSerialized" name="prodsSerialized" type="hidden"/>
                            @endif
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
            <div class="page-content edit-add container-fluid">
                <div class="text">
                    <h3>Produse din oferta</h3>
                    <p>Poti selecta produsele care apartin ofertei, din lista de produse din stanga, tragand produsele in coloana din dreapta.</p>
                </div>
            </div>
            <div class="col-md-12 panel panel-bordered">
            <div class="container container-products-types">
                <div class="half">
                    <h3 class="text-center">Toate produsele</h3>
                    <ul class="feature" id='left-lovehandles'>
                        @if($products && count($products) > 0)
                          @foreach($products as $product)
                            <li class="feature-item">
                                <div class="feature-inner">
                                    <div class="feature-text">
                                        <input type="hidden" class="hidden-product-id" value="{{$product->id}}" name="prodIds[]"/>
                                        <p><img src="../../../images/draggable.png" class="handle"/></p>
                                        <p>{{$product->title}}</p>
                                    </div>
                                </div>
                            </li>
                          @endforeach
                        @else
                          Niciun produs disponibil
                        @endif
                    </ul>
                </div>

                <div class="half">
                    <h3 class="text-center">Produse din oferta</h3>
                    <ul class="feature" id='right-lovehandles'>
                       @foreach($selectedProducts as $product)
                          <li class="feature-item">
                              <div class="feature-inner">
                                  <div class="feature-text">
                                      <input type="hidden" class="hidden-product-id" value="{{$product['id']}}" name="prodIds[]"/>
                                      <p><img src="../../../images/draggable.png" class="handle"/></p>
                                      <p>{{$product['title']}}</p>
                                  </div>
                              </div>
                          </li>
                        @endforeach
                    </ul>
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
    <script src="../../../js/dragster.min.js" type="text/javascript"></script>
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
//             if($(".retrieved_subtipes").val() != ''){
//               var subtypes = $.parseJSON($(".retrieved_subtipes").val());
//               if(subtypes.length > 0){
//                 for(var i=0;i<subtypes.length;i++){
//                   var text = subtypes[i];
//                   var newOption = new Option(text, text, true, true);
//                   $("#id_subtypes>select.select2").append(newOption).trigger('change');
//                 }
//               }
//             }
            $(".btnCursBnr").click(function(){
              $(this).parent().find("input[name=exchange]").val("{{$currencyBNR}}");
            });
        });
      var options = {
        moves: function (el, container, handle) {
          return handle.classList.contains('handle');
        }
      };

      var dragster = new Dragster(options, document.getElementById('left-lovehandles'), document.getElementById('right-lovehandles'));
      dragster.on('drop', function (el, container) {
          var prodsSerialized = JSON.stringify($("#right-lovehandles .hidden-product-id").serializeArray());
          if("{{$add}}"){
            $(".prodsSerialized").val(prodsSerialized);
          } else{
            console.log(prodsSerialized);
            $.ajax({
                method: 'POST',
                url: '/admin/saveOfferTypeProducts',
                data: {_token: '{{csrf_token()}}',type_id:'{{$dataTypeContent->getKey()}}' , prodIds: prodsSerialized},
                context: this,
                async: true,
                cache: false,
                dataType: 'json'
            }).done(function(res) {
                if (res.success == false) {
                    toastr.error(res.error, 'Eroare');
                } else{
                  toastr.success(res.msg, 'Success');
                }
            })
            .fail(function(xhr, status, error) {
                if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                    .indexOf("CSRF token mismatch") >= 0) {
                    window.location.reload();
                }
            });
          }
      })
    </script>
@stop
