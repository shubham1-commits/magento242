define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Elsnertech_simple/web/js/view/validate-phonenumber'
    ],
    function (Component, additionalValidators, validatePhonenumber) {
        'use strict';
        additionalValidators.registerValidator(validatePhonenumber);
        return Component.extend({

            alert("ok");

        });
    }
);