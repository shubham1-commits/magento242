define([
    'mage/utils/wrapper',
    'jquery'
], function (wrapper, $) {
    'use strict';

    return function (agreementsAssignerAction) {
        return wrapper.wrap(agreementsAssignerAction, function (originalAction, paymentData) {
            originalAction(paymentData);

            var checkoutFormData = $('.opc-block-summary div.checkout-agreements input, .opc-block-summary div.checkout-agreements textarea').serializeArray(),
                data = {},
                agreements = [],
                re = /^agreement\[\d+?\]$/;
            checkoutFormData.forEach(function(item){
                data[item.name] = item.value;
                if (re.test(item.name)) {
                    agreements.push(item.value);
                }
            });

            if (agreements.length) {
                if (paymentData['extension_attributes'] === undefined) {
                    paymentData['extension_attributes'] = {};
                }

                paymentData['extension_attributes']['agreement_ids'] = agreements;
            }

            return paymentData;
        });
    };
});