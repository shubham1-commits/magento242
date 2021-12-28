<?php

namespace Elsnertech\Promobar\Block;

use Magento\Framework\View\Element\Template\Context;
use Elsnertech\Promobar\Model\PromobarFactory;

class Category extends \Magento\Framework\View\Element\Template
{
    protected $_promobar;
    
    protected $_registry;

    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        PromobarFactory $promobar
    ) {
        $this->_registry = $registry;
        $this->_promobar = $promobar;
        parent::__construct($context);
    }

    public function getCategoryCollection()
    {
        $category = $this->_registry->registry('current_category');
        return $category->getId();
    }

    public function getCrudCollection()
    {
        $promobar = $this->_promobar->create();
        $collection = $promobar->getCollection();
        $collection->addFieldToFilter('status', '1');
        $collection->setOrder('priority', 'ASC');
        return $collection;
    }
}
