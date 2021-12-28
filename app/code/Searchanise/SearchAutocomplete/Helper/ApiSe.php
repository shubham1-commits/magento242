<?php

namespace Searchanise\SearchAutocomplete\Helper;

use Searchanise\SearchAutocomplete\Model\Configuration;

class ApiSe extends \Magento\Framework\App\Helper\AbstractHelper
{
    const COMPRESS_RATE = 5;
    const PLATFORM_NAME = 'magento2';
    const CONFIG_PREFIX = 'se_';

    // The "All" variant of the items per page menu is replaced with this value
    // if the "Allow All Products per Page" option is active.
    const MAX_PAGE_SIZE = 100;

    const SUGGESTIONS_MAX_RESULTS = 1;
    const FLOAT_PRECISION = 2; // for server float = decimal(12,2)
    const LABEL_FOR_PRICES_USERGROUP = 'se_price_';

    const EXPORT_STATUS_QUEUED     = 'queued';
    const EXPORT_STATUS_START      = 'start';
    const EXPORT_STATUS_PROCESSING = 'processing';
    const EXPORT_STATUS_SENT       = 'sent';
    const EXPORT_STATUS_DONE       = 'done';
    const EXPORT_STATUS_SYNC_ERROR = 'sync_error';
    const EXPORT_STATUS_NONE       = 'none';

    const STATUS_NORMAL = 'normal';
    const STATUS_DISABLED = 'disabled';

    const NOT_USE_HTTP_REQUEST     = 'not_use_http_request';
    const NOT_USE_HTTP_REQUEST_KEY = 'Y';

    const FL_SHOW_STATUS_ASYNC     = 'show_status';
    const FL_SHOW_STATUS_ASYNC_KEY = 'Y';

    /**
     * @var string
     */
    private $parentPrivateKeySe;

    /**
     * @var array
     */
    private $privateKeySe = [];

    /**
     * @var array
     */
    public static $exportStatusTypes = [
        self::EXPORT_STATUS_QUEUED,
        self::EXPORT_STATUS_START,
        self::EXPORT_STATUS_PROCESSING,
        self::EXPORT_STATUS_SENT,
        self::EXPORT_STATUS_DONE,
        self::EXPORT_STATUS_SYNC_ERROR,
        self::EXPORT_STATUS_NONE,
    ];

    /**
     * @var array
     */
    public $seStoreIds = [];

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $adminSession;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $backendUrl;

    /**
     * @var \Searchanise\SearchAutocomplete\Model\Configuration
     */
    private $configuration;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Searchanise\SearchAutocomplete\Model\Format
     */
    private $format;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Notification
     */
    private $notificationHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Logger
     */
    private $loggerHelper;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Searchanise\SearchAutocomplete\Model\QueueFactory
     */
    private $queueFactory;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\ApiProducts
     */
    private $apiProductsHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\ApiPages
     */
    private $apiPagesHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\ApiCategories
     */
    private $apiCategoriesHelper;

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    private $moduleResource;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\Response
     */
    private $httpResponse = null;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $httpRequest;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        \Magento\Backend\Model\Auth\SessionFactory $adminSessionFactory,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Searchanise\SearchAutocomplete\Model\Configuration $configuration,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Searchanise\SearchAutocomplete\Model\Format $format,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Searchanise\SearchAutocomplete\Helper\Data $dataHelper,
        \Searchanise\SearchAutocomplete\Helper\Notification $notificationHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Module\ResourceInterface $moduleResource,
        \Magento\Framework\App\State $appState,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Framework\App\Request\Http $httpRequest,
        \Searchanise\SearchAutocomplete\Model\QueueFactory $queueFactory,
        \Searchanise\SearchAutocomplete\Helper\Logger $loggerHelper,
        \Searchanise\SearchAutocomplete\Helper\ApiProducts $apiProducts,
        \Searchanise\SearchAutocomplete\Helper\ApiPages $apiPagesHelper,
        \Searchanise\SearchAutocomplete\Helper\ApiCategories $apiCategoriesHelper
    ) {
        $this->configuration = $configuration;
        $this->customerSession = $customerSessionFactory->create();
        $this->adminSession = $adminSessionFactory->create();
        $this->backendUrl = $backendUrl;
        $this->storeManager = $storeManager;
        $this->format = $format;
        $this->jsonHelper = $jsonHelper;
        $this->dataHelper = $dataHelper;
        $this->notificationHelper = $notificationHelper;
        $this->productMetadata = $productMetadata;
        $this->loggerHelper = $loggerHelper;
        $this->localeDate = $localeDate;
        $this->moduleResource = $moduleResource;
        $this->appState = $appState;
        $this->appEmulation = $appEmulation;
        $this->httpRequest = $httpRequest;
        $this->queueFactory = $queueFactory;
        $this->apiProductsHelper = $apiProducts;
        $this->apiPagesHelper = $apiPagesHelper;
        $this->apiCategoriesHelper = $apiCategoriesHelper;

        parent::__construct($context);
    }

    public function getSearchInputSelector()
    {
        return $this->configuration->getValue(Configuration::XML_PATH_SEARCH_INPUT_SELECTOR);
    }

    /**
     * Format date using current locale options
     *
     * @param  timestamp|int
     * @param  string        $format
     * @param  bool          $showTime
     * @return string
     */
    public function formatDate($timestamp = null, $format = \IntlDateFormatter::SHORT, $showTime = false)
    {
        if (empty($timestamp)) {
            return '';
        }

        return $this->localeDate->formatDate((new \DateTime)->setTimestamp($timestamp), $format, $showTime);
    }

    /**
     * Get module status for the store view
     *
     * @param  number $storeId    Store identifier
     * @param  string $moduleName Module Name
     * @return string
     */
    public function getStatusModule($storeId = null, $moduleName = 'Searchanise_SearchAutocomplete')
    {
        if (empty($moduleName)) {
            return 'D';
        }

        return $this->configuration->getValue('advanced/modules_disable_output/' . $moduleName, $storeId) ? 'D' : 'Y';
    }

    /**
     * Check module status for the store view
     *
     * @param  number $storeId    Store identifier
     * @param  string $moduleName Module Name
     * @return string
     */
    public function checkStatusModule($storeId = null, $moduleName = 'Searchanise_SearchAutocomplete')
    {
        return $this->getStatusModule($storeId, $moduleName) == 'Y';
    }

    public function getApiKey($storeId = null)
    {
        return $this->configuration->getValue(
            Configuration::XML_PATH_API_KEY,
            $this->storeManager->getStore($storeId)->getId()
        );
    }

    public function getApiKeys()
    {
        $key_ids = [];
        $stores = $this->getStores();

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $key_ids[$store->getId()] = $this->getApiKey($store->getId());
            }
        }

        return $key_ids;
    }

    /**
     * Delete all keys for the stores
     *
     * @param  array|number $storeIds       Store identifier
     * @param  boolean      $unsetStoreData Unset Store data
     * @return boolean
     */
    public function deleteKeys($storeIds = null, $unsetStoreData = false)
    {
        $stores = $this->getStores($storeIds);

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->sendAddonStatusRequest('deleted', $store);

                if ($unsetStoreData == true) {
                    $this->setApiKey(null, $store->getId());
                    $this->setPrivateKey(null, $store->getId());
                    $this->setExportStatus(null, $store->getId());
                }

                $this->queueFactory->create()->deleteKeys($store->getId());
            }
        }

        return true;
    }

    /**
     * checks if the private key exists
     *
     * @return boolean
     */
    public function checkPrivateKey($storeId = null)
    {
        $key = $this->getPrivateKey($storeId);

        return empty($key) ? false : true;
    }

    public function setApiKey($value, $storeId = null)
    {
        $this->configuration->setValue(Configuration::XML_PATH_API_KEY, $value, $storeId);
    }

    public function getServiceUrl($onlyHttp = true)
    {
        $ret = $this->configuration->getValue(Configuration::XML_PATH_SERVICE_URL);

        if (!$onlyHttp) {
            if ($this->storeManager->getStore()->isCurrentlySecure()) {
                $ret = str_replace('http://', 'https://', $ret);
            }
        }

        return $ret;
    }

    /**
     * Get 'not_use_http_request' param for URL
     *
     * @return string
     */
    public function getParamNotUseHttpRequest()
    {
        return self::NOT_USE_HTTP_REQUEST . '=' . self::NOT_USE_HTTP_REQUEST_KEY;
    }

    public function getReSyncLink()
    {
        return 'searchanise/searchanise/resync';
    }

    public function getOptionsLink()
    {
        return 'searchanise/searchanise/options';
    }

    public function getConnectLink()
    {
        return 'searchanise/searchanise/signup';
    }

    public function getSearchaniseLink()
    {
        return 'searchanise/searchanise/index';
    }

    public static function getModuleLink()
    {
        return 'searchanise/searchanise/index';
    }

    /**
     * Get async link
     *
     * @param  boolean $flNotUserHttpRequest
     * @return string
     */
    public function getAsyncLink($flNotUserHttpRequest = false)
    {
        $link = 'searchanise/async/';

        if ($flNotUserHttpRequest) {
            $link .= '?' . $this->getParamNotUseHttpRequest();
        }

        return $link;
    }

    /**
     * Form and get async url
     *
     * @return string
     */
    public function getAsyncUrl($flNotUserHttpRequest = false, $storeId = '', $flCheckSecure = true)
    {
        return $this->getUrl(
            $this->getAsyncLink(false),
            $flNotUserHttpRequest,
            $storeId,
            $flCheckSecure,
            [
            '_nosid' => true,
            ]
        );
    }

    public function getModuleUrl()
    {
        return $this->backendUrl->getUrl($this->getModuleLink());
    }

    /**
     * Build query from the array
     *
     * @param  string   $link                 Dispatch for URL
     * @param  boolean  $flNotUserHttpRequest
     * @param  NULL|int $store_id             Store identifier
     * @param  boolean  $flCheckSecure
     * @param  array    $params               Additional params
     * @return string
     */
    public function getUrl($link, $flNotUserHttpRequest = false, $storeId = '', $flCheckSecure = true, $params = [])
    {
        if ($storeId != '') {
            $prevStoreId = $this->storeManager->getStore()->getId();
            // need for generate correct url
            if ($prevStoreId != $storeId) {
                $this->storeManager->setCurrentStore($storeId);
            }
        }

        $defaultParams = [];

        $params = array_merge($defaultParams, $params);

        if ($flCheckSecure) {
            if ($this->storeManager->getStore()->isCurrentlySecure()) {
                $params['_secure'] = true;
            }
        }

        $params['store'] = $this->storeManager->getStore();
        $url = $this->_urlBuilder->getUrl($link, $params);

        if ($flNotUserHttpRequest) {
            $url .= strpos($asyncUrl, '?') === false ? '?' : '&';
            $url .= $this->getParamNotUseHttpRequest();
        }

        if ($storeId != '') {
            if ($prevStoreId != $storeId) {
                $this->storeManager->setCurrentStore($prevStoreId);
            }
        }

        return $url;
    }

    /**
     * Check 'AutoInstall' flag
     *
     * @return boolean
     */
    public function checkAutoInstall()
    {
        // ToDo: remove this wrapper (?)
        return $this->configuration->checkAutoInstall();
    }

    public function checkCronAsync()
    {
        return
            $this->configuration->getValue(Configuration::XML_PATH_CRON_ASYNC_ENABLED)
            && !$this->getIsIndexEnabled();
    }

    public function checkAjaxAsync()
    {
        return
            $this->configuration->getValue(Configuration::XML_PATH_AJAX_ASYNC_ENABLED)
            && !$this->getIsIndexEnabled();
    }

    public function checkObjectAsync()
    {
        return
            $this->configuration->getValue(Configuration::XML_PATH_OBJECT_ASYNC_ENABLED)
            && !$this->getIsIndexEnabled();
    }

    public function getIsIndexEnabled()
    {
        return $this->configuration->getIsIndexEnabled();
    }

    public function getMaxPageSize()
    {
        return $this->configuration->getMaxPageSize();
    }

    public function getAddonOptions($store = null)
    {
        $ret = [];

        $ret['service_ur']         = $this->getServiceUrl();
        $ret['parent_private_key'] = $this->getParentPrivateKey();
        $ret['private_key']        = $this->getPrivateKeys();
        $ret['api_key']            = $this->getApiKeys();
        $ret['export_status']      = $this->getExportStatuses();

        $ret['last_request'] = $this->formatDate(
            $this->configuration->getLastRequest(),
            \IntlDateFormatter::MEDIUM,
            true
        );
        $ret['last_resync']  = $this->formatDate(
            $this->configuration->getLastResync(),
            \IntlDateFormatter::MEDIUM,
            true
        );

        $ret['addon_status']  = $this->getStatusModule() == 'Y' ? 'enabled' : 'disabled';
        $ret['addon_version'] = $this->moduleResource->getDataVersion('Searchanise_SearchAutocomplete');

        $ret['core_edition'] = $this->productMetadata->getEdition();
        $ret['core_version'] = $this->getMagentoVersion();
        $ret['core_version_info'] = $this->getVersionInfo();

        return $ret;
    }

    /**
     * Update current module version
     *
     * @return boolean
     */
    public function updateInsalledModuleVersion()
    {
        $currentVersion = $this->moduleResource->getDataVersion('Searchanise_SearchAutocomplete');

        return $this->configuration->setInsalledModuleVersion($currentVersion);
    }

    /**
     * Check if module is updated
     *
     * @return boolean
     */
    public function checkModuleIsUpdated()
    {
        $currentVersion = $this->moduleResource->getDataVersion('Searchanise_SearchAutocomplete');

        return $this->configuration->getInsalledModuleVersion() != $currentVersion;
    }

    /**
     * Returns magento version
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $version = $this->productMetadata->getVersion();
    }

    public function getVersionInfo()
    {
        $versionInfo = [];
        $version = $this->getMagentoVersion();

        if (!empty($version)) {
            list($major, $minor, $revision) = explode('.', $version);

            $versionInfo = [
                'major'     => $major,
                'minor'     => $minor,
                'revision'  => $revision,
                'patch'     => '',
                'stability' => '',
                'number'    => ''
            ];
        }

        return $versionInfo;
    }

    public function getSearchWidgetsLink($onlyHttp = true)
    {
        return $this->getServiceUrl($onlyHttp) . '/widgets/v1.0/init.js';
    }

    public function getIsShowOutOfStockProducts()
    {
        return $this->configuration
            ->getIsShowOutOfStockProducts();
    }

    public function getIsUseSecureUrlsInFrontend($store)
    {
        return $this->configuration->getIsUseSecureUrlsInFrontend();
    }

    public function getPriceFormat($store_id = null)
    {
        $store = $this->storeManager->getStore($store_id);
        $locale_code = $this->configuration->getValue(
            \Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE,
            $store->getStoreId()
        );
        $currency_code= $store->getCurrentCurrencyCode();

        $price_format = $this->format->getPriceFormat($locale_code, $currency_code);
        $position_price = strpos($price_format['pattern'], '%s');
        $symbol = str_replace('%s', '', $price_format['pattern']);

        $se_rate = 1;
        $rate = $store->getCurrentCurrencyRate();

        if (!empty($rate)) {
            $seRate = 1 / $rate;
        }

        $price_format = [
            'rate'                => $se_rate, // It requires inverse value.
            'decimals'            => $price_format['precision'],
            'decimals_separator'  => $price_format['decimal_symbol'],
            'thousands_separator' => $price_format['group_symbol'],
            'symbol'              => $symbol,
            'after'               => $position_price == 0,
        ];

        return $price_format;
    }

    public function getCurLabelForPricesUsergroup()
    {
        $customer_group_id = $this->customerSession->getCustomerGroupId();

        if (!$customer_group_id) {
            $customer_group_id= 0;
        }

        return $this->getLabelForPricesUsergroup() . $customer_group_id;
    }

    public static function getLabelForPricesUsergroup()
    {
        return self::LABEL_FOR_PRICES_USERGROUP;
    }

    public static function getFloatPrecision()
    {
        return self::FLOAT_PRECISION;
    }

    public static function getSuggestionsMaxResults()
    {
        return self::SUGGESTIONS_MAX_RESULTS;
    }

    /**
     * Excape characters
     *
     * @param  string $str
     * @return string|mixed
     */
    public static function escapingCharacters($str)
    {
        $ret = '';

        if ($str != '') {
            $str = trim($str);

            if ($str != '') {
                $str = str_replace('|', '\|', $str);
                $str = str_replace(',', '\,', $str);

                $ret = $str;
            }
        }

        return $ret;
    }

    /**
     * Main signup function to get keys for stores
     *
     * @param  \Magento\Catalog\Model\Store $curStore         Current store
     * @param  boolean                      $showNotification Flag to show notifications
     * @param  boolean                      $flSendRequest    Flag to send the request
     * @return boolean
     * @TODO:  Check $curStore object class
     */
    public function signup($curStore = null, $showNotification = true, $flSendRequest = true)
    {
        static $isShowed = false;
        $email = '';
        $connected = false;
        ignore_user_abort(true);
        set_time_limit(0);

        if ($this->adminSession && $this->adminSession->hasUser()) {
            $email = $this->adminSession->getUser()->getEmail();
        }

        if (!empty($email)) {
            $stores = !empty($curStore) ? [$curStore->getId() => $curStore] : $this->getStores();
            $parentPrivateKey = $this->getParentPrivateKey();

            foreach ($stores as $store) {
                $privateKey = $this->getPrivateKey($store->getStoreId());

                if (!empty($privateKey)) {
                    if ($flSendRequest) {
                        if ($store->getIsActive()) {
                            $this->sendAddonStatusRequest('enabled', $store);
                        } else {
                            $this->sendAddonStatusRequest('disabled', $store);
                        }
                    }

                    continue;
                }

                if ($showNotification == true && empty($isShowed)) {
                    $this->echoConnectProgress('Connecting to Searchanise..', $this->httpResponse);
                    $isShowed = true;
                }

                $url = $this->getUrl(
                    '',
                    false,
                    $store->getId(),
                    true,
                    [
                    '_nosid' => true,
                    '_query' => '___store=' . $store->getCode(),
                    ]
                );

                if (!(strstr($url, 'http'))) {
                    $base_url = $this->storeManager->getStore()->getBaseUrl();
                    $url = str_replace('index.php/', $base_url, $url);
                }

                list($h, $response) = $this->httpRequest(
                    \Zend_Http_Client::POST,
                    $this->getServiceUrl() . '/api/signup/json',
                    [
                        'url'                => $url,
                        'email'              => $email,
                        'version'            => $this->configuration->getServerVersion(),
                        'platform'           => self::PLATFORM_NAME,
                        'parent_private_key' => $parentPrivateKey,
                    ],
                    [],
                    [],
                    $this->configuration->getRequestTimeout()
                );

                if ($showNotification == true) {
                    $this->echoConnectProgress('.', $this->httpResponse);
                }

                if (!empty($response) && $responseKeys = $this->parseResponse($response, true)) {
                    $apiKey = empty($responseKeys['keys']['api']) ? false : $responseKeys['keys']['api'];
                    $privateKey = empty($responseKeys['keys']['private']) ? false : $responseKeys['keys']['private'];

                    if (empty($apiKey) || empty($privateKey)) {
                        return false;
                    }

                    if (empty($parentPrivateKey)) {
                        $this->setParentPrivateKey($privateKey);
                        $parentPrivateKey = $privateKey;
                    }

                    $this->setApiKey($apiKey, $store->getId());
                    $this->setPrivateKey($privateKey, $store->getId());

                    $connected = true;
                } else {
                    if ($showNotification == true) {
                        $this->echoConnectProgress(' Error<br />', $this->httpResponse);
                    }

                    break;
                }

                $this->setExportStatus(self::EXPORT_STATUS_NONE, $store->getStoreId());
            }
        }

        if ($connected) {
            if ($this->checkAutoInstall()) {
                $this->configuration->setAutoInstall();
            }

            if ($showNotification) {
                $this->echoConnectProgress(' Done<br/>', $this->httpResponse);
                $this->notificationHelper->setNotification(
                    \Searchanise\SearchAutocomplete\Helper\Notification::TYPE_NOTICE,
                    __('Notice'),
                    __('Congratulations, you\'ve just connected to Searchanise')
                );
            }
        }

        return $connected;
    }

    /**
     * Set current output response
     *
     * @param \Magento\Framework\HTTP\PhpEnvironment\Response $response
     */
    public function setHttpResponse(\Magento\Framework\HTTP\PhpEnvironment\Response $response = null)
    {
        $this->httpResponse = $response;
        $this->loggerHelper->setResponseContext($response);

        return $this;
    }

    /**
     * Adds progress to response
     *
     * @param string                                          $text
     * @param \Magento\Framework\HTTP\PhpEnvironment\Response $response
     */
    public function echoConnectProgress($text, \Magento\Framework\HTTP\PhpEnvironment\Response $response = null)
    {
        if (!empty($response) && !empty($text)) {
            $response->appendBody($text);
        }
    }

    /**
     * Send addon status
     *
     * @param string                     $status   Addons status (enabled/disabled/deleted)
     * @param \Magento\Store\Model\Store $curStore Current store
     */
    public function sendAddonStatusRequest($status = 'enabled', \Magento\Store\Model\Store $curStore = null)
    {
        $stores = !empty($curStore) ? [$curStore] : $this->getStores();

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $privateKey = $this->getPrivateKey($store->getStoreId());
                $this->sendRequest('/api/state/update/json', $privateKey, ['addon_status' => $status], true);
            }
        }
    }

    public function sendRequest($urlPart, $privateKey, $data, $onlyHttp = true)
    {
        $result = false;

        if (!empty($privateKey)) {
            $params = ['private_key' => $privateKey] + $data;

            list($h, $body) = $this->httpRequest(
                \Zend_Http_Client::POST,
                $this->getServiceUrl($onlyHttp) . $urlPart,
                $params,
                [],
                [],
                $this->configuration->getRequestTimeout()
            );

            if ($body) {
                $result = $this->parseResponse($body, false);
            }

            $this->configuration->setLastRequest($this->getTime());
        }

        return $result;
    }

    /**
     * Send http request
     *
     * @param  string $method       Method name
     * @param  string $url          Host url
     * @param  array  $data         Url parameters
     * @param  array  $cookies      Cookies for send
     * @param  array  $basicAuth    Basic http authorization data
     * @param  number $timeout      Timeout value
     * @param  number $maxredirects Max redirects value
     * @return array
     */
    public function httpRequest(
        $method = \Zend_Http_Client::POST,
        $url = '',
        $data = [],
        $cookies = [],
        $basicAuth = [],
        $timeout = 1,
        $maxredirects = 5
    ) {
        $this->loggerHelper->log('===== Http Request =====', [
            'method'        => $method,
            'url'           => $url,
            'data'          => $data,
            'cookies'       => $cookies,
            'basicAuth'     => $basicAuth,
            'timeout'       => $timeout,
            'maxRedirects'  => $maxredirects,
        ], Logger::TYPE_DEBUG);

        $requestStartTime = microtime(true);
        $responseHeader = '';
        $responseBody = '';
        $client = new \Zend_Http_Client();
        $client->setUri($url);

        $client->setConfig([
            'httpversion'   => \Zend_Http_Client::HTTP_0,
            'maxredirects'  => $maxredirects,
            'timeout'       => $timeout,
        ]);

        if ($method == \Zend_Http_Client::GET) {
            $client->setParameterGet($data);
        } elseif ($method == \Zend_Http_Client::POST) {
            $client->setParameterPost($data);
        }

        $response = false;
        try {
            $response = $client->request($method);
            $responseBody = $response->getBody();
        } catch (\Exception $e) {
            $this->loggerHelper->log($e->getMessage());
            $this->loggerHelper->log(
                '===== Response Error =====',
                ['response' => $response],
                Logger::TYPE_DEBUG
            );
        }

        $requestEndTime = microtime(true);

        $this->loggerHelper->log(
            '===== Response Body =====',
            [
                'body' => $responseBody,
                'time' => sprintf('%0.2f', $requestEndTime - $requestStartTime),
            ],
            Logger::TYPE_DEBUG
        );

        return [$responseHeader, $responseBody];
    }

    /**
     * Parse response from service
     *
     * @param  string $jsonData json service response
     * @return mixed false if errors returned, true if response is ok, object if data was passed in the response
     */
    public function parseResponse($jsonData, $showNotification = false, $objectDecodeType = \Zend_Json::TYPE_ARRAY)
    {
        $result = false;
        $data = false;

        try {
            if (trim($jsonData) === 'CLOSED;' || trim($jsonData) === 'CLOSED') {
                $data = false;
            } else {
                $data = $this->jsonHelper->jsonDecode($jsonData, $objectDecodeType);
            }
        } catch (\Exception $e) {
            if ($objectDecodeType == \Zend_Json::TYPE_ARRAY) {
                return $this->parseResponse($jsonData, $showNotification, \Zend_Json::TYPE_OBJECT);
            }

            $this->loggerHelper->log(
                '===== ParseResponse : jsonDecode =====',
                $e->getMessage()
            );
            $data = false;
        }

        if (empty($data)) {
            $result = false;
        } elseif (is_array($data) && !empty($data['errors'])) {
            foreach ($data['errors'] as $e) {
                if ($showNotification == true) {
                    $this->notificationHelper->setNotification(
                        \Searchanise\SearchAutocomplete\Helper\Notification::TYPE_ERROR,
                        __('Error'),
                        __('Searchanise: [%1] %2', $e->getCode(), $e->getMessage())
                    );
                }
            }

            $result = false;
        } elseif ($data === 'ok') {
            $result = true;
        } else {
            $result = $data;
        }

        return $result;
    }

    public function getPrivateKey($store_id = null)
    {
        return $this->configuration->getValue(
            Configuration::XML_PATH_PRIVATE_KEY,
            $this->storeManager->getStore($store_id)->getId()
        );
    }

    public function getPrivateKeys()
    {
        $key_ids = [];
        $stores = $this->getStores();

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $key_ids[$store->getId()] = $this->getPrivateKey($store->getId());
            }
        }

        return $key_ids;
    }

    public function setPrivateKey($value = null, $storeId = null)
    {
        $store = $this->storeManager->getStore($storeId);

        if (!empty($store)) {
            $this->privateKeySe[$store->getId()] = $value;
            $this->configuration->setValue(Configuration::XML_PATH_PRIVATE_KEY, $value, $store->getId());
        }
    }

    public function getParentPrivateKey()
    {
        if (!isset($this->parentPrivateKeySe)) {
            $this->parentPrivateKeySe = $this->configuration->getValue(Configuration::XML_PATH_PARENT_PRIVATE_KEY);
        }

        return $this->parentPrivateKeySe;
    }

    public function checkParentPrivateKey()
    {
        $parentPrivateKey = $this->getParentPrivateKey();

        return !empty($parentPrivateKey);
    }

    public function setParentPrivateKey($value = null)
    {
        $this->parentPrivateKeySe = $value;
        $this->configuration->setValue(Configuration::XML_PATH_PARENT_PRIVATE_KEY, $value);
    }

    public function getDate($format = 'Y-m-d H:i:s')
    {
        return date($format);
    }

    public function getTime()
    {
        return time();
    }

    /**
     * Get export statuses
     *
     * @param  \Magento\Store\Model\Store $store
     * @return array
     */
    public function getExportStatuses($store = null)
    {
        $statuses = [];
        $stores = $this->getStores($store);

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $statuses[$store->getId()] = $this->getExportStatus($store->getId());
            }
        }

        return $statuses;
    }

    public function setExportStatus($value, $storeId = null)
    {
        $this->configuration->setValue(Configuration::XML_PATH_EXPORT_STATUS, $value, $storeId);
    }

    public function getExportStatus($storeId = null)
    {
        return $this->configuration->getValue(Configuration::XML_PATH_EXPORT_STATUS, $storeId);
    }

    public function checkExportStatus($storeId = null)
    {
        return $this->getExportStatus($storeId) == self::EXPORT_STATUS_DONE;
    }

    public function getSyncMode()
    {
        return $this->configuration->getValue(Configuration::XML_PATH_SYNC_MODE);
    }

    public function queueImport($curStoreId = null, $showNotification = true)
    {
        if (!$this->checkParentPrivateKey()) {
            return false;
        }

        $this->configuration->setNotificationAsyncCompleted(false);

        // Delete all exist queue, need if exists error
        $this->queueFactory->create()->clearActions($curStoreId);

        $this->queueFactory->create()->addAction(
            \Searchanise\SearchAutocomplete\Model\Queue::ACT_PREPARE_FULL_IMPORT,
            null,
            $curStoreId
        );

        $stores = $this->getStores($curStoreId);

        foreach ($stores as $store) {
            $this->setExportStatus(self::EXPORT_STATUS_QUEUED, $store->getId());
        }

        if ($showNotification == true) {
            $this->notificationHelper->setNotification(
                \Searchanise\SearchAutocomplete\Helper\Notification::TYPE_NOTICE,
                __('Notice'),
                __('The product catalog is queued for syncing with Searchanise')
            );
        }

        return true;
    }

    /**
     * Show notification message
     *
     * @return boolean
     */
    public function showNotificationAsyncCompleted()
    {
        if (!$this->configuration->checkNotificationAsyncCompleted()) {
            $all_stores_done = true;
            $stores = $this->getStores();

            foreach ($stores as $store) {
                if (!$this->checkExportStatus($store->getId())) {
                    $all_stores_done = false;
                    break;
                }
            }

            if ($all_stores_done) {
                $textNotification = __(
                    'Catalog indexation is complete. Configure Searchanise via the <a href="%1">Admin Panel</a>.',
                    $this->getModuleUrl()
                );
                $this->notificationHelper->setNotification(
                    \Searchanise\SearchAutocomplete\Helper\Notification::TYPE_NOTICE,
                    __('Searchanise'),
                    $textNotification
                );
                $this->configuration->setNotificationAsyncCompleted(true);
            }
        }

        return true;
    }

    /**
     * Check if the area is backend
     *
     * @return boolean
     */
    public function getIsAdmin()
    {
        return $this->appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
    }

    public function getStoreUrl($storeId)
    {
        static $storeUrl = [];

        if (!isset($storeUrl[$storeId])) {
            $url = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Magento\Framework\Url');
            $url->setData('store', $storeId);
            $storeUrls[$storeId] = $url;
        }

        return $storeUrls[$storeId];
    }

    public function getStores($storeIds = null)
    {
        if (empty($storeIds)) {
            $stores = $this->storeManager->getStores();
        } else {
            foreach ((array)$storeIds as $storeId) {
                $store = $this->storeManager->getStore($storeId);

                if (!empty($store)) {
                    $stores[$store->getId()] = $store;
                }
            }
        }

        if (!empty($this->seStoreIds)) {
            foreach ($stores as $storeId => $store) {
                if (!in_array($storeId, $this->seStoreIds)) {
                    unset($stores[$storeId]);
                }
            }
        }

        return $stores;
    }

    public function getStoreByWebsiteIds($websiteIds = [])
    {
        $ret = [];

        if (!empty($websiteIds)) {
            if (!is_array($websiteIds)) {
                $websiteIds = [
                    0 => $websiteIds
                ];
            }

            $stores = $this->getStores();

            if (!empty($stores)) {
                foreach ($stores as $k => $store) {
                    $websiteId = $store->getWebsite()->getId();

                    if (in_array($websiteId, $websiteIds)) {
                        $ret[] = $store->getId();
                    }
                }
            }
        }

        return $ret;
    }

    public function getStoreByWebsiteCodes($websiteCodes = [])
    {
        // ToCheck: deprecated
        $ret = [];

        if (!empty($websiteCodes)) {
            if (!is_array($websiteCodes)) {
                $websiteCodes = [
                    0 => $websiteCodes
                ];
            }

            $stores = $this->getStores();

            if (!empty($stores)) {
                foreach ($stores as $k => $store) {
                    $websiteCode = $store->getWebsite()->getCode();

                    if (in_array($websiteCode, $websiteCodes)) {
                        $ret[] = $store->getId();
                    }
                }
            }
        }

        return $ret;
    }

    public function getIsSearchaniseSearchEnabled($store = null)
    {
        if (empty($store)) {
            $store = $this->storeManager->getStore();
        }

        // Check if store is not in excluded store ids
        if (!empty($this->seStoreIds) && !in_array($store->getId(), $this->seStoreIds)) {
            return false;
        }

        // Check if page is allowed for search
        if (
            !in_array($this->httpRequest->getFullActionName(), [
                'catalogsearch_result_index',    // Search result page
                'catalogsearch_advanced_result', // Advanced search result page
            ])
        ) {
            return false;
        }

        return $this->configuration->getIsSearchaniseSearchEnabled();
    }

    public function checkSearchaniseResult($searchaniseRequest = null, $store = null)
    {
        if (empty($store)) {
            $store = $this->storeManager->getStore();
        }

        if (!empty($this->seStoreIds) && !in_array($store->getId(), $this->seStoreIds)) {
            return false;
        }

        $exportStatus = $this->getExportStatus($store->getId());

        if (
            $this->checkStatusModule($store->getId()) == 'Y'
            && in_array($exportStatus, [self::EXPORT_STATUS_DONE, self::EXPORT_STATUS_QUEUED])
            && !empty($searchaniseRequest)
        ) {
            if ($searchaniseRequest === true) {
                return true;
            }

            // TODO: Add check here
            return true;
        }

        return false;
    }

    public function async($flIgnoreProcessing = false)
    {
        $this->loggerHelper->log("===== Async: Started =====", Logger::TYPE_DEBUG);

        ignore_user_abort(true);
        set_time_limit(0);

        $asyncMemoryLimit = $this->configuration->getAsyncMemoryLimit();

        if (substr(ini_get('memory_limit'), 0, -1) < $asyncMemoryLimit) {
            ini_set('memory_limit', $asyncMemoryLimit . 'M');
        }

        $isProfilerEnabled = \Magento\Framework\Profiler::isEnabled();

        // Disable profile during processing to prevent memory leak
        if ($isProfilerEnabled) {
            \Magento\Framework\Profiler::disable();
        }

        $this->storeManager->setCurrentStore('admin'); // (!)
        $this->echoConnectProgress('.', $this->httpResponse);

        $q = $this->queueFactory->create()->getNextQueue();

        while (!empty($q)) {
            $queryStartTime = microtime(true);
            $dataForSend = [];
            $status = true;

            $this->loggerHelper->log('===== Async: Processing query =====', $q, Logger::TYPE_DEBUG);

            try {
                $store = $this->storeManager->getStore($q['store_id']);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Store not found, skip queue
                $this->queueFactory->create()->load($q['queue_id'])->delete();
                $q = [];

                $this->loggerHelper->log("===== Async: Store id {$q['store_id']} not exists. Query processing skipped =====", Logger::TYPE_DEBUG);

                continue;
            }

            $header = $this->apiProductsHelper->getHeader($store);
            $data = $q['data'];

            if (!empty($data) && $data !== \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA) {
                $data = $this->unserialize($data);
            }

            $privateKey = $this->getPrivateKey($store->getId());

            if (empty($privateKey)) {
                $this->queueFactory->create()->load($q['queue_id'])->delete();
                $q = [];

                $this->loggerHelper->log(
                    "===== Async: Private key not exits for store {$store->getId()}. Processing skipped. ====",
                    Logger::TYPE_DEBUG
                );

                continue;
            }

            try {
                //Note: $q['started'] can be in future.
                if ($q['status'] == \Searchanise\SearchAutocomplete\Model\Queue::STATUS_PROCESSING
                    && ($q['started'] + $this->configuration->getMaxProcessingTime() > $this->getTime())
                ) {
                    if (!$flIgnoreProcessing) {
                        // Restore profiler original status
                        if ($isProfilerEnabled) {
                            \Magento\Framework\Profiler::enable();
                        }

                        return \Searchanise\SearchAutocomplete\Model\Queue::STATUS_PROCESSING;
                    }
                }

                if ($q['error_count'] >= $this->configuration->getMaxErrorCount()) {
                    $this->setExportStatus(self::EXPORT_STATUS_SYNC_ERROR, $store->getId());

                    // Restore profiler original status
                    if ($isProfilerEnabled) {
                        \Magento\Framework\Profiler::enable();
                    }

                    return \Searchanise\SearchAutocomplete\Model\Queue::STATUS_DISABLED;
                }

                // Set queue to processing state
                $this->queueFactory
                    ->create()
                    ->load($q['queue_id'])
                    ->setData('status', \Searchanise\SearchAutocomplete\Model\Queue::STATUS_PROCESSING)
                    ->setData('started', $this->getTime())
                    ->save();

                if ($q['action'] == \Searchanise\SearchAutocomplete\Model\Queue::ACT_PREPARE_FULL_IMPORT) {
                    $this->queueFactory
                        ->create()
                        ->getCollection()
                        ->addFieldToFilter('action', [
                            'neq' => \Searchanise\SearchAutocomplete\Model\Queue::ACT_PREPARE_FULL_IMPORT
                        ])
                        ->addFilter('store_id', $store->getId())
                        ->load()
                        ->delete();

                        $queueData = [
                            'data'     => \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA,
                            'action'   => \Searchanise\SearchAutocomplete\Model\Queue::ACT_START_FULL_IMPORT,
                            'store_id' => $store->getId(),
                        ];

                        $this->queueFactory
                            ->create()
                            ->setData($queueData)
                            ->save();

                        $queueData = [
                            'data'     => \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA,
                            'action'   => \Searchanise\SearchAutocomplete\Model\Queue::ACT_GET_INFO,
                            'store_id' => $store->getId(),
                        ];

                        $this->queueFactory
                            ->create()
                            ->setData($queueData)
                            ->save();

                        $queueData = [
                            'data'     => \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA,
                            'action'   => \Searchanise\SearchAutocomplete\Model\Queue::ACT_DELETE_FACETS_ALL,
                            'store_id' => $store->getId(),
                        ];

                        $this->queueFactory
                            ->create()
                            ->setData($queueData)
                            ->save();

                        $this->_addTaskByChunk(
                            $store,
                            \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PRODUCTS,
                            true
                        )->_addTaskByChunk(
                            $store,
                            \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_CATEGORIES,
                            true
                        )->_addTaskByChunk(
                            $store,
                            \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PAGES,
                            true
                        );

                        $this->echoConnectProgress('.', $this->httpResponse);

                        $queueData = [
                            'data'     => \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA,
                            'action'   => \Searchanise\SearchAutocomplete\Model\Queue::ACT_END_FULL_IMPORT,
                            'store_id' => $store->getId(),
                        ];

                        $this->queueFactory
                            ->create()
                            ->setData($queueData)
                            ->save();

                        $status = true;
                } elseif ($q['action'] == \Searchanise\SearchAutocomplete\Model\Queue::ACT_START_FULL_IMPORT) {
                    $status = $this->sendRequest(
                        '/api/state/update/json',
                        $privateKey,
                        [
                            'full_import' => self::EXPORT_STATUS_START
                        ],
                        true
                    );

                    if ($status == true) {
                        $this->setExportStatus(self::EXPORT_STATUS_PROCESSING, $store->getId());
                    }
                } elseif ($q['action'] == \Searchanise\SearchAutocomplete\Model\Queue::ACT_GET_INFO) {
                    $params = [];
                    $info = $this->sendRequest('/api/state/info/json', $privateKey, $params, true);

                    if (!empty($info['result_widget_enabled'])) {
                        $this->configuration->setResultsWidgetEnabled(
                            $info['result_widget_enabled'] == 'Y',
                            $store->getId()
                        );
                    }
                } elseif ($q['action'] == \Searchanise\SearchAutocomplete\Model\Queue::ACT_END_FULL_IMPORT) {
                    $status = $this->sendRequest(
                        '/api/state/update/json',
                        $privateKey,
                        [
                        'full_import' => self::EXPORT_STATUS_DONE
                        ],
                        true
                    );

                    if ($status == true) {
                        $this->setExportStatus(self::EXPORT_STATUS_DONE, $store->getId());
                        $this->configuration->setLastResync($this->getTime());
                    }
                } elseif (\Searchanise\SearchAutocomplete\Model\Queue::isDeleteAllAction($q['action'])) {
                    $type = \Searchanise\SearchAutocomplete\Model\Queue::getAPITypeByAction($q['action']);

                    if ($type) {
                        $status = $this->sendRequest("/api/{$type}/delete/json", $privateKey, ['all' => true], true);
                    }
                } elseif (\Searchanise\SearchAutocomplete\Model\Queue::isUpdateAction($q['action'])) {
                    $dataForSend = [];

                    if ($q['action'] == \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PRODUCTS) {
                        $items = $this->apiProductsHelper->generateProductsFeed($data, $store);

                        if (!empty($items)) {
                            $dataForSend = [
                                'header' => $header,
                                'schema' => $this->apiProductsHelper->getSchema($store),
                                'items'  => $items,
                            ];
                        }
                    } elseif ($q['action'] == \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_CATEGORIES) {
                        $categories = $this->apiCategoriesHelper->generateCategoriesFeed($data, $store);

                        if (!empty($categories)) {
                            $dataForSend = [
                                'header'     => $header,
                                'categories' => $categories,
                            ];
                        }
                    } elseif ($q['action'] == \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PAGES) {
                        $pages = $this->apiPagesHelper->generatePagesFeed($data, $store);

                        if (!empty($pages)) {
                            $dataForSend = [
                                'header' => $header,
                                'pages'  => $pages,
                            ];
                        }
                    } elseif ($q['action'] == \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_ATTRIBUTES) {
                        $dataForSend = [
                            'header' => $header,
                            'schema' => $this->apiProductsHelper->getSchema($store),
                        ];
                    }

                    if (!empty($dataForSend)) {
                        $dataForSend = $this->jsonHelper->jsonEncode($dataForSend);

                        if (function_exists('gzcompress')) {
                            $dataForSend = gzcompress($dataForSend, self::COMPRESS_RATE);
                        }

                        $status = $this->sendRequest('/api/items/update/json', $privateKey, ['data' => $dataForSend], true);
                    }
                } elseif (\Searchanise\SearchAutocomplete\Model\Queue::isDeleteAction($q['action'])) {
                    $type = \Searchanise\SearchAutocomplete\Model\Queue::getAPITypeByAction($q['action']);

                    if (!empty($type)) {
                        if ($q['action'] == \Searchanise\SearchAutocomplete\Model\Queue::ACT_DELETE_PRODUCTS) {
                            // Bulk products delete
                            $dataForSend = ['id' => (array)$data];

                            $status = $this->sendRequest("/api/{$type}/delete/json", $privateKey, $dataForSend, true);

                            $this->echoConnectProgress('.', $this->httpResponse);
                        } else {
                            // Single delete
                            foreach ($data as $itemId) {
                                $dataForSend = [];

                                if ($q['action'] == \Searchanise\SearchAutocomplete\Model\Queue::ACT_DELETE_FACETS) {
                                    $dataForSend['attribute'] = $itemId;
                                } else {
                                    $dataForSend['id'] = $itemId;
                                }

                                $status = $this->sendRequest("/api/{$type}/delete/json", $privateKey, $dataForSend, true);

                                $this->echoConnectProgress('.', $this->httpResponse);

                                if ($status == false) {
                                    break;
                                }
                            }
                        }
                    }
                } elseif ($q['action'] == \Searchanise\SearchAutocomplete\Model\Queue::ACT_PHRASE) {
                    if (!empty($data) && is_array($data)) {
                        foreach ($data as $phrase) {
                            $status = $this->sendRequest(
                                '/api/phrases/update/json',
                                $privateKey,
                                [
                                'phrase' => $phrase
                                ],
                                true
                            );

                            $this->echoConnectProgress('.', $this->httpResponse);

                            if ($status == false) {
                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->loggerHelper->log($e->getMessage(), Logger::TYPE_ERROR);
                $this->loggerHelper->log(
                    '===== Query processing error =====',
                    $e->getMessage(),
                    Logger::TYPE_DEBUG
                );
                $status = false;
            }

            // Change queue item status
            if ($status == true) {
                $this->queueFactory->create()->load($q['queue_id'])->delete();
                $q = $this->queueFactory->create()->getNextQueue();
            } else {
                $nextStartedTime = ($this->getTime() - $this->configuration->getMaxProcessingTime())
                    + $q['error_count'] * 60;

                $modelQueue = $this->queueFactory->create()->load($q['queue_id']);
                $modelQueue
                    ->setData('status', \Searchanise\SearchAutocomplete\Model\Queue::STATUS_PROCESSING)
                    ->setData('error_count', $modelQueue->getData('error_count') + 1)
                    ->setData('started', $nextStartedTime)
                    ->save();

                break; //try later
            }

            $queryEndTime = microtime(true);

            $this->loggerHelper->log(
                '===== Query was process with status =====',
                [
                    'status' => $status,
                    'time'   => sprintf('%0.2f', $queryEndTime - $queryStartTime),
                ],
                Logger::TYPE_DEBUG
            );
            $this->echoConnectProgress('.', $this->httpResponse);
        }

        // Restore profiler original status
        if ($isProfilerEnabled) {
            \Magento\Framework\Profiler::enable();
        }

        $this->loggerHelper->log("==== Async: Ended ====", Logger::TYPE_DEBUG);
        $this->loggerHelper->log('async was processed', Logger::TYPE_INFO);

        return 'OK';
    }

    /**
     * Check if there is a record in the queue
     *
     * @return boolean
     */
    public function checkStartAsync()
    {
        $ret = false;
        $q = $this->queueFactory->create()->getNextQueue();

        if (!empty($q)) {
            //Note: $q['started'] can be in future.
            if ($q['status'] == \Searchanise\SearchAutocomplete\Model\Queue::STATUS_PROCESSING
                && ($q['started'] + $this->configuration->getMaxProcessingTime() > $this->getTime())
            ) {
                $ret = false;
            } elseif ($q['error_count'] >= $this->configuration->getMaxErrorCount()) {
                if ($q['store_id']) {
                    $store = $this->storeManager->getStore($q['store_id']);
                } else {
                    $store = null;
                }

                $statuses = $this->getExportStatuses($store);

                if (!empty($statuses)) {
                    foreach ($statuses as $statusKey => $status) {
                        if ($status != self::EXPORT_STATUS_SYNC_ERROR) {
                            if ($store) {
                                $this->setExportStatus(self::EXPORT_STATUS_SYNC_ERROR, $store->getId());
                            } else {
                                $stores = $this->getStores();
                                foreach ($stores as $stKey => $_st) {
                                    $this->setExportStatus(self::EXPORT_STATUS_SYNC_ERROR, $_st->getId());
                                }
                                break;
                            }
                        }
                    }
                }

                $ret = false;
            } else {
                $ret = true;
            }
        }

        return $ret;
    }

    /**
     * Build query from the array
     *
     * @param  array  $array  data to build query from
     * @param  string $query  part of query to attach new data
     * @param  string $prefix prefix
     * @return string well-formed query
     */
    public function buildQuery(array $array, $query = '', $prefix = '')
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $query = $this->buildQuery($v, $query, rawurlencode(empty($prefix) ? "$k" : $prefix . "[$k]"));
            } else {
                $query .= (!empty($query) ? '&' : '')
                    . (empty($prefix) ? $k : $prefix . rawurlencode("[$k]")). '=' . rawurlencode($v);
            }
        }

        return $query;
    }

    /**
     * Adds chunk for further processing
     *
     * @param  \Magento\Store\Model\Store $store
     * @param  string                     $action
     * @param  string                     $isOnlyActive
     * @return boolean
     */
    private function _addTaskByChunk(
        \Magento\Store\Model\Store $store,
        $action = \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PRODUCTS,
        $isOnlyActive = false
    ) {
        $i = 0;
        $step = 50;
        $start = 0;
        $max = 0;

        if ($action == \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PRODUCTS) {
            $step = $this->configuration->getProductsPerPass() * 50;
            list($start, $max) = $this->apiProductsHelper->getMinMaxProductId($store);
        } elseif ($action == \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_CATEGORIES) {
            $step = $this->configuration->getCategoriesPerPass() * 50;
            list($start, $max) = $this->apiCategoriesHelper->getMinMaxCategoryId($store);
        } elseif ($action == \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PAGES) {
            $step = $this->configuration->getPagesPerPass() * 50;
            list($start, $max) = $this->apiPagesHelper->getMinMaxPageId($store);
        }

        do {
            $end = $start + $step;
            $chunkItemIds = null;

            if ($action == \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PRODUCTS) {
                $chunkItemIds = $this->apiProductsHelper->getProductIdsFromRange(
                    $start,
                    $end,
                    $step,
                    $store,
                    $isOnlyActive
                );
            } elseif ($action == \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_CATEGORIES) {
                $chunkItemIds = $this->apiCategoriesHelper->getCategoryIdsFromRange(
                    $start,
                    $end,
                    $step,
                    $store
                );
            } elseif ($action == \Searchanise\SearchAutocomplete\Model\Queue::ACT_UPDATE_PAGES) {
                $chunkItemIds = $this->apiPagesHelper->getPageIdsFromRange(
                    $start,
                    $end,
                    $step,
                    $store
                );
            }

            $start = $end + 1;

            if (empty($chunkItemIds)) {
                continue;
            }

            $chunkItemIds = array_chunk($chunkItemIds, $this->configuration->getProductsPerPass());

            foreach ($chunkItemIds as $itemIds) {
                $queueData = [
                    'data'     => $this->serialize($itemIds),
                    'action'   => $action,
                    'store_id' => $store->getId(),
                ];

                $this->queueFactory->create()->setData($queueData)->save();

                // It is necessary for save memory.
                unset($_result);
                unset($_data);
                unset($queueData);
            }
        } while ($end <= $max);

        $this->loggerHelper->log("===== Async: _addTaskByChunk =====", [
            'action'      => $action,
            'start'       => $start,
            'max'         => $max,
            'step'        => $step,
            'store_id'    => $store->getId(),
            'chunkItemIds' => count($chunkItemIds),
        ], Logger::TYPE_DEBUG);

        return $this;
    }

    /**
     * Serialize wrapper
     * 
     * @param mixed $data
     * @return string
     */
    public function serialize($data)
    {
        // Use default serializer
        if (class_exists('Magento\Framework\Serialize\SerializerInterface', false)) {
            try {
                return \Magento\Framework\App\ObjectManager::getInstance()
                    ->get('Magento\Framework\Serialize\SerializerInterface')
                    ->serialize($data);
            } catch (\Exception $e) {
                // Error occurs, perhaps class doesn't exist
            }
        }

        if (class_exists('Zend_Serializer', false)) {
            // Old version of Zend framwork
            return \Zend_Serializer::serialize($data);
        }

        // Use new zend framework
        return \Zend\Serializer\Serializer::serialize($data);
    }

    /**
     * Unserialize wrapper
     * 
     * @param string $data
     * @return mixed
     */
    public function unserialize($data)
    {
        // Use default serializer
        if (class_exists('Magento\Framework\Serialize\SerializerInterface', false)) {
            try {
                return \Magento\Framework\App\ObjectManager::getInstance()
                    ->get('Magento\Framework\Serialize\SerializerInterface')
                    ->unserialize($data);
            } catch (\Exception $e) {
                // Error occurs, perhaps class doesn't exist
            }
        }

        if (class_exists('Zend_Serializer', false)) {
            // Old version of Zend framwork
            return \Zend_Serializer::unserialize($data);
        }

        // Use new zend framework
        return \Zend\Serializer\Serializer::unserialize($data);
    }
}
