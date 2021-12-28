define(['jquery'], function ($) {
    "use strict";
    $(document).ready(function () {

       var oneValue = "global";
       var classname = "testclass";
       var test = jQuery('#url').val();   

       var parentremove = function(){
            jQuery('#order').remove();
            jQuery('#generalinquiry').remove();
            jQuery('#customer').remove();
            jQuery('#product').remove();
        }

        var sendermessage = function(message){
            jQuery(".quick-replies").css("display", "none");
            var data = '<div class="sender-wrapper" style="display: inline-block;"><p id="sender">'+message+'</p></div>';
            jQuery(".msg-wrap").append(data);
        };


        var childlink = function(url, oneValue) {
           jQuery(".chatbot-data-wrap").html();
            jQuery.ajax({
                url: url,
                type: "POST",
                data: {
                    numone: oneValue
                },
                cache: false,
                success: function (response) {
                    jQuery(".quick-replies").append(response);
                    // jQuery(".quick-replies").css("display", "flex");
                }
            });
        }


        $(document).on('click', '#orderlist', function () {
            var url = test + "chatboat/order/orderlist";
            let oneValue = "orderlist is selected";
            sendermessage(oneValue);
            var classname = 'orlist';
            var data = "increment_id";
            mainajaxcall(url, oneValue,classname,data);
        });   

        var receivermessage = function(message,maincls){    
            jQuery(".quick-replies").css("display", "none");
            var data = '<div class="reci-wrapper" style="display: inline-block;"><p id="reci" class="'+maincls+'">'+message+'</p></div>';
            jQuery(".msg-wrap").append(data);
            jQuery(".quick-replies").css("display", "flex");
        };
    
        var mainajaxcall = function(url, oneValue ,classname ,data1) {
            jQuery.ajax({
                url: url,
                type: "POST",
                data: {numone:oneValue},
                cache: false,
                success: function(response){
                    if(response=="Click Yes to create new Account") {
                       newlogin();
                    } else {
                        response = JSON.parse(response);
                        for(var index in response) {
                            if(response[index].increment_id) {
                              receivermessage(response[index].increment_id ,classname);
                            }
                            else{
                                receivermessage(response ,classname); 
                                $('.quick-replies').css("display","flex");
                            }
                        };
                    }
                }
            });
        }

        $(document).on('click', '#address', function () {
            let oneValue = "get Address";
            sendermessage(oneValue);
            var url = test + "chatboat/address/address";
            jQuery(".quick-replies").css("display", "none");
            jQuery.ajax({
                url: url,
                type: "POST",
                data: {numone:oneValue},
                cache: false,
                dataType: 'json',
                success: function(response){
                    if(response=="Click Yes to create new Account") {
                       newlogin();
                    } else {
                        var company = response['company'];
                        var city = response['city'];
                        var country = response['country'];
                        var phone = response['phone'];
                        var region = response['region'];
                        var responce1 = 'company name is'+company+'<br>'+'city is'+city+'<br>'+'country is'+country+'<br>'+'phone is'+phone+'<br>'+'region is'+region;
                        receivermessage(responce1,classname);                        
                    }
                }
            });
        });


        $(document).on('click', '#forgotpassword', function () {
            let oneValue = "forgotpassword";
            sendermessage(oneValue);
            var url = test + "chatboat/forgotpassword/index";
            jQuery(".quick-replies").css("display", "none");
            jQuery.ajax({
                url: url,
                type: "POST",
                data: {numone:oneValue},
                cache: false,
                dataType: 'json',
                success: function(response){
                    console.log(response);
                }
            });
        });



        $(document).on('click', '.orstatus', function () {
            var url = test + "chatboat/order/orstatus";
            var oneValue = jQuery(this).html();
            jQuery(".quick-replies").css("display", "none");
            jQuery.ajax({
                url: url,
                type: "POST",
                data: {numone:oneValue},
                cache: false,
                dataType: 'json',
                success: function(response){
                    if(response['productname']) {
                        var productname = response['productname'];
                        var status = response['status'];
                        var responce1 = 'product name is'+productname+'<br>'+'status is'+status;
                        receivermessage(responce1,classname);                        
                    } else {
                        receivermessage(responce,classname);
                    }
                }
            });
        });


        $(document).on('click', '#goback', function () {
            $('#goback').remove();
            var links = '<button id="generalinquiry" class="controller">General support</button><button id="order" class="controller">⚡️Order management</button><button id="customer" class="controller">Customer management</button><button id="product" class="controller">💲Product management</button>';
            $('.quick-replies').append(links);
            $('#cart').remove();
            $('#wishlist').remove();
            $('#address').remove();
            $('#forgotpassword').remove();
            $('#orderlist').remove();
            $('#orderstatus').remove();
        });

        $(document).on('click','#order',function () {
            parentremove();
            var url = test + "chatboat/management/order";
            childlink(url, oneValue);
        });

        $(document).on('click','#customer',function () {
            parentremove();
            var url = test + "chatboat/management/customer";
            childlink(url, oneValue);
        });

        $(document).on('click','#product',function () {
            parentremove();
            var url = test + "chatboat/management/product";
            childlink(url, oneValue);
        });

        $(document).on('click', '.orlist', function () {
            var url = test + "chatboat/order/orderid";
            var oneValue = jQuery(this).html();
            jQuery(".quick-replies").css("display", "none");
            jQuery.ajax({
                url: url,
                type: "POST",
                data: {numone:oneValue},
                cache: false,
                dataType: 'json',
                success: function(response){
                    console.log(response); 
                    var classname =""
                    var productname = response['productname'];
                    var price = response['price'];
                    var region = response['region'];
                    var postcode = response['postcode'];
                    var street = response['street'];
                    var responce1 = 'product name is'+productname+'price is'+price+'region is'+region+'street is'+street;
                    receivermessage(responce1,"classname");
                }
            });
        });

        $(document).on('click', '#orderstatus', function () {
            var oneValue = "Order status is seleted";
            sendermessage(oneValue);
            var classname = "orstatus";
            var data = "increment_id";
            var url = test + "chatboat/order/orderstatus";
            mainajaxcall(url, oneValue,classname ,data);
        });




        $('.open-button').click(function () {
            var url = test + "chatboat/index/index";
            var data1 = "ab";
            jQuery(".quick-replies").css("display", "none");
            jQuery.ajax({
                url: url,
                type: "POST",
                data: {
                    numone: data1
                },
                cache: false,
                success: function (response) {
                    jQuery(".msg-wrap").append(response);
                    if (response == '<div class="reci-wrapper" style="display: inline-block;"><p id="reci">👋 Hi ! I m a Bot. Let me know if you have any questions regarding our tool!</p></div><div class="reci-wrapper" style="display: inline-block;"><p id="reci">Select the topic or write your question below.</p></div>') {
                        jQuery(".quick-replies").css("display", "flex");
                    }
                }
            });
        });



    });
});