<?php

namespace Elsnertech\Promobar\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CustmOptions implements OptionSourceInterface
{

    protected $_categories;

    public function __construct(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $collection)
    {
        $this->_categories = $collection;
    }

    public function toOptionArray()
    {

        $collection = $this->_categories->create();
        $collection->addAttributeToSelect('*')->addFieldToFilter('is_active', 1);
        $itemArray = ['value' => '', 'label' => '--Please Select--'];
        $options = [];
        $options = $itemArray;
        foreach ($collection as $category) {
            $options[] = ['value' => $category->getId(), 'label' => $category->getName()];
        }
        return $options;
    }
}
