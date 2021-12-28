<?php
namespace Elsnertech\Zohointegration\Controller\adminhtml\Sync;
class CustomerSyncall extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
	protected $_customerFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Customer\Model\CustomerFactory $customerFactory,
		\Elsnertech\Zohointegration\Model\Zohosync $zohosync,
		\Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirect,
        \Magento\Framework\View\Result\PageFactory $pageFactory)
	{
		$this->_pageFactory = $pageFactory;
		$this->_zohosync = $zohosync;
	    $this->resultRedirect = $resultRedirect;
        $this->messageManager = $messageManager;
		$this->_customerFactory = $customerFactory;
		return parent::__construct($context);
	}

	public function execute()
	{
		$Collection = $this->_customerFactory->create()->getCollection();     
		foreach($Collection as $customer)
		{
		    $id[] = $customer->getid();
		}
		$this->_zohosync->zohoCustomercron($id);
		$resultRedirect = $this->resultRedirect->create();
        $resultRedirect->setPath('zohoinventory/sync/customer');
        return $resultRedirect;
        $this->messageManager->addSuccess(__("customer Sync sucessfully"));
	}
}