<?php
namespace Elsnertech\Chatboat\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Elsnertech\Chatboat\Model\Api $api,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->_customerSession = $customerSession;
        $this->_api = $api;
        return parent::__construct($context);
    }

    public function execute()
    {
        $customer = $this->_customerSession;
        if ($customer->isLoggedIn()) {
            $data = "👋 Hi ! I m a Bot. Let me know if you have any questions regarding our tool!";
            $data1 = "Select the topic or write your question below.";
            $res = '<div class="reci-wrapper" style="display: inline-block;"><p id="reci">'.$data.'</p></div><div class="reci-wrapper" style="display: inline-block;"><p id="reci">'.$data1.'</p></div>';
            $this->_api->chatStore(" ",$data);
            $this->_api->chatStore(" ",$data1);
            echo $res;

        } else {
            $res = '
                <div class="customerdiv">
                <div class="reci-wrapper" style="display: inline-block;"><p id="reci">
                <input type="text" id="name" placeholder="Enter NAME">
                <input type="email" id="email" placeholder="Enter EMAIL">
                </p></div></div>';
            echo $res;
        }
    }
}
