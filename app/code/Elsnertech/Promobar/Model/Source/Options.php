<?php

namespace Elsnertech\Promobar\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Options implements ArrayInterface
{
        
    public function toOptionArray()
    {
        $result = [];

        $options = [ '1column' => 'Header Top', '2columns-left' => 'Left Column', '2columns-right' => 'Right Column','page-bottom' => 'Bottom Content','page-top' => 'Top Content' ];
        foreach ($options as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }
        return $result;
    }
}
