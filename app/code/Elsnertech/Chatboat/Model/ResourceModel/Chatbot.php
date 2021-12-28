<?php
namespace Elsnertech\Chatboat\Model\ResourceModel;

class Chatbot extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }
    
    protected function _construct()
    {
        $this->_init('newcustomer', 'post_id');
    }
}
