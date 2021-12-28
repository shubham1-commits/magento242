<?php
namespace Elsnertech\Promobar\Block\Adminhtml\Product;

use Elsnertech\Promobar\Model\PromobarFactory;

class CustomTab extends \Magento\Backend\Block\Template
{
    protected $_template = 'Elsnertech_Promobar::custom_tab.phtml';
    protected $_promobar;
    protected $_registry;
    protected $storeManager;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,

        PromobarFactory $promobar,
        array $data = []
    ) {
        $this->_promobar = $promobar;
        $this->_registry = $registry;
        $this->storeManager = $storeManager;

        parent::__construct($context, $data);
    }

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
     public function MediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getCrudCollection()
    {
        $id = $this->getRequest()->getParam('id');
        $grid = $this->_promobar->create();
        $collection = $grid->load($id);
        return $collection;
    }

}
