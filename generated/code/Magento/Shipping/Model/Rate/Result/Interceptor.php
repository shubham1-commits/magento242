<?php
namespace Magento\Shipping\Model\Rate\Result;

/**
 * Interceptor class for @see \Magento\Shipping\Model\Rate\Result
 */
class Interceptor extends \Magento\Shipping\Model\Rate\Result implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Store\Model\StoreManagerInterface $storeManager)
    {
        $this->___init();
        parent::__construct($storeManager);
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'reset');
        return $pluginInfo ? $this->___callPlugins('reset', func_get_args(), $pluginInfo) : parent::reset();
    }

    /**
     * {@inheritdoc}
     */
    public function setError($error)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setError');
        return $pluginInfo ? $this->___callPlugins('setError', func_get_args(), $pluginInfo) : parent::setError($error);
    }

    /**
     * {@inheritdoc}
     */
    public function getError()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getError');
        return $pluginInfo ? $this->___callPlugins('getError', func_get_args(), $pluginInfo) : parent::getError();
    }

    /**
     * {@inheritdoc}
     */
    public function append($result)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'append');
        return $pluginInfo ? $this->___callPlugins('append', func_get_args(), $pluginInfo) : parent::append($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllRates()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getAllRates');
        return $pluginInfo ? $this->___callPlugins('getAllRates', func_get_args(), $pluginInfo) : parent::getAllRates();
    }

    /**
     * {@inheritdoc}
     */
    public function getRateById($id)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getRateById');
        return $pluginInfo ? $this->___callPlugins('getRateById', func_get_args(), $pluginInfo) : parent::getRateById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getRatesByCarrier($carrier)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getRatesByCarrier');
        return $pluginInfo ? $this->___callPlugins('getRatesByCarrier', func_get_args(), $pluginInfo) : parent::getRatesByCarrier($carrier);
    }

    /**
     * {@inheritdoc}
     */
    public function asArray()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'asArray');
        return $pluginInfo ? $this->___callPlugins('asArray', func_get_args(), $pluginInfo) : parent::asArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getCheapestRate()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getCheapestRate');
        return $pluginInfo ? $this->___callPlugins('getCheapestRate', func_get_args(), $pluginInfo) : parent::getCheapestRate();
    }

    /**
     * {@inheritdoc}
     */
    public function sortRatesByPrice()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'sortRatesByPrice');
        return $pluginInfo ? $this->___callPlugins('sortRatesByPrice', func_get_args(), $pluginInfo) : parent::sortRatesByPrice();
    }

    /**
     * {@inheritdoc}
     */
    public function updateRatePrice($packageCount)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'updateRatePrice');
        return $pluginInfo ? $this->___callPlugins('updateRatePrice', func_get_args(), $pluginInfo) : parent::updateRatePrice($packageCount);
    }
}
