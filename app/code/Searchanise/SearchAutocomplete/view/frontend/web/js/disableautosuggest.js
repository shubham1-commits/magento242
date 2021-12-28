define(
    [
    'jquery',
    'jquery/ui',
    'Magento_Search/form-mini'
    ], function ($) {
        'use strict';

        $.widget(
            'searchanise.quickSearch', $.mage.quickSearch, {
                options: {
                    minSearchLength: 1000,
                },
            }
        );
        console.log($.searchanise.quickSearch);
        return $.searchanise.quickSearch;
    }
);
