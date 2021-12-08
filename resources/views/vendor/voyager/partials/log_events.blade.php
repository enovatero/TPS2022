@foreach($offerEvents as $event)
  <label class="control-label">
    {{\Carbon\Carbon::parse($event->created_at)->format('Y.m.d - H:i')}} --- <strong>{{$event->user_name}}</strong> {!! $event->message !!}
  </label>
@endforeach