<?php
namespace Elsnertech\Chatboat\Controller\Forgotpassword;

class Forgotpassword extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Customer\Model\Session $customer,
		\Magento\Framework\View\Result\PageFactory $pageFactory)
	{
		$this->_pageFactory = $pageFactory;
		$this->customer = $customer;
		return parent::__construct($context);
	}

	public function execute()
	{
		$customer = $this->customer;
	    $customerId = $customer->getId();
		echo $customerId;
	}
}