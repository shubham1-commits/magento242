<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model\Config\Source\Shipping;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Shipping\Model\Config as ShippingConfig;

/**
 * Class Methods
 */
class Methods implements ArrayInterface
{
    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $shippingConfig;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    private $methodsAsArray = [];

    /**
     * @var array
     */
    private $methodsAsOptionArray = [];

    /**
     * @var array|null
     */
    private $dhlMethods;

    /**
     * @param ShippingConfig $shippingConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ShippingConfig $shippingConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->shippingConfig = $shippingConfig;
        $this->scopeConfig    = $scopeConfig;
    }

    /**
     * @param bool $isActiveOnlyFlag
     * @param bool $forceRenew
     * @return array
     */
    public function toArray($isActiveOnlyFlag = false, $forceRenew = false)
    {
        $intFlag = (int)$isActiveOnlyFlag;
        if (!empty($this->methodsAsArray[$intFlag]) && !$forceRenew) {
            return $this->methodsAsArray[$intFlag];
        }

        $methodsAsOptionArray = $this->toOptionArray($isActiveOnlyFlag);
        $methodsAsArray       = [];
        foreach ($methodsAsOptionArray as $carrier) {
            if (empty($carrier['value'])) {
                continue;
            }
            $carrierMethods = $carrier['value'];
            foreach ($carrierMethods as $carrierMethod) {
                if (empty($carrierMethod['value'])) {
                    continue;
                }
                $methodsAsArray[] = $carrierMethod['value'];
            }
        }

        $this->methodsAsArray[$intFlag] = $methodsAsArray;

        return $this->methodsAsArray[$intFlag];
    }

    /**
     * Return array of carriers.
     * If $isActiveOnlyFlag is set to true, will return only active carriers
     *
     * @param bool $isActiveOnlyFlag
     * @return array
     */
    public function toOptionArray($isActiveOnlyFlag = false)
    {
        $intFlag = (int)$isActiveOnlyFlag;
        if (!empty($this->methodsAsOptionArray[$intFlag])) {
            return $this->methodsAsOptionArray[$intFlag];
        }

        $carriers = $this->shippingConfig->getAllCarriers();
        /**
         * @var string $carrierCode
         * @var \Magento\Shipping\Model\Carrier\AbstractCarrier $carrierModel
         */
        foreach ($carriers as $carrierCode => $carrierModel) {
            if (!$carrierModel->isActive() && (bool)$isActiveOnlyFlag === true) {
                continue;
            }

            if ($carrierModel->getCarrierCode() === 'dhl' &&
                is_a($carrierModel, 'Magento\Dhl\Model\Carrier', true) &&
                method_exists($carrierModel, 'getDhlProductTitle')
            ) {
                /** @var \Magento\Dhl\Model\Carrier $carrierModel */
                $carrierMethods = $this->getDhlAllowedMethods($carrierModel);
            } else {
                $carrierMethods = $carrierModel->getAllowedMethods();
            }

            if ($carrierModel->getCarrierCode() === 'tig_postnl' && empty($carrierMethods['regular'])) {
                /** @var \TIG\PostNL\Model\Carrier\PostNL $carrierModel */
                $carrierMethods['regular'] = __('Regular');
            }

            if (!$carrierMethods || !is_array($carrierMethods)) {
                continue;
            }
            $carrierTitle          = $this->scopeConfig->getValue(
                'carriers/' . $carrierCode . '/title',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $methods[$carrierCode] = ['label' => $carrierTitle, 'value' => []];
            foreach ($carrierMethods as $methodCode => $methodTitle) {
                // Workaround for magento 2.4 instore pickup bug with incorrect method code in getAllowedMethods
                if ($carrierModel->getCarrierCode() === 'instore' && $methodCode === 'instore') {
                    $methodCode = 'pickup';
                }
                if (is_array($methodTitle)) {
                    foreach ($methodTitle as $subCode => $subData) {
                        $methods[$carrierCode]['value'][] = [
                            'value' => $carrierCode . '_' . $subCode,
                            'label' => '[' . $carrierCode . '] ' . ($subData ? $subData : $methodCode),
                        ];
                    }
                } else {
                    $methods[$carrierCode]['value'][] = [
                        'value' => $carrierCode . '_' . $methodCode,
                        'label' => '[' . $carrierCode . '] ' . ($methodTitle ? $methodTitle : $methodCode),
                    ];
                }
            }
        }

        if (empty($methods)) {
            $methods = [
                'label' => [],
                'value' => []
            ];
        }

        $this->methodsAsOptionArray[$intFlag] = $methods;

        return $this->methodsAsOptionArray[$intFlag];
    }

    /**
     * @param \Magento\Dhl\Model\Carrier $dhlCarrier
     * @return array
     */
    public function getDhlAllowedMethods($dhlCarrier)
    {
        if ($this->dhlMethods === null) {
            $docMethodsPath    = 'carriers/dhl/doc_methods';
            $nonDocMethodsPath = 'carriers/dhl/nondoc_methods';

            $docMethods    = $this->scopeConfig->getValue($docMethodsPath);
            $nonDocMethods = $this->scopeConfig->getValue($nonDocMethodsPath);

            $allowedMethods = array_merge(
                explode(',', $docMethods),
                explode(',', $nonDocMethods)
            );

            $dhlMethods = array_merge(
                $dhlCarrier->getDhlProducts($dhlCarrier::DHL_CONTENT_TYPE_DOC),
                $dhlCarrier->getDhlProducts($dhlCarrier::DHL_CONTENT_TYPE_NON_DOC)
            );

            $methods = [];
            foreach ($allowedMethods as $method) {
                $methods[$method] = isset($dhlMethods[$method]) ? (string)$dhlMethods[$method] : 'CODE: ' . $method;
            }

            $this->dhlMethods = $methods;
        }

        return $this->dhlMethods;
    }
}
