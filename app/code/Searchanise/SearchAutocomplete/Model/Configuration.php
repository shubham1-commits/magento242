<?php

namespace Searchanise\SearchAutocomplete\Model;

/**
 * Configuration class
 */
class Configuration
{
    const SCOPE_DEFAULT = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
    const SCOPE_STORE_READ = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    const SCOPE_STORE_WRITE = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;

    const SYNC_MODE_REALTIME = 'realtime';
    const SYNC_MODE_PERIODIC = 'periodic';
    const SYNC_MODE_MANUAL   = 'manual';

    const ATTR_SHORT_DESCRIPTION = 'short_description';
    const ATTR_DESCRIPTION = 'description';

    const XML_PATH_API_KEY = 'searchanise/searchanise_general/api_key';
    const XML_PATH_SERVICE_URL = 'searchanise/searchanise_general/service_url';
    const XML_PATH_SEARCH_INPUT_SELECTOR = 'searchanise/searchanise_general/search_input_selector';
    const XML_PATH_AUTO_INSTALL_INSTALLED = 'searchanise/searchanise_general/auto_install_initiated';
    const XML_PATH_PRIVATE_KEY = 'searchanise/searchanise_general/private_key';
    const XML_PATH_PARENT_PRIVATE_KEY = 'searchanise/searchanise_general/parent_private_key';
    const XML_PATH_REQUEST_TIMEOUT = 'searchanise/searchanise_general/request_timeout';
    const XML_PATH_SERVER_VERSION = 'searchanise/searchanise_general/server_version';
    const XML_PATH_LAST_REQUEST = 'searchanise/searchanise_general/last_request';
    const XML_PATH_LAST_RESYNC = 'searchanise/searchanise_general/last_resync';
    const XML_PATH_EXPORT_STATUS = 'searchanise/searchanise_general/export_status';
    const XML_PATH_CRON_ASYNC_ENABLED = 'searchanise/searchanise_general/cron_async_enabled';
    const XML_PATH_AJAX_ASYNC_ENABLED = 'searchanise/searchanise_general/ajax_async_enabled';
    const XML_PATH_OBJECT_ASYNC_ENABLED = 'searchanise/searchanise_general/object_async_enabled';
    const XML_PATH_SYNC_MODE = 'searchanise/searchanise_general/sync_mode';
    const XML_PATH_ASYNC_MEMORY_LIMIT = 'searchanise/searchanise_general/async_memory_limit';
    const XML_PATH_MAX_PROCESSING_TIME = 'searchanise/searchanise_general/max_processing_time';
    const XML_PATH_MAX_ERROR_COUNT = 'searchanise/searchanise_general/max_error_count';
    const XML_PATH_MAX_SEARCH_REQUEST_LENGTH = 'searchanise/searchanise_general/max_search_request_length';
    const XML_PATH_SEARCH_TIMEOUT = 'searchanise/searchanise_general/search_timeout';
    const XML_PATH_PRODUCTS_PER_PASS = 'searchanise/searchanise_general/products_per_pass';
    const XML_PATH_CATEGORIES_PER_PASS = 'searchanise/searchanise_general/categories_per_pass';
    const XML_PATH_PAGES_PER_PASS = 'searchanise/searchanise_general/pages_per_pass';
    const XML_PATH_NOTIFICATION_ASYNC_COMPLETED = 'searchanise/searchanise_general/notification_async_completed';
    const XML_PATH_RESULTS_WIDGET_ENABLED = 'searchanise/searchanise_general/results_widget_enabled';
    const XML_PATH_USE_FULL_FEED = 'searchanise/searchanise_general/use_full_feed';
    const XML_PATH_INSTALLED_MODULE_VERSION = 'searchanise/searchanise_general/installed_module_version';
    const XML_PATH_USE_DIRECT_IMAGES_LINKS = 'searchanise/searchanise_general/use_direct_image_links';
    const XML_PATH_DESCRIPTION_ATTR = 'searchanise/searchanise_general/summary_attr';
    const XML_PATH_RENDER_PAGE_TEMPLATE = 'searchanise/searchanise_general/render_page_template';
    const XML_PATH_ENABLE_SEARCHANISE_SEARCH = 'searchanise/searchanise_general/enabled_searchanise_search';
    const XML_PATH_ENABLE_DEBUG = 'searchanise/searchanise_general/enable_debug';
    const XML_PATH_INDEX_ENABLED = 'searchanise/searchanise_general/index_enabled';
    const XML_PATH_CUSTOMER_USERGROUPS_ENABLED = 'searchanise/searchanise_general/enable_customer_usergroups';
    const XML_PATH_MAX_PAGE_SIZE = 'searchanise/searchanise_general/max_page_size';

    // @var \Magento\Framework\App\Config\ScopeConfigInterface
    private $scopeConfig;
    // @var \Magento\Framework\App\Config\Storage\WriterInterface
    private $writeInterface;
    // @var \Magento\Framework\App\CacheInterface
    private $cache;

    private static $configCache = [];

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $writeInteface,
        \Magento\Framework\App\CacheInterface $cache
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->writeInterface = $writeInteface;
        $this->cache = $cache;
    }

    /**
     * Returns settings value
     *
     * @param  string     $xml_path xml path to config value
     * @return NULL|mixed       config value or null
     * @param  NULL|mixed $store_id Store identifier
     */
    public function getValue($xml_path, $store_id = null)
    {
        $value = null;

        if (!empty($xml_path)) {
            if (empty($store_id)) {
                $value = isset(self::$configCache[0][$xml_path])
                    ? self::$configCache[0][$xml_path]
                    : $this->scopeConfig->getValue($xml_path, self::SCOPE_DEFAULT);
            } else {
                $value = isset(self::$configCache[$store_id][$xml_path])
                    ? self::$configCache[$store_id][$xml_path]
                    : $this->scopeConfig->getValue($xml_path, self::SCOPE_STORE_READ, $store_id);
            }
        }

        return $value;
    }

    /**
     * Set a new value for config
     *
     * @param string     $xml_path xml path to config value
     * @param mixed      $value    The new value
     * @param NULL|mixed $store_id Store identifier
     */
    public function setValue($xml_path, $value, $store_id = null)
    {
        if (empty($store_id)) {
            $this->writeInterface->save($xml_path, $value);
            self::$configCache[0][$xml_path] = $value;
        } else {
            $this->writeInterface->save($xml_path, $value, self::SCOPE_STORE_WRITE, $store_id);
            self::$configCache[$store_id][$xml_path] = $value;
        }

        $this->cache->clean([\Magento\Framework\App\Cache\Type\Config::CACHE_TAG]);
    }

    /**
     * @param boolean $value
     * @param number  $storeId
     */
    public function setResultsWidgetEnabled($value, $storeId = null)
    {
        $this->setValue(self::XML_PATH_RESULTS_WIDGET_ENABLED, $value ? 1 : 0, $storeId);

        return true;
    }

    /**
     * @param number $storeId
     */
    public function getResultsWidgetEnabled($storeId = null)
    {
        return $this->getValue(self::XML_PATH_RESULTS_WIDGET_ENABLED, $storeId);
    }

    public function setUseFullFeed($value = null)
    {
        $this->setValue(self::XML_PATH_USE_FULL_FEED, $value ? 1 : 0);

        return true;
    }

    public function getUseFullFeed()
    {
        return $this->getValue(self::XML_PATH_USE_FULL_FEED);
    }

    public function getMaxSearchRequestLength()
    {
        return $this->getValue(self::XML_PATH_MAX_SEARCH_REQUEST_LENGTH);
    }

    public function getSearchTimeout()
    {
        return $this->getValue(self::XML_PATH_SEARCH_TIMEOUT);
    }

    public function getProductsPerPass()
    {
        return $this->getValue(self::XML_PATH_PRODUCTS_PER_PASS);
    }

    public function getCategoriesPerPass()
    {
        return $this->getValue(self::XML_PATH_CATEGORIES_PER_PASS);
    }

    public function getPagesPerPass()
    {
        return $this->getValue(self::XML_PATH_PAGES_PER_PASS);
    }

    /**
     * Check if notification async comlpeted is enabled
     *
     * @return boolean
     */
    public function checkNotificationAsyncCompleted()
    {
        return $this->getValue(self::XML_PATH_NOTIFICATION_ASYNC_COMPLETED) == 1;
    }

    /**
     * Set notification async comlpeted
     *
     * @param  boolean $value
     * @return boolean
     */
    public function setNotificationAsyncCompleted($value = null)
    {
        $this->setValue(self::XML_PATH_NOTIFICATION_ASYNC_COMPLETED, $value ? 1 : 0);

        return true;
    }

    /**
     * Set last resync date
     *
     * @param  timestamp $value
     * @return bool
     */
    public function setLastResync($value = null)
    {
        $this->setValue(self::XML_PATH_LAST_RESYNC, $value);

        return true;
    }

    /**
     * Get last resync date
     *
     * @return timestamp
     */
    public function getLastResync()
    {
        return $this->getValue(self::XML_PATH_LAST_RESYNC);
    }

    /**
     * Set last request date
     *
     * @param  timestamp $value
     * @return bool
     */
    public function setLastRequest($value = null)
    {
        $this->setValue(self::XML_PATH_LAST_REQUEST, $value);

        return true;
    }

    /**
     * Get last request date
     *
     * @return timestamp
     */
    public function getLastRequest()
    {
        return $this->getValue(self::XML_PATH_LAST_REQUEST);
    }

    /**
     * Get current module version
     *
     * @return string
     */
    public function getInsalledModuleVersion()
    {
        return $this->getValue(self::XML_PATH_INSTALLED_MODULE_VERSION);
    }

    /**
     * Set current module version
     *
     * @param  string $value
     * @return boolean
     */
    public function setInsalledModuleVersion($value = null)
    {
        $this->setValue(self::XML_PATH_INSTALLED_MODULE_VERSION, $value);

        return true;
    }

    public function checkAutoInstall()
    {
        return $this->getValue(self::XML_PATH_AUTO_INSTALL_INSTALLED) != 1;
    }

    public function setAutoInstall($value = true)
    {
        $this->setValue(self::XML_PATH_AUTO_INSTALL_INSTALLED, $value ? 1 : 0);
    }

    public function getServerVersion()
    {
        return $this->getValue(self::XML_PATH_SERVER_VERSION);
    }

    public function getIsSearchaniseSearchEnabled()
    {
        return $this->getValue(self::XML_PATH_ENABLE_SEARCHANISE_SEARCH);
    }

    public function getMaxProcessingTime()
    {
        return $this->getValue(self::XML_PATH_MAX_PROCESSING_TIME);
    }

    public function getMaxErrorCount()
    {
        return $this->getValue(self::XML_PATH_MAX_ERROR_COUNT);
    }

    public function getIsRealtimeSyncMode()
    {
        return $this->getValue(self::XML_PATH_SYNC_MODE) == self::SYNC_MODE_REALTIME;
    }

    public function getIsPeriodicSyncMode()
    {
        return $this->getValue(self::XML_PATH_SYNC_MODE) == self::SYNC_MODE_PERIODIC;
    }

    public function getIsManualSyncMode()
    {
        return $this->getValue(self::XML_PATH_SYNC_MODE) == self::SYNC_MODE_MANUAL;
    }

    public function getIsUseDirectImagesLinks()
    {
        return $this->getValue(self::XML_PATH_USE_DIRECT_IMAGES_LINKS) == 1;
    }

    public function getRequestTimeout()
    {
        return $this->getValue(self::XML_PATH_REQUEST_TIMEOUT);
    }

    public function getAsyncMemoryLimit()
    {
        return  $this->getValue(self::XML_PATH_ASYNC_MEMORY_LIMIT);
    }

    public function getSummaryAttr()
    {
        $attr = $this->getValue(self::XML_PATH_DESCRIPTION_ATTR);
        return !empty($attr) ? $attr : self::ATTR_SHORT_DESCRIPTION;
    }

    public function getIsRenderPageTemplateEnabled()
    {
        return $this->getValue(self::XML_PATH_RENDER_PAGE_TEMPLATE) == 1;
    }

    public function getIsDebugEnabled()
    {
        return $this->getValue(self::XML_PATH_ENABLE_DEBUG) == 1;
    }

    public function getIsIndexEnabled()
    {
        return $this->getValue(self::XML_PATH_INDEX_ENABLED) == 1;
    }

    public function getIsCustomerUsergroupsEnabled()
    {
        return $this->getValue(self::XML_PATH_CUSTOMER_USERGROUPS_ENABLED) == 1;
    }

    public function getMaxPageSize()
    {
        return $this->getValue(self::XML_PATH_MAX_PAGE_SIZE);
    }

    public function getIsShowOutOfStockProducts()
    {
        return $this->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_SHOW_OUT_OF_STOCK);
    }

    public function getIsUseSecureUrlsInFrontend()
    {
        return $this->getValue('\Magento\Store\Model\Store::XML_PATH_SECURE_IN_FRONTEND');
    }
}
