<?php

namespace Searchanise\SearchAutocomplete\Controller\Adminhtml\Searchanise;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;

class Signup extends Action
{
    /**
     * \Searchanise\SearchAutocomplete\Helper\ApiSe
     */
    private $apiSeHelper;

    public function __construct(Context $context, \Searchanise\SearchAutocomplete\Helper\ApiSe $apiSeHelper)
    {
        $this->apiSeHelper = $apiSeHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->apiSeHelper->getStatusModule() == 'Y') {
            if (!$this->apiSeHelper->signup()) {
                $this->_redirect($this->apiSeHelper->getSearchaniseLink());
            }

            $this->apiSeHelper->queueImport();
            $this->_redirect($this->apiSeHelper->getSearchaniseLink());
        }
    }
}
