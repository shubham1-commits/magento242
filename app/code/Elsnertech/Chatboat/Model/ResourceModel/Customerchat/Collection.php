<?php
namespace Elsnertech\Chatboat\Model\ResourceModel\Customerchat;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'customerchat';
    protected $_eventObject = 'post_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Elsnertech\Chatboat\Model\Customerchat', 'Elsnertech\Chatboat\Model\ResourceModel\Customerchat');
    }
}
