define(['jquery'], function($){
   "use strict";
    $(document).ready(function(){

    let test = jQuery('#url').val();
    jQuery('.refress').click(function(){
        var url = test+"zohointegration/index/save";
        let code = jQuery('#code').val();
        let client_id = jQuery('#client_id').val();
        let client_secret = jQuery('#client_secret').val();
        let redirect_uri = jQuery('#redirect_uri').val();
        jQuery.ajax({
                url: url,
                type: "POST",
                data: {code:code,client_id:client_id,client_secret:client_secret,redirect_uri:redirect_uri},
                cache: false,
                success: function(response){
                  jQuery("#output").val(response);
            }
        });
    });

    });
});