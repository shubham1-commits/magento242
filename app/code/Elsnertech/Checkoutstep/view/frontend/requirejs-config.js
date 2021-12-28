var config = {
    'map': { '*': {} },
    'config': {
        'mixins': {
            'Magento_Checkout/js/view/shipping': {
               'Elsnertech_Checkoutstep/js/view/shipping-payment-mixin': true
            },
            'Magento_Checkout/js/view/payment': {
               'Elsnertech_Checkoutstep/js/view/shipping-payment-mixin': true
            },
            'Magento_CheckoutAgreements/js/model/agreements-assigner': {
                'Elsnertech_Checkoutstep/js/model/agreements-assigner-mixin': true
            }
       }
    }
}

config.map['*'] = {
        'Magento_Checkout/template/shipping': 'Elsnertech_Checkoutstep/template/shipping',
        'Magento_Checkout/template/payment': 'Elsnertech_Checkoutstep/template/payment',
        'Magento_CheckoutAgreements/js/model/agreement-validator': 'Elsnertech_Checkoutstep/js/model/agreement/agreement-validator'
    };
