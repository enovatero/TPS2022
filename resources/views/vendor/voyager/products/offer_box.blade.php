
    <div class="box-header">
      <h3 class="box-title">Produse{{$type != null ? ' '.$type : ''}}</h3>
    </div>
    <div class="box-body table-responsive no-padding">
      <table class="table table-hover items">
        <tbody>
          <tr>
            <th></th>
            <th>Denumire produs</th>
            <th style="text-align:center">U.M.</th>
            <th style="text-align:center">Cantitate</th>
            <th style="text-align:right">EUR <br>fara TVA </th>
            <th style="text-align:right">RON <br>cu TVA </th>
            <th style="text-align:right">RON <br>TOTAL </th>
            <th></th>
            <th style="text-align:right">PI</th>
            <th style="text-align:right">Exclusiv</th>
            <th style="text-align:right">Silviu M</th>
            <th style="text-align:right">Distributie</th>
            <th style="text-align:right">Telefon</th>
            <th style="text-align:right">Client Final</th>
            <th style="text-align:right">Lista</th>
          </tr>
          @if($products != null)
            @foreach($products as $key =>  $product)
              <tr>
                <td>{{$key+1}}</td>
                <td style="text-align: left;"> {{$product->name}} <input type="hidden" name="products[1520][variant_id]" id="product_variant_1520" value="">
                </td>
                <td style="text-align:center">ml</td>
                <td style="text-align:center">
                  <input type="text" autocomplete="off" class="form-control input-sm" style="width: 60px; display:inline" name="products[1520][quantity]" id="product_quantity_1520" data-productid="1520" data-variantid="" data-field="quantity" value="" onkeyup="recalculate(1520,24073,'piramida');" data-oldval="" data-newval="">
                </td>
                <td style="text-align:right">
                  <input type="text" autocomplete="off" class="form-control input-sm" style="width: 60px; display:inline" name="products[1520][price_eur]" id="product_priceEur_1520" value="" tabindex="-1">
                </td>
                <td style="text-align:right">
                  <input type="text" class="form-control input-sm" style="width: 60px; display:inline" name="products[1520][price_ron]" id="product_priceRon_1520" value="" readonly="" tabindex="-1">
                </td>
                <td style="text-align:right">
                  <input type="text" class="form-control input-sm CL_productPrice_piramida" style="width: 60px; display:inline" name="products[1520][price_total]" id="product_priceTotal_1520" value="" readonly="" tabindex="-1">
                </td>
                <td style="background: lightgrey"></td>
                <td style="text-align: right">
                  <span id="product_priceIn_1520"></span>
                  <input type="hidden" name="productPriceIn[1520]" class="CL_productPriceIn_piramida" value="">
                </td>
                <td style="text-align: right">
                  <span id="product_priceRule_1520_2"></span>
                  <input type="hidden" name="productPriceRule[2][1520]" class="CL_productPriceRule_2_piramida" value="">
                </td>
                <td style="text-align: right">
                  <span id="product_priceRule_1520_3"></span>
                  <input type="hidden" name="productPriceRule[3][1520]" class="CL_productPriceRule_3_piramida" value="">
                </td>
                <td style="text-align: right">
                  <span id="product_priceRule_1520_4"></span>
                  <input type="hidden" name="productPriceRule[4][1520]" class="CL_productPriceRule_4_piramida" value="">
                </td>
                <td style="text-align: right">
                  <span id="product_priceRule_1520_5"></span>
                  <input type="hidden" name="productPriceRule[5][1520]" class="CL_productPriceRule_5_piramida" value="">
                </td>
                <td style="text-align: right">
                  <span id="product_priceRule_1520_6"></span>
                  <input type="hidden" name="productPriceRule[6][1520]" class="CL_productPriceRule_6_piramida" value="">
                </td>
                <td style="text-align: right">
                  <span id="product_priceRule_1520_7"></span>
                  <input type="hidden" name="productPriceRule[7][1520]" class="CL_productPriceRule_7_piramida" value="">
                </td>
              </tr>
            @endforeach
          @endif
          
          <tr class="total">
            <td colspan="6" class="totals" style="text-align: right;">
              <b>Total general cu TVA inclus - RON -</b>
            </td>
            <td class="totals">
              <b>
                <span id="subtotal_piramida"></span>
              </b>
              <input type="hidden" name="Hsubtotal_piramida" id="Hsubtotal_piramida" value="0">
            </td>
            <td style="background: lightgrey"></td>
            <td style="text-align: right">
              <span id="total_priceIn_piramida"></span>
            </td>
            <td style="text-align: right">
              <span id="total_priceRule_2_piramida"></span>
            </td>
            <td style="text-align: right">
              <span id="total_priceRule_3_piramida"></span>
            </td>
            <td style="text-align: right">
              <span id="total_priceRule_4_piramida"></span>
            </td>
            <td style="text-align: right">
              <span id="total_priceRule_5_piramida"></span>
            </td>
            <td style="text-align: right">
              <span id="total_priceRule_6_piramida"></span>
            </td>
            <td style="text-align: right">
              <span id="total_priceRule_7_piramida"></span>
            </td>
          </tr>
          <tr class="total">
            <td colspan="6" class="totals" style="text-align: right;">
              <b>Reducere - RON -</b>
            </td>
            <td class="totals">
              <b>
                <span id="discount_piramida"></span>
              </b>
              <input type="hidden" name="Hdiscount_piramida" id="Hdiscount_piramida" value="0">
            </td>
            <td style="background: lightgrey"></td>
            <th style="text-align:right">PI</th>
            <th style="text-align:right">Exclusiv</th>
            <th style="text-align:right">Silviu M</th>
            <th style="text-align:right">Distributie</th>
            <th style="text-align:right">Telefon</th>
            <th style="text-align:right">Client Final</th>
            <th style="text-align:right">Lista</th>
          </tr>
          <tr class="total">
            <td colspan="6" class="totals" style="text-align: right;">
              <b>Total final - RON -</b>
            </td>
            <td class="totals">
              <b>
                <span id="total_piramida"></span>
              </b>
              <input type="hidden" name="Htotal_piramida" id="Htotal_piramida" value="">
            </td>
          </tr>
          <tr class="totals">
            <td colspan="7" class="totals">
              <input type="text" class="form-control" style="width: 100px; float: right; text-align: right" name="Itotal_piramida" id="Itotal_piramida" value="">
            </td>
          </tr>
        </tbody>
      </table>
    </div>