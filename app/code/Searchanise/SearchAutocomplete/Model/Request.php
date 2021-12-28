<?php

namespace Searchanise\SearchAutocomplete\Model;

use \Searchanise\SearchAutocomplete\Exception\RequestException;
use \Searchanise\SearchAutocomplete\Exception\ApiKeyException;

/**
 * Searchanise request model
 */
class Request extends \Magento\Framework\Model\AbstractModel
{
    const ERROR_EMPTY_API_KEY                   = 'EMPTY_API_KEY';
    const ERROR_INVALID_API_KEY                 = 'INVALID_API_KEY';
    const ERROR_TO_BIG_START_INDEX              = 'TO_BIG_START_INDEX';
    const ERROR_SEARCH_DATA_NOT_IMPORTED        = 'SEARCH_DATA_NOT_IMPORTED';
    const ERROR_FULL_IMPORT_PROCESSED           = 'FULL_IMPORT_PROCESSED';
    const ERROR_FACET_ERROR_TOO_MANY_ATTRIBUTES = 'FACET_ERROR_TOO_MANY_ATTRIBUTES';
    const ERROR_NEED_RESYNC_YOUR_CATALOG        = 'NEED_RESYNC_YOUR_CATALOG';
    const ERROR_FULL_FEED_DISABLED              = 'FULL_FEED_DISABLED';
    const ERROR_ENGINE_SUSPENDED                = 'ENGINE_SUSPENDED';

    const SEPARATOR_ITEMS = "'";

    /**
     * @var array
     */
    private $searchResult = [];

    /**
     * @var array
     */
    private $searchParams = [];

    /**
     * @var string
     */
    private $apiKey = '';

    /**
     * @var string
     */
    private $privateKey = '';

    /**
     * @var \Magento\Store\Model\Store
     */
    private $store = null;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\ApiSe
     */
    private $apiSeHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Data
     */
    private $searchaniseHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Logger
     */
    private $loggerHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Model\Configuration
     */
    private $configuration;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    private $error = '';

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Searchanise\SearchAutocomplete\Helper\ApiSe $apiSeHelper,
        \Searchanise\SearchAutocomplete\Helper\Data $searchaniseHelper,
        \Searchanise\SearchAutocomplete\Helper\Logger $loggerHelper,
        \Searchanise\SearchAutocomplete\Model\Configuration $configuration,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->apiSeHelper = $apiSeHelper;
        $this->searchaniseHelper = $searchaniseHelper;
        $this->jsonHelper = $jsonHelper;
        $this->loggerHelper = $loggerHelper;
        $this->configuration = $configuration;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function hasError()
    {
        return !empty($this->error);
    }

    /**
     * Set request store
     *
     * @param  \Magento\Store\Model\Store $value
     * @return \Searchanise\SearchAutocomplete\Model\Request
     */
    public function setStore(\Magento\Store\Model\Store $value)
    {
        $this->store = $value;

        return $this;
    }

    /**
     * Returns selected store
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Returns private key for current store
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->apiSeHelper->getPrivateKey($this->store ? $this->store->getId() : null);
    }

    /**
     * Returns api key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiSeHelper->getApiKey($this->store ? $this->store->getId() : null);
    }

    /**
     * Checks if api key exists
     *
     * @return boolean
     */
    public function checkApiKey()
    {
        $apiKey= $this->getApiKey();

        return !empty($apiKey);
    }

    /**
     * Checks if Searchanise result is valid
     *
     * @return boolean
     */
    public function checkSearchResult()
    {
        return !empty($this->searchResult);
    }

    /**
     * Set current Searchanise result
     *
     * @param  array $value
     * @return \Searchanise\SearchAutocomplete\Model\Request
     */
    public function setSearchResult($value = [])
    {
        $this->searchResult = $value;

        $this->setAttributesCount();

        return $this;
    }

    /**
     * Returns Searchanise result
     *
     * @return array
     */
    public function getSearchResult()
    {
        return $this->searchResult;
    }

    /**
     * Returns list of founded product ids
     *
     * @return array
     */
    public function getProductIds()
    {
        $res = $this->getSearchResult();

        return empty($res['items'])
            ? []
            : array_map(function($item) {
                return $item['product_id'];
            }, $res['items']);
    }

    /**
     * Returns result facets
     *
     * @return array
     */
    public function getFacets()
    {
        $res = $this->getSearchResult();

        return !empty($res['facets']) ? $res['facets'] : [];
    }

    /**
     * Returns total found products
     *
     * @return number
     */
    public function getTotalProducts()
    {
        $res = $this->getSearchResult();

        return empty($res['totalItems']) ? 0 : $res['totalItems'];
    }

    /**
     * Returns suggestion list
     *
     * @return array
     */
    public function getSuggestions()
    {
        $res = $this->getSearchResult();

        return empty($res['suggestions']) ? [] : $res['suggestions'];
    }

    /**
     * Set search parameters
     *
     * @param  array $params
     * @return \Searchanise\SearchAutocomplete\Model\Request
     */
    public function setSearchParams($params = [])
    {
        $this->searchParams = $params;

        return $this;
    }

    /**
     * Set search parameter
     *
     * @param  string $key
     * @param  mixed  $value
     * @return \Searchanise\SearchAutocomplete\Model\Request
     */
    public function setSearchParam($key, $value)
    {
        if (empty($this->searchParams)) {
            $this->searchParams = [];
        }

        $this->searchParams[$key] = $value;

        return $this;
    }

    public function mergeSearchParam($key, array $value)
    {
        if (empty($this->searchParams)) {
            $this->searchParams = [];
        }

        $this->searchParams[$key] = array_merge($value, $this->searchParams[$key]);
        return $this;
    }

    /**
     * Returns current search parameters
     *
     * @return array
     */
    public function getSearchParams()
    {
        return $this->searchParams;
    }

    /**
     * Build search string
     *
     * @return string
     */
    protected function getStrFromParams($params = [], $mainKey = null)
    {
        $ret = '';

        if (!empty($params)) {
            foreach ($params as $key => $param) {
                if (is_array($param)) {
                    $ret .= $this->getStrFromParams($param, $key);
                } else {
                    if (!$mainKey) {
                        $ret .= $key . '=' . $param . '&';
                    } else {
                        $ret .= $mainKey . '[' . $key . ']=' . $param . '&';
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Search string getter
     *
     * @return string
     */
    public function getSearchParamsStr()
    {
        return $this->getStrFromParams($this->getSearchParams());
    }

    /**
     * Merge search parameters
     *
     * @param  array $new_params Search parameters to merge
     * @return \Searchanise\SearchAutocomplete\Model\Request
     */
    public function mergeSearchParams($new_params = [])
    {
        return $this->setSearchParams(array_merge($new_params, $this->getSearchParams()));
    }

    /**
     * Unset search paramter
     *
     * @param  string $key Search parameter
     * @return \Searchanise\SearchAutocomplete\Model\Request
     */
    public function unsetSearchParams($key = '')
    {
        if (isset($this->searchParams[$key])) {
            unset($this->searchParams[$key]);
        }

        return $this;
    }

    /**
     * Checks if search paramter exists
     *
     * @param  string $key Search parameters
     * @return unknown|\Searchanise\SearchAutocomplete\Model\Request
     */
    public function checkSearchParams($key = '')
    {
        if (empty($this->searchParams[$key])) {
            return $this->unsetSearchParams($key);
        }

        return $this;
    }

    /**
     * Send search request to Searchanse
     *
     * @return \Searchanise\SearchAutocomplete\Model\Request
     */
    public function sendSearchRequest()
    {
        $this->error = '';
        $this->setSearchResult();

        if (!$this->checkApiKey()) {
            throw new ApiKeyException();
            return $this;
        }

        $default_params = [
            'items'  => 'true',
            'facets' => 'true',
            'output' => 'json',
        ];

        $this
            ->mergeSearchParams($default_params)
            ->checkSearchParams('restrictBy')
            ->checkSearchParams('union');

        $query = $this->apiSeHelper->buildQuery($this->getSearchParams());
        $this->setSearchParam('api_key', $this->getApiKey());

        if ($this->searchaniseHelper->checkDebug()) {
            $this->loggerHelper->printR(
                $this->apiSeHelper->getServiceUrl()
                . '/search?api_key=' . $this->getApiKey() . '&' . $this->getSearchParamsStr()
            );
            $this->loggerHelper->printR($this->getSearchParams());
        }

        if (strlen($query) > $this->configuration->getMaxSearchRequestLength()) {
            list($header, $received) = $this->apiSeHelper->httpRequest(
                \Zend_Http_Client::POST,
                $this->apiSeHelper->getServiceUrl() . '/search',
                $this->getSearchParams(),
                [],
                [],
                $this->configuration->getSearchTimeout()
            );
        } else {
            list($header, $received) = $this->apiSeHelper->httpRequest(
                \Zend_Http_Client::GET,
                $this->apiSeHelper->getServiceUrl(). '/search',
                $this->getSearchParams(),
                [],
                [],
                $this->configuration->getSearchTimeout()
            );
        }

        if (empty($received)) {
            throw new RequestException(__('Searchanise: Empty response was returned by server.'));
            return $this;
        }

        try {
            $result = $this->jsonHelper->jsonDecode($received);
        } catch (\Exception $e) {
            throw new RequestException(__('Searchanise: Decode response error occurs.') . ' ' . $e->getMessage());
            return $this;
        }

        if ($this->searchaniseHelper->checkDebug()) {
            $this->loggerHelper->printR($result);
        }

        if (isset($result['error'])) {
            switch ($result['error']) {
                case self::ERROR_EMPTY_API_KEY:
                case self::ERROR_TO_BIG_START_INDEX:
                case self::ERROR_SEARCH_DATA_NOT_IMPORTED:
                case self::ERROR_FULL_IMPORT_PROCESSED:
                case self::ERROR_FACET_ERROR_TOO_MANY_ATTRIBUTES:
                case self::ERROR_ENGINE_SUSPENDED:
                    // Nothing
                    break;

                case self::ERROR_INVALID_API_KEY:
                    if ($this->getStore()) {
                        $this->apiSeHelper->deleteKeys($this->getStore()->getId(), true);

                        if ($this->apiSeHelper->signup($this->getStore()->getId(), false)) {
                            $this->apiSeHelper->queueImport($this->getStore()->getId(), false);
                        }
                    }
                    break;

                case self::ERROR_NEED_RESYNC_YOUR_CATALOG:
                    $this->apiSeHelper->queueImport($this->getStore()->getId(), false);
                    break;

                case self::ERROR_FULL_FEED_DISABLED:
                    $this->configuration->setUseFullFeed(0);
                    break;
            }

            throw new RequestException($result['error']);
            return $this;
        }

        if (empty($result) || !is_array($result) || !isset($result['totalItems'])) {
            throw new RequestException(__('Searchanise: Invalid search result.'));
            return $this;
        }

        $this->setSearchResult($result);

        return $this;
    }
}
