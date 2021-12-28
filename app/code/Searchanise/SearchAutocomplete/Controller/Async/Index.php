<?php

namespace Searchanise\SearchAutocomplete\Controller\Async;

class Index extends \Magento\Framework\App\Action\Action
{
    const UPDATE_TIMEOUT = 3600;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\ApiSe
     */
    private $apiSeHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Data
     */
    private $searchaniseHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\ApiSe
     */
    private $notUseHttpRequestText = false;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\ApiSe
     */
    private $flShowStatusAsync = false;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Searchanise\SearchAutocomplete\Helper\ApiSe $apiSeHelper,
        \Searchanise\SearchAutocomplete\Helper\Data $searchaniseHelper
    ) {
        $this->storeManager = $storeManager;
        $this->apiSeHelper = $apiSeHelper;
        $this->searchaniseHelper = $searchaniseHelper;

        parent::__construct($context);
    }

    /**
     * Get 'not_use_http_request' param for URL
     *
     * @return string
     */
    private function _getNotUseHttpRequestText()
    {
        $this->notUseHttpRequestText = $this
            ->getRequest()
            ->getParam(\Searchanise\SearchAutocomplete\Helper\ApiSe::NOT_USE_HTTP_REQUEST);

        return $this->notUseHttpRequestText;
    }

    /**
     * Check if 'not_use_http_request' param is true
     *
     * @return boolean
     */
    private function _checkNotUseHttpRequest()
    {
        return $this->_getNotUseHttpRequestText() == \Searchanise\SearchAutocomplete\Helper\ApiSe::NOT_USE_HTTP_REQUEST_KEY;
    }

    /**
     * Get 'show_status' param for URL
     *
     * @return string
     */
    private function _getFlShowStatusAsync()
    {
        $this->flShowStatusAsync = $this
            ->getRequest()
            ->getParam(\Searchanise\SearchAutocomplete\Helper\ApiSe::FL_SHOW_STATUS_ASYNC);

        return $this->flShowStatusAsync;
    }

    /**
     * Check if 'show_status' param is true
     *
     * @return boolean
     */
    private function _checkShowSatusAsync()
    {
        return $this->_getFlShowStatusAsync() == \Searchanise\SearchAutocomplete\Helper\ApiSe::FL_SHOW_STATUS_ASYNC_KEY;
    }

    /**
     * Async
     *
     * {@inheritDoc}
     *
     * @see \Magento\Framework\App\ActionInterface::execute()
     */
    public function execute()
    {
        // ToCheck:
        $storeId = null;
        $result = '';
        $flIgnoreProcessing = false;

        if ($this->apiSeHelper->getStatusModule($storeId) == 'Y') {
            $checkKey = $this->searchaniseHelper->checkPrivateKey();
            $this->apiSeHelper->setHttpResponse($this->getResponse());

            ignore_user_abort(true);
            set_time_limit(self::UPDATE_TIMEOUT);

            if ($checkKey && $this->getRequest()->getParam('display_errors') === 'Y') {
                error_reporting(E_ALL | E_STRICT);
            } else {
                error_reporting(0);
            }

            $flIgnoreProcessing = $checkKey && $this->getRequest()->getParam('ignore_processing') == 'Y';

            try {
                $result = $this->apiSeHelper->async($flIgnoreProcessing);
            } catch (\Exception $e) {
                return $this->resultFactory->create(\Magento\Framework\Controller\resultFactory::TYPE_JSON)->setData(
                    [
                    'status' => __('Error') . '[' . $e->getCode() . ']: ' . $e->getMessage(),
                    ]
                );
            }
        } else {
            $result = __('Module is disabled');
        }

        if ($this->_checkShowSatusAsync()) {
            return $this->resultFactory->create(\Magento\Framework\Controller\resultFactory::TYPE_JSON)->setData(
                [
                'status' => $result,
                ]
            );
        } else {
            return $this->resultFactory->create(\Magento\Framework\Controller\resultFactory::TYPE_RAW);
        }
    }
}
