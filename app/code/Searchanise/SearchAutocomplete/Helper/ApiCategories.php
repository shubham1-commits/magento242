<?php

namespace Searchanise\SearchAutocomplete\Helper;

/**
 * Categories helper for searchanise
 */
class ApiCategories extends \Magento\Framework\App\Helper\AbstractHelper
{
    // use id to hide categories
    private static $excludedCategories = [
    ];

    private static $additionalsAttrs = [];

    /**
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Data
     */
    private $searchaniseHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $catalogResourceModelCategoryCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Searchanise\SearchAutocomplete\Helper\Data $searchaniseHelper,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $catalogResourceModelCategoryCollectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->searchaniseHelper = $searchaniseHelper;
        $this->catalogResourceModelCategoryCollectionFactory = $catalogResourceModelCategoryCollectionFactory;

        parent::__construct($context);
    }

    /**
     * Generate feed for category
     *
     * @param  \Magento\Catalog\Model\Category $category  Category
     * @param  \Magento\Store\Model\Store      $store     Store
     * @param  string                          $checkData Flag to check the data
     * @return array
     */
    public function generateCategoryFeed(
        \Magento\Catalog\Model\Category $category,
        \Magento\Store\Model\Store $store = null,
        $checkData = true
    ) {
        $item = [];

        if ($checkData
            && (empty($category)
            || !$category->getName()
            || !$category->getIsActive()
            || in_array($category->getId(), self::$excludedCategories))
        ) {
            return $item;
        }

        // Need for generate correct url.
        if (!empty($store)) {
            $category->getUrlInstance()->setScope($store->getId());
        }

        $item['id'] = $category->getId();
        $item['parent_id'] = $this->getParentCategoryId($category, $store);
        $item['title'] = $category->getName();
        $item['link'] = $category->getUrl();
        $item['image_link'] = $this->getCategoryImageUrl($category, $store);
        $item['summary'] = $category->getDescription();

        return $item;
    }

    /**
     * Return parent category id
     * 
     * @param \Magento\Catalog\Model\Category    Category object
     * @param \Magento\Store\Model\Store $store  Store object
     * 
     * @return int
     */
    private function getParentCategoryId(
        \Magento\Catalog\Model\Category $category,
        \Magento\Store\Model\Store $store = null
    ) {
        $parentCategoryId = 0;

        if ($category) {
            try {
                $parentCategory = $category->getParentCategory();
                $parentCategoryId = $parentCategory ? $parentCategory->getId() : 0;
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // No parent category
                $parentCategoryId = 0;
            }
        }

        return $parentCategoryId;
    }

    /**
     * Returns category image url
     *
     * @param \Magento\Catalog\Model\Category    Category object
     * @param \Magento\Store\Model\Store $store  Store object
     * 
     * @return string 
     */
    private function getCategoryImageUrl(
        \Magento\Catalog\Model\Category $category,
        \Magento\Store\Model\Store $store = null
    ) {
        $imageUrl = '';

        if ($category) {
            try {
                $imageUrl = $category->getImageUrl();
            } catch (\Exception $e) {
                // No image
                $imageUrl = '';
            }
        }

        return $imageUrl;
    }

    /**
     * Returns additional categories attributes
     * 
     * @return array
     */
    private function getAdditionalAttrs()
    {
        return self::$additionalsAttrs;
    }

    /**
     * Returns root category id
     * 
     * @return int
     */
    private function getRootCategoryId()
    {
        static $rootCategoryId = -1;

        if ($rootCategoryId !== -1) {
            return $rootCategoryId;
        }

        $collection = $this->catalogResourceModelCategoryCollectionFactory
            ->create()
            ->addAttributeToFilter('parent_id', '0');

        $rootCategory = $collection->getFirstItem();
        $rootCategoryId = $rootCategory->getId();

        return $rootCategoryId;
    }

    /**
     * Return categories by category ids
     *
     * @param  mixed                      $categoryIds
     * @param  \Magento\Store\Model\Store $store
     * @return array
     */
    public function getCategories(
        $categoryIds = \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA,
        \Magento\Store\Model\Store $store = null
    ) {
        static $arrCategories = [];

        $keyCategories = '';
        $storeId = !empty($store) ? $store->getId() : 0;
        $storeRootCategoryId = $this->storeManager->getStore($storeId)->getRootCategoryId();
        $storeRootCategoryPath = sprintf('%d/%d', $this->getRootCategoryId(), $storeRootCategoryId);
        $additionalAttr = $this->getAdditionalAttrs();

        $this->searchaniseHelper->startEmulation($store);

        if (!empty($categoryIds)) {
            if (is_array($categoryIds)) {
                $keyCategories .= implode('_', $categoryIds);
            } else {
                $keyCategories .= $categoryIds;
            }
        }

        $keyCategories .= ':' .  $storeId;

        if (!isset($arrCategories[$keyCategories])) {
            $collection = $this->catalogResourceModelCategoryCollectionFactory->create();

            $collection
                ->distinct(true)
                ->addAttributeToSelect('*')
                ->setStoreId($storeId)
                ->addAttributeToFilter('level', ['gt' => 1])
                ->addPathFilter($storeRootCategoryPath)
                ->addOrderField('entity_id');

            if (!empty($store)) {
                $collection->setStoreId($store->getId());
            }

            if ($categoryIds !== \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA) {
                // Already exist automatic definition 'one value' or 'array'.
                $collection->addIdFilter(array_unique($categoryIds));
            }

            $arrCategories[$keyCategories] = $collection->load();
        }

        $this->searchaniseHelper->stopEmulation();

        return $arrCategories[$keyCategories];
    }

    /**
     * Generate categories feeds
     *
     * @param  unknown $categoryIds
     * @param  unknown $store
     * @param  string  $checkData
     * @return array[]
     */
    public function generateCategoriesFeed(
        $categoryIds = \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA,
        \Magento\Store\Model\Store $store = null,
        $checkData = true
    ) {
        $items = [];

        $categories = $this->getCategories($categoryIds, $store);

        if (!empty($categories)) {
            $this->searchaniseHelper->startEmulation($store);

            foreach ($categories as $category) {
                if ($item = $this->generateCategoryFeed($category, $store, $checkData)) {
                    $items[] = $item;
                }
            }

            $this->searchaniseHelper->stopEmulation();
        }

        return $items;
    }

    /**
     * Returns mix/max category ids values
     *
     * @param  \Magento\Store\Model\Store $store
     * @return array(mix, max)
     */
    public function getMinMaxCategoryId(\Magento\Store\Model\Store $store = null)
    {
        $this->searchaniseHelper->startEmulation($store);

        $startId = $endId = 0;

        $categoryStartCollection = $this->catalogResourceModelCategoryCollectionFactory->create()
            ->addAttributeToSelect(['entity_id'])
            ->addAttributeToSort('entity_id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
            ->setPageSize(1);

        if (!empty($store)) {
            $categoryStartCollection->setStoreId($store->getId());
        }

        $categoryEndCollection = $this->catalogResourceModelCategoryCollectionFactory->create()
            ->addAttributeToSelect(['entity_id'])
            ->addAttributeToSort('entity_id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->setPageSize(1);

        if (!empty($store)) {
            $categoryEndCollection->setStoreId($store->getId());
        }

        if ($categoryStartCollection->getSize() > 0) {
            $firstItem = $categoryStartCollection->getFirstItem();
            $startId = $firstItem->getId();
        }

        if ($categoryEndCollection->getSize() > 0) {
            $firstItem = $categoryEndCollection->getFirstItem();
            $endId = $firstItem->getId();
        }

        $this->searchaniseHelper->stopEmulation();

        return [$startId, $endId];
    }

    /**
     * Returns category ids from range
     *
     * @param  number                     $start Start category id
     * @param  number                     $end   End category id
     * @param  number                     $step  Step value
     * @param  \Magento\Store\Model\Store $store
     * @return array
     */
    public function getCategoryIdsFromRange($start, $end, $step, \Magento\Store\Model\Store $store = null)
    {
        $arrCategories = [];

        $this->searchaniseHelper->startEmulation($store);

        $categories = $this->catalogResourceModelCategoryCollectionFactory->create()
            ->distinct(true)
            ->addAttributeToSelect(['entity_id'])
            ->addFieldToFilter('entity_id', ['from' => $start, 'to' => $end])
            ->setPageSize($step);

        if (!empty($store)) {
            $categories->setStoreId($store->getId());
        }

        $arrCategories = $categories->getAllIds();
        // It is necessary for save memory.
        unset($categories);

        $this->searchaniseHelper->stopEmulation();

        return $arrCategories;
    }

    /**
     * Get children for categories
     *
     * @param  number $catId Category identifier
     * @return array
     */
    public function getAllChildrenCategories($catId)
    {
        $categoryIds = [];

        $categories = $this->catalogResourceModelCategoryCollectionFactory->create()
            ->setStoreId($this->storeManager->getStore()->getId())
            ->addFieldToFilter('entity_id', $catId)
            ->load();

        if (!empty($categories)) {
            foreach ($categories as $cat) {
                if (!empty($cat)) {
                    $categoryIds = $cat->getAllChildren(true);
                }
            }
        }

        return $categoryIds;
    }
}
