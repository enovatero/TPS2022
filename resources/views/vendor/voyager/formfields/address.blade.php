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
             <select name="state[]" class="form-control select-state" selectedValue="{{old('state', $dataTypeContent->state ?? '') != '' ? old('state', $dataTypeContent->state)[$key] : ''}}"  state_code="{{old('state', $dataTypeContent->state ?? '') != '' ? in_array($key, old('state', $dataTypeContent->state)) && old('state', $dataTypeContent->state)[$key] : ''}}" state_name="{{old('state_name', $dataTypeContent->state_name ?? '') != '' ? in_array($key, old('state', $dataTypeContent->state_name)) && old('state', $dataTypeContent->state_name)[$key] : ''}}"></select>
          </div>
          <div class="form-group col-md-12 column-element-address">
             <label class="control-label">Oras/Localitate/Sector</label>
             <select name="city[]" class="form-control select-city" selectedValue="{{old('city', $dataTypeContent->city ?? '') != '' ? in_array($key, old('city', $dataTypeContent->city)) && old('city', $dataTypeContent->city)[$key] : ''}}" city_id="{{old('city', $dataTypeContent->city ?? '') != '' ? in_array($key, old('city', $dataTypeContent->city)) && old('city', $dataTypeContent->city)[$key] : ''}}" city_name="{{old('city_name', $dataTypeContent->city_name ?? '') != '' ? old('city_name', $dataTypeContent->city_name)[$key] : ''}}"></select>        
          </div>
          <div class="form-group col-md-12 column-element-address" style="width: 100%;">
             <label class="control-label">Denumire WME</label>
             <input class="control-label" required type="text" name="wme_name[]" style="width: 100%;" value="{{ old('wme_name', $dataTypeContent->wme_name ?? '') != '' ? old('wme_name', $dataTypeContent->wme_name)[$key] : ''}}"/>                          
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
             <select name="state[]" class="form-control select-state" selectedValue="{{old('state', $item->state ?? '') != '' ? old('state', $item->state) : ''}}" state_code="{{old('state', $item->state ?? '') != '' ? old('state', $item->state) : ''}}" state_name="{{$item->state_name}}"></select>
          </div>
          <div class="form-group col-md-12 column-element-address">
             <label class="control-label">Oras/Localitate/Sector</label>
             <select name="city[]" class="form-control select-city" selectedValue="{{old('city', $item->city ?? '') != '' ? old('city', $item->city) : ''}}" city_id="{{old('city', $item->city ?? '') != '' ? old('city', $item->city) : ''}}" city_name="{{$item->city_name}}"></select>        
          </div>
          <div class="form-group col-md-12 column-element-address" style="width: 100%;">
             <label class="control-label">Denumire WME</label>
             <input class="control-label" required type="text" name="wme_name[]" style="width: 100%;" value="{{ old('wme_name', $item->wme_name ?? '') != '' ? old('wme_name', $item->wme_name): ''}}"/>                          
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
    <div class="form-group col-md-12 column-element-address" style="width: 100%;">
       <label class="control-label">Denumire WME</label>
       <input class="control-label" required type="text" name="wme_name[]" style="width: 100%;"/>                          
    </div>
  </div>
@endif