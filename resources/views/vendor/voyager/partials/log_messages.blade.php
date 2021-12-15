@foreach($offerMessages as $msg)
  <label class="control-label">
    {{\Carbon\Carbon::parse($msg->created_at)->format('Y.m.d - H:i')}} --- <strong>{{$msg->user_name}}</strong> {!! $msg->message !!}
  </label>
@endforeach