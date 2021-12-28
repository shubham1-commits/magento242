<?php
namespace Elsnertech\Chatboat\Controller\Forgotpassword;

class Index extends \Magento\Framework\App\Action\Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Elsnertech\Chatboat\Model\Api $api,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement
    ) {
        $this->customer = $customerSession;
        $this->_api = $api;
        $this->request = $request;
        $this->customerAccountManagement = $customerAccountManagement;
        parent::__construct($context);
    }


    public function execute()
    {
        $post = $this->request->getParam('numone');
        $customer = $this->customer;
        $customerName = $customer->getName();
        $customerId = $customer->getId();
        $email = 'shubham.elsner@gmail.com';
        if (!\Zend_Validate::is($email, 'EmailAddress')) {
            $this->session->setForgottenEmail($email);
            $this->messageManager->addErrorMessage(__('Please correct the email address.'));
        }

        try {
            $this->customerAccountManagement->initiatePasswordReset($email, $this->customerAccountManagement::EMAIL_RESET);
            $this->_api->chatStore(" ","Email is send");
        } 
    }
}
