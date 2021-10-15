<div class="box-body table-responsive no-padding table-prices">
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
            <td style="text-align: left;"> {{$product->title}}</td>
            <td style="text-align:center">{{$product->um_title->title}}</td>
            <td style="text-align:center">
              <input type="number" autocomplete="off" class="form-control input-sm cantitate" style="width: 60px; display:inline">
            </td>
            <td style="text-align:center;">
              <input readonly type="number" autocomplete="off" class="form-control input-sm eurFaraTVA" style="width: 60px; display:inline" value="0.00">
            </td>
            <td style="text-align:center;">
              <input readonly type="number" class="form-control input-sm ronCuTVA" style="width: 60px; display:inline" value="0.00">
            </td>
            <td style="text-align:center;">
              <input readonly type="number" class="form-control input-sm ronTotal" style="width: 60px; display:inline" value="0.00">
            </td>
            <td style="background: lightgrey"></td>
            <td style="text-align: center;"><span></span></td>
            <td style="text-align: center;"><span></span></td>
            <td style="text-align: center;"><span></span></td>
            <td style="text-align: center;"><span></span></td>
            <td style="text-align: center;"><span></span></td>
            <td style="text-align: center;"><span></span></td>
            <td style="text-align: center;"><span></span></td>
          </tr>
        @endforeach
      @endif

      <tr class="total">
        <td colspan="6" class="totals" style="text-align: right;">
          <b>Total general cu TVA inclus - RON -</b>
        </td>
        <td class="totals"><b><span></span></b><input type="hidden"></td>
        <td style="background: lightgrey"></td>
        <td style="text-align: center;"><span></span></td>
        <td style="text-align: center;"><span></span></td>
        <td style="text-align: center;"><span></span></td>
        <td style="text-align: center;"><span></span></td>
        <td style="text-align: center;"><span></span></td>
        <td style="text-align: center;"><span></span></td>
        <td style="text-align: center;"><span></span></td>
      </tr>
      <tr class="total">
        <td colspan="6" class="totals" style="text-align: right;"><b>Reducere - RON -</b></td>
        <td class="totals"><b><span></span></b><input type="hidden"></td>
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
        <td colspan="6" class="totals" style="text-align: right;"><b>Total final - RON -</b></td>
        <td class="totals"><b><span></span></b><input type="hidden"></td>
      </tr>
      <tr class="totals">
        <td colspan="7" class="totals"><input type="text" class="form-control" style="width: 100px; float: right; text-align: right"></td>
      </tr>
    </tbody>
  </table>
</div>