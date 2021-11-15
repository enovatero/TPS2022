$(document).ready(function(){
  window.selectCountryChange = function(vthis, state_code = null, state_name = null){
    var data_send;
    if(state_code != null && state_name != null){
      data_send = {_token: $("meta[name=csrf-token]").attr("content"), country_code: $(vthis).val(), selected_state: state_code + "_" + state_name};
    } else{
      data_send = {_token: $("meta[name=csrf-token]").attr("content"), country_code: $(vthis).val()};
    }
    $.ajax({
        method: 'POST',
        url: '/getCounties',
        data: data_send,
        context: vthis,
        async: true,
        cache: false,
        dataType: 'json'
    }).done(function(res) {
        if (res.success == false) {
            toastr.error(res.error, 'Eroare');
        } else{
          $(vthis).parent().parent().find(".select-state").html(res.html);
//           var stateValue = $(vthis).parent().parent().find('.select-state').attr('selectedValue');
//           if(stateValue != ''){
//             $(vthis).parent().parent().find('.select-state').find('option[value='+stateValue+']').prop('selected',true).trigger('change');
//           }
//           if(state_code == null && state_name == null){
            $(vthis).parent().parent().find(".select-city").html('');
//           }
        }
    })
    .fail(function(xhr, status, error) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
            .indexOf("CSRF token mismatch") >= 0) {
            window.location.reload();
        }
    });
    return false;
  };
  window.selectStateChange = function(vthis, city_id = null, city_name = null, state_code = null, country_code = null){
     var data_send;
     var state_code_retrieved = state_code != null ? state_code : $(vthis).val();
     country_code = $(vthis).parent().parent().find(".select-country").val() != null ? $(vthis).parent().parent().find(".select-country").val() : country_code;
     if(city_id != null && city_name != null){
        data_send = {_token: $("meta[name=csrf-token]").attr("content"), state_code: state_code_retrieved, country_code: country_code, selected_city: city_id + "_" + city_name};
     } else{
        data_send = {_token: $("meta[name=csrf-token]").attr("content"), state_code: state_code_retrieved, country_code: country_code}
     }
     $.ajax({
          method: 'POST',
          url: '/getCities',
          data: data_send,
          context: vthis,
          async: true,
          cache: false,
          dataType: 'json'
      }).done(function(res) {
          if (res.success == false) {
              toastr.error(res.error, 'Eroare');
          } else{
            $(vthis).parent().parent().find($(".select-city")).html(res.html);
          }
      })
      .fail(function(xhr, status, error) {
          if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
              .indexOf("CSRF token mismatch") >= 0) {
              window.location.reload();
          }
      });
    return false;
  };
  $(document).on("change", ".select-country", function(){
     window.selectCountryChange(this);
  });
  $(document).on("change", ".select-state", function(){
     window.selectStateChange(this);
  });
  $(".select-country").each(function(){
    var country_code = $(this).val();
    var state_code = $(this).parent().parent().find(".select-state").attr('state_code');
    var state_name = $(this).parent().parent().find(".select-state").attr('state_name');
    var city_id = $(this).parent().parent().find(".select-city").attr('city_id');
    var city_name = $(this).parent().parent().find(".select-city").attr('city_name');
    window.selectCountryChange($(this)[0], state_code, state_name);
    var vthis = this;
    setTimeout(function(){
      window.selectStateChange($(vthis).parent().parent().find($(".select-state"))[0], city_id, city_name, state_code, country_code);
    }, 300);
  });
  $(".side-menu").hover(function(){
      var leftBarWidth = $(".app-container .content-container .side-menu").width();
      if(leftBarWidth >= 60 && leftBarWidth <= 124){
        leftBarWidth = 250;
      }
      var newWidth = $(window).width() - leftBarWidth;
      $(".navbar-fixed-top").css("width", newWidth+"px");
    }, function(){
      var leftBarWidth = $(".app-container .content-container .side-menu").width();
      if(leftBarWidth <= 250 && leftBarWidth >= 125){
        leftBarWidth = 60;
      }
      var newWidth = $(window).width() - leftBarWidth;
      $(".navbar-fixed-top").css("width", newWidth+"px");
    }
  );
  $(".admin-left > ul li>a").each(function(){
    var elemClasses = $(this).find($("span")).first().attr('class').split(/\s+/);
    var isImg = elemClasses[1].toLowerCase().includes('/images/voyager_menu/');
    if(isImg){
      $(this).find($("span")).first().addClass('voyager-custom-menu-img').css('background-image', 'url(' + elemClasses[1] + ')');
    }
  })
});