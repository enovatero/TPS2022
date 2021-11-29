<html>
<head>
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
	</style>
</head>
<body>
@php
    $offerType = $offer->offerType;
    $parentsWithProducts = $offer->parentsWithProducts();
    $counter = 0;
    $counter1 = 0;
    $counter2 = 0;
    $twoColumns = false;
    if($parentsWithProducts){
      foreach($parentsWithProducts as $parent){
        if($parent->category && $parent->category->two_columns == 1){
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
        @if($offer->attrs() && count($offer->attrs())>0)
          @foreach($offer->attrs() as $attribute)
            <strong>{{$attribute['title']}}:</strong> {{strtoupper($attribute['value'])}}<br>
          @endforeach
        @endif
      </p>
		</td>
	</tr>
</table>
  
@if($twoColumns)
  <table width="100%">
	  <tr>
      <td width="50%">
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
    
  @if($parentsWithProducts)
    @foreach($parentsWithProducts as $parent)
      @if($parent->products && count($parent->products) > 0)
        @foreach($parent->products as $product)
          @php
            if(in_array($product->id, $offer->selected_products) && $parent->category->two_columns == 0){
              $counter1++;
            }
          @endphp
          @if(in_array($product->id, $offer->selected_products) && $parent->category->two_columns == 0)
            <tr class="items {{in_array($product->id, $offer->selected_products) ? 'item_wborder' : ''}}">
              <td align="center">{{$counter1}}</td>
              <td>{{$product->name}}</td>
              <td align="center" @if(in_array($product->id, $offer->selected_products)) class="bold" @endif>{{$parent->um_title->title}}</td>
              <td align="center" @if(in_array($product->id, $offer->selected_products)) class="bold" @endif>{{$parent->qty}}</td>
            </tr>
          @endif
        @endforeach
      @endif
    @endforeach
  @endif
	</tbody>
</table>
      </td>
      <td width="50%">
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
    
  @if($parentsWithProducts)
    @foreach($parentsWithProducts as $parent)
      @if($parent->products && count($parent->products) > 0)
        @foreach($parent->products as $product)
          @php
            if(in_array($product->id, $offer->selected_products) && $parent->category->two_columns == 1){
              $counter2++;
            }
          @endphp
          @if(in_array($product->id, $offer->selected_products) && $parent->category->two_columns == 1)
            <tr class="items {{in_array($product->id, $offer->selected_products) ? 'item_wborder' : ''}}">
              <td align="center">{{$counter2}}</td>
              <td>{{$product->name}}</td>
              <td align="center" @if(in_array($product->id, $offer->selected_products)) class="bold" @endif>{{$parent->um_title->title}}</td>
              <td align="center" @if(in_array($product->id, $offer->selected_products)) class="bold" @endif>{{$parent->qty}}</td>
            </tr>
          @endif
        @endforeach
      @endif
    @endforeach
  @endif
	</tbody>
</table>
      </td>
    </tr>
  </table>
  
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
    
  @if($parentsWithProducts)
    @foreach($parentsWithProducts as $parent)
      @if($parent->products && count($parent->products) > 0)
        @foreach($parent->products as $product)
          @php
            if(in_array($product->id, $offer->selected_products)){
              $counter++;
            }
          @endphp
          @if(in_array($product->id, $offer->selected_products))
            <tr class="items {{in_array($product->id, $offer->selected_products) ? 'item_wborder' : ''}}">
              <td align="center">{{$counter}}</td>
              <td>{{$product->name}}</td>
              <td align="center" @if(in_array($product->id, $offer->selected_products)) class="bold" @endif>{{$parent->um_title->title}}</td>
              <td align="center" @if(in_array($product->id, $offer->selected_products)) class="bold" @endif>{{$parent->qty}}</td>
            </tr>
          @endif
        @endforeach
      @endif
    @endforeach
  @endif
	</tbody>
</table>

@endif
  

<table width="100%">
	<tr>
		<td>
			<p></p>
		</td>
    <td align="right" width="100%">
        <p style="font-size: 15pt; width: 100%;"><b>Responsabil productie:</b></p>
    </td>
	</tr>
</table>

</body>
</html>