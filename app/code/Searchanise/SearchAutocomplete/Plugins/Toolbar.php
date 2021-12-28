<?php

namespace Searchanise\SearchAutocomplete\Plugins;

/**
 * Toolbar plugin
 */
class Toolbar
{
    /**
     * @var \Searchanise\SearchAutocomplete\Helper\ApiSe
     */
    private $apiSeHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Data
     */
    private $searchaniseHelper;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $httpRequest;

    public function __construct(
        \Searchanise\SearchAutocomplete\Helper\ApiSe $apiSeHelper,
        \Searchanise\SearchAutocomplete\Helper\Data $searchaniseHelper
    ) {
        $this->apiSeHelper = $apiSeHelper;
        $this->searchaniseHelper = $searchaniseHelper;
    }

    /**
     * Check if searchanise fulltext search is enabled
     * 
     * @return bool
     */
    protected function getIsSearchaniseSearchEnabled()
    {
        return
            $this->apiSeHelper->getIsSearchaniseSearchEnabled()
            && $this->searchaniseHelper->checkEnabled();
    }

    /**
     * Modify available orders
     * 
     * @param \Magento\Catalog\Block\Product\ProductList\Toolbar $subject
     * @param array $orders
     * @return array
     */
    public function afterGetAvailableOrders(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        $orders
    ) {
        if ($this->getIsSearchaniseSearchEnabled()) {
            // Ordering by position doesn't support by Searchanise
            if (isset($orders['position'])) {
                unset($orders['position']);
                $subject->setAvailableOrders($orders);
                $subject->setDefaultOrder('name');
                $subject->setDefaultDirection('desc');
            }
        }

        return $orders;
    }

    /**
     * Modify available limits
     * 
     * @param \Magento\Catalog\Block\Product\ProductList\Toolbar $subject
     * @param array $availableLimit
     * @return array
     */
    public function afterGetAvailableLimit(\Magento\Catalog\Block\Product\ProductList\Toolbar $subject, $availableLimit)
    {
        if ($this->getIsSearchaniseSearchEnabled() && !empty($availableLimit)) {
            $maxPageSize = $this->apiSeHelper->getMaxPageSize();
            $bChanged = false;

            if (isset($availableLimit['all'])) {
                unset($availableLimit['all']);
                $bChanged = true;
            }

            foreach ($availableLimit as $name => $val) {
                if ($val > $maxPageSize) {
                    unset($availableLimit[$name]);
                    $bChanged = true;
                }
            }

            if ($bChanged) {
                if (!isset($availableLimit[$maxPageSize])) {
                    $availableLimit[$maxPageSize] = $maxPageSize;
                }

                $currentMode = $subject->getCurrentMode();

                if (in_array($currentMode, ['list', 'grid'])) {
                    $subject->_availableLimity = $availableLimit;
                } else {
                    $subject->_defaultAvailableLimit = $availableLimit;
                }
            }
        } // Endif of getIsSearchaniseSearchEnabled

        return $availableLimit;
    }
}
