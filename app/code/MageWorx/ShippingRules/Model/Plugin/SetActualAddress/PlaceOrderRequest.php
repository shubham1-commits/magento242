<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\ShippingRules\Model\Plugin\SetActualAddress;

use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use MageWorx\ShippingRules\Model\QuoteAddressActualisation;

/**
 * Class PlaceOrderRequest
 */
class PlaceOrderRequest
{
    /**
     * @var QuoteAddressActualisation
     */
    private $quoteAddressActualisation;

    /**
     * PlaceOrderRequest constructor.
     *
     * @param QuoteAddressActualisation $quoteAddressActualisation
     */
    public function __construct(
        QuoteAddressActualisation $quoteAddressActualisation
    ) {
        $this->quoteAddressActualisation = $quoteAddressActualisation;
    }

    /**
     * @param CartManagementInterface $subject
     * @param $cartId
     * @param PaymentInterface|null $paymentMethod
     * @return array
     */
    public function beforePlaceOrder(
        CartManagementInterface $subject,
        $cartId,
        PaymentInterface $paymentMethod = null
    ): array {
        $this->quoteAddressActualisation->processWithCartId($cartId);

        return [$cartId, $paymentMethod];
    }
}
