<?php

namespace Searchanise\SearchAutocomplete\Helper;

class ApiPages extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var array
     */
    private static $excludedPages = [
        'no-route', // 404 page
        'enable-cookies', // Enable Cookies
        'privacy-policy-cookie-restriction-mode', // Privacy Policy
        'service-unavailable', // 503 Service Unavailable
        'private-sales', // Welcome to our Exclusive Online Store
        'home', // Home
    ];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Searchanise\SearchAutocomplete\Model\Configuration
     */
    private $configuration;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Data
     */
    private $searchaniseHelper;

    /**
     * @var Magento\Cms\Model\Template\FilterProvider
     */
    private $filterProvider;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    private $cmsResourceModelPageCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Searchanise\SearchAutocomplete\Model\Configuration $configuration,
        \Searchanise\SearchAutocomplete\Helper\Data $searchaniseHelper,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $cmsResourceModelPageCollectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->filterProvider = $filterProvider;
        $this->configuration = $configuration;
        $this->searchaniseHelper = $searchaniseHelper;
        $this->cmsResourceModelPageCollectionFactory = $cmsResourceModelPageCollectionFactory;

        parent::__construct($context);
    }

    /**
     * Generate feed for the page
     *
     * @param  \Magento\Cms\Model\Page    $page
     * @param  \Magento\Store\Model\Store $store
     * @param  string                     $checkData
     * @return array
     */
    public function generatePageFeed(
        \Magento\Cms\Model\Page $page,
        \Magento\Store\Model\Store $store = null,
        $checkData = true
    ) {
        $item = [];

        if ($checkData
            && (empty($page)
            || !$page->getId()
            || !$page->getTitle()
            || !$page->getIsActive()
            || in_array($page->getIdentifier(), self::$excludedPages))
        ) {
            return $item;
        }

        $apiSeHelper = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('\Searchanise\SearchAutocomplete\Helper\ApiSe');

        $item['id'] = $page->getId();
        $item['title'] = $page->getTitle();
        $item['link'] = $apiSeHelper
            ->getStoreUrl($store->getId())
            ->getUrl(
                null,
                [
                    '_direct' => $page->getIdentifier(),
                    '_secure' => $apiSeHelper->getIsUseSecureUrlsInFrontend($store->getId()),
                ]
            );

        if ($this->configuration->getIsRenderPageTemplateEnabled()) {
            $item['summary'] = $this->filterProvider->getPageFilter()->filter($page->getContent());
        } else {
            $item['summary'] = $page->getContent();
        }

        return $item;
    }

    /**
     * Retruns pages by pages ids
     *
     * @param  mixed                      $pageIds Pages ids
     * @param  \Magento\Store\Model\Store $store   Stores
     * @return array
     */
    public function getPages(
        $pageIds = \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA,
        \Magento\Store\Model\Store $store = null
    ) {
        static $arrPages = [];

        $keyPages = '';

        $this->searchaniseHelper->startEmulation($store);

        if (!empty($pageIds)) {
            if (is_array($pageIds)) {
                $keyPages .= implode('_', $pageIds);
            } else {
                $keyPages .= $pageIds;
            }
        }

        $storeId = !empty($store) ? $store->getId() : 0;
        $keyPages .= ':' .  $storeId;

        if (!isset($arrPages[$keyPages])) {
            $collection = $this->cmsResourceModelPageCollectionFactory
                ->create()
                ->addFieldToFilter('is_active', 1);

            if ($store) {
                $collection->addStoreFilter($storeId);
            }

            if ($pageIds !== \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA) {
                // Already exist automatic definition 'one value' or 'array'.
                $this->_addIdFilter($collection, $pageIds);
            }

            $arrPages[$keyPages] = $collection->load();
        }

        $this->searchaniseHelper->stopEmulation();

        return $arrPages[$keyPages];
    }

    /**
     * Generate feed for the pages
     *
     * @param  mixed                      $pageIds   Page ids
     * @param  \Magento\Store\Model\Store $store     Store
     * @param  boolean                    $checkData
     * @return array
     */
    public function generatePagesFeed(
        $pageIds = \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA,
        \Magento\Store\Model\Store $store = null,
        $checkData = true
    ) {
        $items = [];

        $pages = $this->getPages($pageIds, $store);

        if (!empty($pages)) {
            $this->searchaniseHelper->startEmulation($store);

            foreach ($pages as $page) {
                $item = $this->generatePageFeed($page, $store, $checkData);

                if (!empty($item)) {
                    $items[] = $item;
                }
            }

            $this->searchaniseHelper->stopEmulation();
        }

        return $items;
    }

    /**
     * Returns mix/max page ids values
     *
     * @param  \Magento\Store\Model\Store $store
     * @return array(mix, max)
     */
    public function getMinMaxPageId(\Magento\Store\Model\Store $store = null)
    {
        $this->searchaniseHelper->startEmulation($store);

        $startId = $endId = 0;

        $pageStartCollection = $this->cmsResourceModelPageCollectionFactory
            ->create()
            ->setOrder('page_id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
            ->setPageSize(1);

        if (!empty($store)) {
            $pageStartCollection->addStoreFilter($store);
        }

        $pageEndCollection = $this->cmsResourceModelPageCollectionFactory
            ->create()
            ->setOrder('page_id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->setPageSize(1);

        if (!empty($store)) {
            $pageEndCollection->addStoreFilter($store);
        }

        if ($pageStartCollection->getSize() > 0) {
            $firstItem = $pageStartCollection->getFirstItem();
            $startId = $firstItem->getId();
        }

        if ($pageEndCollection->getSize() > 0) {
            $firstItem = $pageEndCollection->getFirstItem();
            $endId = $firstItem->getId();
        }

        $this->searchaniseHelper->stopEmulation();

        return [$startId, $endId];
    }

    /**
     * Returns page ids from range
     *
     * @param  number                     $start Start page id
     * @param  number                     $end   End page id
     * @param  number                     $step  Step value
     * @param  \Magento\Store\Model\Store $store
     * @return array
     */
    public function getPageIdsFromRange($start, $end, $step, \Magento\Store\Model\Store $store = null)
    {
        $arrPages = [];

        $this->searchaniseHelper->startEmulation($store);

        $pages = $this->cmsResourceModelPageCollectionFactory
            ->create()
            ->addFieldToFilter('page_id', ['from' => $start, 'to' => $end])
            ->addFieldToFilter('is_active', 1)
            ->setPageSize($step);

        if (!empty($store)) {
            $pages = $pages->addStoreFilter($store->getId());
        }

        $arrPages = $pages->getAllIds();
        // It is necessary for save memory.
        unset($pages);

        $this->searchaniseHelper->stopEmulation();

        return $arrPages;
    }

    /**
     * Add Id filter
     *
     * @param  \Magento\Cms\Model\ResourceModel\Page\Collection $collection
     * @param  array                                            $pageIds
     * @return \Magento\Cms\Model\ResourceModel\Page\Collection
     */
    private function _addIdFilter(\Magento\Cms\Model\ResourceModel\Page\Collection &$collection, $pageIds)
    {
        if (is_array($pageIds)) {
            if (empty($pageIds)) {
                $condition = '';
            } else {
                $condition = ['in' => $pageIds];
            }
        } elseif (is_numeric($pageIds)) {
            $condition = $pageIds;
        } elseif (is_string($pageIds)) {
            $ids = explode(',', $pageIds);

            if (empty($ids)) {
                $condition = $pageIds;
            } else {
                $condition = ['in' => $ids];
            }
        }

        return $collection->addFieldToFilter('page_id', $condition);
    }
}
