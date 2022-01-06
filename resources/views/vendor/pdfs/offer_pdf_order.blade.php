<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<style>
      body{
        font-size: 9pt;
        font-family: "DejaVu Sans";
      }
		p {
			margin: 0;
			font-size:8pt;
		}
		td {
			vertical-align: top;
			font-size:9pt;
		}
		table {
			margin: 1mm 0;
		}
		.items td {
			border: solid 1px slategray;
			padding: 1px 5px;
			font-size: 8pt;
		}
		.item_wborder td {
			border-bottom: solid 2px black !important;
		}
		table thead td {
			background-color: #EEEEEE;
			text-align: center;
			border: 0.1mm solid darkgray;
		}
		.items td.blanktotal {
			background-color: #FFFFFF;
			border: 0mm none #000000;
			border-top: 0.1mm solid #000000;
			border-right: 0.1mm solid #000000;
		}
		.items td.totals {
			text-align: right;
			border: 0.1mm solid darkgray;
		}
		table.items {
			border-collapse: collapse;
			border: 2px solid black;
		}
		h3{
			margin: 2pt 0;
			padding: 2pt 0;
			font-size:10pt;
		}
		.bold {
			font-weight: bold;
		}
    * {
      box-sizing: border-box;
    }
   .row {
      margin-left:-5px;
      margin-right:-5px;
     width: 100%;
    }

    .column {
      width: 100%;
      padding: 5px;
    }
	</style>
</head>
<body>
@php
    $counter = 1;
    $counter1 = 1;
    $counter2 = 1;
    $twoColumns = false;
    if($offerProducts){
      foreach($offerProducts as $offerProduct){
        if($offerProduct->getParent->category && $offerProduct->getParent->category->two_columns == 1){
          $twoColumns = true;
        }
      }
    }
@endphp
<table width="100%">
	<tr>
		<td width="48%">
      <p style="font-size: 18pt;">
        {{$offer->offerType->title}}
      </p>
			<p style="font-size: 12pt">
        <strong>Agent</strong>: {{Auth::user()->name}}<br>
      </p>
			<p style="font-size: 12pt">
        <strong>Client</strong>: {{$offer->client ? $offer->client->name : ''}}<br><br>
      </p>
      @if(!$twoColumns)
        <p style="font-size: 12pt">
          <strong>Observatii</strong><br>{{$offer->observations != null ? ucfirst($offer->observations) : 'Fara observatii'}}
        </p>
      @endif
		</td>
		<td width="40%" style="text-align: right; font-size: 18pt">
			Comanda: <b>#{{$offer->numar_comanda}}</b>
			<p style="text-align: left; font-size: 12pt">
        <strong>Livrare:</strong> Ridicare personala
      </p>
			<p style="text-align: left; font-size: 12pt">
        <strong>Detalii livrare:</strong> {{$offer->delivery_details != null ? $offer->delivery_details : '-'}}
      </p>
      @if(!$twoColumns)
        <p style="text-align: left; font-size: 12pt">
          <strong>Ambalare:</strong> {{$offer->packing != null ? $offer->packing : '-'}}
        </p>
        @if($offer->transparent_band == 1)
          <p style="text-align: left; font-size: 12pt">
            <strong>Banda transparenta</strong>
          </p>
        @endif
        <p style="text-align: left; font-size: 12pt">
          <strong>Numar cutii:</strong> {{$offer->boxes}}
        </p>
      @endif
			<p style="text-align: left; font-size: 12pt">
        <strong>Responsabil ambalare:</strong>
      </p>
		</td>
	</tr>
</table>
  <table width="100%">
  <tr>
		<td width="100%" style="text-align: left; font-size: 18pt">
      @if($twoColumns)
        <p style="font-size: 12pt">
          <strong>Observatii</strong><br>{{$offer->observations != null ? ucfirst($offer->observations) : 'Fara observatii'}}
        </p>
      @endif
		</td>
  </tr>
  </table>
<br><br>
<table width="100%">
	<tr>
		<td width="48%">
			<p style="font-size: 14pt">
        @if($attributes && count($attributes)>0)
          @foreach($attributes as $attr)
            {{$attr->attribute->title}}: 
            @if($attr->attribute->type == 0)
              @php
                $dim = \App\Dimension::find($attr->col_dim_id);
              @endphp
              <strong>{{strtoupper($dim->value)}}</strong><br>
            @else 
              @php
                $col = \App\Color::find($attr->col_dim_id);
              @endphp
              <strong>{{strtoupper($col->ral)}}</strong><br>
            @endif
          @endforeach
        @endif
      </p>
		</td>
	</tr>
</table>
  
@if($twoColumns)
   @php 
    $newProducts = []; 
    $newProductsLeft = []; 
    $newProductsRight = []; 
   @endphp
  
   @if($offerProducts)
    @foreach($offerProducts as $offerProduct)
      @if($offerProduct->product && $offerProduct->product != null && $offerProduct->qty > 0)
        @php
          if($offerProduct->getParent->category->two_columns == 0){
            array_push($newProductsLeft, [
              'parent' => $offerProduct->getParent,
              'product' => $offerProduct->product,
              'qty'    => $offerProduct->qty,
              'two_columns' => 0,
            ]);
          }
          if($offerProduct->getParent->category->two_columns == 1){
            array_push($newProductsRight, [
              'parent' => $offerProduct->getParent,
              'product' => $offerProduct->product,
              'qty'    => $offerProduct->qty,
              'two_columns' => 1,
            ]);
          }
        @endphp
      @endif
    @endforeach
    @php
      if($newProductsLeft && count($newProductsLeft) > 0 && $newProductsRight && count($newProductsRight) > 0){
          foreach($newProductsLeft as $key => $item){
            if(array_key_exists($key, $newProductsRight)){
              array_push($newProducts, $item);
              array_push($newProducts, $newProductsRight[$key]);
            }else{
              array_push($newProducts, $item);
            }
          }
        }else if($newProductsLeft && count($newProductsLeft) > 0){
          $newProducts = $newProductsLeft;
        }else{
          $newProducts = $newProductsRight;
        }
        $counterLeft = 1;
        $counterRight = 1;
    @endphp
  @endif
  <div class="row">
      <div class="column">
        <table class="items" width="100%" cellpadding="1">
        <thead>
        <tr>
          <td width="5%">Nr.<br>crt.</td>
          <td>Denumirea produselor</td>
          <td width="5%">U.M.</td>
          <td width="10%">Cantitate</td>
          <td width="10%" style="border-top: 1px solid #ffffff;border-bottom: 1px solid #ffffff;background-color: #ffffff;"></td>
          <td width="5%">Nr.<br>crt.</td>
          <td>Denumirea produselor</td>
          <td width="5%">U.M.</td>
          <td width="10%">Cantitate</td>
        </tr>
        </thead>
        <tbody>

        @if($newProducts)
          @foreach($newProducts as $key => $item)
            @if($item['two_columns'] == 1)
              continue;
            @else
              @if($item['qty'] > 0)
                <tr class="items">
                  <td align="center">{{$counterLeft++}}</td>
                  <td>{{$item['product']->name}}</td>
                  <td align="center">{{$item['parent']->um_title->title}}</td>
                  <td align="center">{{$item['qty']}}</td>
                  @if(array_key_exists($key+1, $newProducts) && $newProducts[$key+1]['two_columns'] == 1)
                    <td align="center" style="border-top: 1px solid #ffffff;border-bottom: 1px solid #ffffff;background-color: #ffffff;"></td>
                    <td align="center">{{$counterRight++}}</td>
                    <td>{{$newProducts[$key+1]['product']->name}}</td>
                    <td align="center">{{$newProducts[$key+1]['parent']->um_title->title}}</td>
                    <td align="center">{{$newProducts[$key+1]['qty']}}</td>
                  @else
                    <td align="center" style="border-top: 1px solid #ffffff;border-bottom: 1px solid #ffffff;background-color: #ffffff;"></td>
                    <td align="center">{{$counterRight++}}</td>
                    <td align="center"></td>
                    <td align="center"></td>
                    <td align="center"></td>
                  @endif
                </tr>
              @endif
            @endif
            
          @endforeach
        @endif
        </tbody>
      </table>
      </div>
  </div>
  
@else
  <table class="items" width="100%" cellpadding="1">
	<thead>
	<tr>
		<td width="5%">Nr.<br>crt.</td>
		<td>Denumirea produselor</td>
		<td width="5%">U.M.</td>
		<td width="10%">Cantitate</td>
	</tr>
	</thead>
	<tbody>
    
  @if($offerProducts)
    @foreach($offerProducts as $offerProduct)
       @if($offerProduct->product && $offerProduct->product != null && $offerProduct->qty > 0)
          <tr class="items item_wborder">
              <td align="center">{{$counter++}}</td>
              <td>{{$offerProduct->product->name}}</td>
              <td align="center" class="bold">{{$offerProduct->getParent->um_title->title}}</td>
              <td align="center" class="bold">{{$offerProduct->qty}}</td>
            </tr>
      @endif
    @endforeach
  @endif
	</tbody>
</table>

@endif
<div class="row">
  <div class="column">
      <table class="items" width="100%" cellpadding="1">
      <thead>
      <tr>
        <p @if($twoColumns) style="font-size: 15pt; width: 100%;text-align:left;" @else style="font-size: 15pt; width: 100%;text-align:right;" @endif><b>Responsabil productie:</b></p>    
        </tr>
        </thead>
      </table>
  </div>
</div>

</body>
</html>