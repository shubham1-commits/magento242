<?php
namespace Elsnertech\Zohointegration\Model;
	
class SalesOrder1 extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'saled_order';

	protected $_cacheTag = 'saled_order';

	protected $_eventPrefix = 'saled_order';

	protected function _construct()
	{
		$this->_init('Elsnertech\Zohointegration\Model\ResourceModel\SalesOrder1');
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}
}