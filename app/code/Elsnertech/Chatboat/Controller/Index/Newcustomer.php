<?php
namespace Elsnertech\Chatboat\Controller\Index;

class Newcustomer extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $_chatbotFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $request,
        \Elsnertech\Chatboat\Model\ChatbotFactory $ChatbotFactory,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_pageFactory = $pageFactory;
        $this->request = $request;
        $this->_chatbotFactory = $ChatbotFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        $name = $this->request->getParam('name');
        $e = $this->request->getParam('email');
        if (!empty($name)) {
            $customer = $this->_chatbotFactory->create();
            $customer->setData('name', $name);
            $customer->setData('email', $e);
            $customer->save();
            $res = '<div class="reci-wrapper" style="display: inline-block;"><p id="reci">click agree to Sign-In</p></div>';
            echo $res;
        } else {
            $res = '<div class="reci-wrapper" style="display: inline-block;"><p id="reci">emptydata</p></div>';
            echo $res;
        }
    }
}
