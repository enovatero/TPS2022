@foreach($offerEvents as $event)
  <label class="control-label">
      <span style="font-weight: bold">{{\Carbon\Carbon::parse($event->created_at)->format('Y.m.d - H:i')}} --- {{$event->user_name}}</span><br> {!! $event->message !!}
  </label>
@endforeach
