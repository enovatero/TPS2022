@if(old('address', $dataTypeContent->address ?? '') != '' || (isset($addresses) && $addresses && count($addresses) > 0))
  @if(old('address', $dataTypeContent->address ?? '') != '')
    @foreach(old('address', $dataTypeContent->address ?? '') as $key => $item)
        <div class="panel-body container-box-adresa">
          @if(!$removeDelete)
            <div class="container-delete-adresa btnDeleteAddress"><span class="voyager-x"></span></div>
          @endif
          <div class="form-group col-md-12 column-element-address">
             <label class="control-label">Introdu adresa(strada, nr, bloc, etaj, ap)</label>
             <input class="control-label" required type="text" name="address[]" data-google-address autocomplete="off" value="{{ old('address', $dataTypeContent->address ?? '') != '' ? old('address', $dataTypeContent->address)[$key] : ''}}"/>                          
          </div>
          <div class="form-group col-md-12 column-element-address">
             <label class="control-label">Tara</label>
                @include('vendor.voyager.formfields.countries', ['selected' => old('country', $dataTypeContent->country ?? '') != '' ? old('country', $dataTypeContent->country)[$key] : null])                       
          </div>
          <div class="form-group col-md-12 column-element-address">
             <label class="control-label" for="state">Judet/Regiune</label>
             <select name="state[]" class="form-control select-state" selectedValue="{{old('state', $dataTypeContent->state ?? '') != '' ? old('state', $dataTypeContent->state)[$key] : ''}}"></select>
          </div>
          <div class="form-group col-md-12 column-element-address">
             <label class="control-label">Oras/Localitate/Sector</label>
             <select name="city[]" class="form-control select-city" selectedValue="{{old('city', $dataTypeContent->city ?? '') != '' ? old('city', $dataTypeContent->city)[$key] : ''}}"></select>        
          </div>
        </div>
      @endforeach
  @else
    @foreach($addresses as $key => $item)
        <div class="panel-body container-box-adresa">
          <input type="hidden" name="ids[]" value="{{$item->id}}"/>
          @if(!$removeDelete && $key != 0)
            <div class="container-delete-adresa btnDeleteAddress" idForDelete="{{$item->id}}"><span class="voyager-x"></span></div>
          @endif
          <div class="form-group col-md-12 column-element-address">
             <label class="control-label">Introdu adresa(strada, nr, bloc, etaj, ap)</label>
             <input class="control-label" required type="text" name="address[]" data-google-address autocomplete="off" value="{{ old('address', $item->address ?? '') != '' ? old('address', $item->address): ''}}"/>                          
          </div>
          <div class="form-group col-md-12 column-element-address">
             <label class="control-label">Tara</label>
                @include('vendor.voyager.formfields.countries', ['selected' => old('country', $item->country ?? '') != '' ? old('country', $item->country) : null])                       
          </div>
          <div class="form-group col-md-12 column-element-address">
             <label class="control-label" for="state">Judet/Regiune</label>
             <select name="state[]" class="form-control select-state" selectedValue="{{old('state', $item->state ?? '') != '' ? old('state', $item->state) : ''}}"></select>
          </div>
          <div class="form-group col-md-12 column-element-address">
             <label class="control-label">Oras/Localitate/Sector</label>
             <select name="city[]" class="form-control select-city" selectedValue="{{old('city', $item->city ?? '') != '' ? old('city', $item->city) : ''}}"></select>        
          </div>
        </div>
      @endforeach
  @endif
@else
  <div class="panel-body container-box-adresa">
    @if(!$removeDelete)
      <div class="container-delete-adresa btnDeleteAddress"><span class="voyager-x"></span></div>
    @endif
    <div class="form-group col-md-12 column-element-address">
       <label class="control-label">Introdu adresa(strada, nr, bloc, etaj, ap)</label>
       <input class="control-label" required type="text" name="address[]" data-google-address autocomplete="off"/>                          
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
@endif