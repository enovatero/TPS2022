<div class="nw--container">
  <div class="nw__topside">
    <span class="nw__title">Cele mai noi comenzi </span>
    <a href="/admin/lista-oferte" class="nw__see">Vezi toate </a>
  </div>
  <span class="nw__bar"></span>
  @if($top5Orders && count($top5Orders) > 0)
    @foreach($top5Orders as $order)
      <div class="nw__details">
        <a href="/admin/offers/{{$order->id}}/edit" class="nw__orderNr">{{$order->id." ".($order->numar_comanda ? "(C{$order->numar_comanda})" : "")}}</a>
        <span class="nw__clientName">{{$order->client->name ?? ''}}</span>
        <span class="nw__price"> {{number_format($order->total_final, 2, ',', '.')}} RON </span>
        <span class="nw__date"> {{\Carbon\Carbon::parse($order->offer_date)->format('d F')}} </span>
      </div>
    @endforeach
  @else
    Nicio comanda disponibila
  @endif

</div>
