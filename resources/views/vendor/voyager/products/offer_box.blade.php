@php
  $cleanRulePrices = \App\RulesPrice::get();
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
        @foreach($parents as $key =>  $parent)
          @php
            if($parent->products && count($parent->products) > 0){
              foreach($parent->products as &$product){
                $attrs = \App\ProductAttribute::where('product_id', $product->id)->get();
                $atribute = [];
                if($attrs && count($attrs) > 0){
                  foreach($attrs as $attr){
                    array_push($atribute, [
                      'rel_id' => $attr->id,
                      'attr_id' => $attr->attribute_id,
                      'type' => $attr->getType(),
                      'value' => json_decode($attr->value, true) && json_last_error() != 4 ? json_decode($attr->value, true) : $attr->value
                    ]);
                  }
                }
                $product->selectedAttributes = $atribute;
              }
            }
          @endphp
          <tr>
            <td>
              {{$key+1}}
              @foreach($parent->products as $product)
                @php
                  $foundedAttributes = [];
                  foreach($product->selectedAttributes as $selectedAttr){
                    if($selectedAttr['type'] == 1){
                      array_push($foundedAttributes, $selectedAttr['attr_id'].'_'.$selectedAttr['value'][0].'_'.$selectedAttr['value'][1]);
                    }else{
                      array_push($foundedAttributes, $selectedAttr['attr_id'].'_'.$selectedAttr['value']);
                    }
                  }
                  $foundedAttributes = json_encode($foundedAttributes);
                @endphp
                <input style="display: none;" type="text" par_id="{{$parent->id}}" prod_id="{{$product->id}}" cat_id="{{$parent->category->id}}" attributes="{{$foundedAttributes}}" numberOfAttributes="{{count($product->selectedAttributes)}}" parent_id="parent-{{$parent->id}}" product_id="product-{{$product->id}}" class="attributeSelector" value="{{$product->price}}"/>
                <input style="display: none;" type="hidden" value="{{$parent->id}}"/>
              @endforeach
            </td>
            <td style="text-align: left;"> {{$parent->title}}</td>
            <td style="text-align:center">{{$parent->um_title->title}}</td>
            <td style="text-align:center">
              <input type="number" autocomplete="off" class="form-control input-sm changeQty parentId-{{$parent->id}}" parentId="{{$parent->id}}" style="width: 70px; display:inline" name="offerQty[]">
            </td>
            <td style="text-align:center;">
              <input readonly type="number" autocomplete="off" class="form-control input-sm eurFaraTVA parent-{{$parent->id}}" style="width: 70px; display:inline; cursor: not-allowed;" value="0.00">
            </td>
            <td style="text-align:center;">
              <input readonly type="number" class="form-control input-sm ronCuTVA parent-{{$parent->id}}" style="width: 70px; display:inline; cursor: not-allowed;" value="0.00">
            </td>
            <td style="text-align:center;">
              <input readonly type="number" class="form-control input-sm ronTotal parent-{{$parent->id}}" style="width: 70px; display:inline; cursor: not-allowed;" value="0.00">
            </td>
            <td style="background: lightgrey"></td>
            <td style="text-align: center;">
              <input class="pret-intrare parent-{{$parent->id}}" type="hidden"/>
              <span class="pretIntrare parent-{{$parent->id}}">0.00</span>
            </td>
            @foreach($cleanRulePrices as $rule)
              <td style="text-align: center;">
                <input class="baseParent-{{$parent->id}} baseRule-{{$rule->id}} inputBaseRule" parent_id="{{$parent->id}}" base_rule_id="{{$rule->id}}" type="hidden" display="none"/>
                <span class="parent-{{$parent->id}} baseRule-{{$rule->id}}">0.00</span>
              </td>
            @endforeach
            <input style="display: none;" type="hidden" name="parentIds[]" value="{{$parent->id}}"/>
          </tr>
        @endforeach
      @endif

      <tr class="total">
        <td colspan="6" class="totals" style="text-align: right;font-weight: bold;">
          <b style="font-weight: bold;">Total general cu TVA inclus - RON -</b>
        </td>
        <td class="totals"><b><span class="totalGeneralCuTva" style="font-weight: bold;">0.00</span></b><input name="totalGeneral" type="hidden"></td>
        <td style="background: lightgrey"></td>
        <td style="text-align: center;font-weight: bold;"><span class="totalPricePi">0.00</span></td>
        @foreach($cleanRulePrices as $rule)
          <td style="text-align: center;"><span class="totalPrice{{$rule->id}}">0.00</span></td>
        @endforeach
      </tr>
      <tr class="total">
        <td colspan="6" class="totals" style="text-align: right;font-weight: bold;"><b style="font-weight: bold;">Reducere - RON -</b></td>
        <td class="totals"><b><span class="reducereRon" style="font-weight: bold;">{{$reducere != null || $reducere != 0 ? $reducere : '0.00'}}</span></b><input name="reducere" type="hidden" value="{{$reducere}}"></td>
        <td style="background: lightgrey"></td>
        <th style="text-align:right;font-weight: bold;">PI</th>
        @foreach($cleanRulePrices as $rule)
          <th style="text-align:right;font-weight: bold;">{{$rule->title}}</th>
        @endforeach
      </tr>
      <tr class="total">
        <td colspan="6" class="totals" style="text-align: right;"><b style="font-weight: bold;">Total final - RON -</b></td>
        <td class="totals">
          <input type="number" class="totalHandled" class="form-control" style="width: 100px; float: right; text-align: right" name="totalCalculatedPrice">
          <b style="display: none !important;"><span class="totalFinalRon" style="font-weight: bold;">0.00</span></b>
          <input name="totalFinal" type="hidden">
        </td>
      </tr>
<!--       <tr class="totals">
        <td colspan="7" class="totals"><input type="number" class="totalHandled" class="form-control" style="width: 100px; float: right; text-align: right" name="totalCalculatedPrice"></td>
      </tr> -->
    </tbody>
  </table>
</div>