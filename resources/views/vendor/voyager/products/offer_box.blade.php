@php
  $cleanRulePrices = $priceRules;
  $totalCalculat = 0;
  $totalCalculatPi = 0;
  foreach($priceRules as $rule){
    $totalCalculatRules[$rule->id] = 0;
  }
@endphp
<div class="box-body table-responsive no-padding table-prices">
  <input name="selectedProducts" class="selectedProducts" type="hidden"/>
  <table class="table table-hover items">
    <tbody>
      <tr>
        <th></th>
        <th style="font-weight: bold;">Denumire produs</th>
        <th style="text-align:center;font-weight: bold;">U.M.</th>
        <th style="text-align:center;font-weight: bold;">Cantitate</th>
        <th style="text-align:right;font-weight: bold;">EUR <br>fara TVA </th>
        <th style="text-align:right;font-weight: bold;">RON <br>cu TVA </th>
        <th style="text-align:right;font-weight: bold;">RON <br>TOTAL </th>
        <th></th>
        <th style="text-align:right;font-weight: bold;">PI</th>
        @foreach($cleanRulePrices as $rule)
          <th style="text-align:right;font-weight: bold;">{{$rule->title}}</th>
        @endforeach
      </tr>
      @if($parents != null)
        @foreach($parents as $key => $parent)
          <tr parent_id="{{$parent->id}}">
            @php
              $parent->offerProducts = \App\OfferProduct::where('parent_id', $parent->id)->where('offer_id', $offer->id)->first();
            @endphp
            @if($parent->offerProducts != null) 
              <input type="hidden" name="offerProductIds[]" value="{{$parent->offerProducts->id}}"/>
            @endif
            @php
              if($parent->offerProducts != null && $parent->offerProducts->prices != null){
                $checkRule = $parent->offerProducts->prices->filter(function($item) use($offer){
                    return $item->rule_id == $offer->price_grid_id;
                })->first();
                $eurFaraTVA = $checkRule != null ? $checkRule->eur_fara_tva : 0;
                $ronCuTVA = $checkRule != null ? $checkRule->ron_cu_tva : 0;
                $ronTotal = $ronCuTVA*$parent->offerProducts->qty;
                $totalCalculat += $ronTotal;
                $totalCalculatPi += $checkRule != null ? $checkRule->base_price : 0;
              } else{
                $checkRule = null;
                $eurFaraTVA = 0;
                $ronCuTVA = 0;
                $ronTotal = 0;
              }
            @endphp
            <td>
              {{$key+1}}
            </td>
            <td style="text-align: left;"> {{$parent->title}}</td>
            <td style="text-align:center">{{$parent->um_title->title}}</td>
            <td style="text-align:center">
              <input type="number"  @if($parent->offerProducts != null) name="offerQty[]" value="{{$parent->offerProducts->qty}}" @else readonly @endif autocomplete="off" class="form-control input-sm changeQty parentId-{{$parent->id}}" parentId="{{$parent->id}}" style="width: 70px; display:inline">
            </td>
            <td style="text-align:center;">
              <input readonly type="number" autocomplete="off" class="form-control input-sm eurFaraTVA parent-{{$parent->id}}" style="width: 70px; display:inline; cursor: not-allowed;" @if($eurFaraTVA != 0) value="{{$eurFaraTVA}}" @else value="0.00" @endif>
            </td>
            <td style="text-align:center;">
              <input readonly type="number" class="form-control input-sm ronCuTVA parent-{{$parent->id}}" style="width: 70px; display:inline; cursor: not-allowed;" @if($ronCuTVA != 0) value="{{$ronCuTVA}}" @else value="0.00" @endif>
            </td>
            <td style="text-align:center;">
              <input readonly type="number" class="form-control input-sm ronTotal parent-{{$parent->id}}" style="width: 70px; display:inline; cursor: not-allowed;" @if($ronCuTVA != 0) value="{{$ronTotal}}" @else value="0.00" @endif>
            </td>
            <td style="background: lightgrey"></td>
            <td style="text-align: center;">
              <input class="pret-intrare parent-{{$parent->id}}" type="hidden"/>
              @if($checkRule != null)
                <span class="pretIntrare parent-{{$parent->id}}">{{$checkRule->base_price}}</span>
              @else
                <span class="pretIntrare parent-{{$parent->id}}">0.00</span>
              @endif
            </td>
            @if($parent->offerProducts != null && $parent->offerProducts->prices != null)
              @foreach($parent->offerProducts->prices as $rule)
                @php
                  $subtotalRule = $parent->offerProducts->qty*$rule->ron_cu_tva;
                  $totalCalculatRules[$rule->rule_id] += $subtotalRule;
                @endphp
                  <td style="text-align: center;">
                    <input class="baseParent-{{$parent->id}} baseRule-{{$rule->rule_id}} inputBaseRule" parent_id="{{$parent->id}}" base_rule_id="{{$rule->rule_id}}" type="hidden" display="none"/>
                    <span class="parent-{{$parent->id}} baseRule-{{$rule->rule_id}}">{{$subtotalRule}}</span>
                  </td>
              @endforeach
              @if(count($cleanRulePrices) > count($parent->offerProducts->prices))
                <td style="text-align: center;">
                  <input class="baseParent-{{$parent->id}} inputBaseRule" parent_id="{{$parent->id}}" type="hidden" display="none"/>
                  <span class="parent-{{$parent->id}}">0.00</span>
                </td>
              @endif
            @else
              @foreach($cleanRulePrices as $rule)
                <td style="text-align: center;">
                  <input class="baseParent-{{$parent->id}} baseRule-{{$rule->id}} inputBaseRule" parent_id="{{$parent->id}}" base_rule_id="{{$rule->id}}" type="hidden" display="none"/>
                  <span class="parent-{{$parent->id}} baseRule-{{$rule->id}}">0.00</span>
                </td>
              @endforeach
            @endif
            <input style="display: none;" type="hidden" name="parentIds[]" value="{{$parent->id}}"/>
          </tr>
        @endforeach
      @endif

      <tr class="total">
        <td colspan="6" class="totals" style="text-align: right;font-weight: bold;">
          <b style="font-weight: bold;">Total general cu TVA inclus - RON -</b>
        </td>
        <td class="totals"><b><span class="totalGeneralCuTva" style="font-weight: bold;">{{$totalCalculat != 0 ? $totalCalculat : '0.00'}}</span></b><input name="totalGeneral" type="hidden" value="{{$totalCalculat != 0 ? $totalCalculat : '0.00'}}"></td>
        <td style="background: lightgrey"></td>
        <td style="text-align: center;font-weight: bold;"><span class="totalPricePi">{{$totalCalculatPi != 0 ? $totalCalculatPi : '0.00'}}</span></td>
        @foreach($cleanRulePrices as $rule)
          <td style="text-align: center;"><span class="totalPrice{{$rule->id}}">{{array_key_exists($rule->id, $totalCalculatRules) && $totalCalculatRules[$rule->id] != 0 ? $totalCalculatRules[$rule->id] : '0.00'}}</span></td>
        @endforeach
      </tr>
      <tr class="total">
        <td colspan="6" class="totals" style="text-align: right;font-weight: bold;"><b style="font-weight: bold;">Reducere - RON -</b></td>
        <td class="totals"><b><span class="reducereRon" style="font-weight: bold;">{{$reducere != null || $reducere != 0 ? number_format($reducere, 2) : '0.00'}}</span></b><input name="reducere" type="hidden" value="{{number_format($reducere, 2)}}"></td>
        <td style="background: lightgrey"></td>
        <th style="text-align:right;font-weight: bold;">PI</th>
        @foreach($cleanRulePrices as $rule)
          <th style="text-align:right;font-weight: bold;">{{$rule->title}}</th>
        @endforeach
      </tr>
      <tr class="total">
        <td colspan="6" class="totals" style="text-align: right;"><b style="font-weight: bold;">Total final - RON -</b></td>
        <td class="totals">
          <input type="number" class="totalHandled" class="form-control" style="width: 100px; float: right; text-align: right" name="totalCalculatedPrice" value="{{$totalCalculat != 0 ? number_format($totalCalculat - $reducere, 2) : '0.00'}}">
          <b style="display: none !important;"><span class="totalFinalRon" style="font-weight: bold;">{{$totalCalculat != 0 ? $totalCalculat : '0.00'}}</span></b>
          <input name="totalFinal" type="hidden">
        </td>
      </tr>
<!--       <tr class="totals">
        <td colspan="7" class="totals"><input type="number" class="totalHandled" class="form-control" style="width: 100px; float: right; text-align: right" name="totalCalculatedPrice"></td>
      </tr> -->
    </tbody>
  </table>
</div>