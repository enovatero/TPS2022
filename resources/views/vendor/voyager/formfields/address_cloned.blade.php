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
