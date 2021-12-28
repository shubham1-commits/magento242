require([
"jquery"
], function($){

    	$(window).on('load', function() {
        
            setTimeout(function(){

                jQuery(document).change(function() {
                    var x = document.getElementsByClassName('item_url_text')[0].value;
                    document.getElementById("promo_link_color").update(x);


                    var y = document.getElementsByClassName('item_bg_color')[0].value;
                   document.getElementById("promo_bg_color").style.color = y;


                   // var z = document.getElementsByClassName('promobarContainer')[0].value;
                    // document.getElementById("promo_link_color").update(x);




                temp_html = jQuery('#promobarContainer').html();
                console.log(temp_html.trim());
                jQuery('.promobar_container').val(temp_html.trim());
                jQuery('.promobar_container').trigger('input');
                jQuery('.promobar_container').trigger('change');





                });

                $(document).on('change', 'textarea', function() {
                    $("#promo_content_color").html($("#promobar_form_content").val());


                temp_html = jQuery('#promobarContainer').html();
                console.log(temp_html.trim());
                jQuery('.promobar_container').val(temp_html.trim());
                jQuery('.promobar_container').trigger('input');
                jQuery('.promobar_container').trigger('change');
                

                });

                 // var temp_html = JSON.stringify(jQuery('#promobarContainer').html());
                  // jQuery('.promobar_container').html(temp_html);
                 //jQuery('.item_url_text').val('hello');
                 //jQuery('.item_url_text').input('hhhhello');
                // jQuery('.item_url_text').val("babu");
                //var z = document.getElementById('promobarContainer');
                   //document.getElementsByClassName('promobar_container').update(z).html();


                 //   var z = document.getElementById('promobarContainer');
                 //    document.getElementById("promobar_container").set(z);
                 // console.log(jQuery('#promobar_container').html());
                //    // console.log(jQuery('.promobarContainer').val());

                // var z = document.getElementById('promobar_container')[0].value;
                //    document.getElementById("promobar_container").update(z);

                  
                 // var yes =  document.getElementById("save-button").value;
                 // alert(yes);



                // temp_html = jQuery('#promobarContainer').html();
                // console.log(temp_html.trim());

                // jQuery('.promobar_container').val(temp_html.trim());
                
                // jQuery('.promobar_container').trigger('input');
                // jQuery('.promobar_container').trigger('change');

                // console.log(jQuery('.promobar_container').val());


                   
            



            }, 10000);
    });
        // $(document).on('submit', function() {
        //     alert("submitted");
        // });


});
