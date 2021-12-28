<?php
namespace Elsnertech\Chatboat\Model;

class Customerchat extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'customerchat';

    protected $_cacheTag = 'customerchat';

    protected $_eventPrefix = 'customerchat';

    protected function _construct()
    {
        $this->_init('Elsnertech\Chatboat\Model\ResourceModel\Customerchat');
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
