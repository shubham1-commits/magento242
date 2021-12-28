<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model\Config\Source\Shipping;

use Magento\Framework\Data\OptionSourceInterface;
use MageWorx\ShippingRules\Model\Rule;

/**
 * Class Actions
 */
class Actions implements OptionSourceInterface
{

    /**
     * Return array of available actions.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $actions = $this->toArray();
        $result  = [['value' => '', 'label' => '']];

        foreach ($actions as $actionTypeCode => $actionTypes) {
            $result[$actionTypeCode] = ['label' => $actionTypeCode];
            foreach ($actionTypes as $actionCode => $actionLabel) {
                $result[$actionTypeCode]['value'][] = [
                    'label' => __($actionLabel),
                    'value' => $actionCode
                ];
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $matrix  = Rule::getCalculationMatrix();
        $actions = [
            'Fixed'   => [],
            'Percent' => []
        ];

        foreach ($matrix as $key => $calcMethod) {
            if (mb_stripos($calcMethod, Rule::ACTION_CALCULATION_FIXED) !== false) {
                $actions['Fixed'][$key] = $calcMethod;
            } else {
                $actions['Percent'][$key] = $calcMethod;
            }
        }

        return $actions;
    }
}
