<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Block\Adminhtml\Form\Field;

use Magento\Shipping\Model\Config as ShippingConfig;

/**
 * Class Methods
 */
class Methods extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @var ShippingConfig
     */
    protected $shippingMethodsConfig;

    /**
     * Shipping methods cache
     *
     * @var array
     */
    private $shippingMethods;

    /**
     * @var array|null
     */
    private $dhlMethods;

    /**
     * @param \Magento\Framework\View\Element\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        ShippingConfig $shippingConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->shippingMethodsConfig = $shippingConfig;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getMethods() as $methodId => $methodLabel) {
                $this->addOption($methodId, addslashes($methodLabel));
            }
        }

        return parent::_toHtml();
    }

    /**
     * Retrieve allowed shipping methods
     *
     * @param int $methodId return name by shipping method id
     * @return array|string
     */
    protected function _getMethods($methodId = null)
    {
        if ($this->shippingMethods === null) {
            $this->shippingMethods = [];
            $this->shippingMethods = $this->getShippingMethodsList();
        }

        if ($methodId !== null) {
            return isset($this->shippingMethods[$methodId]) ? $this->shippingMethods[$methodId] : null;
        }

        return $this->shippingMethods;
    }

    /**
     * Option array of all shipping methods
     *
     * @param bool $isActiveOnlyFlag
     *
     * @return array
     */
    private function getShippingMethodsList($isActiveOnlyFlag = false)
    {
        $methods  = [];
        $carriers = $this->shippingMethodsConfig->getAllCarriers();
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

            foreach ($carrierMethods as $methodCode => $methodTitle) {
                if (is_array($methodTitle)) {
                    continue;
                }
                $methods[$carrierCode . '_' . $methodCode] =
                    '[' . $carrierCode . '_' . $methodCode . '] ' . ($methodTitle ? $methodTitle : $methodCode);
            }
        }

        return $methods;
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

            $docMethods    = $this->_scopeConfig->getValue($docMethodsPath);
            $nonDocMethods = $this->_scopeConfig->getValue($nonDocMethodsPath);

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
