<?php
namespace Elsnertech\Chatboat\Model;

class Chatbot extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'newcustomer';

    protected $_cacheTag = 'newcustomer';

    protected $_eventPrefix = 'newcustomer';

    protected function _construct()
    {
        $this->_init('Elsnertech\Chatboat\Model\ResourceModel\Chatbot');
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
