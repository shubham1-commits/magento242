<?php
namespace Elsnertech\Zohointegration\Model\ResourceModel\SalesOrder1;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'entity_id';
	protected $_eventPrefix = 'saled_order';
	protected $_eventObject = 'post_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('Elsnertech\Zohointegration\Model\SalesOrder1', 'Elsnertech\Zohointegration\Model\ResourceModel\SalesOrder1');
	}

}