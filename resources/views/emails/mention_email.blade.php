<style>
  @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@100;400&display=swap');
</style>
<div style="max-width:650px;margin:0 auto;background-color: #ffffff;padding-left: 50px; padding-right: 50px;">
    <div style="margin-bottom: 25px;">
{{--        <a href="{{config('app.url')}}" style="display: block; width: 120px;">--}}
{{--            <img src="{{Voyager::image(setting('admin.icon_image'))}}" alt="logo" style="width: 100%;">--}}
{{--        </a>--}}
    </div>
    <h1 style="color:#000000;font-family: 'Lexend', sans-serif;font-size:16px;text-align:left;">Salut {{$user_name}}!</h1>
    <p style="color:#000000;font-family: 'Lexend', sans-serif;font-size:12px;text-align:left;line-height: 1.6;">
        @if($is_admin)
          {{$agent}} a trimis urmatorul mesaj: {{$message}}
        @else
          {{$agent}} te-a mentionat cu urmatorul mesaj: {{$message}}
        @endif
    </p>
    <div style="width: 100%;margin-top: 35px;margin-bottom: 35px;">
        <a href="{{config('app.url')}}/admin/offers/{{$offer_id}}/edit" style="margin: 0 auto;display: block;font-family: 'Lexend', sans-serif; width: 160px;border-radius: 5px;color: #ffffff;text-align: center;padding-top: 14px;padding-bottom: 14px;font-size: 12px;background-color: #0600B7;text-decoration: none;padding-left: 25px;padding-right: 25px;">Vezi oferta</a>
    </div>
</div>
