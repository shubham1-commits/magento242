<?php

namespace Elsnertech\Promobar\Model\ResourceModel\Promobar;
 
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    
    protected function _construct()
    {
        $this->_init(
            'Elsnertech\Promobar\Model\Promobar',
            'Elsnertech\Promobar\Model\ResourceModel\Promobar'
        );
    }
}
