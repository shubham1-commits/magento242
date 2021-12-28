<?php

namespace Searchanise\SearchAutocomplete\Model\Config\Source;

class DescriptionAttr implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            \Searchanise\SearchAutocomplete\Model\Configuration::ATTR_SHORT_DESCRIPTION => __('Short Description'),
            \Searchanise\SearchAutocomplete\Model\Configuration::ATTR_DESCRIPTION       => __('Description'),
        ];
    }
}
