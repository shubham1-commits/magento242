<?php
namespace Elsnertech\Zohointegration\Controller\Index;

class Test extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Elsnertech\Zohointegration\Model\CatalogProductFactory $catalogproduct1Factory,
		\Magento\Framework\View\Result\PageFactory $pageFactory)
	{
		$this->_pageFactory = $pageFactory;
		$this->_catalogproduct1Factory = $catalogproduct1Factory;
		return parent::__construct($context);
	}

	public function execute()
	{
		$post = $this->_catalogproduct1Factory->create();
		$collection = $post->getCollection();
		foreach($collection as $item){
			echo "<pre>";
			print_r($item->getData());
			echo "</pre>";
		}
		exit();
	}
}