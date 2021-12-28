<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model\Plugin\SetActualAddress;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use MageWorx\ShippingRules\Model\QuoteAddressActualisation;
use Psr\Log\LoggerInterface;

/**
 * Class ShippingMethodManagement
 *
 * Responsible for saving correct quote and address information in the Validator and RulesApplier
 * when using next interfaces:
 *
 * @see \Magento\Quote\Api\ShippingMethodManagementInterface
 * @see \Magento\Quote\Model\ShippingMethodManagementInterface
 * @see \Magento\Quote\Api\ShipmentEstimationInterface
 *
 * or main class which implements that interfaces by default:
 *
 * @see \Magento\Quote\Model\ShippingMethodManagement
 *
 * @important The default Magento API and Shipping Rules will not work correctly without that plugin.
 */
class ShippingMethodManagement
{
    /**
     * @var QuoteAddressActualisation
     */
    private $quoteAddressActualisation;

    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ShippingMethodManagement constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param QuoteAddressActualisation $quoteAddressActualisation
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        AddressRepositoryInterface $addressRepository,
        QuoteAddressActualisation $quoteAddressActualisation,
        LoggerInterface $logger
    ) {
        $this->quoteRepository           = $quoteRepository;
        $this->addressRepository         = $addressRepository;
        $this->quoteAddressActualisation = $quoteAddressActualisation;
        $this->logger                    = $logger;
    }

    /**
     * @param \Magento\Quote\Api\ShipmentEstimationInterface $subject
     * @param $cartId
     * @param AddressInterface $address
     * @return array
     */
    public function beforeEstimateByExtendedAddress(
        \Magento\Quote\Api\ShipmentEstimationInterface $subject,
        $cartId,
        AddressInterface $address
    ): array {
        try {
            $quote = $this->quoteRepository->getActive($cartId);
        } catch (NoSuchEntityException $noSuchEntityException) {
            return [$cartId, $address];
        }
        $address->setAddressType(
            $quote->getIsVirtual() ? AbstractAddress::TYPE_BILLING : AbstractAddress::TYPE_SHIPPING
        );
        $address->setQuote($quote);

        $this->quoteAddressActualisation->storeData($quote, $address);

        return [$cartId, $address];
    }

    /**
     * @param \Magento\Quote\Model\ShippingMethodManagementInterface $subject
     * @param $cartId
     * @return array
     */
    public function beforeGet(\Magento\Quote\Model\ShippingMethodManagementInterface $subject, $cartId): array
    {
        $this->quoteAddressActualisation->processWithCartId($cartId);

        return [$cartId];
    }

    /**
     * @param \Magento\Quote\Api\ShippingMethodManagementInterface $subject
     * @param $cartId
     * @return array
     */
    public function beforeGetList(\Magento\Quote\Api\ShippingMethodManagementInterface $subject, $cartId): array
    {
        $this->quoteAddressActualisation->processWithCartId($cartId);

        return [$cartId];
    }

    /**
     * @param \Magento\Quote\Api\ShippingMethodManagementInterface $subject
     * @param $cartId
     * @param \Magento\Quote\Api\Data\EstimateAddressInterface $address
     * @return array
     */
    public function beforeEstimateByAddress(
        \Magento\Quote\Api\ShippingMethodManagementInterface $subject,
        $cartId,
        \Magento\Quote\Api\Data\EstimateAddressInterface $address
    ): array {
        $this->quoteAddressActualisation->processWithCartId($cartId);

        return [$cartId, $address];
    }

    /**
     * @param \Magento\Quote\Api\ShippingMethodManagementInterface $subject
     * @param $cartId
     * @param $addressId
     * @return array
     */
    public function beforeEstimateByAddressId(
        \Magento\Quote\Api\ShippingMethodManagementInterface $subject,
        $cartId,
        $addressId
    ): array {
        try {
            $quote           = $this->quoteRepository->getActive($cartId);
            $customerAddress = $this->addressRepository->getById($addressId);
            /** @var \Magento\Quote\Model\Quote\Address $shippingAddress */
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCustomerAddressData($customerAddress);
            $shippingAddress->setCustomerAddressId($customerAddress->getId());
        } catch (LocalizedException $localizedException) {
            return [$cartId, $addressId];
        }

        $this->quoteAddressActualisation->storeData($quote, $shippingAddress);

        return [$cartId, $addressId];
    }
}
