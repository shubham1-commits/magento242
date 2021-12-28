<?php

namespace Searchanise\SearchAutocomplete\Controller\Add;

class Index extends \Magento\Framework\App\Action\Action
{
    const STATUS_SUCCESS    = 'OK';
    const STATUS_NO_ACTION  = 'NO_ACTION';
    const STATUS_FAILED     = 'FAILED';

    const DEFAULT_GROUP_QUANTITY    = 1;
    const DEFAULT_BUNDLE_QUANTITY   = 1;

    const MAX_CONFIGURABLE_INTERATION = 30;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->productFactory = $productFactory;
        $this->cart = $cart;
        $this->stockRegistry = $stockRegistry;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    /**
     * Async
     *
     * {@inheritDoc}
     *
     * @see \Magento\Framework\App\ActionInterface::execute()
     */
    public function execute()
    {
        $request = $this->getRequest();

        if ($request->getParam('test') == 'Y') {
            $result = $this->testAddToCart();
            return $this->resultJsonFactory->create()->setData($result);
        }

        $productId = $request->getParam('id');
        $quantity = (int)$request->getParam('quantity');

        try {
            $response['status'] = $this->addToCart($productId, $quantity);
        } catch (\Exception $e) {
            $response['status'] = self::STATUS_FAILED;
            $response['message'] = $e->getMessage();
        }

        if ($response['status'] == self::STATUS_SUCCESS) {
            $response['redirect'] = $this->_url->getUrl('checkout/cart');
        } else {
            // Unable to add product to the cart. Just redirect customer to the product page
            $product = $this->productFactory->create()->load($productId);

            if ($product) {
                $response['redirect'] = $product->getProductUrl();
            }
        }

        return $this->resultJsonFactory->create()->setData($response);
    }

    /**
     * Add to cart test functionality
     */
    private function testAddToCart()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(3600);

        $errors = [];
        $this->cart->truncate()->save();

        $request = $this->getRequest();
        $page = $request->getParam('page', 1);
        $size = $request->getParam('size', 0);

        $products = $this->productFactory->create()->getCollection();

        if (!empty($size)) {
            $products->setCurPage($page)->setPageSize($size);
        }

        $products->load();

        foreach ($products as $product) {
            if (!$this->getStockItem($product)->getIsInStock()) {
                continue;
            }

            try {
                $this->addToCart($product->getId());
                $this->cart->truncate();
            } catch (\Exception $e) {
                $errors[$product->getId()] = $e->getMessage();
            }
        }
        $this->cart->save();

        return [
            'page'   => $page,
            'size'   => $size,
            'count'  => $products->count(),
            'total'  => $this->productFactory->create()->getCollection()->getSize(),
            'errors' => $errors
        ];
    }

    /**
     * Add to cart function
     *
     * @param  number $productId Product identifier
     * @param  number $qty       Quanity value
     * @param  array  $options   Add to cart options
     * @return string
     * @thrown \Exception
     */
    private function addToCart($productId, $qty = 1, $options = [])
    {
        $product = $this->productFactory->create()->load($productId);

        if (!$product || !$qty) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Incorrect product or quantity parameter'));
        }

        $params = [
            'qty' => $qty,
        ];

        if (!empty($options)) {
            $params['options'] = $options;
        }

        switch ($product->getTypeId()) {
            case \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE:
                $this->setBundleOptions($product, $params);
                // We have to reload the product to avoid fatal error.
                // It happended only for bundle product
                $product = $this->productFactory->create()->load($productId);
                break;
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $this->setConfigurableOptions($product, $params);
                break;
            case \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE:
                $this->setGroupedOptions($product, $params);
                break;
            case \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE:
                $this->setDownloadableOptions($product, $params);
                break;
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
                // Simple, no action
                break;
            default:
                // Not supported product types
                return self::STATUS_NO_ACTION;
        }

        if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            for ($i = 0; $i < self::MAX_CONFIGURABLE_INTERATION; $i++) {
                try {
                    $this->cart->addProduct($product, $params);
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                    $this->setConfigurableOptions($product, $params);
                    continue;
                }

                $error = false;
                break;
            }

            if (!empty($error)) {
                throw new \Magento\Framework\Exception\LocalizedException($error);
            }
        } else {
            $this->cart->addProduct($product, $params);
        }

        $this->cart->save();

        return self::STATUS_SUCCESS;
    }

    /**
     * Set required product options
     *
     * @param  object $product Magento product model
     * @param  array  $params  Add to cart parameters
     * @return boolean
     */
    private function setOptions(\Magento\Catalog\Model\Product $product, array &$params)
    {
        foreach ($product->getOptions() as $option) {
            if (!$option->getIsRequire()) {
                continue;
            }

            switch ($option->getType()) {
                case \Magento\Catalog\Model\Product\Option::OPTION_TYPE_DROP_DOWN:
                case \Magento\Catalog\Model\Product\Option::OPTION_TYPE_RADIO:
                    $values = $option->getValues();
                    $v = current($values);
                    $params['options'][$option->getId()] = $v->getData()['option_type_id'];
                    break;
                case \Magento\Catalog\Model\Product\Option::OPTION_TYPE_CHECKBOX:
                case \Magento\Catalog\Model\Product\Option::OPTION_TYPE_MULTIPLE:
                    $values = $option->getValues();

                    foreach ($values as $v) {
                        $params['options'][$option->getId()][] = $v->getData()['option_type_id'];
                    }
                    break;
                default:
                    // Not suported
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Option type is not supported: ') . $option->getType()
                    );
            }
        }

        return true;
    }

    /**
     * Set links for downloadable products
     *
     * @param  object $product Magento product model
     * @param  array  $params  Add to cart parameters
     * @return boolean
     */
    private function setDownloadableOptions(\Magento\Catalog\Model\Product $product, array &$params)
    {
        $links = $product->getTypeInstance()->getLinks($product);

        if (empty($links)) {
            return false;
        }

        foreach ($links as $link) {
            $params['links'][] = $link->getId();
        }

        return true;
    }

    /**
     * Set configurable options for add to cart
     *
     * @param  object $product Magento product model
     * @param  array  $params  Add to cart params
     * @return boolean
     */
    private function setConfigurableOptions(\Magento\Catalog\Model\Product $product, array &$params)
    {
        $configurableAttributeOptions = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);
        $bNextInteration = !empty($params['super_attribute']);
        $bContinue = false;

        foreach ($configurableAttributeOptions as $attribute) {
            $allValues = array_column($attribute['values'], 'value_index');
            $currentProductValue = $product->getData($attribute['attribute_code']);

            if (in_array($currentProductValue, $allValues)) {
                $params['super_attribute'][$attribute['attribute_id']] = $currentProductValue;
            } elseif (is_array($allValues)) {
                if (!empty($params['super_attribute'][$attribute['attribute_id']])) {
                    if (!$bContinue) {
                        $key = array_search($params['super_attribute'][$attribute['attribute_id']], $allValues);

                        if (key_exists($key + 1, $allValues)) {
                            $params['super_attribute'][$attribute['attribute_id']] = $allValues[$key + 1];
                            $bContinue = true;
                        }
                    }
                } else {
                    $params['super_attribute'][$attribute['attribute_id']] = current($allValues);
                }
            }
        }

        return !$bNextInteration || $bContinue;
    }

    /**
     * Set bundle options for add to cart
     *
     * @param  object $product Magento product model
     * @param  array  $params  Add to cart params
     * @return boolean
     */
    private function setBundleOptions(\Magento\Catalog\Model\Product $product, &$params)
    {
        $optionCollection = $product->getTypeInstance()->getOptionsCollection($product);
        $selectionCollection = $product->getTypeInstance()->getSelectionsCollection(
            $product->getTypeInstance()->getOptionsIds($product),
            $product
        );
        $options = $optionCollection->appendSelections($selectionCollection);

        $bundle_option = $bundle_option_qty = [];

        foreach ($options as $option) {
            $_selections = $option->getSelections();

            foreach ($_selections as $selection) {
                $bundle_option[$option->getOptionId()][] = $selection->getSelectionId();
                break;
            }
        }

        $params = array_merge(
            $params,
            [
            'product'           => $product->getId(),
            'bundle_option'     => $bundle_option,
            'related_product'   => null,
            ]
        );

        return true;
    }

    /**
     * Set grouped options for add to cart
     *
     * @param  object $product Magento product model
     * @param  array  $params  Add to cart params
     * @return boolean
     */
    private function setGroupedOptions(\Magento\Catalog\Model\Product $product, &$params)
    {
        $childs = $product->getTypeInstance()->getAssociatedProducts($product);

        foreach ($childs as $c_product) {
            if ($this->getStockItem($c_product)->getIsInStock()) {
                $params['super_group'][$c_product->getId()] = self::DEFAULT_GROUP_QUANTITY;
            }
        }

        return true;
    }

    /**
     * Returns stock item
     *
     * @param  \Magento\Catalog\Model\Product $product Product model
     * @return mixed
     */
    private function getStockItem(
        \Magento\Catalog\Model\Product $product
    ) {
        $stockItem = null;

        if (!empty($product)) {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
        }

        return $stockItem;
    }
}
