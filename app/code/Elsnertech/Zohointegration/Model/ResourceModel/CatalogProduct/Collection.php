<?php
namespace Elsnertech\Zohointegration\Model\ResourceModel\CatalogProduct;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    protected $_eventPrefix = 'catalog_product_entity';
    protected $_eventObject = 'post_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Elsnertech\Zohointegration\Model\CatalogProduct', 'Elsnertech\Zohointegration\Model\ResourceModel\CatalogProduct');
    }

}