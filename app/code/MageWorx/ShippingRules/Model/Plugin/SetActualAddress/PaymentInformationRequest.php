<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model\Plugin\SetActualAddress;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use MageWorx\ShippingRules\Model\QuoteAddressActualisation;

/**
 * Class PaymentInformationRequest
 */
class PaymentInformationRequest
{
    /**
     * @var QuoteAddressActualisation
     */
    private $quoteAddressActualisation;

    /**
     * PaymentInformationRequest constructor.
     *
     * @param QuoteAddressActualisation $quoteAddressActualisation
     */
    public function __construct(
        QuoteAddressActualisation $quoteAddressActualisation
    ) {
        $this->quoteAddressActualisation = $quoteAddressActualisation;
    }

    /**
     * @param PaymentInformationManagementInterface $subject
     * @param $cartId
     * @return array
     */
    public function beforeGetPaymentInformation(
        PaymentInformationManagementInterface $subject,
        $cartId
    ): array {
        $this->quoteAddressActualisation->processWithCartId($cartId);

        return [$cartId];
    }

    /**
     * @param PaymentInformationManagementInterface $subject
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return array
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagementInterface $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ): array {
        $this->quoteAddressActualisation->processWithCartId($cartId);

        return [$cartId, $paymentMethod, $billingAddress];
    }
}
