/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/form/form',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, Component, fullScreenLoader) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'Elsnertech_Checkoutstep/custom-message'
        },

        /**
         * Is login form enabled for current customer.
         *
         * @return {Boolean}
         */
        isActive: function () {
            return 1;
        }
    });
});
