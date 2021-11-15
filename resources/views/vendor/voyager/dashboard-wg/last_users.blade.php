<div class="lw--container">
  <div class="nw__topside">
    <span class="nw__title">Ultimii utilizatori </span>
    <a href="/admin/users" class="nw__see">Vezi tot </a>
  </div>
  <span class="nw__bar"></span>
  
  @if($top5Users && count($top5Users) > 0)
    @foreach($top5Users as $usr)
      <div class="nw__details">
        <div> 
           <img class="lw__img" src="{{Voyager::image($usr->avatar)}}" />
           <a href="admin/users/{{$usr->id}}/edit" class="lw__user--name">{{$usr->name}} - {{$usr->role->display_name}} </a>
        </div>
        <span class="nw__date"> {{\Carbon\Carbon::parse($usr->created_at)->format('d F')}} </span>
      </div>
    @endforeach
  @else
    Niciun utilizator disponibil
  @endif

</div>
