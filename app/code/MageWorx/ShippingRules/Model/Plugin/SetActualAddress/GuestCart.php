<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model\Plugin\SetActualAddress;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use MageWorx\ShippingRules\Model\QuoteAddressActualisation;

/**
 * Class GuestCart
 */
class GuestCart
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var QuoteAddressActualisation
     */
    private $quoteAddressActualisation;

    /**
     * GuestCart constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteAddressActualisation $quoteAddressActualisation
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteAddressActualisation $quoteAddressActualisation
    ) {
        $this->quoteRepository           = $quoteRepository;
        $this->quoteAddressActualisation = $quoteAddressActualisation;
    }

    /**
     * @param ShippingInformationManagementInterface $subject
     * @param $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return array
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagementInterface $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ): array {
        try {
            $quote = $this->quoteRepository->getActive($cartId);
        } catch (NoSuchEntityException $noSuchEntityException) {
            return [$cartId, $addressInformation];
        }
        /** @var AddressInterface $shippingAddress */
        $shippingAddress = $addressInformation->getShippingAddress();
        $shippingAddress->setAddressType(
            $quote->getIsVirtual() ? AbstractAddress::TYPE_BILLING : AbstractAddress::TYPE_SHIPPING
        );

        $shippingAddress->setQuote($quote);

        $this->quoteAddressActualisation->storeData($quote, $shippingAddress);

        return [$cartId, $addressInformation];
    }
}
