<html>
<head>
	<style>
		body {font-family: sans-serif;
    font-size: 9pt;
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
    $reducere= $offer->reducere;
@endphp
<table width="100%">
	<tr>
		<td width="10%"><img src="{{Voyager::image(setting('admin.icon_image'))}}" style="height: 80px"></td>
		<td width="{{$offerType && $offerType->header_img != null ? '45%' : '50%'}}" style="color:#0000BB;">
			<span style="font-weight: bold; font-size: 12pt;">{{setting('admin.title')}}</span><br>
			Sediu: {{setting('admin.sediu_tps')}}<br>
			CUI: {{setting('admin.cui_tps')}} | Reg. Com.: {{setting('admin.reg_com_tps')}}<br>
			<b>Agent:</b> {{$offer->agent ? $offer->agent->name : ''}} | Tel: {{$offer->agent ? $offer->agent->phone : ''}}<br>
			<b>Email:</b> {{$offer->agent ? $offer->agent->email : ''}}<br>
			<b>Distribuitor:</b> {{$offer->distribuitor ? $offer->distribuitor->title : ''}}
		</td>
		<td width="{{$offerType && $offerType->header_img != null ? '35%' : '40%'}}" style="text-align: right; font-size: 10pt">
			Oferta: <b>{{'TPS'.$offer->id}} {{$offer->serie}} / {{\Carbon\Carbon::parse($offer->offer_date)->format('d-m-Y')}}</b>
			<p style="font-size: 8pt;"><br>
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
      @if($offerType && $offerType->header_img != null)
        <td width="10%">
          <img src="{{Voyager::image($offerType->header_img)}}" style="width: 80px;"/>
        </td>
      @endif
		</td>
	</tr>
</table>

<table width="100%" cellpadding="1" style="border-collapse: collapse; border: solid 2px black">
	<tr>
		<td width="49%" style="border: 0; padding: 1mm 2mm;">
			<b>Detalii cumparator:</b><br>
			<span style="text-transform: uppercase">{{$offer->client ? $offer->client->name : ''}}</span><br>
			Sediu: {{$offer->delivery_address ? $offer->delivery_address->address : ''}}, {{$offer->delivery_address ? $offer->delivery_address->city_name() : ''}}<br>
			CUI: {{$offer->client ? $offer->client->cui : ''}} | Reg. Com.: {{$offer->client ? $offer->client->reg_com : ''}} <br>
			Email: {{$offer->client ? $offer->client->email : ''}}
		</td>
		<td width="2%" style="border: 0;"></td>
		<td width="49%" style="border: 0; padding: 1mm 2mm;">
            <b>Detalii livrare:</b><br>
            Adresa: {{$offer->delivery_address ? $offer->delivery_address->address : ''}}, {{$offer->delivery_address ? $offer->delivery_address->city_name() : ''}}<br>
            Persoana de contact: {{$offer->client ? $offer->client->name : ''}}<br>
            Telefon: {{$offer->client ? $offer->client->phone : ''}}<br>
			Data de livrare: {{\Carbon\Carbon::parse($offer->offer_date)->format('d-m-Y')}}
		</td>
	</tr>
</table>

@if($offerType && $offerType->header_img != null)
  <table>
    <tr>
      <td>
        <img src="{{Voyager::image($offerType->left_img)}}" style="width: 140px;">
      </td>
      <td>
@endif
<table class="items" width="100%" cellpadding="1">
	<thead>
	<tr>
		<td width="5%">Nr.<br>crt.</td>
		<td>Denumirea produselor</td>
		<td width="5%">U.M.</td>
		<td width="10%">Cantitate</td>
		<td width="11%" style="white-space: nowrap">Pret unitar<br>(EUR fara TVA)</td>
		<td width="11%" style="white-space: nowrap">Pret unitar<br>(RON cu TVA)</td>
		<td width="14%" style="white-space: nowrap">Total<br>(RON cu TVA)</td>
	</tr>
	</thead>
	<tbody>
	
    @php
      $counter = 1;
      $reducere = $offer->reducere;
      $totalFinal = 0;
      $totalCalculat = 0;
      $totalCalculatPi = 0;
    @endphp
    
  @if($offerProducts)
    @foreach($offerProducts as $offerProduct)
      @if($offerProduct->product && $offerProduct->product != null)
         @php
            $checkRule = $offerProduct->prices->filter(function($item) use($offer){
                return $item->rule_id == $offer->price_grid_id;
            })->first();
            $eurFaraTVA = $checkRule != null ? $checkRule->eur_fara_tva : 0;
            $ronCuTVA = $checkRule != null ? $checkRule->ron_cu_tva : 0;
            $ronTotal = $ronCuTVA*$offerProduct->qty;
            $totalCalculat += $ronTotal;
            $totalCalculatPi += $checkRule != null ? $checkRule->base_price : 0;
          @endphp
          <tr class="items item_wborder">
              <td align="center">{{$counter++}}</td>
              <td>{{$offerProduct->product->name}} {{$offerProduct->product->id}}</td>
              <td align="center" class="bold">{{$offerProduct->getParent->um_title->title}}</td>
              <td align="center" class="bold">{{$offerProduct->qty}}</td>
              <td align="right" class="bold">{{$eurFaraTVA}}</td>
              <td align="right" class="bold">{{$ronCuTVA}}</td>
              <td align="right" class="bold">{{$ronTotal}}</td>
            </tr>
      @endif
    @endforeach
  @endif
    
	<tr class="total">
		<td colspan="6" class="totals"><b>Total general cu TVA inclus - RON -</b></td>
		<td class="totals"><b>{{$totalCalculat}}</b></td>
	</tr>
	<tr class="total">
		<td colspan="6" class="totals"><b>Reducere - RON -</b></td>
		<td class="totals"><b>{{$reducere != 0 ? '- '.$reducere : '0.00'}}</b></td>
	</tr>
	<tr class="total">
		<td colspan="6" class="totals"><b>Total final - RON -</b></td>
		<td class="totals"><b>{{$totalCalculat - $reducere}}</b></td>
	</tr>
	</tbody>
</table>

@if($offerType && $offerType->header_img != null)
      </td>
    </tr>
  </table>   
@endif
<table width="100%">
	<tr>
		<td width="70%">
			<p>
				{!! $offerType && $offerType->footer_left_text ? $offerType->footer_left_text : '' !!}
			</p>
		</td>
    @if($offerType && $offerType->show_length_boxes == 1)
      <td width="30%" align="right">
              <br>
              Lungime totala: <b>{{$offer->dimension}}</b><br><br>
              Total cutii: <b>{{$offer->boxes}}</b>
      </td>
    @endif
	</tr>
	<tr>
		<td>
			<p><b>Observatii:</b></p>
			<p>{{$offer->observations != null ? ucfirst($offer->observations) : 'Fara observatii'}}</p>
		</td>
        <td align="right">
            <p><b>Semnatura de receptie:</b></p>
        </td>
	</tr>
</table>

</body>
</html>