<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model\Plugin\SetActualAddress;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use MageWorx\ShippingRules\Model\QuoteAddressActualisation;
use Psr\Log\LoggerInterface;

/**
 * Class GuestPaymentInformationRequest
 */
class GuestPaymentInformationRequest
{
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var QuoteAddressActualisation
     */
    private $quoteAddressActualisation;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GuestPaymentInformationRequest constructor.
     *
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param QuoteAddressActualisation $quoteAddressActualisation
     * @param LoggerInterface $logger
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteAddressActualisation $quoteAddressActualisation,
        LoggerInterface $logger
    ) {
        $this->quoteIdMaskFactory        = $quoteIdMaskFactory;
        $this->quoteAddressActualisation = $quoteAddressActualisation;
        $this->logger                    = $logger;
    }

    /**
     * @param GuestPaymentInformationManagementInterface $subject
     * @param $cartId
     * @param $email
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return array
     */
    public function beforeSavePaymentInformation(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ): array {
        try {
            /** @var QuoteIdMask $quoteIdMask */
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $this->quoteAddressActualisation->processWithCartId($quoteIdMask->getQuoteId());
        } catch (LocalizedException $localizedException) {
            $this->logger->critical($localizedException->getLogMessage());
        }

        return [$cartId, $email, $paymentMethod, $billingAddress];
    }
}
