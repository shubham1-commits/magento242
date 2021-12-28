<?php
namespace Krishna\Feedback\Controller\Index;

class Save extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
	protected $_postFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
		\Krishna\Feedback\Model\PostFactory $postFactory)
	{
		$this->_pageFactory = $pageFactory;
		$this->_postFactory = $postFactory;
		return parent::__construct($context);
	}

	public function execute()
	{
        $data = $this->getRequest()->getPost();	
        $post = $this->_postFactory->create();
        $post->setData('name', $data['name']);
        $post->setData('email', $data['email']);
        $post->setData('mobileno', $data['mobileno']);
        $post->setData('comment', $data['comment']);
        $post->save();
        $this->_redirect('*/*/');
        $this->messageManager->addSuccess(__('Your values has beeen submitted successfully.'));


	}
}
