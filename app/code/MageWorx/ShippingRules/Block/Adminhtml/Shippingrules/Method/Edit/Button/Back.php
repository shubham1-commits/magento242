<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Block\Adminhtml\Shippingrules\Method\Edit\Button;

/**
 * Class Back
 */
class Back extends Generic
{
    /**
     * Get back button data
     * Can redirect to the corresponding carrier form
     *
     * @param int $sortOrder
     * @return array
     */
    public function getButtonData($sortOrder = 10)
    {
        $url     = $this->resolveRedirectBackUrl();
        $label   = __('Back');
        $onClick = sprintf("location.href = '%s';", $url);
        $result  = [
            'label'      => $label,
            'on_click'   => $onClick,
            'class'      => 'back',
            'sort_order' => $sortOrder
        ];

        return $result;
    }

    /**
     * Generate redirect url: to the grid or to the corresponding carrier form
     *
     * @return string
     */
    private function resolveRedirectBackUrl()
    {
        if ($this->isBackToCarrier() && $this->getMethod()->getCarrierCode()) {
            // Returns to the corresponding carriers edit form
            $carrierCode = $this->getMethod()->getCarrierCode();
            $url       = $this->getUrl(
                'mageworx_shippingrules/shippingrules_carrier/edit',
                [
                    'carrier_code' => $carrierCode
                ]
            );
        } else {
            // Returns back to grid
            $url = $this->getUrl('*/*/');
        }

        return $url;
    }
}
