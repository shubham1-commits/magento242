define([
    'Magento_Ui/js/form/element/abstract',
    'mageUtils',
    'jquery',
    'jquery/colorpicker/js/colorpicker'
], function (Element, utils, $) {
    'use strict';


    return Element.extend({
        defaults: {
            visible: true,
            label: '',
            error: '',
            uid: utils.uniqueid(),
            disabled: false,
            links: {
                value: '${ $.provider }:${ $.dataScope }'
            }
        },


        initialize: function () {
            this._super();
        },

        initColorPickerCallback: function (element) {
            var self = this;

             $(element).ColorPicker({
            onSubmit: function(hsb, hex, rgb, el) {
                self.value("#"+hex);
                $(el).ColorPickerHide();
            },
            onBeforeShow: function () {
                $(this).ColorPickerSetColor(this.value);
            },
            
            onChange: function(hsb, hex, rgb, el) {
                self.value('#'+hex);
                if( self.inputName == 'bg_color' ){
                    $("#promo_bg_color").css({"background-color": "#"+hex});

                temp_html = jQuery('#promobarContainer').html();
                console.log(temp_html.trim());
                jQuery('.promobar_container').val(temp_html.trim());
                jQuery('.promobar_container').trigger('input');
                jQuery('.promobar_container').trigger('change');

                }
                else if( self.inputName == 'conent_color' ){
                    $("#promo_content_color").css({"color": "#"+hex});

                temp_html = jQuery('#promobarContainer').html();
                console.log(temp_html.trim());
                jQuery('.promobar_container').val(temp_html.trim());
                jQuery('.promobar_container').trigger('input');
                jQuery('.promobar_container').trigger('change');

                }
                else if( self.inputName == 'url_text_color' ){
                    $("#promo_link_color").css({"color": "#"+hex});

                temp_html = jQuery('#promobarContainer').html();
                console.log(temp_html.trim());
                jQuery('.promobar_container').val(temp_html.trim());
                jQuery('.promobar_container').trigger('input');
                jQuery('.promobar_container').trigger('change');
                
                }

               // console.log(self);
            }
            }).bind('keyup', function(){
                $(this).ColorPickerSetColor(this.value);
            });
        }
    });

    
});