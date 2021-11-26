@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing').' '.__('voyager::generic.settings'))

@section('css')
    <style>
        .panel-actions .voyager-trash {
            cursor: pointer;
        }
        .panel-actions .voyager-trash:hover {
            color: #e94542;
        }
        .settings .panel-actions{
            right:0px;
        }
        .panel hr {
            margin-bottom: 10px;
        }
        .panel {
            padding-bottom: 15px;
        }
        .sort-icons {
            font-size: 21px;
            color: #ccc;
            position: relative;
            cursor: pointer;
        }
        .sort-icons:hover {
            color: #37474F;
        }
        .voyager-sort-desc {
            margin-right: 10px;
        }
        .voyager-sort-asc {
            top: 10px;
        }
        .page-title {
            margin-bottom: 0;
        }
        .panel-title code {
            border-radius: 30px;
            padding: 5px 10px;
            font-size: 11px;
            border: 0;
            position: relative;
            top: -2px;
        }
        .modal-open .settings  .select2-container {
            z-index: 9!important;
            width: 100%!important;
        }
        .new-setting {
            text-align: center;
            width: 100%;
            margin-top: 20px;
        }
        .new-setting .panel-title {
            margin: 0 auto;
            display: inline-block;
            color: #999fac;
            font-weight: lighter;
            font-size: 13px;
            background: #fff;
            width: auto;
            height: auto;
            position: relative;
            padding-right: 15px;
        }
        .settings .panel-title{
            padding-left:0px;
            padding-right:0px;
        }
        .new-setting hr {
            margin-bottom: 0;
            position: absolute;
            top: 7px;
            width: 96%;
            margin-left: 2%;
        }
        .new-setting .panel-title i {
            position: relative;
            top: 2px;
        }
        .new-settings-options {
            display: none;
            padding-bottom: 10px;
        }
        .new-settings-options label {
            margin-top: 13px;
        }
        .new-settings-options .alert {
            margin-bottom: 0;
        }
        #toggle_options {
            clear: both;
            float: right;
            font-size: 12px;
            position: relative;
            margin-top: 15px;
            margin-right: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            z-index: 9;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .new-setting-btn {
            margin-right: 15px;
            position: relative;
            margin-bottom: 0;
            top: 5px;
        }
        .new-setting-btn i {
            position: relative;
            top: 2px;
        }
        textarea {
            min-height: 120px;
        }
        textarea.hidden{
            display:none;
        }

        .voyager .settings .nav-tabs{
            background:none;
            border-bottom:0px;
        }

        .voyager .settings .nav-tabs .active a{
            border:0px;
        }

        .select2{
            width:100% !important;
            border: 1px solid #f1f1f1;
            border-radius: 3px;
        }

        .voyager .settings input[type=file]{
            width:100%;
        }

        .settings .select2{
            margin-left:10px;
        }

        .settings .select2-selection{
            height: 32px;
            padding: 2px;
        }

        .voyager .settings .nav-tabs > li{
            margin-bottom:-1px !important;
        }

        .voyager .settings .nav-tabs a{
            text-align: center;
            background: #f8f8f8;
            border: 1px solid #f1f1f1;
            position: relative;
            top: -1px;
            border-bottom-left-radius: 0px;
            border-bottom-right-radius: 0px;
        }

        .voyager .settings .nav-tabs a i{
            display: block;
            font-size: 22px;
        }

        .tab-content{
            background:#ffffff;
            border: 1px solid transparent;
        }

        .tab-content>div{
            padding:10px;
        }

        .settings .no-padding-left-right{
            padding-left:0px;
            padding-right:0px;
        }

        .nav-tabs > li.active > a, .nav-tabs > li.active > a:focus, .nav-tabs > li.active > a:hover{
            background:#fff !important;
            color:#62a8ea !important;
            border-bottom:1px solid #fff !important;
            top:-1px !important;
        }

        .nav-tabs > li a{
            transition:all 0.3s ease;
        }


        .nav-tabs > li.active > a:focus{
            top:0px !important;
        }

        .voyager .settings .nav-tabs > li > a:hover{
            background-color:#fff !important;
        }
    </style>
@stop

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-settings"></i> {{ __('voyager::generic.settings') }}
    </h1>
@stop

@section('content')
    <div class="container-fluid">
        @include('voyager::alerts')
        @if(config('voyager.show_dev_tips'))
        <div class="alert alert-info">
            <strong>{{ __('voyager::generic.how_to_use') }}:</strong>
            <p>{{ __('voyager::settings.usage_help') }} <code>setting('group.key')</code></p>
        </div>
        @endif
    </div>

    <div class="page-content settings container-fluid">
        <form action="{{ route('voyager.settings.update') }}" method="POST" enctype="multipart/form-data">
            {{ method_field("PUT") }}
            {{ csrf_field() }}
            <input type="hidden" name="setting_tab" class="setting_tab" value="{{ $active }}" />
            <div class="panel">

                <div class="page-content settings container-fluid">
                    <ul class="nav nav-tabs">
                        @foreach($settings as $group => $setting)
                            <li @if($group == $active) class="active" @endif>
                                <a data-toggle="tab" href="#{{ \Illuminate\Support\Str::slug($group) }}">{{ $group }}</a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content">
                        @foreach($settings as $group => $group_settings)
                        <div id="{{ \Illuminate\Support\Str::slug($group) }}" class="tab-pane fade in @if($group == $active) active @endif">
                            @foreach($group_settings as $setting)
                            <div class="panel-heading">
                                <h3 class="panel-title">
                                    {{ $setting->display_name }} @if(config('voyager.show_dev_tips'))<code>setting('{{ $setting->key }}')</code>@endif
                                </h3>
                                <div class="panel-actions">
                                    <a href="{{ route('voyager.settings.move_up', $setting->id) }}">
                                        <i class="sort-icons voyager-sort-asc"></i>
                                    </a>
                                    <a href="{{ route('voyager.settings.move_down', $setting->id) }}">
                                        <i class="sort-icons voyager-sort-desc"></i>
                                    </a>
                                    @can('delete', Voyager::model('Setting'))
                                    <i class="voyager-trash"
                                       data-id="{{ $setting->id }}"
                                       data-display-key="{{ $setting->key }}"
                                       data-display-name="{{ $setting->display_name }}"></i>
                                    @endcan
                                </div>
                            </div>
                            <div class="panel-body no-padding-left-right">
                                @if($setting->key == 'admin.default_countries')
                                  <input type="hidden" class="trick-selected-countries" name="admin.default_countries" value="{{$setting->value}}"/>
                                @endif
                                <div class="col-md-10 no-padding-left-right">
                                    @if ($setting->type == "text")
                                        <input type="text" class="form-control" name="{{ $setting->key }}" value="{{ $setting->value }}">
                                    @elseif($setting->type == "text_area")
                                        <textarea class="form-control" name="{{ $setting->key }}">{{ $setting->value ?? '' }}</textarea>
                                    @elseif($setting->type == "rich_text_box")
                                        <textarea class="form-control richTextBox" name="{{ $setting->key }}">{{ $setting->value ?? '' }}</textarea>
                                    @elseif($setting->type == "code_editor")
                                        <?php $options = json_decode($setting->details); ?>
                                        <div id="{{ $setting->key }}" data-theme="{{ @$options->theme }}" data-language="{{ @$options->language }}" class="ace_editor min_height_400" name="{{ $setting->key }}">{{ $setting->value ?? '' }}</div>
                                        <textarea name="{{ $setting->key }}" id="{{ $setting->key }}_textarea" class="hidden">{{ $setting->value ?? '' }}</textarea>
                                    @elseif($setting->type == "image" || $setting->type == "file")
                                        @if(isset( $setting->value ) && !empty( $setting->value ) && Storage::disk(config('voyager.storage.disk'))->exists($setting->value))
                                            <div class="img_settings_container">
                                                <a href="{{ route('voyager.settings.delete_value', $setting->id) }}" class="voyager-x delete_value"></a>
                                                <img src="{{ Storage::disk(config('voyager.storage.disk'))->url($setting->value) }}" style="width:200px; height:auto; padding:2px; border:1px solid #ddd; margin-bottom:10px;">
                                            </div>
                                            <div class="clearfix"></div>
                                        @elseif($setting->type == "file" && isset( $setting->value ))
                                            @if(json_decode($setting->value) !== null)
                                                @foreach(json_decode($setting->value) as $file)
                                                  <div class="fileType">
                                                    <a class="fileType" target="_blank" href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) }}">
                                                      {{ $file->original_name }}
                                                    </a>
                                                    <a href="{{ route('voyager.settings.delete_value', $setting->id) }}" class="voyager-x delete_value"></a>
                                                 </div>
                                                @endforeach
                                            @endif
                                        @endif
                                        <input type="file" name="{{ $setting->key }}">
                                    @elseif($setting->type == "select_dropdown")
                                        <?php $options = json_decode($setting->details); ?>
                                        <?php $selected_value = (isset($setting->value) && !empty($setting->value)) ? $setting->value : NULL; ?>
                                        <select class="form-control" name="{{ $setting->key }}">
                                            <?php $default = (isset($options->default)) ? $options->default : NULL; ?>
                                            @if(isset($options->options))
                                                @foreach($options->options as $index => $option)
                                                    <option value="{{ $index }}" @if($default == $index && $selected_value === NULL) selected="selected" @endif @if($selected_value == $index) selected="selected" @endif>{{ $option }}</option>
                                                @endforeach
                                            @endif
                                        </select>

                                    @elseif($setting->type == "radio_btn")
                                        <?php $options = json_decode($setting->details); ?>
                                        <?php $selected_value = (isset($setting->value) && !empty($setting->value)) ? $setting->value : NULL; ?>
                                        <?php $default = (isset($options->default)) ? $options->default : NULL; ?>
                                        <ul class="radio">
                                            @if(isset($options->options))
                                                @foreach($options->options as $index => $option)
                                                    <li>
                                                        <input type="radio" id="option-{{ $index }}" name="{{ $setting->key }}"
                                                               value="{{ $index }}" @if($default == $index && $selected_value === NULL) checked @endif @if($selected_value == $index) checked @endif>
                                                        <label for="option-{{ $index }}">{{ $option }}</label>
                                                        <div class="check"></div>
                                                    </li>
                                                @endforeach
                                            @endif
                                        </ul>
                                    @elseif($setting->type == "checkbox")
                                        <?php $options = json_decode($setting->details); ?>
                                        <?php $checked = (isset($setting->value) && $setting->value == 1) ? true : false; ?>
                                        @if (isset($options->on) && isset($options->off))
                                            <input type="checkbox" name="{{ $setting->key }}" class="toggleswitch" @if($checked) checked @endif data-on="{{ $options->on }}" data-off="{{ $options->off }}">
                                        @else
                                            <input type="checkbox" name="{{ $setting->key }}" @if($checked) checked @endif class="toggleswitch">
                                        @endif
                                    @endif
                                </div>
                                <div class="col-md-2 no-padding-left-right">
                                    <select class="form-control group_select" name="{{ $setting->key }}_group">
                                        @foreach($groups as $group)
                                        <option value="{{ $group }}" {!! $setting->group == $group ? 'selected' : '' !!}>{{ $group }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if(!$loop->last)
                                <hr>
                            @endif
                            @endforeach
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
            <button type="submit" class="btn btn-primary pull-right">{{ __('voyager::settings.save') }}</button>
        </form>

        <div style="clear:both"></div>

        @can('add', Voyager::model('Setting'))
        <div class="panel" style="margin-top:10px;">
            <div class="panel-heading new-setting">
                <hr>
                <h3 class="panel-title"><i class="voyager-plus"></i> {{ __('voyager::settings.new') }}</h3>
            </div>
            <div class="panel-body">
                <form action="{{ route('voyager.settings.store') }}" method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" name="setting_tab" class="setting_tab" value="{{ $active }}" />
                    <div class="col-md-3">
                        <label for="display_name">{{ __('voyager::generic.name') }}</label>
                        <input type="text" class="form-control" name="display_name" placeholder="{{ __('voyager::settings.help_name') }}" required="required">
                    </div>
                    <div class="col-md-3">
                        <label for="key">{{ __('voyager::generic.key') }}</label>
                        <input type="text" class="form-control" name="key" placeholder="{{ __('voyager::settings.help_key') }}" required="required">
                    </div>
                    <div class="col-md-3">
                        <label for="type">{{ __('voyager::generic.type') }}</label>
                        <select name="type" class="form-control" required="required">
                            <option value="">{{ __('voyager::generic.choose_type') }}</option>
                            <option value="text">{{ __('voyager::form.type_textbox') }}</option>
                            <option value="text_area">{{ __('voyager::form.type_textarea') }}</option>
                            <option value="rich_text_box">{{ __('voyager::form.type_richtextbox') }}</option>
                            <option value="code_editor">{{ __('voyager::form.type_codeeditor') }}</option>
                            <option value="checkbox">{{ __('voyager::form.type_checkbox') }}</option>
                            <option value="radio_btn">{{ __('voyager::form.type_radiobutton') }}</option>
                            <option value="select_dropdown">{{ __('voyager::form.type_selectdropdown') }}</option>
                            <option value="file">{{ __('voyager::form.type_file') }}</option>
                            <option value="image">{{ __('voyager::form.type_image') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="group">{{ __('voyager::settings.group') }}</label>
                        <select class="form-control group_select group_select_new" name="group">
                            @foreach($groups as $group)
                                <option value="{{ $group }}">{{ $group }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <a id="toggle_options"><i class="voyager-double-down"></i> {{ mb_strtoupper(__('voyager::generic.options')) }}</a>
                        <div class="new-settings-options">
                            <label for="options">{{ __('voyager::generic.options') }}
                                <small>{{ __('voyager::settings.help_option') }}</small>
                            </label>
                            <div id="options_editor" class="form-control min_height_200" data-language="json"></div>
                            <textarea id="options_textarea" name="details" class="hidden"></textarea>
                            <div id="valid_options" class="alert-success alert" style="display:none">{{ __('voyager::json.valid') }}</div>
                            <div id="invalid_options" class="alert-danger alert" style="display:none">{{ __('voyager::json.invalid') }}</div>
                        </div>
                    </div>
                    <div style="clear:both"></div>
                    <button type="submit" class="btn btn-primary pull-right new-setting-btn">
                        <i class="voyager-plus"></i> {{ __('voyager::settings.add_new') }}
                    </button>
                    <div style="clear:both"></div>
                </form>
            </div>
        </div>
        @endcan
    </div>

    @can('delete', Voyager::model('Setting'))
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">
                        <i class="voyager-trash"></i> {!! __('voyager::settings.delete_question', ['setting' => '<span id="delete_setting_title"></span>']) !!}
                    </h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_form" method="POST">
                        {{ method_field("DELETE") }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm" value="{{ __('voyager::settings.delete_confirm') }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endcan

@stop

@section('javascript')
    <script>
        $('document').ready(function () {
            $('#toggle_options').click(function () {
                $('.new-settings-options').toggle();
                if ($('#toggle_options .voyager-double-down').length) {
                    $('#toggle_options .voyager-double-down').removeClass('voyager-double-down').addClass('voyager-double-up');
                } else {
                    $('#toggle_options .voyager-double-up').removeClass('voyager-double-up').addClass('voyager-double-down');
                }
            });

            @can('delete', Voyager::model('Setting'))
            $('.panel-actions .voyager-trash').click(function () {
                var display = $(this).data('display-name') + '/' + $(this).data('display-key');

                $('#delete_setting_title').text(display);

                $('#delete_form')[0].action = '{{ route('voyager.settings.delete', [ 'id' => '__id' ]) }}'.replace('__id', $(this).data('id'));
                $('#delete_modal').modal('show');
            });
            @endcan

            $('.toggleswitch').bootstrapToggle();

            $('[data-toggle="tab"]').click(function() {
                $(".setting_tab").val($(this).html());
            });

            $('.delete_value').click(function(e) {
                e.preventDefault();
                $(this).closest('form').attr('action', $(this).attr('href'));
                $(this).closest('form').submit();
            });

            // Initiliaze rich text editor
            tinymce.init(window.voyagerTinyMCE.getConfig());
        });
    </script>
    <script type="text/javascript">
    $(".group_select").not('.group_select_new').select2({
        tags: true,
        width: 'resolve'
    });
    $(".group_select_new").select2({
        tags: true,
        width: 'resolve',
        placeholder: '{{ __("voyager::generic.select_group") }}'
    });
    $(".group_select_new").val('').trigger('change');
      $(document).ready(function(){
        var allCountries = `
          <option value="AF">AFGHANISTAN</option>
          <option value="AX">ALAND ISLANDS</option>
          <option value="AL">ALBANIA</option>
          <option value="DZ">ALGERIA</option>
          <option value="AS">AMERICAN SAMOA</option>
          <option value="AD">ANDORRA</option>
          <option value="AO">ANGOLA</option>
          <option value="AQ">ANTARCTICA</option>
          <option value="AG">ANTIGUA AND BARBUDA</option>
          <option value="AR">ARGENTINA</option>
          <option value="AM">ARMENIA</option>
          <option value="AW">ARUBA</option>
          <option value="AU">AUSTRALIA</option>
          <option value="AT">AUSTRIA</option>
          <option value="AZ">AZERBAIJAN</option>
          <option value="BS">BAHAMAS</option>
          <option value="BH">BAHRAIN</option>
          <option value="BD">BANGLADESH</option>
          <option value="BB">BARBADOS</option>
          <option value="BY">BELARUS</option>
          <option value="BE">BELGIUM</option>
          <option value="BZ">BELIZE</option>
          <option value="BJ">BENIN</option>
          <option value="BM">BERMUDA</option>
          <option value="BT">BHUTAN</option>
          <option value="BO">BOLIVIA</option>
          <option value="BQ">BONAIRE, SAINT EUSTATIUS AND SABA </option>
          <option value="BA">BOSNIA AND HERZEGOVINA</option>
          <option value="BW">BOTSWANA</option>
          <option value="BV">BOUVET ISLAND</option>
          <option value="BR">BRAZIL</option>
          <option value="IO">BRITISH INDIAN OCEAN TERRITORY</option>
          <option value="BN">BRUNEI</option>
          <option value="BG">BULGARIA</option>
          <option value="BF">BURKINA FASO</option>
          <option value="BI">BURUNDI</option>
          <option value="KH">CAMBODIA</option>
          <option value="CM">CAMEROON</option>
          <option value="CA">CANADA</option>
          <option value="CV">CAPE VERDE</option>
          <option value="CF">CENTRAL AFRICAN REPUBLIC</option>
          <option value="TD">CHAD</option>
          <option value="CL">CHILE</option>
          <option value="CN">CHINA</option>
          <option value="CO">COLOMBIA</option>
          <option value="KM">COMOROS</option>
          <option value="CR">COSTA RICA</option>
          <option value="HR">CROATIA</option>
          <option value="CU">CUBA</option>
          <option value="CY">CYPRUS</option>
          <option value="CZ">CZECH REPUBLIC</option>
          <option value="CD">DEMOCRATIC REPUBLIC OF THE CONGO</option>
          <option value="DK">DENMARK</option>
          <option value="DJ">DJIBOUTI</option>
          <option value="DM">DOMINICA</option>
          <option value="DO">DOMINICAN REPUBLIC</option>
          <option value="TL">EAST TIMOR</option>
          <option value="EC">ECUADOR</option>
          <option value="EG">EGYPT</option>
          <option value="SV">EL SALVADOR</option>
          <option value="GQ">EQUATORIAL GUINEA</option>
          <option value="ER">ERITREA</option>
          <option value="EE">ESTONIA</option>
          <option value="ET">ETHIOPIA</option>
          <option value="FO">FAROE ISLANDS</option>
          <option value="FJ">FIJI</option>
          <option value="FI">FINLAND</option>
          <option value="FR">FRANCE</option>
          <option value="GF">FRENCH GUIANA</option>
          <option value="PF">FRENCH POLYNESIA</option>
          <option value="TF">FRENCH SOUTHERN TERRITORIES</option>
          <option value="GA">GABON</option>
          <option value="GM">GAMBIA</option>
          <option value="GE">GEORGIA</option>
          <option value="DE">GERMANY</option>
          <option value="GH">GHANA</option>
          <option value="GR">GREECE</option>
          <option value="GL">GREENLAND</option>
          <option value="GD">GRENADA</option>
          <option value="GP">GUADELOUPE</option>
          <option value="GU">GUAM</option>
          <option value="GT">GUATEMALA</option>
          <option value="GG">GUERNSEY</option>
          <option value="GN">GUINEA</option>
          <option value="GW">GUINEA-BISSAU</option>
          <option value="GY">GUYANA</option>
          <option value="HT">HAITI</option>
          <option value="HN">HONDURAS</option>
          <option value="HK">HONG KONG</option>
          <option value="HU">HUNGARY</option>
          <option value="IS">ICELAND</option>
          <option value="IN">INDIA</option>
          <option value="ID">INDONESIA</option>
          <option value="IR">IRAN</option>
          <option value="IQ">IRAQ</option>
          <option value="IE">IRELAND</option>
          <option value="IM">ISLE OF MAN</option>
          <option value="IL">ISRAEL</option>
          <option value="IT">ITALY</option>
          <option value="CI">IVORY COAST</option>
          <option value="JM">JAMAICA</option>
          <option value="JP">JAPAN</option>
          <option value="JE">JERSEY</option>
          <option value="JO">JORDAN</option>
          <option value="KZ">KAZAKHSTAN</option>
          <option value="KE">KENYA</option>
          <option value="KI">KIRIBATI</option>
          <option value="XK">KOSOVO</option>
          <option value="KW">KUWAIT</option>
          <option value="KG">KYRGYZSTAN</option>
          <option value="LA">LAOS</option>
          <option value="LV">LATVIA</option>
          <option value="LB">LEBANON</option>
          <option value="LS">LESOTHO</option>
          <option value="LR">LIBERIA</option>
          <option value="LY">LIBYA</option>
          <option value="LI">LIECHTENSTEIN</option>
          <option value="LT">LITHUANIA</option>
          <option value="LU">LUXEMBOURG</option>
          <option value="MO">MACAO</option>
          <option value="MK">MACEDONIA</option>
          <option value="MG">MADAGASCAR</option>
          <option value="MW">MALAWI</option>
          <option value="MY">MALAYSIA</option>
          <option value="MV">MALDIVES</option>
          <option value="ML">MALI</option>
          <option value="MH">MARSHALL ISLANDS</option>
          <option value="MQ">MARTINIQUE</option>
          <option value="MR">MAURITANIA</option>
          <option value="MU">MAURITIUS</option>
          <option value="YT">MAYOTTE</option>
          <option value="MX">MEXICO</option>
          <option value="FM">MICRONESIA</option>
          <option value="MD">MOLDOVA</option>
          <option value="MC">MONACO</option>
          <option value="MN">MONGOLIA</option>
          <option value="ME">MONTENEGRO</option>
          <option value="MS">MONTSERRAT</option>
          <option value="MA">MOROCCO</option>
          <option value="MZ">MOZAMBIQUE</option>
          <option value="MM">MYANMAR</option>
          <option value="NA">NAMIBIA</option>
          <option value="NR">NAURU</option>
          <option value="NP">NEPAL</option>
          <option value="NL">NETHERLANDS</option>
          <option value="NC">NEW CALEDONIA</option>
          <option value="NZ">NEW ZEALAND</option>
          <option value="NI">NICARAGUA</option>
          <option value="NE">NIGER</option>
          <option value="NG">NIGERIA</option>
          <option value="KP">NORTH KOREA</option>
          <option value="MP">NORTHERN MARIANA ISLANDS</option>
          <option value="NO">NORWAY</option>
          <option value="OM">OMAN</option>
          <option value="PK">PAKISTAN</option>
          <option value="PW">PALAU</option>
          <option value="PS">PALESTINIAN TERRITORY</option>
          <option value="PA">PANAMA</option>
          <option value="PG">PAPUA NEW GUINEA</option>
          <option value="PY">PARAGUAY</option>
          <option value="PE">PERU</option>
          <option value="PH">PHILIPPINES</option>
          <option value="PL">POLAND</option>
          <option value="PT">PORTUGAL</option>
          <option value="PR">PUERTO RICO</option>
          <option value="QA">QATAR</option>
          <option value="CG">REPUBLIC OF THE CONGO</option>
          <option value="RE">REUNION</option>
          <option value="RO">ROMANIA</option>
          <option value="RU">RUSSIA</option>
          <option value="RW">RWANDA</option>
          <option value="SH">SAINT HELENA</option>
          <option value="KN">SAINT KITTS AND NEVIS</option>
          <option value="LC">SAINT LUCIA</option>
          <option value="PM">SAINT PIERRE AND MIQUELON</option>
          <option value="VC">SAINT VINCENT AND THE GRENADINES</option>
          <option value="WS">SAMOA</option>
          <option value="SM">SAN MARINO</option>
          <option value="ST">SAO TOME AND PRINCIPE</option>
          <option value="SA">SAUDI ARABIA</option>
          <option value="SN">SENEGAL</option>
          <option value="RS">SERBIA</option>
          <option value="SC">SEYCHELLES</option>
          <option value="SL">SIERRA LEONE</option>
          <option value="SG">SINGAPORE</option>
          <option value="SK">SLOVAKIA</option>
          <option value="SI">SLOVENIA</option>
          <option value="SB">SOLOMON ISLANDS</option>
          <option value="SO">SOMALIA</option>
          <option value="ZA">SOUTH AFRICA</option>
          <option value="KR">SOUTH KOREA</option>
          <option value="SS">SOUTH SUDAN</option>
          <option value="ES">SPAIN</option>
          <option value="LK">SRI LANKA</option>
          <option value="SD">SUDAN</option>
          <option value="SR">SURINAME</option>
          <option value="SJ">SVALBARD AND JAN MAYEN</option>
          <option value="SZ">SWAZILAND</option>
          <option value="SE">SWEDEN</option>
          <option value="CH">SWITZERLAND</option>
          <option value="SY">SYRIA</option>
          <option value="TW">TAIWAN</option>
          <option value="TJ">TAJIKISTAN</option>
          <option value="TZ">TANZANIA</option>
          <option value="TH">THAILAND</option>
          <option value="TG">TOGO</option>
          <option value="TK">TOKELAU</option>
          <option value="TO">TONGA</option>
          <option value="TT">TRINIDAD AND TOBAGO</option>
          <option value="TN">TUNISIA</option>
          <option value="TR">TURKEY</option>
          <option value="TM">TURKMENISTAN</option>
          <option value="TV">TUVALU</option>
          <option value="VI">U.S. VIRGIN ISLANDS</option>
          <option value="UG">UGANDA</option>
          <option value="UA">UKRAINE</option>
          <option value="AE">UNITED ARAB EMIRATES</option>
          <option value="GB">UNITED KINGDOM</option>
          <option value="US">UNITED STATES</option>
          <option value="UM">UNITED STATES MINOR OUTLYING ISLANDS</option>
          <option value="UY">URUGUAY</option>
          <option value="UZ">UZBEKISTAN</option>
          <option value="VU">VANUATU</option>
          <option value="VE">VENEZUELA</option>
          <option value="VN">VIETNAM</option>
          <option value="WF">WALLIS AND FUTUNA</option>
          <option value="EH">WESTERN SAHARA</option>
          <option value="YE">YEMEN</option>
          <option value="ZM">ZAMBIA</option>
          <option value="ZW">ZIMBABWE</option>`;
        var selectedValues = $(".trick-selected-countries").val();
        var arrValues = [];
        selectedValues = JSON.parse(selectedValues);
        if(selectedValues && selectedValues != null && selectedValues.length > 0){
          for(var i = 0; i < selectedValues.length; i++){
            arrValues.push(selectedValues[i].id);
          }
        }
        $("select[name='admin.default_countries']")
          .removeAttr('name')
          .addClass('trick-select-countries')
          .html(allCountries)
          .select2({
              tags: true,
              multiple: true,
              tokenSeparators: [',', ' ']
          }).val(arrValues).trigger('change');
        
        $(document).on('change', '.trick-select-countries', function (e) {
          var selVals = $(this).val();
          var countryList = [];
          for(var i = 0; i < selVals.length; i++){
            var selText = $(".trick-select-countries option[value="+selVals[i]+"]").text();
            countryList.push({
              'id': selVals[i],
              'val': selText,
            });
          }
          $(".trick-selected-countries").val(JSON.stringify(countryList));
        });
      })
    </script>
    <iframe id="form_target" name="form_target" style="display:none"></iframe>
    <form id="my_form" action="{{ route('voyager.upload') }}" target="form_target" method="POST" enctype="multipart/form-data" style="width:0;height:0;overflow:hidden">
        {{ csrf_field() }}
        <input name="image" id="upload_file" type="file" onchange="$('#my_form').submit();this.value='';">
        <input type="hidden" name="type_slug" id="type_slug" value="settings">
    </form>

    <script>
        var options_editor = ace.edit('options_editor');
        options_editor.getSession().setMode("ace/mode/json");

        var options_textarea = document.getElementById('options_textarea');
        options_editor.getSession().on('change', function() {
            console.log(options_editor.getValue());
            options_textarea.value = options_editor.getValue();
        });
    </script>
@stop
