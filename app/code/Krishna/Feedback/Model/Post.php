<?php
namespace Krishna\Feedback\Model;
class Post extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'userfeedback';

	protected $_cacheTag = 'userfeedback';

	protected $_eventPrefix = 'userfeedback';

	protected function _construct()
	{
		$this->_init('Krishna\Feedback\Model\ResourceModel\Post');
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