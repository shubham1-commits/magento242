<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model\Rule\Condition;

use Magento\Catalog\Model\ProductCategoryList;
use Magento\SalesRule\Model\Rule\Condition\Product as OriginalProductCondition;
use Magento\Framework\Event\ManagerInterface as EventManager;

/**
 * Class Product
 */
class Product extends OriginalProductCondition
{
    /**
     * @var EventManager
     */
    protected $eventManger;

    /**
     * Product constructor.
     *
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Backend\Helper\Data $backendData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param EventManager $eventManger
     * @param array $data
     * @param ProductCategoryList|null $categoryList
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Eav\Model\Config $config,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        EventManager $eventManger,
        array $data = [],
        ProductCategoryList $categoryList = null
    ) {
        parent::__construct(
            $context,
            $backendData,
            $config,
            $productFactory,
            $productRepository,
            $productResource,
            $attrSetCollection,
            $localeFormat,
            $data,
            $categoryList
        );
        $this->eventManger = $eventManger;
    }

    /**
     * Validate Product Rule Condition
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magento\Quote\Model\Quote\Item $abstractModel
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function validate(\Magento\Framework\Model\AbstractModel $abstractModel)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->extractProduct($abstractModel);

        //use parent product to get category id
        if ($abstractModel->getParentItem() && $this->getAttribute() == 'category_ids') {
            return $this->validateAttribute($abstractModel->getParentItem()->getProduct()->getAvailableInCategories());
        }

        $product->setData('quote_item_sku', $abstractModel->getSku());

        if ($product->getExtensionAttributes()) {
            $this->updateProductStockData($product, $abstractModel);
        }

        // Fix for multiselect attributes validation
        $attr = $this->_productResource->getAttribute($this->getAttribute());
        if ($attr && $attr->getFrontendInput() == 'multiselect') {
            $value = $product->getData($this->getAttribute());
            if ($value === null) {
                $value = $this->_productResource->getAttributeRawValue(
                    $product->getId(),
                    $this->getAttribute(),
                    $abstractModel->getStoreId()
                );
                $value = !empty($value) ? $value : '';
                $product->setData($this->getAttribute(), $value);
            }
        }

        $abstractModel->setProduct($product);

        $result = parent::validate($abstractModel);
        /** @var \MageWorx\ShippingRules\Model\Rule $rule */
        $rule = $this->getRule();
        if ($rule instanceof \MageWorx\ShippingRules\Model\Rule) {
            $rule->logConditions($this->getAttribute(), $result);
        }

        return $result;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $abstractModel
     * @return \Magento\Catalog\Model\Product
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function extractProduct(\Magento\Framework\Model\AbstractModel $abstractModel): \Magento\Catalog\Model\Product
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $abstractModel->getProduct();
        if (!$product instanceof \Magento\Catalog\Model\Product) {
            if ($abstractModel->getProductId()) {
                $product = $this->productRepository->getById($abstractModel->getProductId());
            } else {
                $product = $this->_productFactory->create();
            }
        }

        $this->eventManger->dispatch(
            'mageworx_shippingrules_condition_extract_product',
            [
                'product' => $product,
                'model' => $abstractModel
            ]
        );

        return $product;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Framework\Model\AbstractModel|\Magento\Quote\Model\Quote\Item $quoteItem
     */
    protected function updateProductStockData(
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\Model\AbstractModel $quoteItem
    ) {
        /** @var \Magento\CatalogInventory\Model\Stock\Item $stockItem */
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        if ($stockItem) {
            $isInStock                = $stockItem->getIsInStock();
            $stockQty                 = $stockItem->getQty();
            $isBackordered            = $quoteItem->getTotalQty() > $stockItem->getQty();
            $qtyOrderedToStockQtyDiff = $quoteItem->getTotalQty() - $stockItem->getQty();
            $qtyBackordered           = $qtyOrderedToStockQtyDiff < 0 ? 0 : $qtyOrderedToStockQtyDiff;

            $product->setData('stock_item_stock_status', $isInStock);
            $product->setData('stock_item_qty', $stockQty);
            $product->setData('stock_item_is_backordered', $isBackordered);
            $product->setData('stock_item_qty_backordered', $qtyBackordered);
        }
    }

    /**
     * Add special attributes
     *
     * @param array $attributes
     * @return void
     */
    protected function _addSpecialAttributes(array &$attributes)
    {
        parent::_addSpecialAttributes($attributes);
        $attributes['quote_item_sku'] = __('Cart Item SKU (including custom options SKUs)');

        $attributes['stock_item_stock_status']    = __('Stock Item Status');
        $attributes['stock_item_qty']             = __('Qty');
        $attributes['stock_item_is_backordered']  = __('Backordered');
        $attributes['stock_item_qty_backordered'] = __('Qty Backordered');
    }

    /**
     * Prepares values options to be used as select options or hashed array
     * Result is stored in following keys:
     *  'value_select_options' - normal select array: array(array('value' => $value, 'label' => $label), ...)
     *  'value_option' - hashed array: array($value => $label, ...),
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareValueOptions()
    {
        // Check that both keys exist. Maybe somehow only one was set not in this routine, but externally.
        $selectReady = $this->getData('value_select_options');
        $hashedReady = $this->getData('value_option');
        if ($selectReady && $hashedReady) {
            return $this;
        }

        // Get array of select options. It will be used as source for hashed options
        $selectOptions = null;
        if ($this->getAttribute() === 'attribute_set_id') {
            $entityTypeId  = $this->_config->getEntityType(\Magento\Catalog\Model\Product::ENTITY)->getId();
            $selectOptions = $this->_attrSetCollection
                ->setEntityTypeFilter($entityTypeId)
                ->load()
                ->toOptionArray();
        } elseif ($this->getAttribute() === 'type_id') {
            foreach ($selectReady as $value => $label) {
                if (is_array($label) && isset($label['value'])) {
                    $selectOptions[] = $label;
                } else {
                    $selectOptions[] = ['value' => $value, 'label' => $label];
                }
            }
            $selectReady = null;
        } elseif ($this->getAttribute() === 'stock_item_stock_status') {
            $selectOptions = [
                [
                    'value' => 0,
                    'label' => __('Out Of Stock')
                ],
                [
                    'value' => 1,
                    'label' => __('In Stock')
                ],
            ];
        } elseif ($this->getAttribute() === 'stock_item_is_backordered') {
            $selectOptions = [
                [
                    'value' => 0,
                    'label' => __('No')
                ],
                [
                    'value' => 1,
                    'label' => __('Yes')
                ],
            ];
        } elseif (is_object($this->getAttributeObject())) {
            $attributeObject = $this->getAttributeObject();
            if ($attributeObject->usesSource()) {
                if ($attributeObject->getFrontendInput() == 'multiselect') {
                    $addEmptyOption = false;
                } else {
                    $addEmptyOption = true;
                }
                $selectOptions = $attributeObject->getSource()->getAllOptions($addEmptyOption);
            }
        }

        $this->_setSelectOptions($selectOptions, $selectReady, $hashedReady);

        return $this;
    }

    /**
     * Retrieve input type
     *
     * @return string
     */
    public function getInputType()
    {
        $result = parent::getInputType();

        if ($this->getAttribute() === 'stock_item_stock_status') {
            return 'select';
        }

        if ($this->getAttribute() === 'stock_item_is_backordered') {
            return 'select';
        }

        return $result;
    }

    /**
     * Retrieve value element type
     *
     * @return string
     */
    public function getValueElementType()
    {
        $result = parent::getValueElementType();

        if ($this->getAttribute() === 'stock_item_stock_status') {
            return 'select';
        }

        if ($this->getAttribute() === 'stock_item_is_backordered') {
            return 'select';
        }

        return $result;
    }

    /**
     * Case and type insensitive comparison of values
     *
     * @param string|int|float $validatedValue
     * @param string|int|float $value
     * @param bool $strict
     * @return bool
     */
    protected function _compareValues($validatedValue, $value, $strict = true)
    {
        if ($strict && is_numeric($validatedValue) && is_numeric($value)) {
            return ($validatedValue + 1 - 1) == ($value + 1 - 1);
        } elseif ($strict && $validatedValue === null && is_numeric($value)) {
            return (int)$validatedValue == $value;
        } elseif ($strict && is_bool($validatedValue) && is_numeric($value)) {
            return (int)$validatedValue == $value;
        } else {
            $validatePattern = preg_quote($validatedValue, '~');
            if ($strict) {
                $validatePattern = '^' . $validatePattern . '$';
            }

            return (bool)preg_match('~' . $validatePattern . '~iu', $value);
        }
    }
}
