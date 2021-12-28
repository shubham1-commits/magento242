<?php

namespace Elsnertech\Promobar\Model\ResourceModel;

class Promobar extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('elsnertech_promobar', 'id');
    }
}
