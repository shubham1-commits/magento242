<?php
namespace Elsnertech\Chatboat\Block;

class Initialchatboat extends \Magento\Framework\View\Element\Template
{
    public $scopeConfig;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customer,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        // \Elsnertech\Chatboat\Model\ResourceModel\Customerchat\CollectionFactory $CustomerchatCollectionFactory,
        \Elsnertech\Chatboat\Model\CustomerchatFactory $customerchatFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
        $this->customer = $customer;
        $this->json = $json;
        $this->scopeConfig = $scopeConfig;
        $this->_customerchatFactory = $customerchatFactory;
        // $this->_customerchat = $CustomerchatCollectionFactory;
        parent::__construct($context);
    }

    public function getChat()
    {
        $customer = $this->customer;
        $customerId = $customer->getId();
        $data = $this->_customerchatFactory->create();
        $data = $data->getCollection()->addFilter('customerid',['in' => $customerId]);
        return $data;
    }

    public function getStoreUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function GeneralUrl()
    {
        $valueFromConfig = $this->scopeConfig->getValue(
            'chatbot/general/enable1',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $valueFromConfig;
    }


    public function CustomerUrl()
    {
        $valueFromConfig = $this->scopeConfig->getValue(
            'chatbot/general/enable2',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $valueFromConfig;
    }

    public function OrderUrl()
    {
        $valueFromConfig = $this->scopeConfig->getValue(
            'chatbot/general/enable3',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $valueFromConfig;
    }

    public function ProductUrl()
    {
        $valueFromConfig = $this->scopeConfig->getValue(
            'chatbot/general/enable4',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $valueFromConfig;
    }

    public function getName()
    {
        $valueFromConfig = $this->scopeConfig->getValue(
            'chatbot/general1/chatbotname',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $valueFromConfig;
    }
}
