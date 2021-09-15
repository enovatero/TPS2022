@extends('voyager::master')

@section('page_title', $dataType->getTranslatedAttribute('display_name_plural') . ' ' . __('voyager::bread.order'))

@section('page_header')
<h1 class="page-title">
    <i class="voyager-list"></i>Ordonare si creare subtipuri
</h1>
@stop

@section('content')
<div class="page-content container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-heading">
                    <p class="panel-title" style="color:#777">Rearanjeaza elementele si muta-le pentru a seta subtipurile(Ex. Un element devine subtip daca se afla in interiorul unui tip)</p>
                </div>
                <div class="panel-body" style="padding:30px;">
                    <div class="dd">
                        <ol class="dd-list">
                            @foreach ($results as $result)
                               @if($result->parent_id == null)
                                  <li class="dd-item" data-id="{{ $result->id }}">
                                      <div class="dd-handle" style="height:inherit">
                                         <span>{{ $result->{$display_column} }}</span>
                                      </div>
                                      @if($result->children != null && !$result->children->isEmpty())
                                          @include('voyager::offer-types.suborder', ['items' => $result->children])
                                      @endif
                                  </li>
                                @endif
                            @endforeach
                          
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@section('javascript')

<script>
$(document).ready(function () {
//     $('.dd').nestable({
//         maxDepth: 999
//     });
  
      $('.dd').nestable({
        expandBtnHTML: '',
        collapseBtnHTML: '',
        maxDepth: 2
    });

    /**
    * Reorder items
    */
    $('.dd').on('change', function (e) {
        $.post("/admin/orderOffer", {
            order: JSON.stringify($('.dd').nestable('serialize')),
            _token: '{{ csrf_token() }}'
        }, function (data) {
            toastr.success("{{ __('voyager::bread.updated_order') }}");
        });
    });
});
</script>
@stop
