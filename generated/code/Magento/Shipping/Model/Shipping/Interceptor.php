<?php
namespace Magento\Shipping\Model\Shipping;

/**
 * Interceptor class for @see \Magento\Shipping\Model\Shipping
 */
class Interceptor extends \Magento\Shipping\Model\Shipping implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Shipping\Model\Config $shippingConfig, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Shipping\Model\CarrierFactory $carrierFactory, \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory, \Magento\Shipping\Model\Shipment\RequestFactory $shipmentRequestFactory, \Magento\Directory\Model\RegionFactory $regionFactory, \Magento\Framework\Math\Division $mathDivision, \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry, ?\Magento\Quote\Model\Quote\Address\RateRequestFactory $rateRequestFactory = null, ?\Magento\Shipping\Model\Rate\PackageResultFactory $packageResultFactory = null, ?\Magento\Shipping\Model\Rate\CarrierResultFactory $carrierResultFactory = null)
    {
        $this->___init();
        parent::__construct($scopeConfig, $shippingConfig, $storeManager, $carrierFactory, $rateResultFactory, $shipmentRequestFactory, $regionFactory, $mathDivision, $stockRegistry, $rateRequestFactory, $packageResultFactory, $carrierResultFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getResult');
        return $pluginInfo ? $this->___callPlugins('getResult', func_get_args(), $pluginInfo) : parent::getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function setOrigData($data)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setOrigData');
        return $pluginInfo ? $this->___callPlugins('setOrigData', func_get_args(), $pluginInfo) : parent::setOrigData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function resetResult()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'resetResult');
        return $pluginInfo ? $this->___callPlugins('resetResult', func_get_args(), $pluginInfo) : parent::resetResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'getConfig');
        return $pluginInfo ? $this->___callPlugins('getConfig', func_get_args(), $pluginInfo) : parent::getConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'collectRates');
        return $pluginInfo ? $this->___callPlugins('collectRates', func_get_args(), $pluginInfo) : parent::collectRates($request);
    }

    /**
     * {@inheritdoc}
     */
    public function collectCarrierRates($carrierCode, $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'collectCarrierRates');
        return $pluginInfo ? $this->___callPlugins('collectCarrierRates', func_get_args(), $pluginInfo) : parent::collectCarrierRates($carrierCode, $request);
    }

    /**
     * {@inheritdoc}
     */
    public function composePackagesForCarrier($carrier, $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'composePackagesForCarrier');
        return $pluginInfo ? $this->___callPlugins('composePackagesForCarrier', func_get_args(), $pluginInfo) : parent::composePackagesForCarrier($carrier, $request);
    }

    /**
     * {@inheritdoc}
     */
    public function collectRatesByAddress(\Magento\Framework\DataObject $address, $limitCarrier = null)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'collectRatesByAddress');
        return $pluginInfo ? $this->___callPlugins('collectRatesByAddress', func_get_args(), $pluginInfo) : parent::collectRatesByAddress($address, $limitCarrier);
    }

    /**
     * {@inheritdoc}
     */
    public function setCarrierAvailabilityConfigField($code = 'active')
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setCarrierAvailabilityConfigField');
        return $pluginInfo ? $this->___callPlugins('setCarrierAvailabilityConfigField', func_get_args(), $pluginInfo) : parent::setCarrierAvailabilityConfigField($code);
    }
}
