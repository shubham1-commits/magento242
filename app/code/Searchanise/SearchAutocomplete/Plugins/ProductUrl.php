<?php

namespace Searchanise\SearchAutocomplete\Plugins;

/**
 * Fix product url generation class
 */
class ProductUrl
{
    const FRONTEND_URL = 'Magento\Framework\Url';
    const BACKEND_URL  = 'Magento\Backend\Model\Url';

    /**
     * @var \Magento\UrlRewrite\Model\UrlFinderInterface
     */
    private $urlFinder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        \Magento\UrlRewrite\Model\UrlFinderInterface $urlFinder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->urlFinder = $urlFinder;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
    }

    /**
     * Overrwrite getUrl() method to fix incorrect frontend urls
     * 
     * @param \Magento\Catalog\Model\Product\Url $instance
     * @param callable $proceed
     * @param \Magento\Catalog\Model\Product $product
     * @param array $params
     * 
     * @return string
     */
    public function aroundGetUrl(
        \Magento\Catalog\Model\Product\Url $instance,
        callable $proceed,
        \Magento\Catalog\Model\Product $product,
        $params = []
    ) {
        if (empty($params['_searchanise'])) {
            return $proceed($product, $params);
        }

        $routePath = '';
        $routeParams = $params;

        $storeId = $product->getStoreId();
        $categoryId = $this->getCategoryId($product, $params);
        $urlDataObject = $product->getData('url_data_object');

        if ($urlDataObject !== null) {
            $requestPath = $urlDataObject->getUrlRewrite();
            $routeParams['_scope'] = $urlDataObject->getStoreId();
        } else {
            $requestPath = $product->getRequestPath();

            if ($requestPath === '') {
                $filterData = [
                    \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::ENTITY_ID   => $product->getId(),
                    \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::ENTITY_TYPE => \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator::ENTITY_TYPE,
                    \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::STORE_ID    => $storeId,
                ];

                if ($categoryId) {
                    $filterData[\Magento\UrlRewrite\Service\V1\Data\UrlRewrite::METADATA]['category_id'] = $categoryId;
                }

                $rewrite = $this->urlFinder->findOneByData($filterData);

                if ($rewrite) {
                    $requestPath = $rewrite->getRequestPath();
                    $product->setRequestPath($requestPath);
                } else {
                    $product->setRequestPath(false);
                }
            }
        }

        if (isset($routeParams['_scope'])) {
            $storeId = $this->storeManager->getStore($routeParams['_scope'])->getId();
        }

        if ($storeId != $this->storeManager->getStore()->getId()) {
            $routeParams['_scope_to_url'] = true;
        }

        if ($requestPath) {
            $routeParams['_direct'] = $requestPath;
        } else {
            $routePath = 'catalog/product/view';
            $routeParams['id'] = $product->getId();
            $routeParams['s'] = $product->getUrlKey();

            if ($categoryId) {
                $routeParams['category'] = $categoryId;
            }
        }

        if (!isset($routeParams['_query'])) {
            $routeParams['_query'] = [];
        }

        return $this->getUrlInstance($storeId)->getUrl($routePath, $routeParams);
    }

    /**
     * Returns backend or frontend url model
     * 
     * @param int $storeId
     * @return mixed
     */
    private function getUrlInstance($storeId)
    {
        if (!$storeId) {
            return $this->objectManager->create(self::BACKEND_URL);
        } else {
            return $this->objectManager->create(self::FRONTEND_URL);
        }
    }

    /**
     * Returns category identifier or null
     * 
     * @param \Magento\Catalog\Model\Product $product Product data
     * @param array $params                           Url params
     * @return int|null
     */
    private function getCategoryId(\Magento\Catalog\Model\Product $product, $params = [])
    {
        $categoryId = null;

        if (
            !isset($params['_ignore_category']) 
            && $product->getCategoryId()
            && !$product->getData('do_not_use_category_id')
        ) {
            $categoryId = $product->getCategoryId();
        }

        return $categoryId;
    }
}
