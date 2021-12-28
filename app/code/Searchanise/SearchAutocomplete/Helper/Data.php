<?php

namespace Searchanise\SearchAutocomplete\Helper;

use \Searchanise\SearchAutocomplete\Model\Configuration;
use \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as productAttributeCollectionFactory;

/**
 * Data helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const PARENT_PRIVATE_KEY = 'parent_private_key';

    const DISABLE_VAR_NAME = 'disabled_module_searchanise';
    const DISABLE_KEY      = 'Y';

    const DEBUG_VAR_NAME   = 'debug_module_searchanise';
    const DEBUG_KEY        = 'Y';

    const VISUAL_VAR_NAME  = 'visual';
    const VISUAL_KEY       = 'Y';

    const TEXT_FIND          = 'quick_search_container';
    const TEXT_ADVANCED_FIND = 'advanced_search_container';

    private $disableText;
    private $debugText;
    private $runEmulation = false;

    /**
     * @var array
     */
    private static $searchaniseTypes = [
        self::TEXT_FIND,
        self::TEXT_ADVANCED_FIND,
    ];

    /**
     * Field mapping list
     *
     * @var array
     */
    private $fieldNameMapping = [
        'name'              => 'title',
        'sku'               => 'product_code',
        'description'       => 'full_description',
        'short_description' => 'description',
    ];

    /**
     * @var \Searchanise\SearchAutocomplete\Model\Request
     */
    private $searchaniseRequest = null;

    /**
     * @var string
     */
    private $searchaniseCurentType = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Searchanise\SearchAutocomplete\Model\Configuration
     */
    private $configuration;

    /**
     * @var \Searchanise\SearchAutocomplete\Model\RequestFactory
     */
    private $requestFactory;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $appEmulation;

    /**
     * @var \Magento\CatalogSearch\Helper\Data
     */
    private $catalogSearchHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $catalogProductAttributeCollectionFactory;

    /**
     * @var \Magento\Theme\Block\Html\Pager
     */
    private $pager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Searchanise\SearchAutocomplete\Model\Configuration $configuration,
        \Searchanise\SearchAutocomplete\Model\RequestFactory $requestFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\CatalogSearch\Helper\Data $catalogSearchHelper,
        productAttributeCollectionFactory $catalogProductAttributeCollectionFactory,
        \Magento\Theme\Block\Html\Pager $pager
    ) {
        $this->storeManager = $storeManager;
        $this->configuration = $configuration;
        $this->requestFactory = $requestFactory;
        $this->appEmulation = $appEmulation;
        $this->catalogSearchHelper = $catalogSearchHelper;
        $this->catalogProductAttributeCollectionFactory = $catalogProductAttributeCollectionFactory;
        $this->pager = $pager;

        parent::__construct($context);
    }

    /**
     * Init request
     *
     * @return \Searchanise\SearchAutocomplete\Helper\Data
     */
    public function initSearchaniseRequest()
    {
        $this->searchaniseRequest = $this->requestFactory->create();

        return $this;
    }

    /**
     * Returns searchanise request
     *
     * @return \Searchanise\SearchAutocomplete\Model\Request
     */
    public function getSearchaniseRequest()
    {
        return $this->searchaniseRequest;
    }

    /**
     * Set current request
     *
     * @param \Searchanise\SearchAutocomplete\Model\Request $request
     */
    public function setSearchaniseRequest(\Searchanise\SearchAutocomplete\Model\Request $request)
    {
        $this->searchaniseRequest = $request;
    }

    /**
     * Set current type
     *
     * @param string $type
     */
    public function setSearchaniseCurentType($type = null)
    {
        $this->searchaniseCurentType = $type;
    }

    /**
     * Returns current type
     *
     * @return string
     */
    public function getSearchaniseCurentType()
    {
        return $this->searchaniseCurentType;
    }

    /**
     * Get disable text
     *
     * @return boolean
     */
    public function getDisableText()
    {
        if (!isset($this->disableText)) {
            $this->disableText = $this->_getRequest()->getParam(self::DISABLE_VAR_NAME);
        }

        return $this->disableText;
    }

    /**
     *  Checks if the text is disabled
     *
     * @return boolean
     */
    public function checkEnabled()
    {
        return ($this->getDisableText() != self::DISABLE_KEY);
    }

    /**
     * Get results from path
     *
     * @param  number $store_id Store identifier
     * @return string
     */
    public function getResultsFormPath($store_id = null)
    {
        $store = $this->storeManager->getStore($store_id);

        return $store->getUrl('', ['_secure' => $store->isCurrentlySecure()]) . 'searchanise/result';
    }

    /**
     * Check debug
     *
     * @param  boolean $checkPrivateKey
     * @return boolean
     */
    public function checkDebug($checkPrivateKey = false)
    {
        $checkDebug = ($this->getDebugText() == self::DEBUG_KEY) ? true : false;

        if ($checkDebug && $checkPrivateKey) {
            $checkDebug = $checkDebug && $this->checkPrivateKey();
        }

        return $checkDebug || $this->configuration->getIsDebugEnabled();
    }

    public function checkVisual()
    {
        $checkVisual = ($this->_getRequest()->getParam(self::VISUAL_VAR_NAME) == self::VISUAL_KEY) ? true : false;

        return $checkVisual;
    }

    /**
     * Get debug text
     *
     * @return mixed
     */
    public function getDebugText()
    {
        if (!isset($this->debugText)) {
            $this->debugText = $this->_getRequest()->getParam(self::DEBUG_VAR_NAME);
        }

        return $this->debugText;
    }

    /**
     * checks if the private key exists
     *
     * @return boolean
     */
    public function checkPrivateKey()
    {
        static $check;

        if (!isset($check)) {
            $parentPrivateKey = $this->_getRequest()->getParam(self::PARENT_PRIVATE_KEY);

            if ((empty($parentPrivateKey))
                || ($this->configuration->getValue(Configuration::XML_PATH_PARENT_PRIVATE_KEY) != $parentPrivateKey)
            ) {
                $check = false;
            } else {
                $check = true;
            }
        }

        return $check;
    }

    /**
     * Main execute funtion
     *
     * @param array $searchRequest
     * @return \Searchanise\SearchAutocomplete\Model\Request
     * @throws \Exception
     */
    public function search(array $searchRequest)
    {
        $searchParams = $this->buildSearchParamsFromRequest(
            $searchRequest['type'],
            $searchRequest['request']
        );

        if (empty($searchParams)) {
            return null;
        }

        $request = $this
            ->initSearchaniseRequest()
            ->getSearchaniseRequest()
            ->setStore($this->storeManager->getStore())
            ->setSearchaniseCurentType($searchRequest['type'])
            ->setSearchParams($searchParams)
            ->sendSearchRequest();

        $this->setSearchaniseRequest($request);
        $this->renderSuggestions();

        return $request;
    }

    /**
     * Construct Searchanise search params from request data
     *
     * @param string $requestType
     * @param array $request
     * @return array
     */
    private function buildSearchParamsFromRequest($requestType, array $request)
    {
        $searchRequest = [];

        $request = array_merge([
            'query' => '',
            'filters' => [],
            'queryFilters' => [],
            'orders' => [],
            'pageSize' => 9,
            'curPage' => 1,
        ], $request);

        if (!in_array($requestType, self::$searchaniseTypes)) {
            $requestType = self::TEXT_FIND;
        }

        if ($requestType == self::TEXT_ADVANCED_FIND) {
            $searchRequest['facets']           = 'true';
            $searchRequest['suggestions']      = 'false';
            $searchRequest['query_correction'] = 'false';
        } else {
            $searchRequest['facets']           = 'true';
            $searchRequest['suggestions']      = 'true';
            $searchRequest['query_correction'] = 'false';
        }

        $searchRequest['restrictBy']['status'] = '1';
        $searchRequest['union']['price']['min'] = \Searchanise\SearchAutocomplete\Helper\ApiSe::getLabelForPricesUsergroup();

        if (!$this->configuration->getIsShowOutOfStockProducts()) {
            $searchRequest['restrictBy']['is_in_stock'] = '1';
        }

        if ($requestType == self::TEXT_FIND) {
            $searchRequest['q'] = strtolower(trim($request['query']));
        }

        // Query filters
        if (!empty($request['queryFilters'])) {
            // TODO: Adds query filters here
        }

        // Filters
        foreach ($request['filters'] as $filterName => $condition) {
            $filterName = $this->mapFieldName($filterName);

            if (is_array($condition)) {
                if (isset($condition['like'])) {
                    // Like condition
                    $searchRequest['queryBy'][$filterName] = $condition['like'];
                } elseif (isset($condition['from']) || isset($condition['to'])) {
                    // Range condition
                    $searchRequest['restrictBy'][$filterName] =
                        (isset($condition['from']) ? $condition['from'] : '')
                        . ','
                        . (isset($condition['to']) ? $condition['to'] : '');
                } elseif (isset($condition['in'])) {
                    $searchRequest['restrictBy'][$filterName] = implode('|', $condition['in']);
                } elseif (isset($condition['in_set'])) {
                    $searchRequest['restrictBy'][$filterName] = implode('|', $condition['in_set']);
                } else {
                    // OR condition
                    $searchRequest['restrictBy'][$filterName] = implode('|', $condition);
                }
            } else {
                $searchRequest['restrictBy'][$filterName] = $condition;
            }
        }

        // Orders
        if (!empty($request['orders'])) {
            foreach ($request['orders'] as $sortBy => $order) {
                $searchRequest['sortBy']    = $this->mapFieldName($sortBy);
                $searchRequest['sortOrder'] = $order;
                // Ignore other conditions if exist
                break;
            }
        } else {
            $searchRequest['sortBy'] = 'relevance';
        }

        // Pagination params.
        $size = $request['pageSize'];
        $from = $size * (max(1, $request['curPage']) - 1);

        $searchRequest['startIndex'] = $from;
        $searchRequest['maxResults'] = $size;

        if (
            $requestType == self::TEXT_FIND
            && empty($searchRequest['q'])
        ) {
            // Do not process search if query not set
            return [];
        }

        return $searchRequest;
    }

    /**
     * Render suggestions
     *
     * @return $this
     */
    private function renderSuggestions()
    {
        if ($this->searchaniseRequest) {
            $suggestions = $this->searchaniseRequest->getSuggestions();
            $totalProducts = $this->searchaniseRequest->getTotalProducts();
            $suggestionsMaxResults = \Searchanise\SearchAutocomplete\Helper\ApiSe::getSuggestionsMaxResults();

            if (!empty($suggestions) && $totalProducts == 0) {
                $message = __('Did you mean: ');
                $count_sug = 0;
                $link = [];
                $catalogSearchHelper = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\CatalogSearch\Helper\Data');
                $textFind = $catalogSearchHelper->getEscapedQueryText();

                foreach ($suggestions as $k => $sug) {
                    if (!empty($sug) && $sug != $textFind) {
                        $link[] = '<a href="' . $this->getUrlSuggestion($sug). '">' . $sug .'</a>';
                        $count_sug++;
                    }

                    if ($count_sug >= $suggestionsMaxResults) {
                        break;
                    }
                }

                if (!empty($link)) {
                    $catalogSearchHelper->addNoteMessage($message . implode(', ', $link) . '?');
                }
            }
        }

        return $this;
    }

    /**
     * Returns facet data from Searchanise response
     * 
     * @param string $field
     * @return array
     */
    public function getFacetedData($field)
    {
        if (!$this->searchaniseRequest) {
            return [];
        }

        $result = $facets = $buckets = [];
        $facets = $this->searchaniseRequest->getFacets();

        if (!empty($facets)) {
            foreach ($facets as $facet) {
                if ($facet['attribute'] == 'category_ids' && $field == 'category') {
                    $buckets = $facet['buckets'];
                } elseif ($facet['attribute'] == 'price' && $field == 'price') {
                    // Hack for price, since Searchanise returns price in 60,70 format but magento requires 60_70
                    $buckets = array_map(function($metrics) {
                        return [
                            'value' => implode('_', explode(',', $metrics['value'])),
                            'count' => $metrics['count']
                        ];
                    }, $facet['buckets']);
                } elseif ($facet['attribute'] == $field) {
                    $buckets = $facet['buckets'];
                }
            }
        }

        if ($buckets) {
            foreach ($buckets as $metrics) {
                $result[$metrics['value']] = [
                    'value' => $metrics['value'],
                    'count' => $metrics['count'],
                ];
            }
        }

        return $result;
    }

    /**
     * Returns suggestion link
     *
     * @param  string $suggestion
     * @return string
     */
    private function getUrlSuggestion($suggestion)
    {
        $query = [
            'q'                         => $suggestion,
            $this->pager->getPageVarName()    => null // exclude current page from urls
        ];

        return $this->storeManager->getStore()->getUrl(
            '*/*/*',
            [
                '_current'      => true,
                '_use_rewrite'  => true,
                '_query'        => $query
            ]
        );
    }

    /**
     * Convert standard field name to ES fieldname.
     * (eg. category_ids => category).
     *
     * @param string $fieldName Field name to be mapped.
     *
     * @return string
     */
    public function mapFieldName($fieldName)
    {
        if (isset($this->fieldNameMapping[$fieldName])) {
            $fieldName = $this->fieldNameMapping[$fieldName];
        }

        return $fieldName;
    }

    /**
     * Get product page limit
     * 
     * @return int
     */
    public function getLimit()
    {
        $maxPageSize = $this->configuration->getMaxPageSize();
        $limit = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('\Magento\Catalog\Block\Product\ProductList\Toolbar')
            ->getLimit();

        return (int) min($limit, $maxPageSize);
    }

    /**
     * Returns current page
     * 
     * @return int
     */
    public function getCurrentPage()
    {
        $currentPage = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('\Magento\Catalog\Block\Product\ProductList\Toolbar')
            ->getCurrentPage();

        return max(1, $currentPage);
    }

    /**
     * Run emulation for specific store view
     * 
     * @param \Magento\Store\Model\Store $store
     * @return $this
     */
    public function startEmulation(\Magento\Store\Model\Store $store = null)
    {
        if ($store) {
            $this->storeManager->setCurrentStore($store);
            $this->appEmulation->startEnvironmentEmulation($store->getId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
        } else {
            $this->storeManager->setCurrentStore(0);
            $this->appEmulation->startEnvironmentEmulation(0, \Magento\Framework\App\Area::AREA_FRONTEND, true);
        }

        $this->runEmulation = true;

        return $this;
    }

    /**
     * Stop current emulation
     */
    public function stopEmulation()
    {
        $this->appEmulation->stopEnvironmentEmulation();
        $this->runEmulation = false;

        return $this;
    }
}
