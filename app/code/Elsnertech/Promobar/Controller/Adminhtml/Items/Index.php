<?php

namespace Elsnertech\Promobar\Controller\Adminhtml\Items;

class Index extends \Elsnertech\Promobar\Controller\Adminhtml\Items
{

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Elsnertech_Promo::test');
        $resultPage->getConfig()->getTitle()->prepend(__('PromoBar'));
        $resultPage->addBreadcrumb(__('Test'), __('Test'));
        $resultPage->addBreadcrumb(__('Items'), __('Items'));
        return $resultPage;
    }
}
