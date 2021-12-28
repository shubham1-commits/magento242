<?php
namespace Magento\Customer\Controller\Ajax\Login;

/**
 * Interceptor class for @see \Magento\Customer\Controller\Ajax\Login
 */
class Interceptor extends \Magento\Customer\Controller\Ajax\Login implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Customer\Model\Session $customerSession, \Magento\Framework\Json\Helper\Data $helper, \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement, \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory, \Magento\Framework\Controller\Result\RawFactory $resultRawFactory, ?\Magento\Framework\Stdlib\CookieManagerInterface $cookieManager = null, ?\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory = null)
    {
        $this->___init();
        parent::__construct($context, $customerSession, $helper, $customerAccountManagement, $resultJsonFactory, $resultRawFactory, $cookieManager, $cookieMetadataFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function setAccountRedirect($value)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setAccountRedirect');
        return $pluginInfo ? $this->___callPlugins('setAccountRedirect', func_get_args(), $pluginInfo) : parent::setAccountRedirect($value);
    }

    /**
     * {@inheritdoc}
     */
    public function setScopeConfig($value)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setScopeConfig');
        return $pluginInfo ? $this->___callPlugins('setScopeConfig', func_get_args(), $pluginInfo) : parent::setScopeConfig($value);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'dispatch');
        return $pluginInfo ? $this->___callPlugins('dispatch', func_get_args(), $pluginInfo) : parent::dispatch($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getActionFlag()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getActionFlag');
        return $pluginInfo ? $this->___callPlugins('getActionFlag', func_get_args(), $pluginInfo) : parent::getActionFlag();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getRequest');
        return $pluginInfo ? $this->___callPlugins('getRequest', func_get_args(), $pluginInfo) : parent::getRequest();
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getResponse');
        return $pluginInfo ? $this->___callPlugins('getResponse', func_get_args(), $pluginInfo) : parent::getResponse();
    }
}
