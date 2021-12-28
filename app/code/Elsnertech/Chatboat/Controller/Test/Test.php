<?php
namespace Elsnertech\Chatboat\Controller\Test;

class Test extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Elsnertech\Chatboat\Model\CustomerchatFactory $customerchatFactory,
		\Magento\Framework\Serialize\Serializer\Json $json,
		\Magento\Framework\View\Result\PageFactory $pageFactory)
	{
		$this->_pageFactory = $pageFactory;
		$this->json = $json;
		$this->_customerchatFactory = $customerchatFactory;
		return parent::__construct($context);
	}

	public function execute()
	{
		$data = $this->_customerchatFactory->create();
		$data = $data->load(83);
		$a = $data->getchat();
		//$a = explode(" ",$a);
		$da = '[sender.hi]';
		$a = $a.$da;
		$data->setData("chat",$a);
		$data->save();
	}
}