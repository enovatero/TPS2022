@php
    $edit = !is_null($dataTypeContent->getKey());
    $add  = is_null($dataTypeContent->getKey());
    if($add){
      $categories = \App\Category::get();
    } else{
      $formulas = \App\RulePricesFormula::where('rule_id', $dataTypeContent->id)->get();
      $catIds = [];
      if($formulas != null && count($formulas) > 0){
        foreach($formulas as $form){
          array_push($catIds, $form->categorie);
        }
        $categories = \App\Category::whereNotIn('id', $catIds)->get();
      } else{
        $categories = \App\Category::get();
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
                        <div class="container-doua-coloane">
                          <div class="panel-body col-md-3">
                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <h4 class="col-md-12" style="margin-bottom: 25px;">Detalii regula</h4>

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
                            </div><!-- panel-body -->
                          @if($edit)
                            <div class="panel-body col-md-7" style="width: 100%;">
                              <h4 class="col-md-12" style="margin-bottom: 25px;margin-bottom: 25px;border-top: 1px solid #CECECE;padding-top: 2rem;border-bottom: 1px solid #cecece;padding-bottom: 2rem;">Detalii regula</h4>
                              <div>
                                <div class="box-body">
                                    <table class="table table-hover">
                                      <tbody>
                                        <tr>
                                          <th>Tip obiect</th>
                                          <th>Denumire obiect</th>
                                          <th>Formula(Variabile, Operator, Alte date)</th>
                                          <th></th>
                                        </tr> 
                                        @if($formulas && count($formulas) > 0)
                                          @foreach($formulas as $formula)
                                            <tr>
                                              <td>{{$formula->tip_obiect}}</td>
                                              <td>{{$formula->categorie_name}}</td>
                                              <td>{{$formula->full_formula}}</td>
                                              <td class="al_right">
                                                <button type="button" class="btn btn-danger btn-xs btnRemoveFormula" formula_id="{{$formula->id}}" title="sterge">Sterge</button>
                                              </td>
                                              <td style="display: none !important;"><div class="json_input" formula='{{json_encode($formula)}}'></div></td>
                                            </tr>  
                                          @endforeach
                                        @endif
                                        
                                        <tr class="tr-functions">
                                          <td>
                                            <select name="object_type">
                                              <option selected disabled>Alege...</option>
                                              <option value="category">Categorie</option>
                                            </select>
                                          </td>
                                          <td>
                                            <select name="object_id">
                                              <option selected disabled>Alege...</option>
                                              @foreach($categories as $category)
                                                <option value="{{$category->id}}">{{$category->title}}</option>
                                              @endforeach
                                            </select>
                                          </td>
                                          <td>
                                            <select name="variabila">
                                              <option selected disabled>Alege...</option>
                                              <option value="PI">PI</option>
                                            </select>
                                            <select name="operator">
                                              <option selected disabled>Alege...</option>
                                              <option value="+">+</option>
                                              <option value="-">-</option>
                                              <option value="*">*</option>
                                              <option value="/">/</option>
                                              <option value="%">%</option>
                                              <option value="^">^</option>
                                            </select>
                                            <input name="formula" class="" type="number"/>
                                          </td>
                                          <td class="al_right">
                                            <button type="button" class="btn btn-primary btn-xs btnAddFormula btnAddFormula2" style="margin-left:1%" title="Adauga formula">Adauga formula</button>
                                          </td>
                                        </tr>
                                    </tbody>
                                  </table>

                                <p style="margin-top: 1rem;border-bottom: 1px solid #cecece;padding-bottom: 2rem;"><b>Reguli formula:</b>
                                  <br>- Variabile:<br>PI = pret de intrare produs
                                  <br>- Operanzi: * (inmultire), / (impartire), + (adunare), - (scadere), % (procent), ^ (ridicare la putere)
                                  <br>Ex.: PI*1.25 (25% peste pretul de baza)</p>
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
          
          $(".btnAddFormula").click(function(){
            var tip_obiect = $("select[name=object_type] option:selected").val();
            var categorie = $("select[name=object_id] option:selected").val();
            var categorie_name = $("select[name=object_id] option:selected").text();
            var variabila = $("select[name=variabila] option:selected").val();
            var operator = $("select[name=operator] option:selected").val();
            var formula = $("input[name=formula]").val();
            if(tip_obiect == 'Alege...' || categorie == 'Alege...' || variabila == 'Alege...' || operator == 'Alege...'){
              toastr.error("Selecteaza fiecare element al formulei!", 'Eroare');
              return;
            }
            var full_formula;
            if(formula == ''){
              full_formula = variabila;
            } else{
              full_formula = variabila + "" + operator + "" +formula;
            }
            var created_formula = {
              tip_obiect: tip_obiect,
              categorie: categorie,
              categorie_name: categorie_name,
              variabila: variabila,
              operator: operator,
              formula: formula,
              full_formula: full_formula
            };
            var created_formula_json = JSON.stringify(created_formula);
            var html_created = `       
              <tr class="just-inserted">
                <td>${tip_obiect}</td>
                <td>${categorie_name}</td>
                <td>${full_formula}</td>
                <td class="al_right">
                  <button type="button" class="btn btn-danger btn-xs btnRemoveFormula" title="sterge">Sterge</button>
                </td>
                <td style="display: none !important;"><div class="json_input" formula='${created_formula_json}'></div></td>
              </tr> 
            `;
            $(html_created).insertBefore($(".tr-functions"));
            $("select[name=object_type]").prop('selectedIndex',0);
            $("select[name=object_id]").prop('selectedIndex',0);
            $("select[name=variabila]").prop('selectedIndex',0);
            $("select[name=operator]").prop('selectedIndex',0);
            $("input[name=formula]").val("");
            sendFormulasToDb(JSON.parse(created_formula_json), categorie, 'add');
          });
          
          $(document).on("click", ".btnRemoveFormula", function(){
            var formula_id = $(this).attr("formula_id");
            var vthis = this;
            $.ajax({
                method: 'POST',
                url: '/admin/removeFormula',
                data: {_token: '{{csrf_token()}}',formula_id: formula_id},
                context: this,
                async: true,
                cache: false,
                dataType: 'json'
            }).done(function(res) {
                if (res.success == false) {
                    toastr.error(res.error, 'Eroare');
                } else{
                  toastr.success(res.msg, 'Success');
                  $(vthis).parent().parent().remove();
                  var categorie = res.categoryForAdd;
                  if(categorie != null){
                    $("select[name=object_id]").append($('<option>', {
                        value: categorie.id,
                        text: categorie.title
                    }));
                  }
                }
            })
            .fail(function(xhr, status, error) {
                if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                    .indexOf("CSRF token mismatch") >= 0) {
                    window.location.reload();
                }
            });
          });
          function sendFormulasToDb(formulaArray, categorie, type){
            $.ajax({
                method: 'POST',
                url: '/admin/saveRulePrice',
                data: {_token: '{{csrf_token()}}',rule_id:'{{$dataTypeContent->getKey()}}' , formulaArray: formulaArray},
                context: this,
                async: true,
                cache: false,
                dataType: 'json'
            }).done(function(res) {
                if (res.success == false) {
                    toastr.error(res.error, 'Eroare');
                    $(".just-inserted").last().remove();
                } else{
                  toastr.success(res.msg, 'Success');
                  if(res.categorie != null){
                    $("select[name=object_id] option[value='"+res.categorie+"']").remove();
                  }
                }
            })
            .fail(function(xhr, status, error) {
                if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
                    .indexOf("CSRF token mismatch") >= 0) {
                    window.location.reload();
                }
            });
          }
        });
    </script>
@stop
