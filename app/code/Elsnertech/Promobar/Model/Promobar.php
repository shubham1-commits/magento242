<?php

namespace Elsnertech\Promobar\Model;

use Magento\Framework\Model\AbstractModel;

class Promobar extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('Elsnertech\Promobar\Model\ResourceModel\Promobar');
    }
}
