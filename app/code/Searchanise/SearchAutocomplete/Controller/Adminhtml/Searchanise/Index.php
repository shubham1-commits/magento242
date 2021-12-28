<?php

namespace Searchanise\SearchAutocomplete\Controller\Adminhtml\Searchanise;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    private $resultPageFactory;
    private $resultPage;

    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $this->_setPageData();

        return $this->getResultPage();
    }

    /**
     * TODO: add 'ACL' permission for admin
     protected function _isAllowed()
     {
        return $this->_authorization->isAllowed('SY_Callback::requests');
     }
     */

    private function getResultPage()
    {
        if ($this->resultPage === null) {
            $this->resultPage = $this->resultPageFactory->create();
        }

        return $this->resultPage;
    }

    private function _setPageData()
    {
        $resultPage = $this->getResultPage();

        $resultPage->setActiveMenu('Magento_Catalog::catalog');

        return $this;
    }
}
