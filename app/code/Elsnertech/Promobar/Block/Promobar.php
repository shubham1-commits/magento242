<?php
namespace Elsnertech\Promobar\Block;

use Magento\Framework\View\Element\Template\Context;
use Elsnertech\Promobar\Model\PromobarFactory;

class Promobar extends \Magento\Framework\View\Element\Template
{
    protected $_promobar;
    protected $storeManager;

    public function __construct(
        Context $context,
        \Magento\Framework\View\Layout $layout,
        \Magento\Framework\View\Result\Page $pageResult,
        \Magento\Framework\Stdlib\DateTime\DateTime $stdlibDateTime,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        PromobarFactory $promobar
    ) {
        $this->storeManager = $storeManager;
        $this->_layout = $layout;
        $this->_pageResult = $pageResult;
        $this->_promobar = $promobar;
        $this->_stdlibDateTime = $stdlibDateTime;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }
     public function MediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getCurrentPageLayout()
    {
        $currentPageLayout = $this->_pageResult->getConfig()->getPageLayout();

        if (is_null($currentPageLayout)) {
            return $this->_layout->getUpdate()->getPageLayout();
        }

        return $currentPageLayout;
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    public function getCrudCollection($layout = null)
    {
        $currentTime = $this->_stdlibDateTime->date();
        // echo $currentTime;
        // die();
        
        
        if ($layout == '1column' || $layout == 'page-bottom' || $layout == 'page-top') {
                $promobar = $this->_promobar->create();
                $collection = $promobar->getCollection();
                $collection->addFieldToFilter('status', '1');
                $collection->addFieldToFilter('layout', $layout);
                $collection->addFieldToFilter('start_date', ['lteq' => $currentTime]);
                $collection->addFieldToFilter('end_date', ['gteq' => $currentTime]);
                $collection->setOrder('priority', 'ASC');
                return $collection;
        }
        else if ($this->getCurrentPageLayout() == '3columns') {
            if ($layout == '2columns-left') {
                $promobar = $this->_promobar->create();
                $collection = $promobar->getCollection();
                $collection->addFieldToFilter('status', '1');
                $collection->addFieldToFilter('layout', $layout);
                $collection->addFieldToFilter('start_date', ['lteq' => $currentTime]);
                $collection->addFieldToFilter('end_date', ['gteq' => $currentTime]);
                $collection->setOrder('priority', 'ASC');
                return $collection;
            }

            if ($layout == '2columns-right') {
                $promobar = $this->_promobar->create();
                $collection = $promobar->getCollection();
                $collection->addFieldToFilter('status', '1');
                $collection->addFieldToFilter('layout', $layout);
                $collection->addFieldToFilter('start_date', ['gteq' => $currentTime]);
                $collection->addFieldToFilter('end_date', ['gteq' => $currentTime]);
                $collection->setOrder('priority', 'ASC');
                return $collection;
            }
        }
        else if ($this->getCurrentPageLayout() == $layout) {
            $promobar = $this->_promobar->create();
            $collection = $promobar->getCollection();
            $collection->addFieldToFilter('status', '1');
            $collection->addFieldToFilter('layout', $layout);
            $collection->addFieldToFilter('start_date', ['lteq' => $currentTime]);
            $collection->addFieldToFilter('end_date', ['gteq' => $currentTime]);
            $collection->setOrder('priority', 'ASC');
            return $collection;
        }
        return [];
    }
}
