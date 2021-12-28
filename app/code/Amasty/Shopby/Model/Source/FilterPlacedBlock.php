<?php

namespace Amasty\Shopby\Model\Source;

/**
 * Class FilterPlacedBlock
 * @package Amasty\Shopby\Model\Source
 */
class FilterPlacedBlock implements \Magento\Framework\Option\ArrayInterface
{
    const POSITION_SIDEBAR = 0;
    const POSITION_TOP = 1;
    const POSITION_BOTH = 2;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::POSITION_SIDEBAR,
                'label' => __('Sidebar')
            ],
            [
                'value' => self::POSITION_TOP,
                'label' => __('Top')
            ],
            [
                'value' => self::POSITION_BOTH,
                'label' => __('Both')
            ]
        ];
    }
}
