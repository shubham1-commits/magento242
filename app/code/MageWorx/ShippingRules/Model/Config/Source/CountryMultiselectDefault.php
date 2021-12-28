<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model\Config\Source;

/**
 * Class CountryMultiselectDefault
 */
class CountryMultiselectDefault extends \Magento\Directory\Model\Config\Source\Country
{
    /**
     * Return options array
     *
     * @param boolean $isMultiselect
     * @param string|array $foregroundCountries
     * @return array
     */
    public function toOptionArray($isMultiselect = true, $foregroundCountries = '')
    {
        return parent::toOptionArray($isMultiselect, $foregroundCountries);
    }
}
