<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Block\Adminhtml\Shippingrules\Rate\Edit\Button;

use Magento\Ui\Component\Control\Container;

/**
 * Class SaveAndContinue
 */
class SaveAndContinue extends Generic
{
    /**
     * Get save and continue edit button data
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->getRate() && $this->getRate()->getId()) {
            $data = [
                'label'          => __('Save and Continue Edit'),
                'class'          => 'save',
                'class_name'     => Container::DEFAULT_CONTROL,
                'options'        => [],
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'saveAndContinueEdit'],
                    ],
                ],
            ];
        }

        return $data;
    }
}
