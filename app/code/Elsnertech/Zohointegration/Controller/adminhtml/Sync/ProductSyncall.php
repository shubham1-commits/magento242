<?php
namespace Elsnertech\Zohointegration\Controller\adminhtml\Sync;
class ProductSyncall extends \Magento\Framework\App\Action\Action
{	
	protected $productCollectionFactory;

    public function __construct(
      \Magento\Framework\App\Action\Context $context,
      \Elsnertech\Zohointegration\Model\Zohosync $zohosync,
      \Magento\Framework\Message\ManagerInterface $messageManager,
      \Magento\Framework\Controller\Result\RedirectFactory $resultRedirect,
      \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ){
      $this->productCollectionFactory = $productCollectionFactory;
      $this->_zohosync = $zohosync;
      $this->resultRedirect = $resultRedirect;
      $this->messageManager = $messageManager;
      parent::__construct($context);
    }

  	public function execute()
  	{
     	$collection = $this->productCollectionFactory->create();
      foreach ($collection as $product) {
        $id[] = $product->getid();
      }
      $this->_zohosync->zohoProductcron($id);
      $resultRedirect = $this->resultRedirect->create();
      $resultRedirect->setPath('zohoinventory/sync/product');
      return $resultRedirect;
      $this->messageManager->addSuccess(__("Product Sync sucessfully"));
    }
}
