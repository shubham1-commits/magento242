<?php

namespace Elsnertech\Checkoutstep\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

class OurLayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout)
    {
        $jsLayout['components']['checkout']['children']['steps']['children']['review-step']['children'] = $jsLayout['components']['checkout']['children']['sidebar']['children'];
        unset($jsLayout['components']['checkout']['children']['sidebar']['children']);
        return $jsLayout;
    }
}