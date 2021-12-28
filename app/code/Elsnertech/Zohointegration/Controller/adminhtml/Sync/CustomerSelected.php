<?php
namespace Elsnertech\Zohointegration\Controller\adminhtml\Sync;

class CustomerSelected extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirect,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Elsnertech\Zohointegration\Model\Zohosync $zohosync
    ) {
        $this->_zohosync = $zohosync;
        $this->resultRedirect = $resultRedirect;
        $this->messageManager = $messageManager;
        $this->_customerFactory = $customerFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $selected = $this->getRequest()->getParams();

        if (isset($selected['excluded'])) {
            $id = $selected['excluded'];
            $data =  $this->_customerFactory->create()->getCollection();
            $collection = $data->addAttributeToFilter('entity_id', ['nin' => $id])->getdata();
            foreach ($collection as $key) {
                $id[]  = $key['entity_id']; 
            }
            $this->_zohosync->zohoCustomercron($id);
            
        } elseif (isset($selected['selected'])) {
            $selected = $selected['selected'];
            foreach ($selected as $key) {
               $id[] = $key;
            }
            $this->_zohosync->zohoCustomercron($id);
        }

        $resultRedirect = $this->resultRedirect->create();
        $resultRedirect->setPath('zohoinventory/sync/customer');
        return $resultRedirect;
        $this->messageManager->addSuccess(__("customer Sync sucessfully"));
    }
}