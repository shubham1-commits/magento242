/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/sidebar'
], function ($, Component, quote, stepNavigator, sidebarModel) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Elsnertech_Checkoutstep/payment-information'
        },

        /**
         * @return {Boolean}
         */
        isVisible: function () {
            return stepNavigator.isProcessed('shipping');
        },

        /**
         * @return {String}
         */
        getPaymentMethodTitle: function () {
            var paymentMethod = quote.paymentMethod(),
                paymentMethodTitle = '';
            if (!paymentMethod) {
                return '';
            }
            /*console.log(paymentMethod);
            paymentMethodTitle = paymentMethod['title'];*/

            if (typeof paymentMethod['method'] !== 'undefined') {
                paymentMethodTitle +=  paymentMethod['method'];
            }
            
            return paymentMethodTitle;
        },

        /**
         * Back step.
         */
        back: function () {
            sidebarModel.hide();
            stepNavigator.navigateTo('payment');
        },

        /**
         * Back to shipping method.
         */
        backToShippingMethod: function () {
            sidebarModel.hide();
            stepNavigator.navigateTo('payment', 'opc-shipping_method');
        }
    });
});
