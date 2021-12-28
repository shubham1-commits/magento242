<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Ui\DataProvider\Method\Form;

use MageWorx\ShippingRules\Model\ResourceModel\Method\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class MethodDataProvider
 */
class MethodDataProvider extends AbstractDataProvider
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param PoolInterface $pool
     * @param RequestInterface $request
     * @param DataPersistorInterface $dataPersistor
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        PoolInterface $pool,
        RequestInterface $request,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->pool         = $pool;
        $this->request      = $request;
        $this->storeManager = $storeManager;
        $this->collection   = $collectionFactory->create();
    }


    /**
     * Get meta from all modifiers
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getMeta()
    {
        $meta      = parent::getMeta();
        $modifiers = $this->pool->getModifiersInstances();
        /** @var ModifierInterface $modifier */
        foreach ($modifiers as $modifier) {
            $meta = $modifier->modifyMeta($meta);
        }

        return $meta;
    }

    /**
     * Get data from all modifiers and items from the collection
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getData()
    {
        if (!empty($this->data)) {
            return $this->data;
        }
        $items         = $this->collection->getItems();
        $isSingleStore = $this->storeManager->isSingleStoreMode();
        /** @var \MageWorx\ShippingRules\Model\Carrier\Method $method */
        foreach ($items as $method) {
            $this->data[$method->getId()]                 = $method->getData();
            $this->data[$method->getId()]['single_store'] = $isSingleStore;
        }

        $modifiers = $this->pool->getModifiersInstances();
        /** @var ModifierInterface $modifier */
        foreach ($modifiers as $modifier) {
            $this->data = $modifier->modifyData($this->data);
        }

        return $this->data;
    }
}
