;(function($) {

  console.log("spacman.js");

  $("#sman_login_button").click(function() {
		var form_data = '&action=sman_auth_validate' + 
                    '&nonce=' + sman.nonce + '&' + 
	                   $("#sman_form").serialize();

    sman_common_ajax(form_data);
    return false;
  });


function sman_common_ajax(form_data) {
    $.ajax(
      {
      type: "post",
      url: "/wp-admin/admin-ajax.php",
      data: form_data,
      beforeSend: function ()
        {
          $("#sman_loading_spinner_div").removeClass("hidden");
          $("#sman_loading_spinner_div").show();
          $("#sman_response_div").html("");
        },
      success: function (json)
         {
          $("#sman_loading_spinner_div").addClass("hidden")
          if (json.errors == 0) {
            $("#sman_response_div").html(json.html);
            $("#sman_response_div").removeClass("hidden");
            $("#sman_response_div").fadeIn();
            $("#sman_submit_button").attr("disabled",true);
            $("#sman_dismiss_button_div").removeClass("hidden");
            $("#sman_dismiss_button_div").show();
						Cookies.set('AuthCookie',json.AuthCookie,{path: '/', domain: '.nova-labs.org'});
          } else {
            $("#sman_response_div").html(json.html);
            $("#sman_response_div").removeClass('hidden');
            $("#sman_response_div").fadeIn();
          }
          return true;
        },
        error: function () {
          $("#sman_edit_message_div").append('something bad happened. Server Didn\'t elaborate');
        }
      });
    return false;

		}


})(jQuery);



-->

