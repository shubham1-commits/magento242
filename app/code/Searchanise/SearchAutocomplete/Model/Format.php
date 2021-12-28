<?php

namespace Searchanise\SearchAutocomplete\Model;

use Magento\Framework\Locale\Bundle\DataBundle;

class Format extends \Magento\Framework\Locale\Format
{
    private static $defaultNumberSet = 'latn';

    public function getPriceFormat($locale_code = null, $currency_code = null)
    {
        $locale_code = $locale_code ?: $this->_localeResolver->getLocale();

        if ($currency_code) {
            $currency = $this->currencyFactory->create()->load($currency_code);
        } else {
            $currency = $this->_scopeResolver->getScope()->getCurrentCurrency();
        }

        $locale_data = (new DataBundle())->get($locale_code);
        $default_set = $locale_data['NumberElements']['default'] ?: self::$defaultNumberSet;

        $format = $locale_data['NumberElements'][$default_set]['patterns']['currencyFormat']
            ?: ($locale_data['NumberElements'][self::$defaultNumberSet]['patterns']['currencyFormat']
                ?: explode(';', $locale_data['NumberPatterns'][1])[0]);

        $decimal_symbol = '.';
        $group_symbol = ',';

        $pos = strpos($format, ';');

        if ($pos !== false) {
            $format = substr($format, 0, $pos);
        }

        $format = preg_replace("/[^0\#\.,]/", "", $format);
        $total_precision = 0;
        $decimal_point = strpos($format, '.');

        if ($decimal_point !== false) {
            $total_precision = strlen($format) - (strrpos($format, '.') + 1);
        } else {
            $decimal_point = strlen($format);
        }

        $required_precision = $total_precision;
        $t = substr($format, $decimal_point);
        $pos = strpos($t, '#');

        if ($pos !== false) {
            $required_precision = strlen($t) - $pos - $total_precision;
        }

        if (strrpos($format, ',') !== false) {
            $group = $decimal_point - strrpos($format, ',') - 1;
        } else {
            $group = strrpos($format, '.');
        }

        $integer_required = strpos($format, '.') - strpos($format, '0');

        return [
            'pattern' => $currency->getOutputFormat(),
            'precision' => $total_precision,
            'required_precision' => $required_precision,
            'decimal_symbol' => $decimal_symbol,
            'group_symbol' => $group_symbol,
            'group_length' => $group,
            'integer_required' => $integer_required,
        ];
    }
}
