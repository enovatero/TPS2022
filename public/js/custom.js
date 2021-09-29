$(document).ready(function(){
  $(document).on("change", ".select-country", function(){
     var vthis = this;
     $.ajax({
          method: 'POST',
          url: '/getCounties',
          data: {_token: $("meta[name=csrf-token]").attr("content"), country_code: $(this).val()},
          context: this,
          async: true,
          cache: false,
          dataType: 'json'
      }).done(function(res) {
          if (res.success == false) {
              toastr.error(res.error, 'Eroare');
          } else{
            $(vthis).parent().parent().find(".select-state").html(res.html);
            var stateValue = $(vthis).parent().parent().find('.select-state').attr('selectedValue');
            if(stateValue != ''){
              $(vthis).parent().parent().find('.select-state').find('option[value='+stateValue+']').prop('selected',true).trigger('change');
            }
            $(vthis).parent().parent().find(".select-city").html('');
          }
      })
      .fail(function(xhr, status, error) {
          if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
              .indexOf("CSRF token mismatch") >= 0) {
              window.location.reload();
          }
      });
    return false;
  });
  $(document).on("change", ".select-state", function(){
     var vthis = this;
     $.ajax({
          method: 'POST',
          url: '/getCities',
          data: {_token: $("meta[name=csrf-token]").attr("content"), state_code: $(this).val(), country_code: $(vthis).parent().parent().find(".select-country").val()},
          context: this,
          async: true,
          cache: false,
          dataType: 'json'
      }).done(function(res) {
          if (res.success == false) {
              toastr.error(res.error, 'Eroare');
          } else{
            $(vthis).parent().parent().find(".select-city").html(res.html);
            var cityValue = $(vthis).parent().parent().find('.select-city').attr('selectedValue');
            if(cityValue != ''){
              $(vthis).parent().parent().find('.select-city').find('option[value='+cityValue+']').prop('selected',true).trigger('change');
            }
          }
      })
      .fail(function(xhr, status, error) {
          if (xhr && xhr.responseJSON && xhr.responseJSON.message && xhr.responseJSON.message
              .indexOf("CSRF token mismatch") >= 0) {
              window.location.reload();
          }
      });
    return false;
  });
});