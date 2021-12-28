/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/sidebar',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment-service',
    'mage/translate',
    'Magento_Ui/js/model/messageList'
], function (
    ko,
    $, 
    Component, 
    selectPaymentMethodAction, 
    stepNavigator, 
    checkoutData, 
    sidebarModel,
    getPaymentInformation,
    additionalValidators,
    quote,
    paymentService,
    $t,
    messageList
) {
    'use strict';

    return Component.extend({
        isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),
        errorValidationMessage: ko.observable(false),
        defaults: {
            template: 'Elsnertech_Checkoutstep/payment-select'
        },

        
        back: function () {
            sidebarModel.hide();
            stepNavigator.navigateTo('payment');
        },

        selectPaymentMethod: function (formElement) {

            if (this.validatePaymentInformation(formElement)) {
                var paymentForm = $(formElement).parent().parent();
                var paymentSelector = '[name="payment[method]"]:checked';
                var selectedPayment = paymentForm.find(paymentSelector);
                var methodCode = selectedPayment.val();
                var self = this;
                
                selectPaymentMethodAction(this.getData(methodCode));
                checkoutData.setSelectedPaymentMethod(methodCode);
                getPaymentInformation().done(function () {
                    self.backToShippingMethod();
                });
            }
        },
        validatePaymentInformation: function (formElement) {
            var paymentForm = $(formElement).parent().parent();
            additionalValidators.registerValidator(paymentForm);
            var paymentSelector = '[name="payment[method]"]:checked';
            var selectedPayment = paymentForm.find(paymentSelector);
            var methodCode = selectedPayment.val();
            if (!quote.paymentMethod()) {
                messageList.addErrorMessage({
                  message: $t('The payment method is missing. Select the payment method and try again.')
                });
                return false;
            }
            if(!methodCode){
                messageList.addErrorMessage({
                  message: $t('The payment method is missing. Select the payment method and try again.')
                });
                return false;
            }
            if (!quote.shippingMethod()) {
                messageList.addErrorMessage({
                  message: $t('The shipping method is missing. Select the shipping method and try again.')
                });
                

                return false;
            }
            return true;
        },

        isChecked: ko.computed(function () {
            return quote.paymentMethod() ? quote.paymentMethod().method : null;
        }),

        isRadioButtonVisible: ko.computed(function () {
            return paymentService.getAvailablePaymentMethods().length !== 1;
        }),

        getData: function (methodCode) {
            return {
                'method': methodCode,
                'po_number': null,
                'additional_data': null
            };
        },

        /**
         * Back to shipping method.
         */
        backToShippingMethod: function () {
            //sidebarModel.hide();
            stepNavigator.next();
        }
    });
});
