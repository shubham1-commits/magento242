<?php
namespace Elsnertech\Zohointegration\Controller\adminhtml\Sync;
class OrderSyncall extends \Magento\Framework\App\Action\Action
{	
    protected $orderFactory;

    public function __construct(
    	\Magento\Backend\App\Action\Context $context,
    	\Elsnertech\Zohointegration\Model\Salesorder $salesorder,
 	    \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirect,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
    	parent::__construct($context);
    	$this->_salesorder = $salesorder;
	    $this->resultRedirect = $resultRedirect;
        $this->messageManager = $messageManager;
        $this->_orderFactory = $orderFactory;
    }

	public function execute()
	{
	    $order = $this->_orderFactory->create()->getCollection();
     	foreach($order as $id){
	     	$orderid = $id->getentity_id();
	     	$this->_salesorder->createOrder($orderid,"TRUE");
     	}
	    $resultRedirect = $this->resultRedirect->create();
	    $resultRedirect->setPath('zohoinventory/sync/order');
	    return $resultRedirect;
	    // $this->messageManager->addSuccess(__("Order Sync sucessfully"));    
    }
}