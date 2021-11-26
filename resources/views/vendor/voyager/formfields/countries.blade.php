<select name="country[]" class="form-control select-country" selectedValue="{{$selected != null ? $selected : ''}}">
  <option value="">Alege...</option>
  @php
    $countries = json_decode(setting('admin.default_countries'), true);
  @endphp
  @if($countries && count($countries) > 0)
    @foreach($countries as $country)
      <option value="{{$country['id']}}">{{$country['val']}}</option>
    @endforeach
  @endif
</select>   