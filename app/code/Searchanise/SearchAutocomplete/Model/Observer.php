<?php

namespace Searchanise\SearchAutocomplete\Model;

use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;

class Observer implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Searchanise\SearchAutocomplete\Helper\ApiSe
     */
    private $apiSeHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\ApiProducts
     */
    private $apiProducts;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Notification
     */
    private $notificationHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Model\QueueFactory
     */
    private $queueFactory;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory
     */
    private $configurableProductTypeFactory;

    /**
     * @var \Magento\GroupedProduct\Model\Product\Type\GroupedFactory
     */
    private $groupedProductProductTypeGroupedFactory;

    /**
     * @var \Magento\Bundle\Model\Product\TypeFactory
     */
    private $bundleProductTypeFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $catalogProductFactory;

    /**
     * @var \Magento\Eav\Model\EntityFactory
     */
    private $eavEntityFactory;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Sales\Model\OrderFactory $orderFactory
     */
    private $orderFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var boolean
     */
    private $isExistsCategory = false;

    /**
     * @var array
     */
    private $productIdsInCategory = [];

    public function __construct(
        \Searchanise\SearchAutocomplete\Helper\ApiSe $apiSeHelper,
        \Searchanise\SearchAutocomplete\Helper\ApiProducts $apiProducts,
        \Searchanise\SearchAutocomplete\Helper\Data $dataHelper,
        \Searchanise\SearchAutocomplete\Helper\Notification $notificationHelper,
        \Searchanise\SearchAutocomplete\Model\QueueFactory $queueFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory $configurableProductTypeFactory,
        \Magento\GroupedProduct\Model\Product\Type\GroupedFactory $groupedProductProductTypeGroupedFactory,
        \Magento\Bundle\Model\Product\TypeFactory $bundleProductTypeFactory,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $eavEntityFactory,
        \Magento\Catalog\Model\ProductFactory $catalogProductFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->apiSeHelper = $apiSeHelper;
        $this->apiProducts = $apiProducts;
        $this->dataHelper = $dataHelper;
        $this->notificationHelper = $notificationHelper;
        $this->queueFactory = $queueFactory;
        $this->configurableProductTypeFactory = $configurableProductTypeFactory;
        $this->groupedProductProductTypeGroupedFactory = $groupedProductProductTypeGroupedFactory;
        $this->bundleProductTypeFactory = $bundleProductTypeFactory;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->eavEntityFactory = $eavEntityFactory;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->orderFactory = $orderFactory;
        $this->registry = $registry;
    }

    /**
     * Returns a valid method name
     *
     * @param \Magento\Framework\Event $event
     */
    private function getMethodName(\Magento\Framework\Event $event)
    {
        return lcfirst(implode('', array_map('ucfirst', explode('_', $event->getName()))));
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $method_name = $this->getMethodName($observer->getEvent());

        if (method_exists($this, $method_name)) {
            $this->{$method_name}($observer->getEvent());
        }
    }

    /********************************
     * Layout events
     ********************************/

     /**
      * Before loading page
      *
      * @param \Magento\Framework\Event $event
      * @return \Searchanise\SearchAutocomplete\Model\Observer
      */
    private function layoutLoadBefore(\Magento\Framework\Event $event)
    {
        $layout = $event->getData('layout');
        $api_key = $this->apiSeHelper->getApiKey();

        if (
            $layout
            && $this->apiSeHelper->checkStatusModule()
            && ($this->apiSeHelper->getIsAdmin() || !empty($api_key))
        ) {
            $layout->getUpdate()->addHandle('searchanise_handler');
        }

        return $this;
    }

    /********************************
     * Product events
     ********************************/

    /**
     * Before save product
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogProductSaveBefore(\Magento\Framework\Event $event)
    {
        $this->queueFactory->create()->addActionDeleteProductFromOldStore($event->getProduct());

        return $this;
    }

    /**
     * After save product
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogProductSaveAfter(\Magento\Framework\Event $event)
    {
        $product = $event->getProduct();

        if (!empty($product)) {
            $this->addProductToQueue($product);
        }

        return $this;
    }

    /**
     * Before delete product
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogProductDeleteBefore(\Magento\Framework\Event $event)
    {
        $this->queueFactory->create()->addActionDeleteProduct($event->getProduct());

        return $this;
    }

    /**
     * Product attribute update
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogProductAttributeUpdateBefore(\Magento\Framework\Event $event)
    {
        $productIds = $event->getData('product_ids');

        if (!empty($productIds)) {
            foreach ($productIds as $k => $productId) {
                // TODO: Deprecated
                $product = $this->catalogProductFactory->create()->load($productId);

                if (!empty($product)) {
                    $storeIds = $product->getStoreIds();

                    if (!empty($storeIds)) {
                        foreach ($storeIds as $k => $storeId) {
                            $this->queueFactory->create()->addAction(
                                \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PRODUCTS,
                                $product->getId(),
                                $storeId
                            );
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Product website update
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogProductToWebsiteChange(\Magento\Framework\Event $event)
    {
        $productIds = $event->getData('products');
        $request = $this->request->getPost();

        $storeAddIds = $this->apiSeHelper->getStoreByWebsiteIds($request->get('add_website_ids'));
        $storeRemoveIds = $this->apiSeHelper->getStoreByWebsiteIds($request->get('remove_website_ids'));

        if (!empty($storeAddIds) && !empty($productIds)) {
            foreach ($productIds as $k => $productId) {
                foreach ($storeAddIds as $k => $storeId) {
                    $this->queueFactory->create()->addAction(
                        \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PRODUCTS,
                        $productId,
                        $storeId
                    );
                }
            }
        }

        if (!empty($storeRemoveIds) && !empty($productIds)) {
            foreach ($productIds as $k => $productId) {
                // TODO: Deprecated
                $productOld = $this->catalogProductFactory->create()->load($productId);

                if (!empty($productOld)) {
                    $storeIdsOld = $productOld->getStoreIds();

                    if (!empty($storeIdsOld)) {
                        foreach ($storeRemoveIds as $k => $storeId) {
                            if (in_array($storeId, $storeIdsOld)) {
                                $this->queueFactory->create()->addAction(
                                    \Searchanise\SearchAutocomplete\Model\Queue::ACT_DELETE_PRODUCTS,
                                    $productId,
                                    null,
                                    $storeId
                                );
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

    /********************************
     * Product reviews
     ********************************/
    private function reviewUpdate(\Magento\Framework\Event $event)
    {
        $review = $event->getDataObject();

        if (!empty($review)) {
            $productId = $review->getEntityPkValue();
            $storeAddIds = (array)$review->getStores();

            foreach ($storeAddIds as $storeId) {
                if (!empty($storeId)) {
                    $this->queueFactory->create()->addAction(
                        \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PRODUCTS,
                        $productId,
                        null,
                        $storeId
                    );
                }
            }
        }

        return $this;
    }

    private function reviewSaveAfter(\Magento\Framework\Event $event)
    {
        return $this->reviewUpdate($event);
    }

    private function reviewDeleteAfter(\Magento\Framework\Event $event)
    {
        return $this->reviewUpdate($event);
    }

    /********************************
     * Product import events
     ********************************/

    /**
     * Delete product after import
     *
     * @param \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogProductImportBunchDeleteAfter(\Magento\Framework\Event $event)
    {
        if ($products = $event->getBunch()) {
            $idToDelete = [];
            $oldSku = $event->getAdapter()->getOldSku();

            foreach ($products as $product) {
                $sku = strtolower($product[ImportProduct::COL_SKU]);

                if (isset($oldSku[$sku])) {
                    $idToDelete[] = $oldSku[$sku]['entity_id'];
                }
            }

            if (!empty($idToDelete)) {
                $this->queueFactory
                    ->create()
                    ->addActionDeleteProductIds($idToDelete);
            }
        }

        return $this;
    }

    /**
     * Update products after import
     *
     * @param \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogProductImportBunchSaveAfter(\Magento\Framework\Event $event)
    {
        if ($products = $event->getBunch()) {
            $productIds = [];

            foreach ($products as $product) {
                $newSku = $event->getAdapter()->getNewSku($product[ImportProduct::COL_SKU]);

                if (empty($newSku) || !isset($newSku['entity_id'])) {
                    continue;
                }

                $productIds[] = $newSku['entity_id'];
            }

            if (!empty($productIds)) {
                $this->queueFactory
                    ->create()
                    ->addActionProductIds($productIds, \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PRODUCTS);
            }
        }

        return $this;
    }

    /********************************
     * Category events
     ********************************/

    /**
     * Save category before
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogCategorySaveBefore(\Magento\Framework\Event $event)
    {
        $category = $event->getCategory();

        if ($category && $category->getId()) {
            $this->isExistsCategory = true; // New category doesn't run the catalogCategorySaveBefore function.
            // For category
            $this->queueFactory->create()->addActionCategory($category);

            // For products from category
            // It save before because products could remove from $category.
            $products = $category->getProductCollection();
            $this->queueFactory->create()->addActionProducts($products);

            // save current products ids
            // need for find new products in catalogCategorySaveAfter
            if ($products) {
                $this->productIdsInCategory = [];

                foreach ($products as $product) {
                    if ($product->getId()) {
                        $this->productIdsInCategory[] = $product->getId();
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Save category after
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogCategorySaveAfter(\Magento\Framework\Event $event)
    {
        $category = $event->getCategory();

        if ($category && $category->getId()) {
            // For category
            if (!$this->isExistsCategory) { // if category was created now
                $this->queueFactory->create()->addActionCategory($category);
            }

            // For products from category
            $products = $category->getProductCollection();

            if (!empty($products)) {
                if (empty($this->productIdsInCategory)) {
                    $this->queueFactory->create()->addActionProducts($products);
                } else {
                    $productIds = [];
                    foreach ($products as $product) {
                        $id = $product->getId();

                        if ((!empty($id)) && (!in_array($id, $this->productIdsInCategory))) {
                            $productIds[] = $id;
                        }
                    }

                    $this->queueFactory->create()->addActionProductIds($productIds);
                }
            }
        }

        $this->isExistsCategory = false;
        $this->productIdsInCategory = [];

        return $this;
    }

    /**
     * Move category after
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogCategoryMoveAfter(\Magento\Framework\Event $event)
    {
        $category = $event->getCategory();

        if ($category && $category->getId()) {
            $products = $category->getProductCollection();

            if ($products) {
                $this->queueFactory->create()->addActionProducts($products);
            }
        }

        return $this;
    }

    /**
     * Delete category before
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogCategoryDeleteBefore(\Magento\Framework\Event $event)
    {
        $category = $event->getCategory();

        if ($category && $category->getId()) {
            // For category
            $this->queueFactory->create()->addActionCategory(
                $category,
                \Searchanise\SearchAutocomplete\Model\Queue::ACT_DELETE_CATEGORIES
            );

            // For products from category
            $products = $category->getProductCollection();
            // ToCheck:
            // $this->queueFactory->create()->addActionProducts($products);
        }

        return $this;
    }

    /********************************
     * Store events
     ********************************/

    private function modelSaveBefore(\Magento\Framework\Event $event)
    {
        $object = $event->getData('object');

        //if ($object instanceof \Magento\Store\Model\ResourceModel\Store) {
        if ($object instanceof \Magento\Store\Model\Store) {
            $this->storeBeforeEdit($object);
        }

        return $this;
    }

    /**
     * After delete store
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function storeDelete(\Magento\Framework\Event $event)
    {
        $store = $event->getData('store');

        if ($store && $store->getId()) {
            $this->apiSeHelper->deleteKeys($store->getId());
        }

        return $this;
    }

    /**
     * Before save store
     *
     * @param  \Magento\Store\Model\Store $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function storeBeforeEdit(\Magento\Store\Model\Store $store)
    {
        $this->registry->register('store_save_before' . $store->getId(), $store);

        return $this;
    }

    /**
     * After save store
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function storeEdit(\Magento\Framework\Event $event)
    {
        $store = $event->getData('store');
        $response = $event->getData('response');

        if ($store && $store->getId()) {
            $isActive = $store->getIsActive();
            $isActiveOld = false;
            $this->apiSeHelper->setHttpResponse($response);

            $storeOld = $this->registry->registry('store_save_before' . $store->getId());

            if ($storeOld) {
                $isActiveOld = $storeOld->getIsActive();
                $this->registry->unregister('store_save_before' . $store->getId());
            }

            if ($isActiveOld != $isActive) {
                if ($this->apiSeHelper->signup($store, false, false) == true) {
                    if ($isActive) {
                        $this->apiSeHelper->sendAddonStatusRequest('enabled', $store);
                        $this->apiSeHelper->queueImport($store->getId(), false);
                        $this->notificationHelper->setNotification(
                            \Searchanise\SearchAutocomplete\Helper\Notification::TYPE_NOTICE,
                            __('Notice'),
                            __('Searchanise: New search engine for %1 created. Catalog import started', $store->getName())
                        );
                    } else {
                        $this->apiSeHelper->sendAddonStatusRequest('disabled', $store);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Add store
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function storeAdd(\Magento\Framework\Event $event)
    {
        $store = $event->getData('store');
        $response = $event->getData('response');

        if ($store && $store->getId()) {
            // Create new store. Set empty value to 'PrivateKey' and 'ApiKey'
            $this->apiSeHelper->setApiKey(null, $store->getId());
            $this->apiSeHelper->setPrivateKey(null, $store->getId());

            // Reset store config
            $store->resetConfig();

            $checkPrivateKey = $this->apiSeHelper->checkPrivateKey($store->getId());
            $this->apiSeHelper->setHttpResponse($response);

            if ($this->apiSeHelper->signup($store, false, false) == true) {
                if (!$checkPrivateKey) {
                    if ($store->getIsActive()) {
                        $this->apiSeHelper->queueImport($store->getId(), false);
                        $this->notificationHelper->setNotification(
                            \Searchanise\SearchAutocomplete\Helper\Notification::TYPE_NOTICE,
                            __('Notice'),
                            __('Searchanise: New search engine for %1 created. Catalog import started', $store->getName())
                        );
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Save config 'Advanced' section
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function adminSystemConfigChangedSectionAdvanced(\Magento\Framework\Event $event)
    {
        $groups = $this->request->getPost()->get('groups');
        $storesIds = $event->getData('store');
        $websiteIds = $event->getData('website');
        $response = $event->getData('response');

        if (empty($storesIds) && !empty($websiteIds)) {
            $storesIds = $this->apiSeHelper->getStoreByWebsiteIds($websiteIds);
        }

        $stores = $this->apiSeHelper->getStores($storesIds);
        $this->apiSeHelper->setHttpResponse($response);

        if (!empty($stores) && !empty($groups)) {
            foreach ($groups as $group => $groupData) {
                if (isset($groupData['fields']['Searchanise_SearchAutocomplete']['value'])) {
                    $status = ($groupData['fields']['Searchanise_SearchAutocomplete']['value']) ? 'D' : 'Y';

                    foreach ($stores as $k => $store) {
                        if (!$store->getIsActive() || $this->apiSeHelper->getStatusModule($store) == $status) {
                            continue;
                        } elseif (!$this->apiSeHelper->signup($store, false, false)) {
                            continue;
                        } elseif ($status != 'Y') {
                            $this->apiSeHelper->sendAddonStatusRequest('disabled', $store);
                            continue;
                        }

                        $this->apiSeHelper->sendAddonStatusRequest('enabled', $store);
                        $this->apiSeHelper->queueImport($store, false);
                        $this->notificationHelper->setNotification(
                            \Searchanise\SearchAutocomplete\Helper\Notification::TYPE_NOTICE,
                            __('Notice'),
                            str_replace(
                                '[language]',
                                $store->getName(),
                                __('Searchanise: New search engine for [language] created. Catalog import started')
                            )
                        );
                    }
                }
            }
        }

        return $this;
    }

    /********************************
     * EAV
     ********************************/

    /**
     * Before save attribute
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogEntityAttributeSaveBefore(\Magento\Framework\Event $event)
    {
        $attribute = $event->getAttribute();

        if ($attribute && $attribute->getId()) {
            $isFacet = $this->apiProducts->isFacet($attribute);

            $isFacetPrev = null;

            $prevAttribute = $this->eavEntityFactory->create()
                ->load($attribute->getId());

            if ($prevAttribute) {
                $isFacetPrev = $this->apiProducts->isFacet($prevAttribute);
            }

            if ($isFacet != $isFacetPrev) {
                if (!$isFacet) {
                    $this->queueFactory->create()->addAction(
                        \Searchanise\SearchAutocomplete\Model\Queue::ACT_DELETE_FACETS,
                        $attribute->getId()
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Save attribute
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogEntityAttributeSaveAfter(\Magento\Framework\Event $event)
    {
        $attribute = $event->getAttribute();

        if ($attribute && $attribute->getId()) {
            $this->queueFactory->create()->addAction(
                \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_ATTRIBUTES,
                $attribute->getId()
            );
        }

        return $this;
    }

    /**
     * Delete attribute
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function catalogEntityAttributeDeleteAfter(\Magento\Framework\Event $event)
    {
        $attribute = $event->getAttribute();

        if ($attribute && $attribute->getId()) {
            if ($this->apiProducts->isFacet($attribute)) {
                $this->queueFactory->create()->addAction(
                    \Searchanise\SearchAutocomplete\Model\Queue::ACT_DELETE_FACETS,
                    $attribute->getId()
                );
            }
        }

        return $this;
    }

    /********************************
     * Pages events
     ********************************/

    /**
     * Delete page before
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function cmsPageDeleteBefore(\Magento\Framework\Event $event)
    {
        $page = $event->getObject();

        if ($page && $page->getId()) {
            $this->queueFactory->create()->addActionPage(
                $page,
                \Searchanise\SearchAutocomplete\Model\Queue::ACT_DELETE_PAGES
            );
        }

        return $this;
    }

    /**
     * Save page after
     *
     * @param  \Magento\Framework\Event $event
     * @return \Searchanise\SearchAutocomplete\Model\Observer
     */
    private function cmsPageSaveAfter(\Magento\Framework\Event $event)
    {
        $page = $event->getObject();

        if ($page && $page->getId()) {
            $this->queueFactory->create()->addActionPage($page);
        }

        return $this;
    }

    /********************************
     * Orders event
     ********************************/

     /**
      * Place order action
      *
      * @param  \Magento\Framework\Event $event
      * @return \Searchanise\SearchAutocomplete\Model\Observer
      */
    private function checkoutOnepageControllerSuccessAction(\Magento\Framework\Event $event)
    {
        $orderIds = $event->getOrderIds();

        if (!empty($orderIds)) {
            $order = $this->orderFactory->create()->load($orderIds[0]);
            $orderItems = $order->getAllItems();

            foreach ($orderItems as $orderItem) {
                $product = $orderItem->getProduct();

                if ($product) {
                    $this->addProductToQueue($product);
                }
            }
        }

        return $this;
    }

    /**
     * Add product to queue and it's parents
     * 
     * @param $product \Magento\Catalog\Model\Product
     */
    private function addProductToQueue(\Magento\Catalog\Model\Product $product)
    {
        $this->queueFactory->create()->addActionUpdateProduct($product);

        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
            $parent_ids_arr = array_merge(
                $this->configurableProductTypeFactory->create()->getParentIdsByChild($product->getId()),
                $this->groupedProductProductTypeGroupedFactory->create()->getParentIdsByChild($product->getId()),
                $this->bundleProductTypeFactory->create()->getParentIdsByChild($product->getId())
            );

            if (!empty($parent_ids_arr)) { // If there is one or more parent products.
                $parent_ids_arr = array_unique($parent_ids_arr);

                foreach ($parent_ids_arr as $product_id) { // Update all detected parent products.
                    // TODO: Deprecated
                    $product = $this->catalogProductFactory->create()->load($product_id);

                    if (!empty($product)) {
                        $this->queueFactory->create()->addActionUpdateProduct($product);
                    }
                }
            }
        }

        return $this;
    }
}
