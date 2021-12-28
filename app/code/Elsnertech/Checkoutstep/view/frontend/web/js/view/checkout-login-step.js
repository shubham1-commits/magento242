define(
    [
        'ko',
        'uiComponent',
        'underscore',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Customer/js/model/customer'
    ],
    function (
        ko,
        Component,
        _,
        stepNavigator,
        customer
    ) {
        'use strict';
        /**
        * check-login - is the name of the component's .html template
        */
        return Component.extend({
            defaults: {
                template: 'Elsnertech_Checkoutstep/check-login'
            },

            //add here your logic to display step,
            isVisible: ko.observable(true),
            isLogedIn: customer.isLoggedIn(),
            //step code will be used as step content id in the component template
            stepCode: 'isLogedCheck',
            //step title value
            stepTitle: 'Anmeldung',

            nextFlage: false,

            /**
            *
            * @returns {*}
            */
            initialize: function () {
                this._super();
                // register your step
                let short = 8;
                
                stepNavigator.registerStep(
                    this.stepCode,
                    //step alias
                    null,
                    this.stepTitle,
                    //observable property with logic when display step or hide step
                    this.isVisible,

                    _.bind(this.navigate, this),

                    /**
                    * sort order value
                    * 'sort order value' < 10: step displays before shipping step;
                    * 10 < 'sort order value' < 20 : step displays between shipping and payment step
                    * 'sort order value' > 20 : step displays after payment step
                    */
                    short
                );

                return this;
            },

            /**
            * The navigate() method is responsible for navigation between checkout step
            * during checkout. You can add custom logic, for example some conditions
            * for switching to your custom step
            */
            navigate: function () {
                console.log(this.isLogedIn);
                if(this.isLogedIn){
                    this.navigateToNextStep();
                }
            },

            /**
            * @returns void
            */
            navigateToNextStep: function () {
                stepNavigator.next();
            },

            navigateTo: function () {
                if(this.nextFlage == false){
                    stepNavigator.navigateTo('shipping', 'opc-shipping_method');
                    this.nextFlage = true;
                }
                
                
            }
        });
    }
);