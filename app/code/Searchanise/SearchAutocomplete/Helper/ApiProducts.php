<?php

namespace Searchanise\SearchAutocomplete\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use \Magento\Customer\Model\ResourceModel\Group\CollectionFactory as customerGroupCollectionFactory;
use \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
    as catalogProductAttributeCollectionFactory;
use \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as categoryCollectionFactory;
use \Magento\CatalogInventory\Api\StockRegistryInterface;
use \Magento\Catalog\Model\Product\Attribute\Source\Status as productStatus;
use \Magento\Catalog\Model\Product\Visibility as productVisibility;
use \Magento\Catalog\Model\Indexer\Product\Flat\State as productFlatState;
use \Magento\CatalogInventory\Api\Data\StockItemInterface;
use \Magento\Catalog\Model\Layer\Filter\DataProvider\Price as DataProviderPrice;

/**
 * Products helper for searchanise
 */
class ApiProducts extends \Magento\Framework\App\Helper\AbstractHelper
{
    const WEIGHT_SHORT_TITLE         = 100;
    const WEIGHT_SHORT_DESCRIPTION   = 40;
    const WEIGHT_DESCRIPTION         = 40;
    const WEIGHT_DESCRIPTION_GROUPED = 30;

    const WEIGHT_TAGS              = 60;
    const WEIGHT_CATEGORIES        = 60;

    // <if_isSearchable>
    const WEIGHT_META_TITLE        =  80;
    const WEIGHT_META_KEYWORDS     = 100;
    const WEIGHT_META_DESCRIPTION  =  40;

    const WEIGHT_SELECT_ATTRIBUTES    = 60;
    const WEIGHT_TEXT_ATTRIBUTES      = 60;
    const WEIGHT_TEXT_AREA_ATTRIBUTES = 40;
    // </if_isSearchable>

    const IMAGE_SIZE = 300;
    const THUMBNAIL_SIZE = 70;

    // Product types which as children
    public $hasChildrenTypes = [
        \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE,
        \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
    ];

    public $flWithoutTags = false;
    public $isGetProductsByItems = false;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $catalogResourceModelProductCollectionFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    private $customerGroupCollectionFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    private $catalogProductAttributeCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    private $taxHelper;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    private $catalogHelper;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var \Searchanise\SearchAutocomplete\Model\Configuration
     */
    private $configuration;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Logger
     */
    private $loggerHelper;

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Data
     */
    private $searchaniseHelper;

    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    private $catalogImageFactory;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Review\Model\ReviewFactory
     */
    private $reviewFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    private $productStatus;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    private $productVisibility;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $catalogResourceModelProductCollectionFactory,
        customerGroupCollectionFactory $customerGroupCollectionFactory,
        catalogProductAttributeCollectionFactory $catalogProductAttributeCollectionFactory,
        categoryCollectionFactory $categoryCollectionFactory,
        \Magento\Tax\Helper\Data $taxHelper,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Searchanise\SearchAutocomplete\Model\Configuration $configuration,
        \Searchanise\SearchAutocomplete\Helper\Logger $loggerHelper,
        \Searchanise\SearchAutocomplete\Helper\Data $searchaniseHelper,
        \Magento\Catalog\Helper\ImageFactory $catalogImageFactory,
        StockRegistryInterface $stockRegistry,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        productStatus $productStatus,
        productVisibility $productVisibility
    ) {
        $this->storeManager = $storeManager;
        $this->configuration = $configuration;
        $this->catalogResourceModelProductCollectionFactory = $catalogResourceModelProductCollectionFactory;
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->catalogProductAttributeCollectionFactory = $catalogProductAttributeCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->loggerHelper = $loggerHelper;
        $this->searchaniseHelper = $searchaniseHelper;
        $this->taxHelper = $taxHelper;
        $this->catalogHelper = $catalogHelper;
        $this->categoryFactory = $categoryFactory;
        $this->catalogImageFactory = $catalogImageFactory;
        $this->stockRegistry = $stockRegistry;
        $this->dateTime = $dateTime;
        $this->reviewFactory = $reviewFactory;
        $this->resourceConnection = $resourceConnection;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;

        parent::__construct($context);
    }

    public function getModuleManager()
    {
        if (property_exists($this, 'moduleManager')) {
            $moduleManager = $this->moduleManager;
        } else {
            $moduleManager = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('\Magento\Framework\Module\Manager');
        }

        return $moduleManager;
    }

    public function isVersionMoreThan($version)
    {
        $magentoVersion = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('\Searchanise\SearchAutocomplete\Helper\ApiSe')
            ->getMagentoVersion();

        return version_compare($magentoVersion, $version, '>=');
    }

    public function getProductCollection()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $version = $objectManager
            ->get('\Searchanise\SearchAutocomplete\Helper\ApiSe')
            ->getMagentoVersion();

        if ($this->isVersionMoreThan('2.2')) {
            static $collectionFactory = null;

            if (!$collectionFactory) {
                $collectionFactory = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
            }

            $collection = $collectionFactory
                ->create()
                ->clear();
        } else {
            static $catalogProductFactory = null;

            if (!$catalogProductFactory) {
                $catalogProductFactory = $objectManager->get('\Magento\Catalog\Model\ProductFactory');
            }

            $collection = $catalogProductFactory
                ->create()
                ->getCollection();
        }

        return $collection;
    }

    public function loadProductById($productId)
    {
        // TODO: Load() method is deprected here since 2.2.1. Should be replaced in future
        $product = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('\Magento\Catalog\Model\ProductFactory')
            ->create()
            ->load($productId);

        return $product;
    }

    /**
     * Returns isGetProductsByItems value
     *
     * @param string $value
     */
    public function setIsGetProductsByItems($value = false)
    {
        $this->isGetProductsByItems = $value;
    }

    /**
     * Returns required attributes list
     *
     * @return array
     */
    private function _getRequiredAttributes()
    {
        return [
            'name',
            'short_description',
            'sku',
            'status',
            'visibility',
            'price',
        ];
    }

    public function getAllAttributes()
    {
        $basicAttributes = [
            'name',
            'path',
            'categories',
            'categories_without_path',
            'description',
            'ordered_qty',
            'total_ordered',
            'stock_qty',
            'rating_summary',
            'media_gallery',
            'in_stock',
        ];
        $requiredAttributes = $this->_getRequiredAttributes();

        $additionalAttributes = [];
        $productAttributes = $this->getProductAttributes();
        if (!empty($productAttributes)) {
            foreach ($productAttributes as $attribute) {
                $additionalAttributes[] = $attribute->getAttribute();
            }
        }

        return array_unique(array_merge($requiredAttributes, $basicAttributes, $additionalAttributes));
    }

    /**
     * Generate product feed for searchanise api
     *
     * @param  array                      $productIds Product ids
     * @param  \Magento\Store\Model\Store $store      Store object
     * @param  string                     $checkData
     * @return array
     */
    public function generateProductsFeed(
        array $productIds = [],
        \Magento\Store\Model\Store $store = null,
        $checkData = true
    ) {
        $items = [];

        if ($this->configuration->getValue(productFlatState::INDEXER_ENABLED_XML_PATH)) {
            $this->setIsGetProductsByItems(true);//workaround for get all attributes
            // TODO: Should be used via flag or not used at all
            $this->catalogResourceModelProductCollectionFactory
                ->create()
                ->setStore($store->getId()); // workaround for magento flat products table bug
        }

        $products = $this->getProducts($productIds, $store, null);

        if (!empty($products)) {
            $this->searchaniseHelper->startEmulation($store);

            foreach ($products as $product) {
                if ($item = $this->generateProductFeed($product, $store, $checkData)) {
                    $items[] = $item;
                }
            }

            $this->searchaniseHelper->stopEmulation();
        }

        return $items;
    }

    /**
     * Get product minimal price without "Tier Price" (quantity discount) and with tax (if it is need)
     *
     * @param  \Magento\Catalog\Model\Product                          $product
     * @param  \Magento\Store\Model\Store                              $store
     * @param  \Magento\Catalog\Model\ResourceModel\Product\Collection $childrenProducts
     * @param  int                                                     $customerGroupId
     * @param  bool                                                    $applyTax
     * @return float
     */
    private function _getProductMinimalPrice(
        \Magento\Catalog\Model\Product $product,
        \Magento\Store\Model\Store $store,
        $childrenProducts = null,
        $customerGroupId = null,
        $applyTax = true
    ) {
        $minimalPrice = false;
        $tierPrice = $this->_getMinimalTierPrice($product, $customerGroupId);

        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            $product->setCustomerGroupId(0);
            $minimalPrice = $product->getPriceModel()->getTotalPrices($product, 'min', null, false);

            if ($tierPrice != null) {
                $minimalPrice = min($minimalPrice, $tierPrice);
            }
        } elseif (!empty($childrenProducts)
            && ($product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE
            || $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
        ) {
            $prices = [];
            foreach ($childrenProducts as $childrenProduct) {
                if ($childrenProduct->getStatus() != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                    continue;
                }

                $prices[] = $this->_getProductMinimalPrice(
                    $childrenProduct,
                    $store,
                    null,
                    $customerGroupId,
                    false
                );
            }

            if (!empty($prices)) {
                $minimalPrice = min($prices);
            }
        }

        if ($minimalPrice === false) {
            $minimalPrice = $product->getFinalPrice();

            if ($tierPrice !== null) {
                $minimalPrice = min($minimalPrice, $tierPrice);
            }
        }

        if ($minimalPrice && $applyTax) {
            $minimalPrice = $this->getProductShowPrice($product, $minimalPrice);
        }

        return $minimalPrice;
    }

    /**
     * Get product price with tax if it is need
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @param  float                          $price
     * @return float
     */
    public function getProductShowPrice(\Magento\Catalog\Model\Product $product, $price)
    {
        static $taxHelper;
        static $showPricesTax;

        if (!isset($taxHelper)) {
            $taxHelper = $this->taxHelper;
            $showPricesTax = ($taxHelper->displayPriceIncludingTax() || $taxHelper->displayBothPrices());
        }

        // TODO: Test taxes
        $finalPrice = $this->catalogHelper->getTaxPrice($product, $price, $showPricesTax);

        return $finalPrice;
    }

    /**
     * Generate product attributes
     *
     * @param array                          $item             Product data
     * @param \Magento\Catalog\Model\Product $product          Product model
     * @param array                          $childrenProducts List of the children products
     * @param array                          $unitedProducts   Unit products
     * @param \Magento\Store\Model\Store     $store            Store object
     */
    private function _generateProductAttributes(
        array &$item,
        \Magento\Catalog\Model\Product $product,
        $childrenProducts = null,
        $unitedProducts = null,
        \Magento\Store\Model\Store $store = null
    ) {
        $attributes = $this->getProductAttributes();

        if (!empty($attributes)) {
            $requiredAttributes = $this->_getRequiredAttributes();
            $useFullFeed = $this->configuration->getUseFullFeed();

            foreach ($attributes as $attribute) {
                $attributeCode = $attribute->getAttributeCode();
                $value = $product->getData($attributeCode);

                // unitedValues - main value + childrens values
                $unitedValues = $this->_getIdAttributesValues($unitedProducts, $attributeCode);

                $inputType = $attribute->getData('frontend_input');
                $isSearchable = $attribute->getIsSearchable();
                $isVisibleInAdvancedSearch = $attribute->getIsVisibleInAdvancedSearch();
                $usedForSortBy = $attribute->getUsedForSortBy();
                $isFilterable = $attribute->getIsFilterable();

                $attributeName = 'attribute_' . $attribute->getId();

                $isNecessaryAttribute = $useFullFeed
                    || $isSearchable
                    || $isVisibleInAdvancedSearch
                    || $usedForSortBy
                    || $isFilterable
                    || in_array($attributeCode, $requiredAttributes);

                if (!$isNecessaryAttribute) {
                    continue;
                }

                if (empty($unitedValues)) {
                    // nothing
                    // <system_attributes>
                } elseif ($attributeCode == 'price') {
                    // already defined in the '<cs:price>' field
                } elseif ($attributeCode == 'status' || $attributeCode == 'visibility') {
                    $item[$attributeCode] = $value;
                } elseif ($attributeCode == 'has_options') {
                } elseif ($attributeCode == 'required_options') {
                } elseif ($attributeCode == 'custom_layout_update') {
                } elseif ($attributeCode == 'tier_price') { // quantity discount
                } elseif ($attributeCode == 'image_label') {
                } elseif ($attributeCode == 'small_image_label') {
                } elseif ($attributeCode == 'thumbnail_label') {
                } elseif ($attributeCode == 'tax_class_id') {
                } elseif ($attributeCode == 'url_key') { // seo name
                } elseif ($attributeCode == 'category_ids') {
                } elseif ($attributeCode == 'categories') {
                    // <system_attributes>
                } elseif ($attributeCode == 'group_price') {
                    // nothing
                    // fixme in the future if need
                } elseif ($attributeCode == 'short_description'
                    || $attributeCode == 'name'
                    || $attributeCode == 'sku'
                ) {
                    if (count($unitedValues) > 1) {
                        $item['se_grouped_' . $attributeCode] = array_slice($unitedValues, 1);
                    }
                } elseif ($attributeCode == 'description') {
                    $item['full_description'] = $value;

                    if (count($unitedValues) > 1) {
                        $item['se_grouped_full_' . $attributeCode] = array_slice($unitedValues, 1);
                    }
                } elseif ($attributeCode == 'meta_title'
                    || $attributeCode == 'meta_description'
                    || $attributeCode == 'meta_keyword'
                ) {
                    $item[$attributeCode] = $unitedValues;
                } elseif ($inputType == 'price') {
                    // Other attributes with type 'price'.
                    $item[$attributeCode] = $unitedValues;
                } elseif ($inputType == 'select' || $inputType == 'multiselect') {
                    // <text_values>
                    $unitedTextValues = $this->_getProductAttributeTextValues(
                        $unitedProducts,
                        $attributeCode,
                        $inputType,
                        $store
                    );
                    $item[$attributeCode] = $unitedTextValues;
                } elseif ($inputType == 'text' || $inputType == 'textarea') {
                    $item[$attributeCode] = $unitedValues;
                } elseif ($inputType == 'date') {
                    //Magento's timestamp function makes a usage of timezone and converts it to timestamp
                    $item[$attributeCode] = $this->dateTime->timestamp(strtotime($value));
                } elseif ($inputType == 'media_image') {
                    if ($this->configuration->getIsUseDirectImagesLinks()) {
                        if (empty($store)) {
                            $store = $this->storeManager->getStore();
                        }

                        $imageBaseUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product';
                        $image = $imageBaseUrl . $attribute->getImage($attributeCode);
                    } else {
                        $image = $this->generateImage($product, $attributeCode, true, 0, 0);
                    }

                    if (!empty($image)) {
                        $item[$attributeCode] = is_object($image) ? $image->getUrl() : $image;
                    }
                } elseif ($inputType == 'gallery') {
                    // Nothing.
                } else {
                    // Attribute not will use.
                }
            }
        }

        return $item;
    }

    /**
     *
     * @param array                      $unitedProducts Unit products
     * @param string                     $attributeCode  Attribute code
     * @param string                     $inputType      Input type (seelct, textarea, multiselect and etc)
     * @param \Magento\Store\Model\Store $store
     */
    private function _getProductAttributeTextValues(
        array $products,
        $attributeCode,
        $inputType,
        \Magento\Store\Model\Store $store = null
    ) {
        $arrTextValues = [];

        foreach ($products as $p) {
            if ($values = $this->_getTextAttributeValues($p, $attributeCode, $inputType, $store)) {
                foreach ($values as $key => $value) {
                    $trimValue = trim($value);
                    if ($trimValue != '' && !in_array($trimValue, $arrTextValues)) {
                        $arrTextValues[] = $value;
                    }
                }
            }
        }

        return $arrTextValues;
    }

    /**
     * Returns text attribute values
     *
     * @param \Magento\Catalog\Model\Product $product       Product model
     * @param string                         $attributeCode Attribute code
     * @param string                         $inputType     Input type (seelct, textarea, multiselect and etc)
     * @param \Magento\Store\Model\Store     $store         Store
     */
    private function _getTextAttributeValues(
        \Magento\Catalog\Model\Product $product,
        $attributeCode,
        $inputType,
        \Magento\Store\Model\Store $store = null
    ) {
        //static $arrTextValues = array();
        $key = $attributeCode;

        if ($store) {
            $key .= '__' . $store->getId();
        }

        if (!isset($arrTextValues[$key]) && $product->getData($attributeCode) !== null) {
            $values = [];

            // Dependency of store already exists
            $textValues = $product
                ->getResource()
                ->getAttribute($attributeCode)
                ->setStoreId($store->getId())
                ->getFrontend()
                ->getValue($product);

            if ($textValues != '') {
                if ($inputType == 'multiselect') {
                    $values = array_map('trim', explode(',', $textValues));
                } else {
                    $values[] = $textValues;
                }
            }

            $arrTextValues[$key] = $values;
        } else {
            $arrTextValues[$key] = [];
        }

        return $arrTextValues[$key];
    }

    /**
     * Returns attibute values
     *
     * @param array  $unitedProducts Unit products
     * @param string $attributeCode  Attribute code
     */
    private function _getIdAttributesValues($products, $attributeCode)
    {
        $values = [];

        foreach ($products as $productKey => $product) {
            $value = $product->getData($attributeCode);

            if ($value == '') {
                // Nothing.
            } elseif (is_array($value) && empty($value)) {
                // Nothing.
            } else {
                if (!in_array($value, $values)) {
                    $values[] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * getProductImageLink
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @param  bool                           $flagKeepFrame
     * @param  bool                           $isTumbnail
     * @param  \Magento\Store\Model\Store     $store
     * @return \Magento\Catalog\Model\Product\Image $image
     */
    public function getProductImageLink(
        \Magento\Catalog\Model\Product $product,
        $flagKeepFrame = true,
        $isThumbnail = true,
        \Magento\Store\Model\Store $store = null
    ) {
        $image = null;

        if (!empty($product)) {
            if ($this->configuration->getIsUseDirectImagesLinks()) {
                if (empty($store)) {
                    $store = $this->storeManager->getStore();
                }

                $imageBaseUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product';
                $image = $imageBaseUrl . $product->getImage();
            } else {
                $imageType = $isThumbnail ? 'se_thumbnail' : 'se_image';
                $image = $this->generateImage($product, $imageType, false, false, false);

                if (empty($image)) {
                    // Outdated code, should be removed in future
                    if ($isThumbnail) {
                        $width = $height = self::THUMBNAIL_SIZE;
                    } else {
                        $width = $height = self::IMAGE_SIZE;
                    }

                    foreach (['small_image', 'image', 'thumbnail'] as $imageType) {
                        $image = $this->generateImage($product, $imageType, $flagKeepFrame, $width, $height);

                        if (!empty($image)) {
                            break;
                        }
                    }
                }
            }
        }

        return $image;
    }

    /**
     * generateImage
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @param  bool                           $flagKeepFrame
     * @param  int                            $width
     * @param  int                            $height
     * @return \Magento\Catalog\Model\Product\Image $image
     */
    private function generateImage(
        \Magento\Catalog\Model\Product $product,
        $imageType = 'small_image',
        $flagKeepFrame = true,
        $width = 70,
        $height = 70
    ) {
        $image = null;
        $objectImage = $product->getData($imageType);

        if (in_array($imageType, ['se_image', 'se_thumbnail']) || !empty($objectImage) && $objectImage != 'no_selection') {
            try {
                $image = $this->catalogImageFactory
                    ->create()
                    ->init($product, $imageType)
                    ->setImageFile($product->getImage());

                if ($width || $height) {
                    $image->constrainOnly(true)  // Guarantee, that image picture will not be bigger, than it was.
                        ->keepAspectRatio(true)      // Guarantee, that image picture width/height will not be distorted.
                        ->keepFrame($flagKeepFrame)  // Guarantee, that image will have dimensions, set in $width/$height
                        ->resize($width, $height);
                }
            } catch (\Exception $e) {
                // image not exists
                $image = null;
            }
        }

        return $image;
    }

    /**
     * Return children products
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Store\Model\Store     $store
     */
    public function getChildrenProducts(
        \Magento\Catalog\Model\Product $product,
        \Magento\Store\Model\Store $store = null
    ) {
        $childrenProducts = [];

        if (empty($product)) {
            return $childrenProducts;
        }

        // if CONFIGURABLE OR GROUPED OR BUNDLE
        if (in_array($product->getData('type_id'), $this->hasChildrenTypes)) {
            if ($typeInstance = $product->getTypeInstance()) {
                $requiredChildrenIds = $typeInstance->getChildrenIds($product->getId(), true);
                if ($requiredChildrenIds) {
                    $childrenIds = [];

                    foreach ($requiredChildrenIds as $groupedChildrenIds) {
                        $childrenIds = array_merge($childrenIds, $groupedChildrenIds);
                    }

                    if ($childrenIds) {
                        $childrenProducts = $this->getProducts($childrenIds, $store, null);
                    }
                }
            }
        }

        return $childrenProducts;
    }

    /**
     * Get product minimal tier price
     *
     * @param  \Magento\Catalog\Model\Product $product         Product data
     * @param  number                         $customerGroupId Usergroup
     * @return mixed null|number
     */
    private function _getMinimalTierPrice(\Magento\Catalog\Model\Product $product, $customerGroupId = null, $min = true)
    {
        $price = null;

        if ($customerGroupId) {
            $product->setCustomerGroupId($customerGroupId);
        }

        // Load tier prices
        $tierPrices = $product->getTierPrices();
        if (empty($tierPrices)) {
            if ($attribute = $product->getResource()->getAttribute('tier_price')) {
                $attribute->getBackend()->afterLoad($product);
                $tierPrices = $product->getTierPrices();
            }
        }

        // Detect discount type: fixed or percent (available for bundle products)
        $priceType = 'fixed';
        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            $priceType = $product->getPriceType();

            if ($priceType !== null && $priceType != \Magento\Bundle\Model\Product\Price::PRICE_TYPE_FIXED) {
                $priceType = 'percent';
            }

            $min = $priceType == 'percent' ? !$min : $min;
        }

        // Calculate minimum discount value
        if (!empty($tierPrices) && is_array($tierPrices)) {
            $prices = [];

            foreach ($tierPrices as $priceInfo) {
                if ($priceInfo->getCustomerGroupId() == $customerGroupId) {
                    if ($priceType == 'percent') {
                        if (!empty($priceInfo['extension_attributes'])) {
                            $priceValue = $priceInfo->getExtensionAttributes()->getPercentageValue();
                        } else {
                            $priceValue = $priceInfo->getValue();
                        }
                    } else {
                        $priceValue = $priceInfo->getValue();
                    }

                    $prices[] = $priceValue;
                }
            }

            if (!empty($prices)) {
                $price = $min ? min($prices) : max($prices);
            }
        }

        // Calculate discounted price
        if ($price && $priceType == 'percent') {
            $regularPrice = $this->_getProductMinimalRegularPrice($product, null, false);
            $price = $regularPrice * (1 - $price / 100.0);
        }

        return $price;
    }

    /**
     * Calculate minimal list price
     *
     * @param  \Magento\Catalog\Model\Product $product          Product model
     * @param  array                          $childrenProducts List of the children products
     * @param  bool                           $applyTax         If true tax will be applied
     * @return float
     */
    private function _getProductMinimalRegularPrice(
        \Magento\Catalog\Model\Product $product,
        $childrenProducts = null,
        $applyTax = true
    ) {
        $regularPrice = $product
            ->getPriceInfo()
            ->getPrice(\Magento\Catalog\Pricing\Price\RegularPrice::PRICE_CODE)
            ->getAmount()
            ->getBaseAmount();

        if (!$regularPrice && !empty($childrenProducts)) {
            foreach ($childrenProducts as $childrenProduct) {
                if ($childrenProduct->getStatus() != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                    continue;
                }

                $childRegularPrice = $childrenProduct
                    ->getPriceInfo()
                    ->getPrice(\Magento\Catalog\Pricing\Price\RegularPrice::PRICE_CODE)
                    ->getAmount()
                    ->getBaseAmount();

                $regularPrice = $regularPrice ? min($regularPrice, $childRegularPrice) : $childRegularPrice;
            }
        }

        if ($regularPrice && $applyTax) {
            $regularPrice = $this->getProductShowPrice($product, $regularPrice);
        }

        return (float)$regularPrice;
    }

    /**
     * Generate prices for product
     *
     * @param  array                          $item             Product data
     * @param  \Magento\Catalog\Model\Product $product          Product model
     * @param  array                          $childrenProducts List of the children products
     * @param  \Magento\Store\Model\Store     $store            Store object
     * @return boolean
     */
    private function _generateProductPrices(
        array &$item,
        \Magento\Catalog\Model\Product $product,
        $childrenProducts = null,
        \Magento\Store\Model\Store $store = null
    ) {
        if ($customerGroups = $this->_getCustomerGroups()) {
            foreach ($customerGroups as $customerGroup) {
                // It is needed because the 'setCustomerGroupId' function works only once.
                $productCurrentGroup = clone $product;
                $customerGroupId = $customerGroup->getId();

                if ($customerGroupId == \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID
                    || !isset($equalPriceForAllGroups)
                ) {
                    $price = $this->_getProductMinimalPrice(
                        $productCurrentGroup,
                        $store,
                        $childrenProducts,
                        $customerGroupId
                    );

                    if ($price !== false) {
                        $price = round($price, \Searchanise\SearchAutocomplete\Helper\ApiSe::getFloatPrecision());
                    }

                    if ($customerGroupId == \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID) {
                        $item['price'] = $price;
                        $item['list_price'] = round(
                            $this->_getProductMinimalRegularPrice($product, $childrenProducts),
                            \Searchanise\SearchAutocomplete\Helper\ApiSe::getFloatPrecision()
                        );
                    }
                } else {
                    $price = $equalPriceForAllGroups ?: 0;
                }

                $label_ = \Searchanise\SearchAutocomplete\Helper\ApiSe::getLabelForPricesUsergroup() . $customerGroup->getId();
                $item[$label_] = $price;
                unset($productCurrentGroup);
            }
        }

        return true;
    }

    /**
     * Generate feed for product
     *
     * @param  \Magento\Catalog\Model\Product $product   Product object
     * @param  \Magento\Store\Model\Store     $store     Store object
     * @param  string                         $checkData If true, the additional checks will be perform on the product
     * @return array
     */
    public function generateProductFeed(
        \Magento\Catalog\Model\Product $product,
        \Magento\Store\Model\Store $store = null,
        $checkData = true
    ) {
        $item = [];

        if ($checkData
            && (!$product || !$product->getId() || !$product->getName())
        ) {
            return $item;
        }

        $unitedProducts = [$product]; // current product + childrens products (if exists)
        $childrenProducts = $this->getChildrenProducts($product, $store);

        if ($childrenProducts) {
            foreach ($childrenProducts as $childrenProductsKey => $childrenProduct) {
                $unitedProducts[] = $childrenProduct;
            }
        }

        $item['id'] = $product->getId();
        $item['title'] = $product->getName();
        $item['link'] = $product->getUrlModel()->getUrl($product, [
            '_nosid' => true,
            '_secure' => $this->configuration->getIsUseSecureUrlsInFrontend(),
            '_searchanise' => true,
        ]);
        $item['product_code'] = $product->getSku();
        $item['created'] = strtotime($product->getCreatedAt());

        $summaryAttr = $this->configuration->getSummaryAttr();
        $item['summary'] = $product->getData($summaryAttr);

        $this->_generateProductPrices($item, $product, $childrenProducts, $store);

        $quantity = $this->_getProductQty($product, $store, $unitedProducts);
        $item['quantity'] = ceil($quantity);
        $item['is_in_stock'] = $quantity > 0;

        // Show images without white field
        // Example: image 360 x 535 => 47 х 70
        if ($this->configuration->getResultsWidgetEnabled($store->getId())) {
            $image = $this->getProductImageLink($product, false, false, $store);
        } else {
            $image = $this->getProductImageLink($product, false, true, $store);
        }

        if (!empty($image)) {
            $item['image_link'] = is_object($image) ? $image->getUrl() : $image;
        }

        $this->_generateProductAttributes($item, $product, $childrenProducts, $unitedProducts, $store);

        $item['category_ids'] = $item['categories'] = [];
        $categoryCollection = $this->categoryCollectionFactory
            ->create()
            ->addAttributeToFilter('path', ['like' => "1/{$store->getRootCategoryId()}/%"]);

        $categoryCollection
            ->getSelect()
            ->join(
                [
                'cp' => $product->getResource()->getTable('catalog_category_product')
                ],
                'cp.category_id=e.entity_id'
            )
            ->where('cp.product_id = ' . $product->getId());

        $categoryIds = $categoryCollection->getAllIds();

        if (!empty($categoryIds)) {
            $categoryNames = [];

            foreach ($categoryIds as $catKey => $categoryId) {
                $category = $this->categoryFactory->create()->load($categoryId);

                if (!empty($category)) {
                    $categoryNames[] = $category->getName();
                }
            }

            $item['category_ids'] = $categoryIds;
            $item['categories'] = $categoryNames;
        }

        // Add review data
        if ($product->getRatingSummary()) {
            $item['total_reviews'] = $product->getRatingSummary()->getReviewsCount();
            $item['reviews_average_score'] = $product->getRatingSummary()->getRatingSummary() / 20.0;
        }

        // Add sales data
        $item['sales_amount'] = (int)$product->getData('se_sales_amount');
        $item['sales_total'] = $item['sales_total'] = round(
            (float)$product->getData('se_sales_total'),
            \Searchanise\SearchAutocomplete\Helper\ApiSe::getFloatPrecision()
        );

        $item['related_product_ids'] = $item['up_sell_product_ids'] = $item['cross_sell_product_ids'] = [];

        // Add related products
        $relatedProducts = $product->getRelatedProducts();
        if (!empty($relatedProducts)) {
            foreach ($relatedProducts as $relatedProduct) {
                $item['related_product_ids'][] = $relatedProduct->getId();
            }
        }

        // Add upsell products
        $upsellProducts = $product->getUpSellProducts();
        if (!empty($upsellProducts)) {
            foreach ($upsellProducts as $upsellProduct) {
                $item['up_sell_product_ids'][]  = $upsellProduct->getId();
            }
        }

        // Add crosssell products
        $crossSellProducts = $product->getCrossSellProducts();
        if (!empty($crossSellProducts)) {
            foreach ($crossSellProducts as $crossSellProduct) {
                $item['cross_sell_product_ids'][] = $crossSellProduct->getId();
            }
        }

        return $item;
    }

    /**
     * Returns stock item
     *
     * @param  \Magento\Catalog\Model\Product $product Product model
     * @param  \Magento\Store\Model\Store     $store   Object store
     * @return mixed
     */
    public function getStockItem(
        \Magento\Catalog\Model\Product $product,
        \Magento\Store\Model\Store $store = null
    ) {
        $stockItem = null;

        if (!empty($product)) {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
        }

        return $stockItem;
    }

    /**
     * getProductQty
     *
     * @param  \Magento\Catalog\Model\Product $product
     * @param  \Magento\Store\Model\Store     $store
     * @param  array                          $unitedProducts - Current product + childrens products (if exists)
     * @return float
     */
    private function _getProductQty(
        \Magento\Catalog\Model\Product $product,
        \Magento\Store\Model\Store $store,
        array $unitedProducts = []
    ) {
        $quantity = 1;
        $stockItem = $this->getStockItem($product);

        if (!empty($stockItem)) {
            $manageStock = null;

            if ($stockItem->getData(StockItemInterface::USE_CONFIG_MANAGE_STOCK)) {
                $manageStock = $this->configuration
                    ->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_MANAGE_STOCK);
            } else {
                $manageStock = $stockItem->getData(StockItemInterface::MANAGE_STOCK);
            }

            if (empty($manageStock)) {
                $quantity = 1;
            } else {
                $isInStock = $stockItem->getIsInStock();

                if (!$isInStock) {
                    $quantity = 0;
                } else {
                    $quantity = $stockItem->getQty();

                    if ($quantity <= 0) {
                        $backorders = StockItemInterface::BACKORDERS_NO;

                        if ($stockItem->getData(StockItemInterface::USE_CONFIG_BACKORDERS) == 1) {
                            $backorders = $this->configuration
                                ->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_BACKORDERS);
                        } else {
                            $backorders = $stockItem->getData(StockItemInterface::BACKORDERS);
                        }

                        if ($backorders != StockItemInterface::BACKORDERS_NO) {
                            $quantity = 1;
                        }
                    }

                    if (!empty($unitedProducts)) {
                        $quantity = 0;

                        foreach ($unitedProducts as $itemProductKey => $itemProduct) {
                            $quantity += $this->_getProductQty($itemProduct, $store);
                        }
                    }
                }
            }
        }

        return $quantity;
    }

    /**
     * Returns header for api request
     *
     * @param  \Magento\Store\Model\Store $store Store object
     * @return array
     */
    public function getHeader(\Magento\Store\Model\Store $store = null)
    {
        $url = '';

        if (empty($store)) {
            $this->storeManager->getStore()->getBaseUrl();
        } else {
            $url = $store->getUrl();
        }
        $date = date('c');

        return [
            'id'      => $url,
            'updated' => $date,
        ];
    }

    /**
     * Return list of the products
     *
     * @param  array                      $productIds      List of the product ids
     * @param  \Magento\Store\Model\Store $store           Store object
     * @param  number                     $customerGroupId Customer group id
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProducts(
        array $productIds = [],
        \Magento\Store\Model\Store $store = null,
        $customerGroupId = null
    ) {
        $resultProducts = [];

        if (empty($productIds)) {
            return $resultProducts;
        }

        $this->searchaniseHelper->startEmulation($store);

        static $arrProducts = [];

        $keyProducts = '';

        if (!empty($productIds)) {
            if (is_array($productIds)) {
                $keyProducts .= implode('_', $productIds);
            } else {
                $keyProducts .= $productIds;
            }
        }

        $keyProducts .= ':' .  ($store ? $store->getId() : '0');
        $keyProducts .= ':' .  $customerGroupId;
        $keyProducts .= ':' .  ($this->isGetProductsByItems ? '1' : '0');

        if (!isset($arrProducts[$keyProducts])) {
            $products = [];

            if ($this->isGetProductsByItems) {
                $products = $this->_getProductsByItems($productIds, $store);
            } else {
                $products = $this->getProductCollection()
                    ->distinct(true)
                    ->addAttributeToSelect('*')
                    ->addFinalPrice()
                    ->addMinimalPrice()
                    ->addUrlRewrite();

                if (!empty($customerGroupId)) {
                    if (!emtpy($store)) {
                        $products->addPriceData($customerGroupId, $store->getWebsiteId());
                    } else {
                        $products->addPriceData($customerGroupId);
                    }
                }

                if (!empty($store)) {
                    $products->setStoreId($store)->addStoreFilter($store);
                }

                if ($productIds !== \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA) {
                    // Already exist automatic definition 'one value' or 'array'.
                    $products->addIdFilter($productIds);
                }

                $products->load();

                // Fix: Disabled product not coming in product collection in version 2.2.2 or highter, so try to reload them directly
                if ($productIds !== \Searchanise\SearchAutocomplete\Model\Queue::NOT_DATA && $this->isVersionMoreThan('2.2.2')) {
                    $loadedProductIds = [];
                    foreach ($products as $product) {
                        $loadedProductIds[] = $product->getId();
                    }

                    $skippedProductIds = array_diff($productIds, $loadedProductIds);
                    if (!empty($skippedProductIds)) {
                        $reloadedItems = $this->_getProductsByItems($skippedProductIds, $store);

                        if (!empty($reloadedItems)) {
                            foreach ($reloadedItems as $item) {
                                $products->addItem($item);
                            }
                        }
                    }
                }
            }

            // Fixme in the future
            // Maybe create cache without customerGroupId and setCustomerGroupId after using cache.
            if (count($products) > 0 && (!empty($store) || $customerGroupId != null)) {
                foreach ($products as $key => &$product) {
                    if (!empty($product)) {
                        if (!empty($store)) {
                            $product->setWebsiteId($store->getWebsiteId());
                        }

                        if (!empty($customerGroupId)) {
                            $product->setCustomerGroupId($customerGroupId);
                        }
                    }
                }
            }
            // end fixme

            if ($products instanceof \Magento\Framework\Data\Collection && $this->getModuleManager()->isEnabled('Magento_Review')) {
                $this->reviewFactory->create()->appendSummary($products);
            }

            $this->generateSalesData($products, $store);

            $arrProducts[$keyProducts] = $products;
        } // End isset

        $this->searchaniseHelper->stopEmulation();

        return $arrProducts[$keyProducts];
    }

    /**
     * Attach sales data to products
     *
     * @param $products \Magento\Catalog\Model\ResourceModel\Product\Collection | array
     * @param  \Magento\Store\Model\Store $store        Store object
     * @return boolean
    */
    private function generateSalesData(&$products, \Magento\Store\Model\Store $store = null)
    {
        if ($products instanceof \Magento\Catalog\Model\ResourceModel\Product\Collection) {
            $product_ids = $products->getAllIds();
        } elseif (is_array($products)) {
            $product_ids = array_map(function($product) {
                return $product->getId();
            }, $products);
        }

        $product_ids = array_filter($product_ids);

        if (empty($product_ids)) {
            return false;
        }

        $product_ids = implode(',', $product_ids);
        $ordersTableName = $this->resourceConnection->getTableName('sales_order_item');

        try {
            $salesConnection = $this->resourceConnection->getConnectionByName('sales');
        } catch (\Exception $e) {
            $salesConnection = $this->resourceConnection->getConnection();
        }

        $condition = "product_id IN ({$product_ids})";

        if (!empty($store)) {
            $condition .= ' AND store_id = ' . $store->getId();
        }

        $query = "SELECT
                product_id,
                SUM(qty_ordered) AS sales_amount,
                SUM(row_total) AS sales_total
            FROM {$ordersTableName}
            WHERE {$condition}
            GROUP BY product_id";

        $salesData = $salesConnection->query($query)->fetchAll(
            \PDO::FETCH_GROUP
            | \PDO::FETCH_UNIQUE
            | \PDO::FETCH_ASSOC
        );

        foreach ($products as &$product) {
            $productId = $product->getId();

            if (isset($salesData[$productId])) {
                $product->setData('se_sales_amount', $salesData[$productId]['sales_amount']);
                $product->setData('se_sales_total', $salesData[$productId]['sales_total']);
            }
        }

        return true;
    }

    /**
     * Return product ids for specific range. Used by full import
     *
     * @param  number                     $start        Start range
     * @param  number                     $end          End range
     * @param  number                     $step         Step
     * @param  \Magento\Store\Model\Store $store        Store object
     * @param  bolean                     $isOnlyActive If true, finds only active produts
     * @return array
     */
    public function getProductIdsFromRange(
        $start,
        $end,
        $step,
        \Magento\Store\Model\Store $store = null,
        $isOnlyActive = false
    ) {
        $arrProducts = [];

        $this->searchaniseHelper->startEmulation($store);

        $products = $this->getProductCollection()
            ->clear()
            ->distinct(true)
            ->addAttributeToSelect('entity_id')
            ->addFieldToFilter('entity_id', ['from' => $start, 'to' => $end])
            ->setPageSize($step);

        if (!empty($store)) {
            $products->addStoreFilter($store);
        }

        if ($isOnlyActive) {
            $products->addAttributeToFilter('status', ['in'=> $this->productStatus->getVisibleStatusIds()]);
            // It may require to disable "product visibility" filter if "is full feed".
            if ($this->configuration->getUseFullFeed()) {
                $products->addAttributeToFilter(
                    'visibility',
                    ['in' => $this->productVisibility->getVisibleInSiteIds()]
                );
            } else {
                $products->addAttributeToFilter(
                    'visibility',
                    ['in' => $this->productVisibility->getVisibleInSearchIds()]
                );
            }
        }

        $arrProducts = $products->getAllIds();
        // It is necessary for save memory.
        unset($products);

        /*$this->loggerHelper->log("===== ApiProducts: getProductIdsFromRange =====", [
            'min' => $start,
            'max' => $end,
            'step' => $step,
            'products' => count($arrProducts),
        ], Logger::TYPE_DEBUG);*/

        $this->searchaniseHelper->stopEmulation();

        return $arrProducts;
    }

    /**
     * Get minimum and maximum product ids from store
     *
     * @param  \Magento\Store\Model\Store $store
     * @return number[]|mixed[]
     */
    public function getMinMaxProductId(\Magento\Store\Model\Store $store = null)
    {
        $this->searchaniseHelper->startEmulation($store);

        $startId = $endId = 0;

        $productStartCollection = $this->getProductCollection()
            ->clear()
            ->addAttributeToSelect('entity_id')
            ->setPageSize(1);

        if (!empty($store)) {
            $productStartCollection
            ->setStoreId($store->getId())
            ->addStoreFilter($store);
        }

        $productStartCollection->getSelect()->reset(\Zend_Db_Select::ORDER);
        $productStartCollection->addAttributeToSort('entity_id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $productEndCollection = $this->getProductCollection()
            ->clear()
            ->addAttributeToSelect('entity_id')
            ->setPageSize(1);

        if (!empty($store)) {
            $productEndCollection
                ->setStoreId($store->getId())
                ->addStoreFilter($store);
        }

        $productEndCollection->getSelect()->reset(\Zend_Db_Select::ORDER);
        $productEndCollection->addAttributeToSort('entity_id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC);

        if ($productStartCollection->getSize() > 0) {
            $firstItem = $productStartCollection->getFirstItem();
            $startId = $firstItem->getId();
        }

        if ($productEndCollection->getSize() > 0) {
            $firstItem = $productEndCollection->getFirstItem();
            $endId = $firstItem->getId();
        }

        $this->searchaniseHelper->stopEmulation();

        return [$startId, $endId];
    }
    /**
     * Get products with items
     *
     * @param  array                      $productIds List of product ids
     * @param  \Magento\Store\Model\Store $store      Store object
     * @return array
     */
    private function _getProductsByItems(array $productIds, \Magento\Store\Model\Store $store = null)
    {
        $products = [];

        $productIds = $this->_validateProductIds($productIds, $store);

        if (!empty($productIds)) {
            foreach ($productIds as $key => $productId) {
                if (empty($productId)) {
                    continue;
                }

                // It can use various types of data.
                if (is_array($productId)) {
                    if (isset($productId['entity_id'])) {
                        $productId = $productId['entity_id'];
                    }
                }

                try {
                    $product = $this->loadProductById($productId);
                } catch (\Exception $e) {
                    $this->loggerHelper->log(__("Error: Script couldn't get product'"));
                    continue;
                }

                if (!empty($product)) {
                    $products[] = $product;
                }
            }
        }

        return $products;
    }

    /**
     * Validate list of the products
     *
     * @param  array                      $productIds List of the products
     * @param  \Magento\Store\Model\Store $store      Store object
     * @return array
     */
    private function _validateProductIds(array $productIds, \Magento\Store\Model\Store $store = null)
    {
        $validProductIds = [];

        if (!empty($store)) {
            $this->storeManager->setCurrentStore($store);
        } else {
            $this->storeManager->setCurrentStore(0);
        }

        $products = $this->getProductCollection()
            ->addAttributeToSelect('entity_id');

        if (!empty($store)) {
            $products->addStoreFilter($store);
        }

        // Already exist automatic definition 'one value' or 'array'.
        $products->addIdFilter($productIds);
        $products->load();

        if (count($products) > 0) {
            // Not used because 'arrProducts' comprising 'stock_item' field and is 'array(array())'
            // $arrProducts = $products->toArray(array('entity_id'));
            foreach ($products as $product) {
                $validProductIds[] = $product->getId();
            }
        }

        if (count($validProductIds) != count($productIds) && $this->isVersionMoreThan('2.2.2')) {
            // Fix : Disabled product not coming in product collection in version 2.2.2 or highter
            // So we have to modify SQL query directly and try to reload them
            $updatedfromAndJoin = $updatedWhere = [];

            $fromAndJoin = $products->getSelect()->getPart('FROM');
            $where = $products->getSelect()->getPart('WHERE');
            $products->clear();

            foreach ($fromAndJoin as $key => $index) {
                if ($key == 'stock_status_index' || $key == 'price_index') {
                    $index['joinType'] = 'LEFT JOIN';
                }
                $updatedfromAndJoin[$key] = $index;
            }

            foreach ($where as $key => $condition) {
                if (strpos($condition, 'stock_status_index.stock_status = 1') !== false) {
                    $updatedWhere[] = str_replace('stock_status_index.stock_status = 1', '1', $condition);
                } else {
                    $updatedWhere[] = $condition;
                }
            }

            if (!empty($updatedfromAndJoin)) {
                $products->getSelect()->setPart('FROM', $updatedfromAndJoin);
            }

            if (!empty($updatedWhere)) {
                $products->getSelect()->setPart('WHERE', $updatedWhere);
            }

            $products->load();

            if (count($products) > 0) {
                // Not used because 'arrProducts' comprising 'stock_item' field and is 'array(array())'
                // $arrProducts = $products->toArray(array('entity_id'));
                foreach ($products as $product) {
                    $validProductIds[] = $product->getId();
                }
            }
        }

        // It is necessary for save memory.
        unset($products);

        return $validProductIds;
    }

    /**
     * Get customer group prices for getSchema()
     *
     * @return array
     */
    public function getSchemaCustomerGroupsPrices()
    {
        $items = [];

        if ($customerGroups = $this->_getCustomerGroups()) {
            foreach ($customerGroups as $keyCustomerGroup => $customerGroup) {
                $label = \Searchanise\SearchAutocomplete\Helper\ApiSe::getLabelForPricesUsergroup() . $customerGroup->getId();
                $items[] = [
                    'name'  => $label,
                    'title' => 'Price for ' .  $customerGroup->getData('customer_group_code'),
                    'type'  => 'float',
                ];
            }
        }

        return $items;
    }

    /**
     * Returns customer groups
     *
     * @return customer group collection
     */
    private function _getCustomerGroups()
    {
        static $customerGroups;

        if (!isset($customerGroups)) {
            $customerGroups = $this->customerGroupCollectionFactory->create();

            if (!$this->configuration->getIsCustomerUsergroupsEnabled()) {
                $customerGroups->addFieldToFilter('customer_group_id', \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
            }

            $customerGroups->load();
        }

        return $customerGroups;
    }

    /**
     * Generate custom facet for getSchema()
     *
     * @param string $title
     * @param number $position
     * @param string $attribute
     * @param string $type
     */
    private function _generateFacetFromCustom($title = '', $position = 0, $attribute = '', $type = '')
    {
        $facet = [];

        $facet['title'] = $title;
        $facet['position'] = $position;
        $facet['attribute'] = $attribute;
        $facet['type'] = $type;

        return $facet;
    }

    /**
     * Return product attributes
     *
     * @return array collection
     */
    public function getProductAttributes()
    {
        static $allAttributes = null;

        if (empty($allAttributes)) {
            $allAttributes = $this->catalogProductAttributeCollectionFactory
                ->create()
                ->setItemObjectClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
                ->load();
        }

        return $allAttributes;
    }

    /**
     * Get product schema for searchanise
     *
     * @param  \Magento\Store\Model\Store $store Store object
     * @return array
     */
    public function getSchema(\Magento\Store\Model\Store $store)
    {
        static $schemas;

        if (!isset($schemas[$store->getId()])) {
            $this->storeManager->setCurrentStore($store);

            $schema = $this->getSchemaCustomerGroupsPrices();

            if ($this->configuration->getResultsWidgetEnabled($store->getId())) {
                $schema[] = [
                    'name'        => 'categories',
                    'title'       => __('Category')->getText(),
                    'type'        => 'text',
                    'weight'      => self::WEIGHT_CATEGORIES,
                    'text_search' => 'Y',
                    'facet'       => $this->_generateFacetFromCustom(
                        __('Category')->getText(),
                        10,
                        'categories',
                        'select'
                    ),
                ];

                $schema[] = [
                    'name'        => 'category_ids',
                    'title'       => __('Category')->getText() . ' - IDs',
                    'type'        => 'text',
                    'weight'      => 0,
                    'text_search' => 'N',
                ];
            } else {
                $schema[] = [
                    'name'        => 'categories',
                    'title'       => __('Category')->getText(),
                    'type'        => 'text',
                    'weight'      => self::WEIGHT_CATEGORIES,
                    'text_search' => 'Y',
                ];

                $schema[] = [
                    'name'        => 'category_ids',
                    'title'       => __('Category')->getText() . ' - IDs',
                    'type'        => 'text',
                    'weight'      => 0,
                    'text_search' => 'N',
                    'facet'       => $this->_generateFacetFromCustom(
                        __('Category')->getText(),
                        10,
                        'category_ids',
                        'select'
                    ),
                ];
            }

            $schema = array_merge($schema, [
                [
                    'name'        => 'is_in_stock',
                    'title'       => __('Stock Availability')->getText(),
                    'type'        => 'text',
                    'weight'      => 0,
                    'text_search' => 'N',
                ],
                [
                    'name'        => 'sales_amount',
                    'title'       => __('Bestselling')->getText(),
                    'type'        => 'int',
                    'sorting'     => 'Y',
                    'weight'      => 0,
                    'text_search' => 'N',
                ],
                [
                    'name'        => 'sales_total',
                    'title'       => __('Sales total')->getText(),
                    'type'        => 'float',
                    'filter_type' => 'none',
                ],
                [
                    'name'        => 'created',
                    'title'       => __('created')->getText(),
                    'type'        => 'int',
                    'sorting'     => 'Y',
                    'weight'      => 0,
                    'text_search' => 'N',
                ],
                [
                    'name'        => 'related_product_ids',
                    'title'       => __('Related Products')->getText() . ' - IDs',
                    'filter_type' => 'none',
                ],
                [
                    'name'        => 'up_sell_product_ids',
                    'title'       => __('Up-Sell Products')->getText() . ' - IDs',
                    'filter_type' => 'none',
                ],
                [
                    'name'        => 'cross_sell_product_ids',
                    'title'       => __('Cross-Sell Products')->getText() . ' - IDs',
                    'filter_type' => 'none',
                ],
            ]);

            if ($attributes = $this->getProductAttributes()) {
                foreach ($attributes as $attribute) {
                    if ($items = $this->getSchemaAttribute($attribute)) {
                        foreach ($items as $keyItem => $item) {
                            $schema[] = $item;
                        }
                    }
                }
            }

            $schemas[$store->getId()] = $schema;
        }

        return $schemas[$store->getId()];
    }

    /**
     * Get schema attribute
     *
     * @param  \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute Product attribute
     * @return array
     */
    public function getSchemaAttribute(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        $items = [];

        $requiredAttributes = $this->_getRequiredAttributes();
        $useFullFeed = $this->configuration->getUseFullFeed();

        $attributeCode = $attribute->getAttributeCode();
        $inputType = $attribute->getData('frontend_input');
        $isSearchable = $attribute->getIsSearchable();
        $isVisibleInAdvancedSearch = $attribute->getIsVisibleInAdvancedSearch();
        $usedForSortBy = $attribute->getUsedForSortBy();
        $isFilterable = $attribute->getIsFilterable();
        $attributeName = 'attribute_' . $attribute->getId();

        $isNecessaryAttribute = $useFullFeed
            || $isSearchable
            || $isVisibleInAdvancedSearch
            || $usedForSortBy
            || $isFilterable
            || in_array($attributeCode, $requiredAttributes);

        if (!$isNecessaryAttribute) {
            return $items;
        }

        $type = '';
        $name = $attribute->getAttributeCode();
        $title = $attribute->getStoreLabel();
        $sorting = $usedForSortBy ? 'Y' : 'N';
        $textSearch = $isSearchable ? 'Y' : 'N';
        $attributeWeight = 0;

        // <system_attributes>
        if ($attributeCode == 'price') {
            $type = 'float';
            $textSearch = 'N';
        } elseif ($attributeCode == 'status' || $attributeCode == 'visibility') {
            $type = 'text';
            $textSearch = 'N';
        } elseif ($attributeCode == 'has_options') {
        } elseif ($attributeCode == 'required_options') {
        } elseif ($attributeCode == 'custom_layout_update') {
        } elseif ($attributeCode == 'tier_price') { // quantity discount
        } elseif ($attributeCode == 'image_label') {
        } elseif ($attributeCode == 'small_image_label') {
        } elseif ($attributeCode == 'thumbnail_label') {
        } elseif ($attributeCode == 'tax_class_id') {
        } elseif ($attributeCode == 'url_key') { // seo name
        } elseif ($attributeCode == 'group_price') {
        } elseif ($attributeCode == 'category_ids') {
        } elseif ($attributeCode == 'categories') {
            // <system_attributes>
        } elseif ($attributeCode == 'name' || $attributeCode == 'sku' || $attributeCode == 'short_description') {
            //for original
            if ($attributeCode == 'short_description') {
                $name    = 'description';
                $sorting = 'N';
                $weight  = self::WEIGHT_SHORT_DESCRIPTION;
            } elseif ($attributeCode == 'name') {
                $name    = 'title';
                $sorting = 'Y';//always (for search results widget)
                $weight  = self::WEIGHT_SHORT_TITLE;
            } elseif ($attributeCode == 'sku') {
                $name    = 'product_code';
                $sorting = $sorting;
                $weight  = self::WEIGHT_SHORT_TITLE;
            }

            $items[] = [
                'name'    => $name,
                'title'   => $title,
                'type'    => 'text',
                'sorting' => $sorting,
                'weight'  => $weight,
                'text_search' => $textSearch,
            ];

            // for grouped
            $type = 'text';
            $name  = 'se_grouped_' . $attributeCode;
            $sorting = 'N';
            $title = $attribute->getStoreLabel() . ' - Grouped';
            $attributeWeight = ($attributeCode == 'short_description')
                ? self::WEIGHT_SHORT_DESCRIPTION
                : self::WEIGHT_SHORT_TITLE;
        } elseif ($attributeCode == 'short_description'
            || $attributeCode == 'description'
            || $attributeCode == 'meta_title'
            || $attributeCode == 'meta_description'
            || $attributeCode == 'meta_keyword'
        ) {
            if ($isSearchable) {
                if ($attributeCode == 'description') {
                    $attributeWeight = self::WEIGHT_DESCRIPTION;
                } elseif ($attributeCode == 'meta_title') {
                    $attributeWeight = self::WEIGHT_META_TITLE;
                } elseif ($attributeCode == 'meta_description') {
                    $attributeWeight = self::WEIGHT_META_DESCRIPTION;
                } elseif ($attributeCode == 'meta_keyword') {
                    $attributeWeight = self::WEIGHT_META_KEYWORDS;
                }
            }

            $type = 'text';

            if ($attributeCode == 'description') {
                $name = 'full_description';
                $items[] = [
                    'name'   => 'se_grouped_full_' . $attributeCode,
                    'title'  => $attribute->getStoreLabel() . ' - Grouped',
                    'type'   => $type,
                    'weight' => $isSearchable ? self:: WEIGHT_DESCRIPTION_GROUPED : 0,
                    'text_search' => $textSearch,
                ];
            }
        } elseif ($inputType == 'price') {
            $type = 'float';
        } elseif ($inputType == 'select' || $inputType == 'multiselect') {
            $type = 'text';
            $attributeWeight = $isSearchable ? self::WEIGHT_SELECT_ATTRIBUTES : 0;
        } elseif ($inputType == 'text' || $inputType == 'textarea') {
            if ($isSearchable) {
                if ($inputType == 'text') {
                    $attributeWeight = self::WEIGHT_TEXT_ATTRIBUTES;
                } elseif ($inputType == 'textarea') {
                    $attributeWeight = self::WEIGHT_TEXT_AREA_ATTRIBUTES;
                }
            }
            $type = 'text';
        } elseif ($inputType == 'date') {
            $type = 'int';
        } elseif ($inputType == 'media_image') {
            $type = 'text';
        }

        if (!empty($type)) {
            $item = [
                'name'   => $name,
                'title'  => $title,
                'type'   => $type,
                'sorting' => $sorting,
                'weight' => $attributeWeight,
                'text_search' => $textSearch,
            ];

            $facet = $this->_generateFacetFromFilter($attribute);

            if (!empty($facet)) {
                $item['facet'] = $facet;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Checks if attribute is the facet
     *
     * @param  \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @return boolean
     */
    public function isFacet(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        return $attribute->getIsFilterable() || $attribute->getIsFilterableInSearch() || $attribute->getIsVisibleInAdvancedSearch();
    }

    /**
     * Returns price navigation step
     *
     * @param  \Magento\Store\Model\Store $store
     * @return mixed
     */
    private function _getPriceNavigationStep(\Magento\Store\Model\Store $store = null)
    {
        // TODO: Unused?
        $store = !empty($store) ? $store : $this->storeManager->getStore(0);

        $priceRangeCalculation = $this->configuration->getValue(DataProviderPrice::XML_PATH_RANGE_CALCULATION);

        if ($priceRangeCalculation == DataProviderPrice::RANGE_CALCULATION_MANUAL) {
            return $this->configuration->getValue(DataProviderPrice::XML_PATH_RANGE_STEP);
        }

        return null;
    }

    /**
     * Generate facet from filter
     *
     * @param  \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param  \Magento\Store\Model\Store                         $store
     * @return array
     */
    private function _generateFacetFromFilter(
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute,
        \Magento\Store\Model\Store $store = null
    ) {
        $item = [];

        if ($this->isFacet($attribute)) {
            $attributeType = '';

            $inputType = $attribute->getData('frontend_input');

            // "Can be used only with catalog input type Dropdown, Multiple Select and Price".
            if (($inputType == 'select') || ($inputType == 'multiselect')) {
                $item['type'] = 'select';
            } elseif ($inputType == 'price') {
                $item['type'] = 'dynamic';
                $step = $this->_getPriceNavigationStep($store);

                if (!empty($step)) {
                    $item['min_range'] = $step;
                }
            }

            if (isset($item['type'])) {
                $item['title'] = $attribute->getStoreLabel();
                $item['position']  = ($inputType == 'price')
                    ? $attribute->getPosition()
                    : $attribute->getPosition() + 20;
                $item['attribute'] = $attribute->getAttributeCode();
            }

            if (
                !empty($item)
                && !$attribute->getIsFilterable()
                && !$attribute->getIsFilterableInSearch()
                && $attribute->getIsVisibleInAdvancedSearch()
            ) {
                $item['status'] = 'H';
            }
        }

        return $item;
    }
}
