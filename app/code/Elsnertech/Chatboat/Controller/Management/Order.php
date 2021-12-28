<?php
namespace Elsnertech\Chatboat\Controller\Management;

class Order extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $res = '<button id="orderlist" class="controller">⚡️Order List</button>
            <button id="orderstatus" class="controller">Order status</button>
            <button id="goback" class="controller">Go Back</button>';
        echo $res;
    }
}
